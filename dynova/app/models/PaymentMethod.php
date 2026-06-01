<?php
class PaymentMethod {
    public static function active(): array {
        return db()->query('SELECT * FROM payment_methods WHERE is_active=1 ORDER BY id')->fetchAll();
    }
    public static function all(): array {
        return db()->query('SELECT * FROM payment_methods ORDER BY id')->fetchAll();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM payment_methods WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function save(?int $id, array $d): int {
        if ($id) {
            $s = db()->prepare(
                'UPDATE payment_methods SET name=?, account_title=?, account_number=?, instructions=?, is_active=? WHERE id=?'
            );
            $s->execute([$d['name'], $d['account_title'], $d['account_number'], $d['instructions'] ?? '', (int)($d['is_active'] ?? 1), $id]);
            return $id;
        }
        $s = db()->prepare(
            'INSERT INTO payment_methods (name, account_title, account_number, instructions, is_active) VALUES (?,?,?,?,?)'
        );
        $s->execute([$d['name'], $d['account_title'], $d['account_number'], $d['instructions'] ?? '', (int)($d['is_active'] ?? 1)]);
        return (int)db()->lastInsertId();
    }
    public static function delete(int $id): void {
        db()->prepare('DELETE FROM payment_methods WHERE id=?')->execute([$id]);
    }
}
