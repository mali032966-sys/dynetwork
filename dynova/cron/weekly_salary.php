<?php
/**
 * Weekly salary cron job.
 * Run every Sunday: php /path/to/dynova/cron/weekly_salary.php
 */
require_once __DIR__ . '/../app/bootstrap.php';
$n = Salary::payWeekly();
echo "[" . date('Y-m-d H:i:s') . "] Salary paid to $n users.\n";
