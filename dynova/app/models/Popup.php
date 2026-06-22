<?php
/**
 * Popup model.
 *
 * - Auto-creates the `popups` table on first call (so the live site does
 *   not need a separate SQL migration step).
 * - Provides a tiny CRUD surface used by the admin Popups page.
 * - Exposes `Popup::activeForView()` that returns the single best popup
 *   currently scheduled to show to end users (most-recent active one
 *   inside its start/end window).
 */
class Popup
{
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) return;
        db()->exec("
            CREATE TABLE IF NOT EXISTS popups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type ENUM('text','image') NOT NULL DEFAULT 'text',
                title VARCHAR(160) NULL,
                message TEXT NULL,
                image_path VARCHAR(255) NULL,
                start_at DATETIME NULL,
                end_at   DATETIME NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $done = true;
    }

    public static function all(): array
    {
        self::ensureSchema();
        $s = db()->query('SELECT * FROM popups ORDER BY id DESC');
        return $s ? $s->fetchAll() : [];
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $s = db()->prepare('SELECT * FROM popups WHERE id = ?');
        $s->execute([$id]);
        $r = $s->fetch();
        return $r ?: null;
    }

    public static function save(array $d, ?int $id = null): int
    {
        self::ensureSchema();
        $cols = [
            'type'       => $d['type']       ?? 'text',
            'title'      => $d['title']      ?? null,
            'message'    => $d['message']    ?? null,
            'image_path' => $d['image_path'] ?? null,
            'start_at'   => $d['start_at']   ?: null,
            'end_at'     => $d['end_at']     ?: null,
            'is_active'  => !empty($d['is_active']) ? 1 : 0,
        ];
        if ($id) {
            $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($cols)));
            $stmt = db()->prepare("UPDATE popups SET $set WHERE id = ?");
            $stmt->execute(array_merge(array_values($cols), [$id]));
            return $id;
        }
        $keys   = implode(',', array_keys($cols));
        $marks  = implode(',', array_fill(0, count($cols), '?'));
        db()->prepare("INSERT INTO popups ($keys) VALUES ($marks)")
            ->execute(array_values($cols));
        return (int)db()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        $row = self::find($id);
        if ($row && !empty($row['image_path'])) {
            $abs = dirname(__DIR__, 1) . '/../public/' . ltrim($row['image_path'], '/');
            if (is_file($abs)) @unlink($abs);
        }
        db()->prepare('DELETE FROM popups WHERE id = ?')->execute([$id]);
    }

    /**
     * Returns the single best popup to render to end users right now,
     * or null if none. "Best" = newest active row whose start/end window
     * (if set) covers `now`. NULL bounds are treated as open-ended.
     */
    public static function activeForView(): ?array
    {
        self::ensureSchema();
        $stmt = db()->prepare("
            SELECT * FROM popups
            WHERE is_active = 1
              AND (start_at IS NULL OR start_at <= NOW())
              AND (end_at   IS NULL OR end_at   >= NOW())
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute();
        $r = $stmt->fetch();
        return $r ?: null;
    }
}
