<?php
class Referral {
    public static function log(int $userId, int $sourceUserId, int $level, int $completionId, float $amount, float $percent): void {
        db()->prepare(
            'INSERT INTO referrals (user_id, source_user_id, level, source_completion_id, amount, percent)
             VALUES (?,?,?,?,?,?)'
        )->execute([$userId, $sourceUserId, $level, $completionId, $amount, $percent]);
    }
    public static function earningsByPeriod(int $uid, string $period = 'daily'): float {
        $where = "DATE(created_at)=CURDATE()";
        if ($period === 'weekly') {
            $where = "YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)";
        } elseif ($period === 'yearly') {
            $where = "YEAR(created_at)=YEAR(CURDATE())";
        }
        $s = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM referrals WHERE user_id=? AND $where");
        $s->execute([$uid]);
        return (float)$s->fetchColumn();
    }
    public static function earningsByLevel(int $uid, int $level, string $period = 'all'): float {
        $where = "1=1";
        if ($period === 'daily')  $where = "DATE(created_at)=CURDATE()";
        if ($period === 'weekly') $where = "YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)";
        if ($period === 'yearly') $where = "YEAR(created_at)=YEAR(CURDATE())";
        $s = db()->prepare("SELECT COALESCE(SUM(amount),0) FROM referrals WHERE user_id=? AND level=? AND $where");
        $s->execute([$uid, $level]);
        return (float)$s->fetchColumn();
    }
    public static function levelMembers(int $uid, int $level): array {
        if ($level === 1) {
            $s = db()->prepare('SELECT id,name,whatsapp,created_at FROM users WHERE referred_by=? ORDER BY id DESC');
            $s->execute([$uid]);
            return $s->fetchAll();
        }
        $ids = [$uid];
        for ($i = 1; $i < $level; $i++) {
            if (!$ids) return [];
            $in = implode(',', array_fill(0, count($ids), '?'));
            $s = db()->prepare("SELECT id FROM users WHERE referred_by IN ($in)");
            $s->execute($ids);
            $ids = array_map('intval', array_column($s->fetchAll(), 'id'));
        }
        if (!$ids) return [];
        $in = implode(',', array_fill(0, count($ids), '?'));
        $s = db()->prepare("SELECT id,name,whatsapp,created_at FROM users WHERE referred_by IN ($in) ORDER BY id DESC");
        $s->execute($ids);
        return $s->fetchAll();
    }
}
