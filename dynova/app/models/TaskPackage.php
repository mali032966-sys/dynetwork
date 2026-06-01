<?php
class TaskPackage {
    public static function all(): array {
        return db()->query(
            'SELECT * FROM task_packages ORDER BY sort_order ASC, price ASC'
        )->fetchAll();
    }
    public static function active(): array {
        return db()->query(
            'SELECT * FROM task_packages WHERE is_active=1 ORDER BY sort_order ASC, price ASC'
        )->fetchAll();
    }
    public static function find(int $id): ?array {
        $s = db()->prepare('SELECT * FROM task_packages WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public static function save(?int $id, array $d): int {
        // Validity is no longer surfaced in the UI – default to a very high value
        // so packages effectively never expire (admin can still pass one in if needed).
        $validity = (int)($d['validity_days'] ?? 0);
        if ($validity <= 0) $validity = 36500; // ~100 years

        // New economy: admin enters Daily Tasks + Earning-per-task. Daily / Weekly /
        // Monthly are auto-computed and stored on the row so existing queries
        // (e.g. /packages page, monthly column) keep working without changes.
        $dailyTasks   = max(1, (int)($d['daily_tasks'] ?? 1));
        $perTask      = (float)($d['earning_per_task'] ?? 0);
        $dailyEarning = round($dailyTasks * $perTask, 2);

        // Normalise the withdrawal-ladder CSV: keep only positive numbers,
        // preserve order. Empty / invalid → use the global default.
        $ladderRaw = $d['min_withdrawal_ladder'] ?? '1500,7000,15000,35000,100000,200000';
        $ladder = [];
        foreach (preg_split('/[\s,]+/', (string)$ladderRaw) as $v) {
            $v = trim($v);
            if ($v === '') continue;
            $n = (float)$v;
            if ($n > 0) $ladder[] = (string)(int)round($n);
        }
        $ladderCsv = $ladder ? implode(',', $ladder) : '1500,7000,15000,35000,100000,200000';

        $cols = [
            $d['name'], $d['tier'] ?: 'standard', $d['emoji'] ?? '',
            (float)($d['price'] ?? 0), $dailyTasks, $perTask, $dailyEarning,
            $validity,
            (int)($d['is_featured'] ?? 0),
            (int)($d['is_active'] ?? 1),
            (int)($d['sort_order'] ?? 0),
            $ladderCsv,
        ];
        if ($id) {
            db()->prepare(
                'UPDATE task_packages SET name=?, tier=?, emoji=?, price=?, daily_tasks=?,
                   earning_per_task=?, daily_earning=?, validity_days=?,
                   is_featured=?, is_active=?, sort_order=?, min_withdrawal_ladder=?
                 WHERE id=?'
            )->execute([...$cols, $id]);
            return $id;
        }
        db()->prepare(
            'INSERT INTO task_packages
             (name, tier, emoji, price, daily_tasks, earning_per_task, daily_earning,
              validity_days, is_featured, is_active, sort_order, min_withdrawal_ladder)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute($cols);
        return (int) db()->lastInsertId();
    }
    /**
     * Per-task reward for a user, driven entirely by their active package
     * (`earning_per_task`). Returns 0.0 when the user has no active package
     * — controllers must gate task submission on having one.
     */
    public static function getRewardPerTask(int $user_id): float {
        $active = self::activeForUser($user_id);
        if (!$active) return 0.0;

        // Primary source: explicit per-task field on the package.
        $perTask = (float)($active['earning_per_task'] ?? 0);
        if ($perTask > 0) return round($perTask, 2);

        // Fallback (older rows): derive from daily_earning ÷ daily_tasks.
        $dailyEarning = (float)($active['daily_earning'] ?? 0);
        $dailyTasks   = max(1, (int)($active['daily_tasks'] ?? 0));
        if ($dailyEarning > 0) return round($dailyEarning / $dailyTasks, 2);

        return 0.0;
    }

    /**
     * Per-task reward for a user, kept as a thin wrapper around
     * getRewardPerTask() for backwards compatibility with existing call sites.
     * The `$task` row is no longer used – reward is driven only by the user's
     * active package.
     */
    public static function rewardFor(int $uid, array $task = []): float {
        return self::getRewardPerTask($uid);
    }

    /**
     * Return the per-day task limit for a user, taken from their currently
     * active package. Falls back to the system default for unsubscribed users.
     */
    public static function dailyLimitFor(int $uid): int {
        $active = self::activeForUser($uid);
        if ($active && (int)$active['daily_tasks'] > 0) {
            return (int)$active['daily_tasks'];
        }
        return (int)setting('daily_task_limit', DEFAULT_DAILY_TASK_LIMIT);
    }

    public static function delete(int $id): void {
        db()->prepare('DELETE FROM task_packages WHERE id=?')->execute([$id]);
    }

    /** Parse a CSV ladder string into an ordered array of positive integers. */
    public static function parseLadder(string $csv): array {
        $out = [];
        foreach (preg_split('/[\s,]+/', $csv) as $v) {
            $v = trim($v);
            if ($v === '') continue;
            $n = (int)round((float)$v);
            if ($n > 0) $out[] = $n;
        }
        return $out;
    }

    /** Default ladder when a user has no active package. */
    public static function defaultLadder(): array {
        return [1500, 7000, 15000, 35000, 100000, 200000];
    }

    /**
     * Compute the next minimum withdrawal for a user, based on:
     *   - the ladder stored on the user's active package (or system default)
     *   - how many withdrawal requests they have made so far
     *     (any status except 'rejected' counts as a step on the ladder).
     * Returns ['min' => float, 'ladder' => int[], 'step' => int (1-based), 'count' => int]
     */
    public static function withdrawalLadderFor(int $uid): array {
        $active = self::activeForUser($uid);
        $ladder = $active && !empty($active['min_withdrawal_ladder'])
            ? self::parseLadder($active['min_withdrawal_ladder'])
            : self::defaultLadder();
        if (!$ladder) $ladder = self::defaultLadder();

        $s = db()->prepare(
            'SELECT COUNT(*) FROM withdrawals WHERE user_id=? AND status <> "rejected"'
        );
        $s->execute([$uid]);
        $count = (int)$s->fetchColumn();

        $idx = min($count, count($ladder) - 1);
        return [
            'min'    => (float)$ladder[$idx],
            'ladder' => $ladder,
            'step'   => $idx + 1,
            'count'  => $count,
        ];
    }

    /** Get the currently-active package row for a user (or null). */
    public static function activeForUser(int $uid): ?array {
        $s = db()->prepare(
            "SELECT up.*, p.name AS pkg_name, p.tier, p.emoji
             FROM user_packages up
             JOIN task_packages p ON p.id = up.package_id
             WHERE up.user_id=? AND up.status='active' AND up.expires_at > NOW()
             ORDER BY up.id DESC LIMIT 1"
        );
        $s->execute([$uid]);
        return $s->fetch() ?: null;
    }

    /** Activate a package for a user (debits balance, logs txn, inserts row). */
    public static function activate(int $uid, int $packageId): array {
        $pkg = self::find($packageId);
        if (!$pkg || !$pkg['is_active']) {
            return ['ok' => false, 'error' => 'Package not available.'];
        }
        $u = User::find($uid);
        if (!$u) return ['ok' => false, 'error' => 'User not found.'];
        if ((float) $u['balance'] < (float) $pkg['price']) {
            return ['ok' => false, 'error' =>
                'Insufficient balance. You need ' . money($pkg['price']) . ' to activate this package.'];
        }
        // Debit + log + insert in one shot
        $pdo = db();
        $pdo->beginTransaction();
        try {
            User::subtractBalance($uid, (float) $pkg['price']);
            Transaction::log(
                $uid, 'admin_adjust', -1 * (float) $pkg['price'],
                'Activated package: ' . $pkg['name']
            );
            $expires = date('Y-m-d H:i:s', time() + ((int) $pkg['validity_days']) * 86400);
            $pdo->prepare(
                'INSERT INTO user_packages
                 (user_id, package_id, daily_tasks, daily_earning, price_paid, expires_at)
                 VALUES (?,?,?,?,?,?)'
            )->execute([
                $uid, $packageId,
                (int) $pkg['daily_tasks'],
                (float) $pkg['daily_earning'],
                (float) $pkg['price'],
                $expires,
            ]);
            $pdo->commit();
            // One-time joining bonus (for invitee + referrer) — only fires the very
            // first time this user activates ANY package.
            try {
                JoiningBonus::creditOnFirstActivation($uid, $packageId);
            } catch (Throwable $e) {
                // Don't fail the activation if bonus crediting fails.
            }
            return ['ok' => true, 'expires' => $expires];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Activation failed: ' . $e->getMessage()];
        }
    }
}
