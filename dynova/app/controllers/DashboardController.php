<?php
class DashboardController {
    public function index(): void {
        $u = require_user();
        $todayEarnings = Transaction::todayEarnings((int)$u['id']);
        $teamCount = User::countReferrals((int)$u['id'], 1)
                   + User::countReferrals((int)$u['id'], 2)
                   + User::countReferrals((int)$u['id'], 3);
        $pendingWd = Withdrawal::pendingSumForUser((int)$u['id']);
        $completedToday = Task::completedTodayCount((int)$u['id']);
        $dailyLimit = TaskPackage::dailyLimitFor((int)$u['id']);
        $recent = Transaction::forUser((int)$u['id'], 6);

        // Build a shareable referral link based on the request host.
        // Behind the Emergent proxy the real client-facing host arrives in
        // X-Forwarded-Host/X-Forwarded-Proto, so prefer those over HTTP_HOST.
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO']
              ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host  = $_SERVER['HTTP_X_FORWARDED_HOST']
              ?? $_SERVER['HTTP_HOST']
              ?? 'localhost';
        // If multiple comma-separated hosts/protos arrive, keep the first.
        $proto = trim(explode(',', $proto)[0]);
        $host  = trim(explode(',', $host)[0]);
        $base  = rtrim(BASE_URL, '/');
        $referralLink = $proto . '://' . $host . $base . '/?r=auth/signup&ref=' . urlencode($u['referral_code']);

        view('user/dashboard',
             compact('u','todayEarnings','teamCount','pendingWd','completedToday','dailyLimit','recent','referralLink'),
             'app');
    }
}
