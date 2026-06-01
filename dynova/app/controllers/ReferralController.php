<?php
class ReferralController {
    public function index(): void {
        $u = require_user();
        $uid = (int)$u['id'];
        $period = $_GET['period'] ?? 'daily';
        if (!in_array($period, ['daily','weekly','yearly'], true)) $period = 'daily';

        $teamA = Referral::levelMembers($uid, 1);
        $teamB = Referral::levelMembers($uid, 2);
        $teamC = Referral::levelMembers($uid, 3);

        $earnA = Referral::earningsByLevel($uid, 1, $period);
        $earnB = Referral::earningsByLevel($uid, 2, $period);
        $earnC = Referral::earningsByLevel($uid, 3, $period);
        $earnTotal = $earnA + $earnB + $earnC;

        $percents = [
            'L1' => (float)setting('referral_l1', DEFAULT_REFERRAL_L1),
            'L2' => (float)setting('referral_l2', DEFAULT_REFERRAL_L2),
            'L3' => (float)setting('referral_l3', DEFAULT_REFERRAL_L3),
        ];
        // X-Forwarded-* can be a comma-separated list when chained through
        // multiple proxies; take the first non-empty value.
        $rawHost  = $_SERVER['HTTP_X_FORWARDED_HOST']  ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $rawProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
        $host  = trim(explode(',', $rawHost)[0]);
        $proto = trim(explode(',', $rawProto)[0]) ?: 'https';
        // strip any accidental scheme prefix that might be in the host value
        $host = preg_replace('#^https?://#i', '', $host);
        $refUrl = $proto . '://' . $host . url('?r=auth/signup&ref=' . urlencode($u['referral_code']));

        view('user/referrals', compact('u','period','teamA','teamB','teamC','earnA','earnB','earnC','earnTotal','percents','refUrl'), 'app');
    }
}
