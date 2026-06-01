<?php
/**
 * DYNOVA – Weekly referral bonus audit / catch-up.
 *
 * Live 3-level referral commissions are credited in real time when a task
 * is rated (see TaskController::submit). This weekly job:
 *
 *   1. Recomputes every user's current rank and updates `users.rank_name`.
 *   2. Audits the last 7 days of task_completions to ensure every L1/L2/L3
 *      bonus row in `referrals` has actually been logged. If a referrer was
 *      missed (e.g. live job failed), this job credits the bonus + transaction
 *      + balance retroactively.
 *
 * Schedule: every Monday 02:00 server time.
 */

require_once __DIR__ . '/_bootstrap.php';

cron_run('weekly_bonus', function () {

    // ---------- 1) Recompute every user's rank ----------
    $rankUpdates = 0;
    $users = db()->query('SELECT id, rank_name FROM users WHERE is_blocked=0')->fetchAll();
    foreach ($users as $u) {
        $uid = (int) $u['id'];
        $rank = Salary::rankFor($uid);
        $newName = $rank['name'] ?? '';
        if ($newName !== ($u['rank_name'] ?? '')) {
            db()->prepare('UPDATE users SET rank_name=? WHERE id=?')->execute([$newName, $uid]);
            $rankUpdates++;
        }
    }
    cron_log('weekly_bonus', "Rank recompute complete – {$rankUpdates} user(s) re-ranked.", 'OK');

    // ---------- 2) Audit + catch-up missed referral bonuses (last 7 days) ----------
    $l1 = (float) (setting('referral_l1', DEFAULT_REFERRAL_L1));
    $l2 = (float) (setting('referral_l2', DEFAULT_REFERRAL_L2));
    $l3 = (float) (setting('referral_l3', DEFAULT_REFERRAL_L3));
    $pcts = [1 => $l1, 2 => $l2, 3 => $l3];

    $rows = db()->query(
        "SELECT id, user_id, reward
         FROM task_completions
         WHERE created_at >= (NOW() - INTERVAL 7 DAY)"
    )->fetchAll();

    $catchup = 0;
    $catchupAmount = 0.0;

    $existsStmt = db()->prepare(
        'SELECT 1 FROM referrals WHERE source_completion_id=? AND level=? LIMIT 1'
    );

    foreach ($rows as $r) {
        $sourceUserId = (int) $r['user_id'];
        $completionId = (int) $r['id'];
        $reward       = (float) $r['reward'];

        $chain = User::ancestorChain($sourceUserId); // [l1_id, l2_id, l3_id]
        foreach ($chain as $i => $ancestorId) {
            if (!$ancestorId) continue;
            $level = $i + 1;
            $pct = $pcts[$level] ?? 0;
            if ($pct <= 0) continue;

            $existsStmt->execute([$completionId, $level]);
            if ($existsStmt->fetchColumn()) continue;

            $amount = round($reward * $pct / 100, 2);
            if ($amount <= 0) continue;

            Referral::log((int) $ancestorId, $sourceUserId, $level, $completionId, $amount, $pct);
            User::addBalance((int) $ancestorId, $amount, 'referral_earnings');
            Transaction::log(
                (int) $ancestorId,
                'referral',
                $amount,
                "L{$level} catch-up bonus (completion #{$completionId})"
            );
            $catchup++;
            $catchupAmount += $amount;
        }
    }

    cron_log(
        'weekly_bonus',
        sprintf(
            'Audit complete – credited %d missed bonus(es), total Rs %s.',
            $catchup, number_format($catchupAmount, 2)
        ),
        'OK'
    );
});
