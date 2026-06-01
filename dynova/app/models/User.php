<?php
class User {
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM users WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function findByWhatsapp(string $w): ?array {
        $s = db()->prepare('SELECT * FROM users WHERE whatsapp=?');
        $s->execute([$w]);
        return $s->fetch() ?: null;
    }
    public static function findByReferralCode(string $code): ?array {
        $s = db()->prepare('SELECT * FROM users WHERE referral_code=?');
        $s->execute([$code]);
        return $s->fetch() ?: null;
    }
    public static function create(array $d): int {
        $s = db()->prepare(
            'INSERT INTO users (name, whatsapp, password_hash, referral_code, referred_by)
             VALUES (?,?,?,?,?)'
        );
        $s->execute([
            $d['name'] ?? '', $d['whatsapp'], $d['password_hash'],
            $d['referral_code'], $d['referred_by'] ?? null,
        ]);
        return (int)db()->lastInsertId();
    }
    public static function addBalance(int $uid, float $amount, string $bucket): void {
        // $bucket: balance | task_earnings | referral_earnings | salary_earnings | deposit_total
        $allowed = ['balance','task_earnings','referral_earnings','salary_earnings','deposit_total'];
        if (!in_array($bucket, $allowed, true)) return;
        $sql = "UPDATE users SET {$bucket} = {$bucket} + ?, balance = balance + ? WHERE id = ?";
        // When updating non-balance buckets we still want balance to grow.
        // Exception: deposit_total tracks lifetime deposit volume but the
        // matching credit goes to balance separately when admin approves.
        if ($bucket === 'deposit_total' || $bucket === 'balance') {
            db()->prepare("UPDATE users SET {$bucket} = {$bucket} + ? WHERE id = ?")
                ->execute([$amount, $uid]);
        } else {
            db()->prepare($sql)->execute([$amount, $amount, $uid]);
        }
    }
    public static function subtractBalance(int $uid, float $amount): void {
        db()->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
            ->execute([$amount, $uid]);
    }
    public static function countReferrals(int $uid, int $level = 1): int {
        if ($level === 1) {
            $s = db()->prepare('SELECT COUNT(*) FROM users WHERE referred_by=?');
            $s->execute([$uid]);
            return (int)$s->fetchColumn();
        }
        // gather user ids at previous level then count their direct children
        $ids = [$uid];
        for ($i = 1; $i < $level; $i++) {
            if (!$ids) return 0;
            $in = implode(',', array_fill(0, count($ids), '?'));
            $s = db()->prepare("SELECT id FROM users WHERE referred_by IN ($in)");
            $s->execute($ids);
            $ids = array_map('intval', array_column($s->fetchAll(), 'id'));
        }
        return count($ids);
    }
    /** Get the chain of ancestors up to 3 levels: returns [lvl1_id, lvl2_id, lvl3_id] (any may be null) */
    public static function ancestorChain(int $uid): array {
        $chain = [];
        $cur = self::find($uid);
        for ($i = 0; $i < 3; $i++) {
            $parent = $cur['referred_by'] ?? null;
            if (!$parent) { $chain[] = null; continue; }
            $chain[] = (int)$parent;
            $cur = self::find((int)$parent);
            if (!$cur) break;
        }
        while (count($chain) < 3) $chain[] = null;
        return $chain;
    }
    /** Get business volume (deposits + task earnings) for direct+indirect downline */
    public static function teamBusiness(int $uid): float {
        // gather full team ids up to 3 levels
        $ids = [];
        $current = [$uid];
        for ($lvl = 1; $lvl <= 3; $lvl++) {
            if (!$current) break;
            $in = implode(',', array_fill(0, count($current), '?'));
            $s = db()->prepare("SELECT id FROM users WHERE referred_by IN ($in)");
            $s->execute($current);
            $current = array_map('intval', array_column($s->fetchAll(), 'id'));
            $ids = array_merge($ids, $current);
        }
        if (!$ids) return 0.0;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $s = db()->prepare("SELECT COALESCE(SUM(deposit_total + task_earnings),0) FROM users WHERE id IN ($in)");
        $s->execute($ids);
        return (float)$s->fetchColumn();
    }

    /** Get business volume (deposit_total only) of users at a specific downline level (1, 2 or 3) */
    public static function teamBusinessAtLevel(int $uid, int $level): float {
        if ($level < 1 || $level > 3) return 0.0;
        $current = [$uid];
        for ($i = 1; $i <= $level; $i++) {
            if (!$current) return 0.0;
            $in = implode(',', array_fill(0, count($current), '?'));
            $s = db()->prepare("SELECT id FROM users WHERE referred_by IN ($in)");
            $s->execute($current);
            $current = array_map('intval', array_column($s->fetchAll(), 'id'));
        }
        if (!$current) return 0.0;
        $in = implode(',', array_fill(0, count($current), '?'));
        $s = db()->prepare("SELECT COALESCE(SUM(deposit_total),0) FROM users WHERE id IN ($in)");
        $s->execute($current);
        return (float)$s->fetchColumn();
    }
}
