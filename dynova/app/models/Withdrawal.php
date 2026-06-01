<?php
class Withdrawal {
    public static function create(array $d): int {
        $s = db()->prepare(
            'INSERT INTO withdrawals (user_id, amount, method, account_number, account_title, status)
             VALUES (?,?,?,?,?,"pending")'
        );
        $s->execute([
            $d['user_id'], $d['amount'], $d['method'],
            $d['account_number'], $d['account_title'],
        ]);
        return (int)db()->lastInsertId();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM withdrawals WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function forUser(int $uid): array {
        $s = db()->prepare('SELECT * FROM withdrawals WHERE user_id=? ORDER BY id DESC');
        $s->execute([$uid]);
        return $s->fetchAll();
    }
    public static function pending(): array {
        return db()->query(
            'SELECT w.*, u.whatsapp, u.name FROM withdrawals w
             JOIN users u ON u.id=w.user_id
             WHERE w.status="pending" ORDER BY w.id DESC'
        )->fetchAll();
    }
    public static function all(): array {
        return db()->query(
            'SELECT w.*, u.whatsapp, u.name FROM withdrawals w
             JOIN users u ON u.id=w.user_id
             ORDER BY w.id DESC LIMIT 200'
        )->fetchAll();
    }
    public static function pendingSumForUser(int $uid): float {
        $s = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id=? AND status IN ("pending","approved")');
        $s->execute([$uid]);
        return (float)$s->fetchColumn();
    }
    public static function setStatus(int $id, string $status, ?string $note = null): void {
        db()->prepare('UPDATE withdrawals SET status=?, admin_note=?, processed_at=NOW() WHERE id=?')
            ->execute([$status, $note, $id]);
    }
}
