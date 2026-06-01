<?php
/**
 * DYNOVA – Daily housekeeping.
 *
 * The daily task limit itself is enforced via SQL date filters, so there are
 * no per-user counters to "reset". This script handles all the other daily
 * hygiene work:
 *
 *   1. Expires stale `remember_token` cookies (older than REMEMBER_DAYS).
 *   2. Sweeps PHP session files older than 14 days.
 *   3. Logs a daily summary row to /logs/cron.log:
 *      total users, new users today, tasks completed today, payouts today.
 *
 * Schedule: every day at 00:05 server time.
 */

require_once __DIR__ . '/_bootstrap.php';

cron_run('daily_reset', function () {

    // ---------- 1) Expire old remember tokens ----------
    $cutoff = date('Y-m-d H:i:s', time() - (REMEMBER_DAYS * 86400));
    $stmt = db()->prepare(
        'UPDATE users SET remember_token=NULL
         WHERE remember_token IS NOT NULL AND created_at < ?'
    );
    $stmt->execute([$cutoff]);
    $tokens = $stmt->rowCount();
    cron_log('daily_reset', "Cleared {$tokens} stale remember token(s).", 'INFO');

    // ---------- 2) Old PHP session files ----------
    $sessPath = session_save_path() ?: sys_get_temp_dir();
    $sessKilled = 0;
    if (is_dir($sessPath) && is_readable($sessPath)) {
        $threshold = time() - (14 * 86400);
        foreach (glob($sessPath . '/sess_*') as $f) {
            if (is_file($f) && @filemtime($f) < $threshold) {
                if (@unlink($f)) $sessKilled++;
            }
        }
    }
    cron_log('daily_reset', "Swept {$sessKilled} expired session file(s) from {$sessPath}.", 'INFO');

    // ---------- 3) Daily KPI snapshot ----------
    $newUsers = (int) db()->query(
        'SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()'
    )->fetchColumn();
    $totalUsers = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $tasksToday = (int) db()->query(
        'SELECT COUNT(*) FROM task_completions WHERE DATE(created_at)=CURDATE()'
    )->fetchColumn();
    $depositsToday = (float) db()->query(
        "SELECT COALESCE(SUM(amount),0) FROM deposits
         WHERE status='approved' AND DATE(processed_at)=CURDATE()"
    )->fetchColumn();
    $payoutsToday = (float) db()->query(
        "SELECT COALESCE(SUM(amount),0) FROM withdrawals
         WHERE status IN ('approved','paid') AND DATE(processed_at)=CURDATE()"
    )->fetchColumn();

    cron_log(
        'daily_reset',
        sprintf(
            'KPI – users:%d (+%d today)  tasks_today:%d  deposits:Rs %s  payouts:Rs %s',
            $totalUsers, $newUsers, $tasksToday,
            number_format($depositsToday, 2),
            number_format($payoutsToday, 2)
        ),
        'OK'
    );
});
