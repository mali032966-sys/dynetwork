<?php
/**
 * DYNOVA – Stale deposit handler.
 *
 * Auto-rejects pending deposits that haven't been processed by an admin
 * within 24 hours. This protects users from limbo states – the rejection
 * is logged with a clear admin_note so the user can re-submit with a
 * fresh TID screenshot if needed.
 *
 * NOTE: We never auto-APPROVE deposits (that would be unsafe).
 *       Only auto-REJECT after 24h with a clear note.
 *
 * Schedule: every hour, on the hour.
 */

require_once __DIR__ . '/_bootstrap.php';

cron_run('process_deposits', function () {
    $stmt = db()->query(
        "SELECT id, user_id, amount, method
         FROM deposits
         WHERE status='pending'
           AND created_at < (NOW() - INTERVAL 24 HOUR)"
    );
    $stale = $stmt->fetchAll();

    if (!$stale) {
        cron_log('process_deposits', 'No stale pending deposits.', 'INFO');
        return;
    }

    $note  = 'Auto-rejected by system: no admin approval within 24 hours. ' .
             'Please re-submit with a fresh transaction ID and screenshot.';
    $count = 0;

    foreach ($stale as $d) {
        Deposit::setStatus((int) $d['id'], 'rejected', $note);
        Transaction::log(
            (int) $d['user_id'],
            'admin_adjust',
            0.0,
            "Deposit #{$d['id']} auto-rejected after 24h (method: {$d['method']}, Rs " .
              number_format((float) $d['amount'], 2) . ')'
        );
        $count++;
    }

    cron_log(
        'process_deposits',
        "Auto-rejected {$count} stale deposit(s) older than 24h.",
        'OK'
    );
});
