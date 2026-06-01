<?php
class Salary {
    public static function ranks(): array {
        return db()->query(
            'SELECT * FROM salary_ranks ORDER BY sort_order ASC, monthly_salary ASC'
        )->fetchAll();
    }

    /** Determine the highest rank the user qualifies for (per-level rules), or null */
    public static function rankFor(int $uid): ?array {
        $u = User::find($uid);
        if (!$u) return null;

        // Per-level member counts
        $l1 = User::countReferrals($uid, 1);
        $l2 = User::countReferrals($uid, 2);
        $l3 = User::countReferrals($uid, 3);

        // Per-level business volumes
        $b1 = User::teamBusinessAtLevel($uid, 1);
        $b2 = User::teamBusinessAtLevel($uid, 2);
        $b3 = User::teamBusinessAtLevel($uid, 3);

        $eligible = null;
        foreach (self::ranks() as $r) {
            if ($l1 >= (int)$r['min_l1_members']
             && $l2 >= (int)$r['min_l2_members']
             && $l3 >= (int)$r['min_l3_members']
             && $b1 >= (float)$r['min_l1_business']
             && $b2 >= (float)$r['min_l2_business']
             && $b3 >= (float)$r['min_l3_business']) {
                $eligible = $r;
            }
        }
        return $eligible;
    }

    /** Pay monthly salary to all eligible users (idempotent per month start) */
    public static function payMonthly(): int {
        $stamp = date('Y-m-01');
        $month = date('F Y');
        $users = db()->query('SELECT id FROM users WHERE is_blocked=0')->fetchAll();
        $count = 0;
        foreach ($users as $row) {
            $uid = (int)$row['id'];
            // Gate: only users with an active package earn the monthly salary.
            if (!TaskPackage::activeForUser($uid)) continue;
            $rank = self::rankFor($uid);
            if (!$rank || (float)$rank['monthly_salary'] <= 0) continue;
            try {
                db()->prepare('INSERT INTO salaries (user_id, rank_name, amount, week_ending) VALUES (?,?,?,?)')
                    ->execute([$uid, $rank['name'], (float)$rank['monthly_salary'], $stamp]);
                User::addBalance($uid, (float)$rank['monthly_salary'], 'salary_earnings');
                db()->prepare('UPDATE users SET rank_name=? WHERE id=?')
                    ->execute([$rank['name'], $uid]);
                Transaction::log($uid, 'salary', (float)$rank['monthly_salary'], $rank['name'] . ' monthly salary – ' . $month);
                $count++;
            } catch (PDOException $e) {
                // already paid this month – ignore
            }
        }
        return $count;
    }

    /** Backwards-compatible alias (legacy callers still reference payWeekly) */
    public static function payWeekly(): int {
        return self::payMonthly();
    }
}
