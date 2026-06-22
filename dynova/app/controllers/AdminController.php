<?php
class AdminController {
    /**
     * Derive a short, human-readable task title from a video URL when the admin
     * is no longer asked to type one. Pulls the YouTube video ID where possible
     * and falls back to the host name or a generic label.
     */
    private static function deriveTitleFromUrl(string $url): string {
        $url = trim($url);
        if ($url === '') return 'Video Task';
        $vid = '';
        if (preg_match('~[?&]v=([A-Za-z0-9_-]{6,15})~', $url, $m)) {
            $vid = $m[1];
        } elseif (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,15})~', $url, $m)) {
            $vid = $m[1];
        } elseif (preg_match('~/shorts/([A-Za-z0-9_-]{6,15})~', $url, $m)) {
            $vid = $m[1];
        } elseif (preg_match('~/embed/([A-Za-z0-9_-]{6,15})~', $url, $m)) {
            $vid = $m[1];
        }
        if ($vid !== '') return 'Video ' . $vid;
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $host = preg_replace('/^www\./', '', $host);
        return $host ? ('Video · ' . $host) : 'Video Task';
    }

    // -------------------------------------------------- LOGIN
    public function login(): void {
        if (current_admin()) redirect('admin/dashboard');
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $s = db()->prepare('SELECT * FROM admins WHERE email=?');
            $s->execute([$email]);
            $a = $s->fetch();
            if (!$a || !password_verify($pass, $a['password_hash'])) {
                $errors[] = 'Invalid admin credentials.';
            } else {
                $_SESSION['admin_id'] = $a['id'];
                redirect('admin/dashboard');
            }
        }
        view('admin/login', compact('errors'));
    }

    public function logout(): void {
        unset($_SESSION['admin_id']);
        dev_lock();
        redirect('admin/login');
    }

    // -------------------------------------------------- DEVELOPER UNLOCK
    public function devUnlock(): void {
        require_admin();
        $errors = [];
        $return = trim($_GET['return'] ?? $_POST['return'] ?? '');
        // Whitelist: return must be an admin/* route.
        if ($return !== '' && !preg_match('#^admin/[a-z0-9_\-/]*$#i', $return)) {
            $return = '';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pw = (string)($_POST['dev_password'] ?? '');
            if ($pw === '') {
                $errors[] = 'Please enter the developer password.';
            } elseif (dev_unlock_with_password($pw)) {
                flash_set('success', '🔓 Developer unlock active for ' . DEV_UNLOCK_TTL_MINUTES . ' minutes.');
                redirect($return ?: 'admin/dashboard');
            } else {
                $errors[] = 'Incorrect developer password.';
            }
        }
        view('admin/dev_unlock', compact('errors', 'return'), 'admin');
    }

    public function devLock(): void {
        require_admin();
        dev_lock();
        flash_set('success', '🔒 Developer lock re-engaged. Developer area is now hidden.');
        redirect('admin/developer');
    }

    // -------------------------------------------------- DEVELOPER HUB
    public function developer(): void {
        require_admin();
        require_dev_unlock();  // password-gates the whole hub
        view('admin/developer', [], 'admin');
    }

    // -------------------------------------------------- DASHBOARD
    public function dashboard(): void {
        require_admin();
        $stats = [
            'total_users'        => (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'total_deposits'     => (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM deposits WHERE status='approved'")->fetchColumn(),
            'pending_deposits'   => (int)db()->query("SELECT COUNT(*) FROM deposits WHERE status='pending'")->fetchColumn(),
            'pending_wd'         => (int)db()->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetchColumn(),
            'today_earnings'     => (float)db()->query(
                "SELECT COALESCE(SUM(amount),0) FROM transactions
                 WHERE type IN ('task','referral','salary') AND DATE(created_at)=CURDATE()"
            )->fetchColumn(),
            'total_tasks'        => (int)db()->query('SELECT COUNT(*) FROM tasks')->fetchColumn(),
            'completions_today'  => (int)db()->query('SELECT COUNT(*) FROM task_completions WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
        ];
        // 7-day chart
        $rows = db()->query(
            "SELECT DATE(created_at) d, COALESCE(SUM(amount),0) total
             FROM transactions WHERE type IN ('task','referral','salary')
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at) ORDER BY d ASC"
        )->fetchAll();
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $chart[$d] = 0;
        }
        foreach ($rows as $r) { $chart[$r['d']] = (float)$r['total']; }
        $pendingWd = Withdrawal::pending();
        view('admin/dashboard', compact('stats','chart','pendingWd'), 'admin');
    }

    // -------------------------------------------------- USERS
    public function users(): void {
        require_admin();
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $s = db()->prepare("SELECT * FROM users WHERE whatsapp LIKE ? OR name LIKE ? OR referral_code LIKE ? ORDER BY id DESC LIMIT 200");
            $s->execute(["%$q%","%$q%","%$q%"]);
        } else {
            $s = db()->query('SELECT * FROM users ORDER BY id DESC LIMIT 200');
        }
        $users = $s->fetchAll();
        view('admin/users', compact('users','q'), 'admin');
    }
    public function userEdit(): void {
        require_admin();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $u = User::find($id);
        if (!$u) { flash_set('error','User not found.'); redirect('admin/users'); }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'block') {
                db()->prepare('UPDATE users SET is_blocked=1 WHERE id=?')->execute([$id]);
                flash_set('success','User blocked.');
            } elseif ($action === 'unblock') {
                db()->prepare('UPDATE users SET is_blocked=0 WHERE id=?')->execute([$id]);
                flash_set('success','User unblocked.');
            } elseif ($action === 'adjust') {
                $amt  = (float)($_POST['amount'] ?? 0);
                $note = trim($_POST['note'] ?? '');
                if ($amt != 0) {
                    db()->prepare('UPDATE users SET balance = balance + ? WHERE id=?')->execute([$amt, $id]);
                    Transaction::log($id, 'admin_adjust', $amt, $note ?: 'Admin balance adjust');
                    flash_set('success','Balance adjusted by ' . money($amt));
                }
            }
            redirect('admin/users/edit', ['id' => $id]);
        }
        $tx = Transaction::forUser($id, 30);
        view('admin/user_edit', compact('u','tx'), 'admin');
    }

    // -------------------------------------------------- DEPOSITS
    public function deposits(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $act = $_POST['action'] ?? '';
            $d = Deposit::find($id);
            if ($d && $d['status'] === 'pending') {
                if ($act === 'approve') {
                    Deposit::setStatus($id, 'approved', $_POST['note'] ?? null);
                    User::addBalance((int)$d['user_id'], (float)$d['amount'], 'balance');
                    User::addBalance((int)$d['user_id'], (float)$d['amount'], 'deposit_total');
                    Transaction::log((int)$d['user_id'], 'deposit', (float)$d['amount'], 'Deposit via ' . $d['method']);
                    flash_set('success','Deposit approved.');
                } elseif ($act === 'reject') {
                    Deposit::setStatus($id, 'rejected', $_POST['note'] ?? null);
                    flash_set('success','Deposit rejected.');
                }
            }
            redirect('admin/deposits');
        }
        $pending = Deposit::pending();
        $all = Deposit::all();
        view('admin/deposits', compact('pending','all'), 'admin');
    }

    // -------------------------------------------------- WITHDRAWALS
    public function withdrawals(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $act = $_POST['action'] ?? '';
            $w = Withdrawal::find($id);
            if ($w && $w['status'] === 'pending') {
                if ($act === 'paid' || $act === 'approve') {
                    Withdrawal::setStatus($id, 'paid', $_POST['note'] ?? null);
                    flash_set('success','Withdrawal marked as paid.');
                } elseif ($act === 'reject') {
                    Withdrawal::setStatus($id, 'rejected', $_POST['note'] ?? null);
                    // refund amount back to user balance
                    User::addBalance((int)$w['user_id'], (float)$w['amount'], 'balance');
                    Transaction::log((int)$w['user_id'], 'admin_adjust', (float)$w['amount'], 'Withdrawal refund');
                    flash_set('success','Withdrawal rejected and amount refunded.');
                }
            }
            redirect('admin/withdrawals');
        }
        $pending = Withdrawal::pending();
        $all = Withdrawal::all();
        view('admin/withdrawals', compact('pending','all'), 'admin');
    }

    // -------------------------------------------------- TASKS
    public function tasks(): void {
        require_admin();
        require_dev_unlock();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'add' || $act === 'edit') {
                $id     = (int)($_POST['id'] ?? 0);
                $url    = trim($_POST['video_url'] ?? '');
                $active = isset($_POST['is_active']) ? 1 : 0;

                // Reward per task is now driven by the user's active package
                // (see TaskPackage::getRewardPerTask). Title is auto-derived
                // from the YouTube URL so admin only has to paste a link.
                $title = self::deriveTitleFromUrl($url);
                $desc  = '';

                if ($url) {
                    if ($id) {
                        db()->prepare('UPDATE tasks SET title=?, video_url=?, description=?, is_active=? WHERE id=?')
                            ->execute([$title,$url,$desc,$active,$id]);
                    } else {
                        db()->prepare('INSERT INTO tasks (title, video_url, description, is_active) VALUES (?,?,?,?)')
                            ->execute([$title,$url,$desc,$active]);
                    }
                    flash_set('success','Task saved.');
                } else {
                    flash_set('error','Video URL is required.');
                }
            } elseif ($act === 'delete') {
                db()->prepare('DELETE FROM tasks WHERE id=?')->execute([(int)$_POST['id']]);
                flash_set('success','Task deleted.');
            } elseif ($act === 'toggle') {
                db()->prepare('UPDATE tasks SET is_active = 1 - is_active WHERE id=?')->execute([(int)$_POST['id']]);
            }
            redirect('admin/tasks');
        }
        $tasks = Task::all();
        view('admin/tasks', compact('tasks'), 'admin');
    }

    // -------------------------------------------------- REFERRAL TREE
    public function referrals(): void {
        require_admin();

        // Accept any of:  ?q=<id|mobile|code>   (preferred, single box)
        // or the legacy   ?user_id=<id>         (kept for old links).
        $q   = trim((string)($_GET['q']       ?? $_GET['user_id'] ?? ''));
        $err = null;
        $user = null;

        if ($q !== '') {
            // 1) Numeric -> internal user id
            if (ctype_digit($q) && strlen($q) <= 11) {
                $user = User::find((int)$q);
            }
            // 2) Looks like a mobile number (10-15 digits, optional + prefix)
            if (!$user) {
                $waNorm = preg_replace('/\D+/', '', $q) ?? '';
                if (strlen($waNorm) >= 10 && strlen($waNorm) <= 15) {
                    // try a few common formattings (raw, with +)
                    $user = User::findByWhatsapp($waNorm)
                         ?: User::findByWhatsapp('+' . $waNorm);
                }
            }
            // 3) Treat as a referral code (e.g. "DN2345672AD") — case-insensitive
            if (!$user) {
                $user = User::findByReferralCode(strtoupper($q));
            }
            if (!$user) {
                $err = 'No user found for "' . $q . '". Try a different ID, mobile number, or referral code.';
            }
        }

        $teamA = $teamB = $teamC = [];
        if ($user) {
            $teamA = Referral::levelMembers((int)$user['id'], 1);
            $teamB = Referral::levelMembers((int)$user['id'], 2);
            $teamC = Referral::levelMembers((int)$user['id'], 3);
        }
        view('admin/referrals', compact('user','teamA','teamB','teamC','q','err'), 'admin');
    }

    // -------------------------------------------------- SETTINGS
    public function settings(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $section = $_POST['section'] ?? '';
            if ($section === 'general') {
                foreach (['referral_l1','referral_l2','referral_l3','min_withdrawal','site_name','site_tagline'] as $k) {
                    if (isset($_POST[$k])) setting_set($k, $_POST[$k]);
                }
                flash_set('success','Settings updated.');
            } elseif ($section === 'pm_save') {
                PaymentMethod::save((int)($_POST['id'] ?? 0) ?: null, [
                    'name'           => $_POST['name'] ?? '',
                    'account_title'  => $_POST['account_title'] ?? '',
                    'account_number' => $_POST['account_number'] ?? '',
                    'instructions'   => $_POST['instructions'] ?? '',
                    'is_active'      => isset($_POST['is_active']) ? 1 : 0,
                ]);
                flash_set('success','Payment method saved.');
            } elseif ($section === 'pm_delete') {
                PaymentMethod::delete((int)$_POST['id']);
                flash_set('success','Payment method deleted.');
            } elseif ($section === 'admin_password') {
                // Admin self-service password change. Requires the current
                // password, never a dev unlock — admins must always be able
                // to rotate their own credentials.
                $current = (string)($_POST['current_password'] ?? '');
                $new     = (string)($_POST['new_password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');

                $admin = db()->prepare('SELECT id, password_hash FROM admins WHERE id=?');
                $admin->execute([(int)($_SESSION['admin_id'] ?? 0)]);
                $row = $admin->fetch();

                if (!$row || !password_verify($current, $row['password_hash'])) {
                    flash_set('error', 'Current password is incorrect.');
                } elseif (strlen($new) < 8) {
                    flash_set('error', 'New password must be at least 8 characters.');
                } elseif ($new !== $confirm) {
                    flash_set('error', 'New password and confirmation do not match.');
                } elseif (password_verify($new, $row['password_hash'])) {
                    flash_set('error', 'New password must be different from the current one.');
                } else {
                    $hash = password_hash($new, PASSWORD_BCRYPT);
                    db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')
                        ->execute([$hash, (int)$row['id']]);
                    flash_set('success', 'Admin password updated successfully.');
                }
            }
            redirect('admin/settings');
        }
        $methods = PaymentMethod::all();
        $values = [
            'referral_l1'      => setting('referral_l1', DEFAULT_REFERRAL_L1),
            'referral_l2'      => setting('referral_l2', DEFAULT_REFERRAL_L2),
            'referral_l3'      => setting('referral_l3', DEFAULT_REFERRAL_L3),
            'min_withdrawal'   => setting('min_withdrawal', DEFAULT_MIN_WITHDRAWAL),
            'site_name'        => setting('site_name', APP_NAME),
            'site_tagline'     => setting('site_tagline', 'Rate. Earn. Refer.'),
        ];
        view('admin/settings', compact('values','methods'), 'admin');
    }

    // -------------------------------------------------- RANKS
    public function ranks(): void {
        require_admin();
        require_dev_unlock();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $l1m = (int)($_POST['min_l1_members'] ?? 0);
                $l2m = (int)($_POST['min_l2_members'] ?? 0);
                $l3m = (int)($_POST['min_l3_members'] ?? 0);
                $l1b = (float)($_POST['min_l1_business'] ?? 0);
                $l2b = (float)($_POST['min_l2_business'] ?? 0);
                $l3b = (float)($_POST['min_l3_business'] ?? 0);
                $data = [
                    $_POST['name'] ?? '',
                    $_POST['emoji'] ?? '',
                    $l1m + $l2m + $l3m,                  // legacy min_referrals (total)
                    $l1m, $l2m, $l3m,
                    $l1b + $l2b + $l3b,                  // legacy min_business (total)
                    $l1b, $l2b, $l3b,
                    (float)($_POST['monthly_salary'] ?? 0),
                    (int)($_POST['sort_order'] ?? 0),
                ];
                if ($id) {
                    db()->prepare(
                        'UPDATE salary_ranks SET
                            name=?, emoji=?,
                            min_referrals=?, min_l1_members=?, min_l2_members=?, min_l3_members=?,
                            min_business=?, min_l1_business=?, min_l2_business=?, min_l3_business=?,
                            monthly_salary=?, sort_order=?
                          WHERE id=?'
                    )->execute([...$data, $id]);
                } else {
                    db()->prepare(
                        'INSERT INTO salary_ranks
                          (name, emoji,
                           min_referrals, min_l1_members, min_l2_members, min_l3_members,
                           min_business, min_l1_business, min_l2_business, min_l3_business,
                           monthly_salary, sort_order)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                    )->execute($data);
                }
                flash_set('success','Rank saved.');
            } elseif ($act === 'delete') {
                db()->prepare('DELETE FROM salary_ranks WHERE id=?')->execute([(int)$_POST['id']]);
                flash_set('success','Rank deleted.');
            } elseif ($act === 'pay_now') {
                $n = Salary::payMonthly();
                flash_set('success', "Monthly salaries paid to $n users.");
            }
            redirect('admin/ranks');
        }
        $ranks = Salary::ranks();
        view('admin/ranks', compact('ranks'), 'admin');
    }

    // -------------------------------------------------- JOINING BONUSES
    public function bonuses(): void {
        require_admin();
        require_dev_unlock();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'save') {
                $data = [
                    'package_id'     => (int)($_POST['package_id'] ?? 0),
                    'referrer_bonus' => (float)($_POST['referrer_bonus'] ?? 0),
                    'invitee_bonus'  => (float)($_POST['invitee_bonus'] ?? 0),
                    'is_active'      => isset($_POST['is_active']) ? 1 : 0,
                ];
                if ($data['package_id'] <= 0) {
                    flash_set('error', 'Please pick a package.');
                } else {
                    JoiningBonus::save((int)($_POST['id'] ?? 0) ?: null, $data);
                    flash_set('success', 'Joining bonus saved.');
                }
            } elseif ($act === 'delete') {
                JoiningBonus::delete((int)$_POST['id']);
                flash_set('success', 'Joining bonus deleted.');
            } elseif ($act === 'toggle') {
                db()->prepare('UPDATE joining_bonuses SET is_active = 1 - is_active WHERE id=?')
                    ->execute([(int)$_POST['id']]);
            }
            redirect('admin/bonuses');
        }
        $bonuses  = JoiningBonus::all();
        $packages = TaskPackage::all();
        view('admin/bonuses', compact('bonuses', 'packages'), 'admin');
    }

    // -------------------------------------------------- PACKAGES
    public function packages(): void {
        require_admin();
        require_dev_unlock();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            if ($act === 'save') {
                TaskPackage::save((int)($_POST['id'] ?? 0) ?: null, $_POST);
                flash_set('success', 'Package saved.');
            } elseif ($act === 'delete') {
                TaskPackage::delete((int)($_POST['id'] ?? 0));
                flash_set('success', 'Package deleted.');
            } elseif ($act === 'toggle') {
                db()->prepare('UPDATE task_packages SET is_active = 1 - is_active WHERE id=?')
                    ->execute([(int)($_POST['id'] ?? 0)]);
            }
            redirect('admin/packages');
        }
        $packages = TaskPackage::all();
        view('admin/packages', compact('packages'), 'admin');
    }

    // -------------------------------------------------- TRANSACTIONS
    public function transactions(): void {
        require_admin();
        $type = $_GET['type'] ?? '';
        $allowed = ['deposit','task','referral','salary','withdrawal','admin_adjust'];
        if ($type && in_array($type, $allowed, true)) {
            $s = db()->prepare(
                'SELECT t.*, u.whatsapp, u.name FROM transactions t JOIN users u ON u.id=t.user_id
                 WHERE t.type=? ORDER BY t.id DESC LIMIT 300'
            );
            $s->execute([$type]);
        } else {
            $s = db()->query(
                'SELECT t.*, u.whatsapp, u.name FROM transactions t JOIN users u ON u.id=t.user_id
                 ORDER BY t.id DESC LIMIT 300'
            );
        }
        $rows = $s->fetchAll();
        view('admin/transactions', compact('rows','type','allowed'), 'admin');
    }

    // -------------------------------------------------- POPUP MESSAGES (developer)
    public function popups(): void {
        require_admin();
        require_dev_unlock();
        Popup::ensureSchema();

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'save';

            if ($action === 'delete') {
                Popup::delete((int)($_POST['id'] ?? 0));
                flash_set('success', 'Popup deleted.');
                redirect('admin/popups');
            }

            if ($action === 'toggle') {
                $id = (int)($_POST['id'] ?? 0);
                db()->prepare('UPDATE popups SET is_active = 1 - is_active WHERE id=?')->execute([$id]);
                flash_set('success', 'Popup status updated.');
                redirect('admin/popups');
            }

            // ---- save (create / update) ----
            $id      = (int)($_POST['id'] ?? 0);
            $type    = ($_POST['type'] ?? 'text') === 'image' ? 'image' : 'text';
            $title   = trim((string)($_POST['title']   ?? ''));
            $message = trim((string)($_POST['message'] ?? ''));
            $startAt = trim((string)($_POST['start_at'] ?? ''));
            $endAt   = trim((string)($_POST['end_at']   ?? ''));
            $active  = !empty($_POST['is_active']);
            $existing = $id ? Popup::find($id) : null;
            $imagePath = $existing['image_path'] ?? null;

            // datetime-local comes as "YYYY-MM-DDTHH:MM"; rewrite to MySQL DATETIME.
            $fixDt = function (string $v): ?string {
                if ($v === '') return null;
                $v = str_replace('T', ' ', $v);
                return strlen($v) === 16 ? $v . ':00' : $v;
            };
            $startAt = $fixDt($startAt);
            $endAt   = $fixDt($endAt);

            if ($startAt && $endAt && strtotime($endAt) <= strtotime($startAt)) {
                $errors[] = 'End date must be after start date.';
            }

            if ($type === 'image') {
                if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
                    $f = $_FILES['image'];
                    if ($f['size'] > 5 * 1024 * 1024) {
                        $errors[] = 'Image must be 5 MB or smaller.';
                    }
                    $mime = function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : '';
                    $okMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                    if (!isset($okMimes[$mime])) {
                        $errors[] = 'Unsupported image type. Use JPG, PNG, WEBP or GIF.';
                    }
                    if (!$errors) {
                        $ext = $okMimes[$mime];
                        $dir = __DIR__ . '/../../public/uploads/popups';
                        if (!is_dir($dir)) @mkdir($dir, 0775, true);
                        $name = 'popup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        $abs  = $dir . '/' . $name;
                        if (move_uploaded_file($f['tmp_name'], $abs)) {
                            // Remove the previous image if we're replacing it.
                            if ($existing && !empty($existing['image_path'])) {
                                $oldAbs = __DIR__ . '/../../public/' . ltrim($existing['image_path'], '/');
                                if (is_file($oldAbs)) @unlink($oldAbs);
                            }
                            $imagePath = 'uploads/popups/' . $name;
                        } else {
                            $errors[] = 'Failed to save uploaded image.';
                        }
                    }
                }
                if (!$imagePath) $errors[] = 'Image popups need an image file.';
            } else {
                if ($message === '') $errors[] = 'Text popups need a message.';
            }

            if (!$errors) {
                $newId = Popup::save([
                    'type'       => $type,
                    'title'      => $title !== '' ? $title : null,
                    'message'    => $type === 'text' ? $message : null,
                    'image_path' => $type === 'image' ? $imagePath : null,
                    'start_at'   => $startAt,
                    'end_at'     => $endAt,
                    'is_active'  => $active,
                ], $id ?: null);
                flash_set('success', $id ? 'Popup updated.' : 'Popup created.');
                redirect('admin/popups');
            }
        }

        $editId  = (int)($_GET['edit'] ?? 0);
        $editing = $editId ? Popup::find($editId) : null;
        $popups  = Popup::all();
        view('admin/popups', compact('popups','editing','errors'), 'admin');
    }
}
