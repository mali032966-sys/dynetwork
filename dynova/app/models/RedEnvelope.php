<?php
/**
 * Red Envelope – single-use, deposit-time discount.
 *
 * Design (v2 – 2026-07-13):
 *   - Each user can claim ONE envelope (until admin resets).
 *   - The claim stores the discount amount at the moment it was claimed.
 *   - The claim is later "used" against a single deposit — the user pays
 *     (deposit_amount - envelope) but the wallet is credited the full
 *     deposit_amount when admin approves the deposit.
 *
 * Storage:
 *   `red_envelope_claims`  — one row per claim event (status = unused/used/revoked)
 *   `deposits.envelope_used` (new column) — amount of the envelope applied to the deposit
 *
 * Both are created automatically on first use via ensureSchema().
 */
class RedEnvelope
{
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) return;
        db()->exec("
            CREATE TABLE IF NOT EXISTS red_envelope_claims (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('unused','used','revoked') NOT NULL DEFAULT 'unused',
                claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                used_at    DATETIME NULL,
                deposit_id INT UNSIGNED NULL,
                INDEX idx_user_status (user_id, status),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Ensure deposits.envelope_used column exists.
        try {
            $s = db()->query("SHOW COLUMNS FROM deposits LIKE 'envelope_used'");
            if (!$s->fetch()) {
                db()->exec(
                    "ALTER TABLE deposits
                        ADD COLUMN envelope_used DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER amount"
                );
            }
        } catch (Throwable $e) { /* best-effort */ }
        $done = true;
    }

    /** Return the user's active (unused) claim, or null. */
    public static function activeClaim(int $uid): ?array
    {
        self::ensureSchema();
        $s = db()->prepare(
            "SELECT * FROM red_envelope_claims
              WHERE user_id = ? AND status = 'unused'
              ORDER BY id DESC LIMIT 1"
        );
        $s->execute([$uid]);
        $r = $s->fetch();
        return $r ?: null;
    }

    /** Any claim ever (used, unused, or revoked)? */
    public static function hasEverClaimed(int $uid): bool
    {
        self::ensureSchema();
        $s = db()->prepare("SELECT COUNT(*) FROM red_envelope_claims WHERE user_id=?");
        $s->execute([$uid]);
        return (int) $s->fetchColumn() > 0;
    }

    /**
     * Try to grant a new claim to a user and INSTANTLY credit the
     * bonus amount into their wallet balance.  Returns the claim row
     * on success (with the awarded amount), or null if the user is
     * ineligible.
     *
     * v3 (2026-07-14): the envelope is a BONUS, not a discount.  The
     * amount is picked randomly from the admin-configured pool and
     * immediately credited to `users.balance` + logged as a normal
     * transaction so the user can withdraw it via the standard rules.
     */
    public static function claim(int $uid, ?float $amount = null): ?array
    {
        self::ensureSchema();
        if (!red_envelope_enabled())        return null;
        if (self::hasEverClaimed($uid))     return self::activeClaim($uid); // idempotent

        // Determine the surprise amount — random pick from all configured
        // non-zero package amounts (this is the "surprise" gift).
        // Defensive fallback: if the helper is missing on an older deploy
        // where /app/helpers.php was not re-uploaded, pick inline from the
        // same source so the CLAIM button never 500s.
        if ($amount === null || $amount <= 0) {
            if (function_exists('red_envelope_pick_bonus_amount')) {
                $amount = red_envelope_pick_bonus_amount();
            } else {
                $raw  = (string)(function_exists('setting') ? setting('red_envelope_discounts', '') : '');
                $map  = $raw ? (json_decode($raw, true) ?: []) : [];
                $vals = array_values(array_filter(array_map('floatval', $map), fn($v) => $v > 0));
                $amount = $vals ? (float)$vals[array_rand($vals)] : 0.0;
            }
        }
        if ($amount <= 0) return null;

        // Credit + record in a single transaction so we never partially apply.
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO red_envelope_claims (user_id, amount, status, used_at) VALUES (?, ?, 'used', NOW())")
                ->execute([$uid, $amount]);
            $claimId = (int)$pdo->lastInsertId();
            // Add the bonus to the wallet immediately
            User::addBalance($uid, $amount, 'balance');
            Transaction::log(
                $uid, 'admin_adjust', $amount,
                '🧧 Red Envelope bonus (one-time gift)'
            );
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            return null;
        }
        // Fetch the row we just inserted
        $s = $pdo->prepare('SELECT * FROM red_envelope_claims WHERE id = ?');
        $s->execute([$claimId]);
        $row = $s->fetch();
        return $row ?: null;
    }

    /** Attach + close a claim against a specific deposit id. */
    public static function markUsed(int $claimId, int $depositId): void
    {
        self::ensureSchema();
        db()->prepare(
            "UPDATE red_envelope_claims
                SET status='used', used_at=NOW(), deposit_id=?
              WHERE id=? AND status='unused'"
        )->execute([$depositId, $claimId]);
    }

    /**
     * Admin action: wipe the user's claim history so they can claim a
     * fresh envelope on their next dashboard visit.  We soft-delete
     * previous rows to keep the audit trail.
     */
    public static function resetForUser(int $uid): void
    {
        self::ensureSchema();
        db()->prepare(
            "UPDATE red_envelope_claims SET status='revoked'
              WHERE user_id=? AND status='unused'"
        )->execute([$uid]);
    }

    /**
     * Admin action: force-issue a new envelope even if the user has
     * already claimed one before.  Uses the current configured amount.
     */
    public static function grantForUser(int $uid, ?float $amount = null): ?array
    {
        self::ensureSchema();
        // Revoke any existing unused envelope first
        self::resetForUser($uid);
        if ($amount === null || $amount <= 0) {
            [$amount] = red_envelope_target_for_user($uid);
            if ($amount <= 0) $amount = red_envelope_max_amount();
        }
        if ($amount <= 0) return null;
        db()->prepare("INSERT INTO red_envelope_claims (user_id, amount) VALUES (?, ?)")
            ->execute([$uid, $amount]);
        return self::activeClaim($uid);
    }

    /** Full claim history for a user (newest first). */
    public static function historyForUser(int $uid): array
    {
        self::ensureSchema();
        $s = db()->prepare(
            "SELECT * FROM red_envelope_claims
              WHERE user_id=? ORDER BY id DESC LIMIT 20"
        );
        $s->execute([$uid]);
        return $s->fetchAll();
    }
}
