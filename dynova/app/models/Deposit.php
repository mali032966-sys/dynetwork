<?php
class Deposit {
    public static function create(array $d): int {
        $s = db()->prepare(
            'INSERT INTO deposits (user_id, amount, method, transaction_id, sender_account, screenshot, status)
             VALUES (?,?,?,?,?,?,"pending")'
        );
        $s->execute([
            $d['user_id'], $d['amount'], $d['method'],
            $d['transaction_id'], $d['sender_account'] ?? null,
            $d['screenshot'] ?? null,
        ]);
        return (int)db()->lastInsertId();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM deposits WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function forUser(int $uid): array {
        $s = db()->prepare('SELECT * FROM deposits WHERE user_id=? ORDER BY id DESC');
        $s->execute([$uid]);
        return $s->fetchAll();
    }
    public static function pending(): array {
        return db()->query(
            'SELECT d.*, u.whatsapp, u.name FROM deposits d
             JOIN users u ON u.id=d.user_id
             WHERE d.status="pending" ORDER BY d.id DESC'
        )->fetchAll();
    }
    public static function all(): array {
        return db()->query(
            'SELECT d.*, u.whatsapp, u.name FROM deposits d
             JOIN users u ON u.id=d.user_id
             ORDER BY d.id DESC LIMIT 200'
        )->fetchAll();
    }
    public static function setStatus(int $id, string $status, ?string $note = null): void {
        db()->prepare('UPDATE deposits SET status=?, admin_note=?, processed_at=NOW() WHERE id=?')
            ->execute([$status, $note, $id]);
    }
}
