<?php
class ProfileController {
    public function index(): void {
        $u = require_user();
        $rank = Salary::rankFor((int)$u['id']);
        $refCount1 = User::countReferrals((int)$u['id'], 1);
        $business  = User::teamBusiness((int)$u['id']);
        view('user/profile', compact('u','rank','refCount1','business'), 'app');
    }
    public function password(): void {
        $u = require_user();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cur = $_POST['current'] ?? '';
            $new = $_POST['new'] ?? '';
            $conf = $_POST['confirm'] ?? '';
            if (!password_verify($cur, $u['password_hash'])) $errors[] = 'Current password is incorrect.';
            if (strlen($new) < 6) $errors[] = 'New password must be at least 6 characters.';
            if ($new !== $conf) $errors[] = 'Passwords do not match.';
            if (!$errors) {
                db()->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($new, PASSWORD_BCRYPT), $u['id']]);
                flash_set('success', 'Password updated.');
                redirect('profile');
            }
        }
        view('user/change_password', compact('errors'), 'app');
    }
}
