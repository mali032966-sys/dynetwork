<?php
/**
 * Global helper functions – URLs, CSRF, escaping, money, redirects, settings.
 */

function url(string $path = ''): string {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '/');
}

/** Route URL: /api/?r=route */
function route_url(string $route = '', array $params = []): string {
    $qs = ['r' => $route] + $params;
    return url('') . '?' . http_build_query($qs);
}

function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($amount): string {
    return APP_CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

function redirect(string $route = '', array $params = []): void {
    header('Location: ' . route_url($route, $params));
    exit;
}

/** CSRF token – generate & validate */
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['_csrf'] ?? '';
        if (!$t || !hash_equals($_SESSION['_csrf'] ?? '', $t)) {
            http_response_code(419);
            die('Invalid CSRF token. Please go back and try again.');
        }
    }
}

/** Flash messages */
function flash_set(string $type, string $msg): void {
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_pull(): array {
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Settings (admin_settings table) */
function setting(string $key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value FROM admin_settings')->fetchAll();
            foreach ($rows as $r) { $cache[$r['setting_key']] = $r['setting_value']; }
        } catch (Throwable $e) { /* table may not exist yet */ }
    }
    return $cache[$key] ?? $default;
}

function setting_set(string $key, $value): void {
    $stmt = db()->prepare(
        'INSERT INTO admin_settings (setting_key, setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, (string)$value]);
}

/** Old input – preserve form values on validation error */
function old(string $key, $default = ''): string {
    return e($_SESSION['_old'][$key] ?? $default);
}
function old_flash(array $data): void {
    $_SESSION['_old'] = $data;
}
function old_clear(): void { unset($_SESSION['_old']); }

/** Render a view inside a layout */
function view(string $view, array $data = [], ?string $layout = null): void {
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($layout) {
        require __DIR__ . '/views/layouts/' . $layout . '.php';
    } else {
        echo $content;
    }
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Generate a slug-safe referral code from whatsapp number */
function make_referral_code(string $whatsapp): string {
    $digits = preg_replace('/\D+/', '', $whatsapp);
    $tail = substr($digits, -6);
    return 'DN' . $tail . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
}

// -----------------------------------------------------------------------
// 🧧 Red Envelope helpers (v2.1 – deposit-time single-use coupon,
//     with PER-PACKAGE amounts)
// -----------------------------------------------------------------------

/** Master on/off flag. Feature is opt-in; disabled by default. */
function red_envelope_enabled(): bool {
    return setting('red_envelope_enabled') === '1';
}

/** 'fixed' (per-package amounts) or 'random' (surprise from the pool). */
function red_envelope_mode(): string {
    $m = (string) setting('red_envelope_mode', 'fixed');
    return $m === 'random' ? 'random' : 'fixed';
}

/**
 * Per-package discount map, keyed by task_package.id → Rs amount.
 * Stored as JSON in admin_settings.red_envelope_discounts.
 *   { "2": 50, "3": 100, "4": 200, "5": 300, "6": 500 }
 */
function red_envelope_discounts(): array {
    $raw = (string) setting('red_envelope_discounts', '');
    if ($raw === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}

/** Discount configured for a specific package id (0 if none). */
function red_envelope_amount_for_package(int $packageId): float {
    $m = red_envelope_discounts();
    return max(0.0, (float)($m[(string)$packageId] ?? $m[$packageId] ?? 0));
}

/** Maximum amount across all configured packages (used for "up to Rs X"). */
function red_envelope_max_amount(): float {
    $vals = array_map('floatval', red_envelope_discounts());
    $vals = array_filter($vals, fn($v) => $v > 0);
    return $vals ? (float) max($vals) : 0.0;
}

/**
 * Resolve WHAT amount to claim for a specific user, based on their
 * current package state.
 *  - No active package  → highest configured amount (marketing headline).
 *  - Active package     → amount configured for the *next-tier* package,
 *                          or 0 if user is already at the top tier.
 *
 * In random mode we pick uniformly from all non-zero configured amounts.
 * Returns [amount, targetPackageId, targetPackageName, targetIsUpgrade].
 */
function red_envelope_target_for_user(int $uid): array {
    if (!red_envelope_enabled()) return [0.0, 0, '', false];

    $active = class_exists('TaskPackage') ? TaskPackage::activeForUser($uid) : null;

    if (red_envelope_mode() === 'random') {
        $vals = array_values(array_filter(array_map('floatval', red_envelope_discounts()), fn($v) => $v > 0));
        if (!$vals) return [0.0, 0, '', false];
        $amt = (float) $vals[array_rand($vals)];
        return [$amt, 0, '', (bool)$active];
    }

    // Fixed / per-package mode
    if (!$active) {
        // No active package → headline max amount, no specific target
        $amt = red_envelope_max_amount();
        return [$amt, 0, 'first package', false];
    }
    // Find next-tier package (strictly higher price, still active)
    $rows  = class_exists('TaskPackage') ? TaskPackage::active() : [];
    $next  = null;
    $curPr = (float)$active['price_paid'];
    foreach ($rows as $p) {
        if ((float)$p['price'] > $curPr) {
            if ($next === null || (float)$p['price'] < (float)$next['price']) $next = $p;
        }
    }
    if (!$next) return [0.0, 0, '', true]; // top tier already
    $amt = red_envelope_amount_for_package((int)$next['id']);
    return [$amt, (int)$next['id'], (string)$next['name'], true];
}

/**
 * Look up the discount that applies when a user requests a deposit
 * of `$amount`.  The rule: deposit amount must EXACTLY equal the list
 * price of one of the configured task packages, in which case the
 * discount for that package (from the per-package JSON map) is
 * returned.  Any other amount → no discount.
 *
 * Returns [discount, packageName]  or  [0.0, ''] if no match.
 */
function red_envelope_discount_for_amount(float $amount): array {
    if (!red_envelope_enabled() || $amount <= 0) return [0.0, ''];
    if (!class_exists('TaskPackage')) return [0.0, ''];
    $map = red_envelope_discounts();
    if (!$map) return [0.0, ''];
    foreach (TaskPackage::active() as $p) {
        if ((float)$p['price'] === (float)$amount) {
            $d = (float)($map[(string)$p['id']] ?? $map[$p['id']] ?? 0);
            if ($d > 0) return [$d, (string)$p['name']];
        }
    }
    return [0.0, ''];
}

/** Minimum configured discount across all packages (for the "Rs X–Y off" range). */
function red_envelope_min_amount(): float {
    $vals = array_map('floatval', red_envelope_discounts());
    $vals = array_filter($vals, fn($v) => $v > 0);
    return $vals ? (float) min($vals) : 0.0;
}

// -----------------------------------------------------------------------
// LEGACY helpers (v2 single-amount).  Kept as thin wrappers so any older
// caller keeps working.
// -----------------------------------------------------------------------
function red_envelope_next_amount(): float {
    [$amt] = red_envelope_target_for_user(0);
    if ($amt > 0) return $amt;
    return max(0.0, red_envelope_max_amount());
}
function red_envelope_discount_for(int $uid, int $packageId): float { return 0.0; }
function red_envelope_max_discount(): float { return red_envelope_max_amount(); }
function red_envelope_pick_random(): float { return 0.0; }
