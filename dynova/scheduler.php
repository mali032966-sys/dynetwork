<?php
/**
 * =====================================================================
 *  DYNOVA NETWORK – Master scheduler
 * =====================================================================
 *
 *  The client only needs to register **ONE** cron entry on the host:
 *
 *      * * * * *  /usr/bin/php /home/USER/dynova/scheduler.php >> /dev/null 2>&1
 *
 *  This file decides, every minute, which of the four jobs are due to
 *  run RIGHT NOW and dispatches them. It writes every decision into
 *  /logs/cron.log so the client can audit anything from a single file.
 *
 *  Two modes:
 *    – If `peppeocchi/php-cron-scheduler` is installed (composer install),
 *      we use it (recommended for production).
 *    – Otherwise we fall back to a tiny built-in scheduler that
 *      understands the same schedule definitions, so the client can
 *      deploy the project WITHOUT running composer at all.
 *
 *  The schedule expressions follow standard cron syntax:
 *      min  hour  day-of-month  month  day-of-week
 *
 *  Manual / one-off run (any single job, ignoring schedule):
 *      php scheduler.php run daily_reset
 *      php scheduler.php run monthly_salary
 *      php scheduler.php run weekly_bonus
 *      php scheduler.php run process_deposits
 *      php scheduler.php run all
 *
 *  List the schedule:
 *      php scheduler.php list
 * =====================================================================
 */

$ROOT       = __DIR__;
$CRON_DIR   = $ROOT . '/cron';
$LOG_DIR    = $ROOT . '/logs';
$LOG_FILE   = $LOG_DIR . '/cron.log';

if (!is_dir($LOG_DIR)) { @mkdir($LOG_DIR, 0775, true); }

// Pick up the project timezone so cron timestamps match the app.
if (is_file($ROOT . '/app/config.php')) {
    require_once $ROOT . '/app/config.php';
}

function dn_sched_log(string $msg, string $level = 'INFO'): void {
    global $LOG_FILE;
    $line = sprintf("[%s] [%-5s] [scheduler] %s\n", date('Y-m-d H:i:s'), $level, $msg);
    echo $line;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

/**
 *  All scheduled jobs in one place. Each entry:
 *    file     => script under /cron/
 *    cron     => minute hour dom mon dow
 *    desc     => human description
 */
$JOBS = [
    'daily_reset' => [
        'file' => 'daily_reset.php',
        'cron' => '5 0 * * *',          // every day at 00:05
        'desc' => 'Daily housekeeping + KPI snapshot.',
    ],
    'process_deposits' => [
        'file' => 'process_deposits.php',
        'cron' => '0 * * * *',           // every hour on the hour
        'desc' => 'Auto-reject pending deposits older than 24h.',
    ],
    'weekly_bonus' => [
        'file' => 'weekly_bonus.php',
        'cron' => '0 2 * * 1',           // every Monday at 02:00
        'desc' => 'Recompute ranks + audit 7-day referral bonuses.',
    ],
    'monthly_salary' => [
        'file' => 'monthly_salary.php',
        'cron' => '0 3 1 * *',           // 1st of each month at 03:00
        'desc' => 'Distribute monthly salary based on user ranks.',
    ],
];

/**
 *  Tiny cron-expression matcher (covers the syntax used above).
 *  Supports:  *   N   N,N,N   N-N   *\/N   N\/N
 */
function dn_cron_due(string $expr, ?int $ts = null): bool {
    $ts = $ts ?? time();
    $parts = preg_split('/\s+/', trim($expr));
    if (count($parts) !== 5) return false;
    $vals = [
        (int) date('i', $ts), // minute   0-59
        (int) date('G', $ts), // hour     0-23
        (int) date('j', $ts), // dom      1-31
        (int) date('n', $ts), // month    1-12
        (int) date('w', $ts), // dow      0-6 (Sun=0)
    ];
    $ranges = [[0,59],[0,23],[1,31],[1,12],[0,6]];

    foreach ($parts as $i => $field) {
        $now = $vals[$i];
        [$lo, $hi] = $ranges[$i];
        if (!dn_cron_field_match($field, $now, $lo, $hi)) return false;
    }
    return true;
}

function dn_cron_field_match(string $field, int $value, int $lo, int $hi): bool {
    foreach (explode(',', $field) as $atom) {
        $step = 1;
        if (strpos($atom, '/') !== false) {
            [$atom, $stepStr] = explode('/', $atom, 2);
            $step = max(1, (int) $stepStr);
        }
        if ($atom === '*' || $atom === '') {
            // every value with optional step
            if ((($value - $lo) % $step) === 0) return true;
            continue;
        }
        if (strpos($atom, '-') !== false) {
            [$a, $b] = array_map('intval', explode('-', $atom, 2));
        } else {
            $a = $b = (int) $atom;
        }
        for ($v = $a; $v <= $b; $v += $step) {
            if ($v === $value) return true;
        }
    }
    return false;
}

function dn_run_job(string $key, array $job, string $cronDir): void {
    $path = $cronDir . '/' . $job['file'];
    if (!is_file($path)) {
        dn_sched_log("MISSING script for '{$key}': {$path}", 'ERROR');
        return;
    }
    dn_sched_log("Dispatch -> {$key}");
    // Run the script in-process so we share the bootstrap + log file.
    require $path;
}

// =====================================================================
// CLI commands: list / run
// =====================================================================
$argv0 = $argv[1] ?? 'tick';

if ($argv0 === 'list') {
    echo "DYNOVA scheduled jobs:\n";
    foreach ($JOBS as $k => $j) {
        printf("  %-18s  %-14s  %s\n", $k, $j['cron'], $j['desc']);
    }
    exit;
}

if ($argv0 === 'run') {
    $which = $argv[2] ?? '';
    if ($which === 'all') {
        foreach ($JOBS as $k => $j) dn_run_job($k, $j, $CRON_DIR);
        exit;
    }
    if (isset($JOBS[$which])) {
        dn_run_job($which, $JOBS[$which], $CRON_DIR);
        exit;
    }
    fwrite(STDERR, "Unknown job '{$which}'. Available: " . implode(', ', array_keys($JOBS)) . "\n");
    exit(1);
}

// =====================================================================
// Regular tick (called once per minute by the single host cron entry)
// =====================================================================
$autoload = __DIR__ . '/vendor/autoload.php';
$usePep   = false;

if (is_file($autoload)) {
    require_once $autoload;
    if (class_exists(\GO\Scheduler::class)) {
        $usePep = true;
    }
}

if ($usePep) {
    // ---------- peppeocchi/php-cron-scheduler path ----------
    $scheduler = new \GO\Scheduler([
        'tempDir' => sys_get_temp_dir(),
    ]);
    foreach ($JOBS as $key => $job) {
        $scheduler
            ->php(__DIR__ . '/cron/' . $job['file'])
            ->at($job['cron'])
            ->onlyOne()
            ->then(function ($output) use ($key) {
                dn_sched_log("peppeocchi: {$key} completed.");
            });
    }
    $scheduler->run();
    dn_sched_log('Tick complete (peppeocchi).', 'INFO');
} else {
    // ---------- built-in fallback ----------
    $ranAny = false;
    foreach ($JOBS as $key => $job) {
        if (dn_cron_due($job['cron'])) {
            $ranAny = true;
            dn_run_job($key, $job, $CRON_DIR);
        }
    }
    if (!$ranAny) {
        // Quiet success – do NOT write to file on every minute or the log explodes.
        // (Still echo for cron mail visibility but skip the file.)
        echo sprintf("[%s] [%-5s] [scheduler] tick – nothing due.\n", date('Y-m-d H:i:s'), 'INFO');
    }
}
