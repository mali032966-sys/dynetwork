<?php
/**
 * Bootstrap – loaded by the front controller.
 */
require_once __DIR__ . '/config.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Secure session
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/payment_logo.php';
require_once __DIR__ . '/auth.php';

// Auto-load models (simple, no namespaces)
spl_autoload_register(function ($class) {
    foreach (['models', 'controllers'] as $dir) {
        $f = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (is_file($f)) { require_once $f; return; }
    }
});
