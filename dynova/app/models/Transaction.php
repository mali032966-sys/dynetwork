<?php
class Transaction {
    public static function log(int $uid, string $type, float $amount, string $meta = ''): void {
        db()->prepare('INSERT INTO transactions (user_id, type, amount, meta) VALUES (?,?,?,?)')
            ->execute([$uid, $type, $amount, $meta]);
    }
    public static function forUser(int $uid, int $limit = 30): array {
        $s = db()->prepare('SELECT * FROM transactions WHERE user_id=? ORDER BY id DESC LIMIT ?');
        $s->bindValue(1, $uid, PDO::PARAM_INT);
        $s->bindValue(2, $limit, PDO::PARAM_INT);
        $s->execute();
        return $s->fetchAll();
    }
    public static function todayEarnings(int $uid): float {
        $s = db()->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM transactions
             WHERE user_id=? AND type IN ('task','referral','salary')
             AND DATE(created_at)=CURDATE()"
        );
        $s->execute([$uid]);
        return (float)$s->fetchColumn();
    }
}
