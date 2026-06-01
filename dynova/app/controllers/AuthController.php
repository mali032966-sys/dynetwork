<?php
class AuthController {
    public function login(): void {
        if (current_user()) redirect('dashboard');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $w  = trim($_POST['whatsapp'] ?? '');
            $p  = $_POST['password'] ?? '';
            $rm = !empty($_POST['remember']);
            if (!$w || !$p) $errors[] = 'WhatsApp number and password are required.';
            if (!$errors) {
                $u = User::findByWhatsapp($w);
                if (!$u || !password_verify($p, $u['password_hash'])) {
                    $errors[] = 'Invalid WhatsApp number or password.';
                } elseif ((int)$u['is_blocked'] === 1) {
                    $errors[] = 'Your account has been blocked.';
                } else {
                    $_SESSION['user_id'] = $u['id'];
                    if ($rm) {
                        $token = bin2hex(random_bytes(32));
                        db()->prepare('UPDATE users SET remember_token=? WHERE id=?')
                            ->execute([hash('sha256', $token), $u['id']]);
                        setcookie('dn_remember', $u['id'] . ':' . $token,
                            time() + 86400 * REMEMBER_DAYS, '/', '', false, true);
                    }
                    redirect('dashboard');
                }
            }
        }
        view('auth/login', compact('errors'), 'auth');
    }

    public function signup(): void {
        if (current_user()) redirect('dashboard');
        $errors = [];
        // Generate captcha
        if (empty($_SESSION['captcha_a']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $_SESSION['captcha_a'] = random_int(2, 9);
                $_SESSION['captcha_b'] = random_int(2, 9);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name     = trim($_POST['name'] ?? '');
            $whatsapp = preg_replace('/\s+/', '', trim($_POST['whatsapp'] ?? ''));
            $pass     = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm']  ?? '';
            $captcha  = (int)($_POST['captcha'] ?? 0);
            $ref      = trim($_POST['ref'] ?? '');

            if ($whatsapp && !preg_match('/^\+?\d{10,15}$/', $whatsapp)) {
                $errors[] = 'Invalid WhatsApp number format.';
            }
            if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
            if ($pass !== $confirm) $errors[] = 'Passwords do not match.';
            $expected = ($_SESSION['captcha_a'] ?? 0) + ($_SESSION['captcha_b'] ?? 0);
            if ($captcha !== $expected) $errors[] = 'Captcha is incorrect.';
            if (User::findByWhatsapp($whatsapp)) $errors[] = 'WhatsApp number is already registered.';

            $referrerId = null;
            if ($ref) {
                $r = User::findByReferralCode($ref);
                if ($r) $referrerId = (int)$r['id'];
            }

            if (!$errors) {
                $uid = User::create([
                    'name'           => $name ?: 'User ' . substr($whatsapp, -4),
                    'whatsapp'       => $whatsapp,
                    'password_hash'  => password_hash($pass, PASSWORD_BCRYPT),
                    'referral_code'  => make_referral_code($whatsapp),
                    'referred_by'    => $referrerId,
                ]);
                $_SESSION['user_id'] = $uid;
                // refresh captcha
                $_SESSION['captcha_a'] = random_int(2, 9);
                $_SESSION['captcha_b'] = random_int(2, 9);
                flash_set('success', 'Welcome to ' . APP_NAME . '! Your account is ready.');
                redirect('dashboard');
            }
            // re-roll captcha after failed attempt
            $_SESSION['captcha_a'] = random_int(2, 9);
            $_SESSION['captcha_b'] = random_int(2, 9);
        }
        $a = $_SESSION['captcha_a'];
        $b = $_SESSION['captcha_b'];
        $refQuery = e($_GET['ref'] ?? '');
        view('auth/signup', compact('errors','a','b','refQuery'), 'auth');
    }

    public function logout(): void {
        logout_user();
        redirect('auth/login');
    }
}
