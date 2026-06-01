<?php
/**
 * DYNOVA – Monthly salary payout.
 *
 * Distributes a salary to every user who qualifies for a rank, paid once per
 * calendar month (idempotent via salaries.uniq_user_week using the 1st of the
 * month as the week_ending stamp).
 *
 * Schedule: 1st of every month, 03:00 server time.
 */

require_once __DIR__ . '/_bootstrap.php';

cron_run('monthly_salary', function () {
    $stamp  = date('Y-m-01'); // store the month with day=01
    $month  = date('F Y');
    $users  = db()->query('SELECT id FROM users WHERE is_blocked=0')->fetchAll();
    $paid   = 0;
    $skipped = 0;
    $total  = 0.0;

    foreach ($users as $row) {
        $uid  = (int) $row['id'];
        $rank = Salary::rankFor($uid);
        if (!$rank || (float) $rank['monthly_salary'] <= 0) { $skipped++; continue; }

        // Salary set by admin per rank (monthly).
        $amount = (float) $rank['monthly_salary'];

        try {
            db()->prepare(
                'INSERT INTO salaries (user_id, rank_name, amount, week_ending)
                 VALUES (?,?,?,?)'
            )->execute([$uid, $rank['name'], $amount, $stamp]);

            User::addBalance($uid, $amount, 'salary_earnings');
            db()->prepare('UPDATE users SET rank_name=? WHERE id=?')
                ->execute([$rank['name'], $uid]);

            Transaction::log(
                $uid,
                'salary',
                $amount,
                $rank['name'] . ' monthly salary – ' . $month
            );
            $paid++;
            $total += $amount;
        } catch (PDOException $e) {
            // Duplicate (already paid this month) – just count as skipped.
            $skipped++;
        }
    }

    cron_log(
        'monthly_salary',
        sprintf(
            'Paid %d users (Rs %s), skipped %d. Month: %s',
            $paid, number_format($total, 2), $skipped, $month
        ),
        'OK'
    );
});
