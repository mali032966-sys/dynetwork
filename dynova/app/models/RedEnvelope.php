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
     * Try to grant a new claim to a user.  Returns the claim row on
     * success, null if the user is not eligible (feature disabled, no
     * amount configured, or user already claimed once).
     *
     * If `$amount` is provided (> 0) it is used verbatim — this lets the
     * caller decide the value based on the user's current package (e.g.
     * "upgrade to Gold" → the amount configured for Gold).  Otherwise
     * the default helper is used.
     */
    public static function claim(int $uid, ?float $amount = null): ?array
    {
        self::ensureSchema();
        if (!red_envelope_enabled())        return null;
        if (self::hasEverClaimed($uid))     return self::activeClaim($uid); // idempotent
        if ($amount === null || $amount <= 0) {
            [$amount] = red_envelope_target_for_user($uid);
        }
        if ($amount <= 0)                   return null;
        db()->prepare("INSERT INTO red_envelope_claims (user_id, amount) VALUES (?, ?)")
            ->execute([$uid, $amount]);
        return self::activeClaim($uid);
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
