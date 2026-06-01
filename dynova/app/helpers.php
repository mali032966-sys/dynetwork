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
