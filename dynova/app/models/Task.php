<?php
class Task {
    public static function active(): array {
        return db()->query('SELECT * FROM tasks WHERE is_active=1 ORDER BY id ASC')->fetchAll();
    }
    public static function all(): array {
        return db()->query('SELECT * FROM tasks ORDER BY id DESC')->fetchAll();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM tasks WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function nextForUser(int $uid): ?array {
        // First task user hasn't completed today
        $s = db()->prepare(
            "SELECT t.* FROM tasks t
             WHERE t.is_active=1
               AND NOT EXISTS (
                  SELECT 1 FROM task_completions c
                  WHERE c.task_id = t.id
                    AND c.user_id = ?
                    AND DATE(c.created_at) = CURDATE()
               )
             ORDER BY t.id ASC LIMIT 1"
        );
        $s->execute([$uid]);
        return $s->fetch() ?: null;
    }
    public static function completedTodayCount(int $uid): int {
        $s = db()->prepare(
            'SELECT COUNT(*) FROM task_completions WHERE user_id=? AND DATE(created_at)=CURDATE()'
        );
        $s->execute([$uid]);
        return (int)$s->fetchColumn();
    }
    public static function recordCompletion(int $uid, int $taskId, int $rating, float $reward): int {
        $s = db()->prepare(
            'INSERT INTO task_completions (user_id, task_id, rating, reward) VALUES (?,?,?,?)'
        );
        $s->execute([$uid, $taskId, $rating, $reward]);
        return (int)db()->lastInsertId();
    }
}
