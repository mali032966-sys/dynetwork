<?php
/**
 * Front controller – routes ?r=... to controllers.
 */
require_once __DIR__ . '/../app/bootstrap.php';

$route = $_GET['r'] ?? 'home';
$route = trim($route, '/');

// CSRF for all POSTs
csrf_check();

// =========================================================================
// SYSTEM LOCK  (Developer -> System Lock toggle)
// When `lock_user_actions = 1` we block every user-side action POST but
// keep read-only browsing (dashboard, profile view, ranks, referrals,
// bonuses, wallet balance, deposit/withdraw pages — they just can't be
// submitted). Admin routes are NEVER blocked.
// =========================================================================
$isAdmin = str_starts_with($route, 'admin/') || $route === 'admin';
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !$isAdmin
    && setting('lock_user_actions') === '1') {

    $lockedActions = [
        'auth/signup',
        'tasks/submit',
        'wallet/deposit',
        'wallet/withdraw',
        'packages',
        'profile',
        'profile/password',
    ];
    if (in_array($route, $lockedActions, true)) {
        flash_set('error', 'The platform is under maintenance. User actions are temporarily disabled — please try again later.');
        redirect(current_user() ? 'dashboard' : 'auth/login');
    }
}

// Public/auth routes
$publicRoutes = [
    'home', 'auth/login', 'auth/signup', 'auth/logout',
    'admin/login', 'admin/logout',
];

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
        case $route === 'wallet/red-envelope-claim':
            // 🧧 User taps CLAIM on the dashboard coupon → grant a claim
            //    using the amount that matches their next action:
            //     - no active package → highest configured amount
            //     - active package    → discount for the next-tier package
            $u = require_user();
            [$amt] = red_envelope_target_for_user((int)$u['id']);
            RedEnvelope::claim((int)$u['id'], $amt > 0 ? $amt : null);
            unset($_SESSION['re_popup_seen']);
            redirect('dashboard');
            break;
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

        case $route === 'admin/system-lock':
            // Toggle the system-wide "lock user actions" flag (dev-only).
            require_admin();
            require_dev_unlock();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $cur = setting('lock_user_actions') === '1';
                setting_set('lock_user_actions', $cur ? '0' : '1');
                flash_set('success', $cur
                    ? 'User actions UNLOCKED. The platform is live again.'
                    : 'User actions LOCKED. Users can sign in and browse but cannot deposit, withdraw, rate tasks, activate packages, sign up, or edit profile.');
            }
            redirect('admin/developer');
            break;

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
