<?php
/**
 * Front controller – routes ?r=... to controllers.
 */
require_once __DIR__ . '/../app/bootstrap.php';

$route = $_GET['r'] ?? 'home';
$route = trim($route, '/');

// CSRF for all POSTs
csrf_check();

// Public/auth routes
$publicRoutes = [
    'home', 'auth/login', 'auth/signup', 'auth/logout',
    'admin/login', 'admin/logout',
];

$isAdmin = str_starts_with($route, 'admin/') || $route === 'admin';

try {
    switch (true) {
        // ----- Landing -----
        case $route === 'home' || $route === '':
            (new HomeController())->index(); break;
        case $route === 'auth/login':
            (new AuthController())->login(); break;
        case $route === 'auth/signup':
            (new AuthController())->signup(); break;
        case $route === 'auth/logout':
            (new AuthController())->logout(); break;

        // ----- User pages -----
        case $route === 'dashboard':
            (new DashboardController())->index(); break;
        case $route === 'tasks':
            (new TaskController())->index(); break;
        case $route === 'tasks/submit':
            (new TaskController())->submit(); break;
        case $route === 'packages':
            (new PackageController())->index(); break;
        case $route === 'ranks':
            (new RankController())->index(); break;
        case $route === 'bonuses':
            (new BonusController())->index(); break;
        case $route === 'referrals':
            (new ReferralController())->index(); break;
        case $route === 'wallet':
            (new WalletController())->index(); break;
        case $route === 'wallet/deposit':
            (new WalletController())->deposit(); break;
        case $route === 'wallet/withdraw':
            (new WalletController())->withdraw(); break;
        case $route === 'profile':
            (new ProfileController())->index(); break;
        case $route === 'profile/password':
            (new ProfileController())->password(); break;

        // ----- Admin -----
        case $route === 'admin' || $route === 'admin/dashboard':
            (new AdminController())->dashboard(); break;
        case $route === 'admin/login':
            (new AdminController())->login(); break;
        case $route === 'admin/logout':
            (new AdminController())->logout(); break;
        case $route === 'admin/dev-unlock':
            (new AdminController())->devUnlock(); break;
        case $route === 'admin/dev-lock':
            (new AdminController())->devLock(); break;
        case $route === 'admin/developer':
            (new AdminController())->developer(); break;
        case $route === 'admin/users':
            (new AdminController())->users(); break;
        case $route === 'admin/users/edit':
            (new AdminController())->userEdit(); break;
        case $route === 'admin/deposits':
            (new AdminController())->deposits(); break;
        case $route === 'admin/withdrawals':
            (new AdminController())->withdrawals(); break;
        case $route === 'admin/tasks':
            (new AdminController())->tasks(); break;
        case $route === 'admin/referrals':
            (new AdminController())->referrals(); break;
        case $route === 'admin/settings':
            (new AdminController())->settings(); break;
        case $route === 'admin/ranks':
            (new AdminController())->ranks(); break;
        case $route === 'admin/transactions':
            (new AdminController())->transactions(); break;
        case $route === 'admin/packages':
            (new AdminController())->packages(); break;
        case $route === 'admin/bonuses':
            (new AdminController())->bonuses(); break;
        case $route === 'admin/popups':
            (new AdminController())->popups(); break;

        default:
            http_response_code(404);
            view('errors/404', [], 'app');
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre style="color:#fff;background:#0b1020;padding:20px;font:14px/1.5 monospace">';
    echo "Error: " . e($e->getMessage()) . "\n\n";
    echo e($e->getTraceAsString());
    echo '</pre>';
}
