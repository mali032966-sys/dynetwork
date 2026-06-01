<?php
/**
 * DYNOVA – Shared cron bootstrap (CLI-safe).
 *
 * - Loads config + db + helpers + models WITHOUT starting a PHP session
 *   (sessions are pointless in CLI cron and trigger "headers already sent"
 *    warnings as soon as scheduler.php echoes its dispatch line).
 * - Defines the constants and helper functions every cron script uses:
 *      DYNOVA_LOG_FILE, cron_log(), cron_run().
 *
 * Every script in /cron/ requires this file first.
 */

define('DYNOVA_CRON', true);

$_APP_DIR = realpath(__DIR__ . '/..') . '/app';

require_once $_APP_DIR . '/config.php';
require_once $_APP_DIR . '/db.php';

// helpers.php uses $_SESSION (csrf / flash) but only inside functions we never
// call from cron. Loading it is still useful for setting()/setting_set().
// We provide a fake $_SESSION array so any incidental access doesn't blow up.
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = $_SESSION ?? [];
}
require_once $_APP_DIR . '/helpers.php';
require_once $_APP_DIR . '/payment_logo.php';

// Same autoloader bootstrap.php registers – maps class -> models/ or controllers/
spl_autoload_register(function ($class) use ($_APP_DIR) {
    foreach (['models', 'controllers'] as $dir) {
        $f = $_APP_DIR . '/' . $dir . '/' . $class . '.php';
        if (is_file($f)) { require_once $f; return; }
    }
});

// ---------- log file ----------
if (!defined('DYNOVA_LOG_DIR')) {
    $logDir = realpath(__DIR__ . '/..') . '/logs';
    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
    define('DYNOVA_LOG_DIR',  $logDir);
    define('DYNOVA_LOG_FILE', $logDir . '/cron.log');
}

if (!function_exists('cron_log')) {
    /**
     * Append a tagged line to /logs/cron.log and echo it.
     */
    function cron_log(string $job, string $msg, string $level = 'INFO'): void {
        $line = sprintf(
            "[%s] [%-5s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $job,
            $msg
        );
        echo $line;
        @file_put_contents(DYNOVA_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('cron_run')) {
    /**
     * Wrap a job callable with timing + error reporting.
     */
    function cron_run(string $job, callable $fn): void {
        $start = microtime(true);
        cron_log($job, 'Job started.', 'INFO');
        try {
            $fn();
            $dur = round((microtime(true) - $start) * 1000);
            cron_log($job, "Job finished in {$dur} ms.", 'OK');
        } catch (Throwable $e) {
            cron_log($job, 'FAILED: ' . $e->getMessage(), 'ERROR');
            cron_log($job, $e->getTraceAsString(), 'ERROR');
        }
    }
}
