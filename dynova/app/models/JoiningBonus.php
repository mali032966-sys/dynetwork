<?php
class JoiningBonus {
    public static function all(): array {
        return db()->query(
            'SELECT jb.*, p.name AS pkg_name, p.tier, p.price
             FROM joining_bonuses jb
             JOIN task_packages p ON p.id = jb.package_id
             ORDER BY p.sort_order ASC, p.price ASC'
        )->fetchAll();
    }
    public static function active(): array {
        return db()->query(
            'SELECT jb.*, p.name AS pkg_name, p.tier, p.price
             FROM joining_bonuses jb
             JOIN task_packages p ON p.id = jb.package_id
             WHERE jb.is_active = 1 AND p.is_active = 1
             ORDER BY p.sort_order ASC, p.price ASC'
        )->fetchAll();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM joining_bonuses WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function forPackage(int $packageId): ?array {
        $s = db()->prepare('SELECT * FROM joining_bonuses WHERE package_id=? AND is_active=1');
        $s->execute([$packageId]);
        return $s->fetch() ?: null;
    }
    public static function save(?int $id, array $d): int {
        $pid    = (int)($d['package_id'] ?? 0);
        $refB   = (float)($d['referrer_bonus'] ?? 0);
        $invB   = (float)($d['invitee_bonus'] ?? 0);
        $active = (int)($d['is_active'] ?? 0);
        if ($id) {
            db()->prepare(
                'UPDATE joining_bonuses
                   SET package_id=?, referrer_bonus=?, invitee_bonus=?, is_active=?
                 WHERE id=?'
            )->execute([$pid, $refB, $invB, $active, $id]);
            return $id;
        }
        db()->prepare(
            'INSERT INTO joining_bonuses (package_id, referrer_bonus, invitee_bonus, is_active)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE
               referrer_bonus=VALUES(referrer_bonus),
               invitee_bonus=VALUES(invitee_bonus),
               is_active=VALUES(is_active)'
        )->execute([$pid, $refB, $invB, $active]);
        return (int)db()->lastInsertId();
    }
    public static function delete(int $id): void {
        db()->prepare('DELETE FROM joining_bonuses WHERE id=?')->execute([$id]);
    }

    /**
     * Credit the one-time joining bonus for a user who just activated their first
     * package. Pays the invitee directly and (if eligible) the level-1 referrer.
     * Idempotent: only runs once per user (guarded by users.joining_bonus_received).
     */
    public static function creditOnFirstActivation(int $uid, int $packageId): void {
        $u = User::find($uid);
        if (!$u || (int)$u['joining_bonus_received'] === 1) return;

        $rule = self::forPackage($packageId);
        if (!$rule) {
            // No bonus rule for this package — still flag so we don't keep checking
            db()->prepare('UPDATE users SET joining_bonus_received=1 WHERE id=?')->execute([$uid]);
            return;
        }

        $inv = (float)$rule['invitee_bonus'];
        $ref = (float)$rule['referrer_bonus'];

        if ($inv > 0) {
            User::addBalance($uid, $inv, 'balance');
            Transaction::log($uid, 'admin_adjust', $inv, 'Joining bonus (welcome)');
        }
        if ($ref > 0 && !empty($u['referred_by'])) {
            $rid = (int)$u['referred_by'];
            // Gate: referrer must also have an active package to receive the
            // joining bonus. Otherwise we still flag the invitee so the bonus
            // doesn't fire later when the referrer eventually buys one.
            if (TaskPackage::activeForUser($rid)) {
                User::addBalance($rid, $ref, 'referral_earnings');
                Transaction::log($rid, 'referral', $ref, 'Joining bonus for invitee #' . $uid);
            }
        }
        db()->prepare('UPDATE users SET joining_bonus_received=1 WHERE id=?')->execute([$uid]);
    }
}
