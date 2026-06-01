<?php
/**
 * Authentication helpers – user + admin session management.
 */

function current_user(): ?array {
    if (!empty($_SESSION['user_id'])) {
        static $user = null;
        if ($user === null) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
        return $user;
    }
    // Try remember-me cookie
    if (!empty($_COOKIE['dn_remember'])) {
        [$uid, $token] = array_pad(explode(':', $_COOKIE['dn_remember'], 2), 2, '');
        if ($uid && $token) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND remember_token = ?');
            $stmt->execute([$uid, hash('sha256', $token)]);
            if ($u = $stmt->fetch()) {
                $_SESSION['user_id'] = $u['id'];
                return $u;
            }
        }
    }
    return null;
}

function require_user(): array {
    $u = current_user();
    if (!$u) { redirect('auth/login'); }
    if ((int)$u['is_blocked'] === 1) {
        session_destroy();
        die('Your account is blocked. Contact support.');
    }
    return $u;
}

function current_admin(): ?array {
    if (!empty($_SESSION['admin_id'])) {
        $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

function require_admin(): array {
    $a = current_admin();
    if (!$a) { redirect('admin/login'); }
    return $a;
}

function logout_user(): void {
    if (!empty($_SESSION['user_id'])) {
        db()->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')
            ->execute([$_SESSION['user_id']]);
    }
    setcookie('dn_remember', '', time() - 3600, '/');
    unset($_SESSION['user_id']);
}

// =====================================================================
//  Developer protection – gate add/edit/delete actions in admin panel.
// =====================================================================

/** Returns true if the developer unlock is currently active for this session. */
function dev_unlocked(): bool {
    $until = (int)($_SESSION['_dev_unlock_until'] ?? 0);
    if ($until <= 0) return false;
    if (time() > $until) {
        unset($_SESSION['_dev_unlock_until']);
        return false;
    }
    return true;
}

/** Number of seconds remaining on the current unlock (0 if locked). */
function dev_unlock_remaining(): int {
    $until = (int)($_SESSION['_dev_unlock_until'] ?? 0);
    return max(0, $until - time());
}

/** Verify the developer password and unlock the session for DEV_UNLOCK_TTL_MINUTES. */
function dev_unlock_with_password(string $password): bool {
    if (!defined('DEV_ACCESS_PASSWORD')) return false;
    if (!hash_equals(DEV_ACCESS_PASSWORD, $password)) return false;
    $_SESSION['_dev_unlock_until'] = time() + (DEV_UNLOCK_TTL_MINUTES * 60);
    return true;
}

/** Manually lock again (revoke the unlock immediately). */
function dev_lock(): void {
    unset($_SESSION['_dev_unlock_until']);
}

/**
 * Gate any admin write action. Call at the top of every controller method
 * that mutates configuration data. If the developer hasn't unlocked, the
 * user is redirected to the unlock page with a `return` parameter so they
 * end up back on the page they came from after unlocking.
 */
function require_dev_unlock(): void {
    if (dev_unlocked()) return;
    $return = $_SERVER['REQUEST_URI'] ?? '';
    // Only allow same-origin returns; strip BASE_URL so the route helper
    // can rebuild a proper URL.
    $ret = '';
    if ($return) {
        $base = rtrim(BASE_URL, '/');
        if ($base && strpos($return, $base) === 0) {
            $return = substr($return, strlen($base));
        }
        // Extract just the `r=...` route part for the return param.
        $qs = parse_url($return, PHP_URL_QUERY) ?: '';
        parse_str($qs, $parts);
        $ret = $parts['r'] ?? '';
    }
    flash_set('error', 'Developer unlock required for this action.');
    $params = $ret ? ['return' => $ret] : [];
    redirect('admin/dev-unlock', $params);
}
