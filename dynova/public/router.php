<?php
/**
 * Router script for PHP built-in server.
 * - Strips the BASE_URL prefix (e.g. "/api") before resolving static files.
 * - Manually streams real static files (PHP built-in server `return false`
 *   does not honor REQUEST_URI rewriting, so we serve files ourselves).
 * - Routes everything else to the front controller (index.php).
 */

// Determine the base prefix the app is mounted under (e.g. "/api" on Emergent).
$basePrefix = getenv('DYNOVA_BASE_URL');
if ($basePrefix === false) {
    $basePrefix = '/api';
}
$basePrefix = '/' . trim($basePrefix, '/');
if ($basePrefix === '/') {
    $basePrefix = '';
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip base prefix from URI so /api/assets/css/style.css -> /assets/css/style.css
$stripped = $uri;
if ($basePrefix !== '' && strpos($uri, $basePrefix) === 0) {
    $stripped = substr($uri, strlen($basePrefix)) ?: '/';
    if ($stripped[0] !== '/') {
        $stripped = '/' . $stripped;
    }
}

$docroot = __DIR__;
$file = realpath($docroot . $stripped);

// Security: ensure resolved path is still inside docroot
if ($file && strpos($file, $docroot) === 0 && is_file($file)) {
    // Block direct PHP execution of static-served files
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['php', 'phtml', 'phar'], true)) {
        http_response_code(403);
        exit('Forbidden');
    }

    $mimes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'map'  => 'application/json',
        'txt'  => 'text/plain',
        'html' => 'text/html',
    ];
    $mime = $mimes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

require __DIR__ . '/index.php';
