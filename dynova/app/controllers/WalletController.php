<?php
class WalletController {
    public function index(): void {
        $u = require_user();
        $tx = Transaction::forUser((int)$u['id'], 50);
        $pending = Withdrawal::pendingSumForUser((int)$u['id']);
        view('user/wallet', compact('u','tx','pending'), 'app');
    }

    /**
     * 3-step deposit wizard:
     *  step 1: choose amount + payment method
     *  step 2: show selected method's account details + instructions
     *  step 3: paste transaction ID + upload payment screenshot → submit
     * State is kept in $_SESSION['dep_wizard'] until submitted.
     */
    public function deposit(): void {
        $u = require_user();
        $methods = PaymentMethod::active();
        $errors  = [];
        $step    = (int)($_GET['step'] ?? $_POST['step'] ?? 1);
        if ($step < 1 || $step > 3) $step = 1;
        $wizard  = $_SESSION['dep_wizard'] ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'cancel') {
                unset($_SESSION['dep_wizard']);
                redirect('wallet/deposit');
            }

            if ($action === 'back' && $step > 1) {
                redirect('wallet/deposit', ['step' => $step - 1]);
            }

            if ($step === 1 && $action === 'next') {
                $amount   = (float)($_POST['amount'] ?? 0);
                // The form now submits the PRIMARY KEY of the payment method
                // (input name="method_id"). The previous name-based field
                // ("method") is still accepted for backward compatibility
                // with already-cached pages.
                $methodId = (int)($_POST['method_id'] ?? 0);
                $methodNameRaw = trim($_POST['method'] ?? '');

                // Resolve the chosen method server-side so we never trust the
                // client's name string. If method_id is provided, look it up;
                // otherwise try to resolve by case-insensitive name match
                // against the current list of ACTIVE methods.
                $chosen = null;
                if ($methodId > 0) {
                    foreach ($methods as $m) {
                        if ((int)$m['id'] === $methodId) { $chosen = $m; break; }
                    }
                }
                if (!$chosen && $methodNameRaw !== '') {
                    foreach ($methods as $m) {
                        if (strcasecmp($m['name'], $methodNameRaw) === 0) {
                            $chosen = $m; break;
                        }
                    }
                }

                if ($amount < 100)  $errors[] = 'Minimum deposit is Rs 100.';
                if (!$chosen)       $errors[] = 'Please select a payment method.';

                // Upgrade mode → amount must exactly equal the price
                // difference between the current package and the target.
                $pendingPid = (int)($_SESSION['pending_upgrade_to'] ?? 0);
                if ($pendingPid > 0) {
                    $active = TaskPackage::activeForUser((int)$u['id']);
                    $target = TaskPackage::find($pendingPid);
                    if ($active && $target) {
                        $need = (float)$target['price'] - (float)$active['price_paid'];
                        if (abs($amount - $need) > 0.001) {
                            if ($amount < $need)  $errors[] = 'You must pay the full difference of ' . money($need) . ' to upgrade.';
                            else                  $errors[] = 'You only need to pay ' . money($need) . '.';
                        }
                    }
                }

                if (!$errors) {
                    $_SESSION['dep_wizard'] = [
                        'amount'    => $amount,
                        // Persist BOTH the id and the display name so step 2
                        // can always find the row even if admin later edits
                        // or renames the method.
                        'method_id' => (int)$chosen['id'],
                        'method'    => $chosen['name'],
                    ];
                    redirect('wallet/deposit', ['step' => 2]);
                }
            } elseif ($step === 2 && $action === 'next') {
                if (empty($wizard['amount']) || empty($wizard['method'])) {
                    redirect('wallet/deposit', ['step' => 1]);
                }
                redirect('wallet/deposit', ['step' => 3]);
            } elseif ($step === 3 && $action === 'submit') {
                if (empty($wizard['amount']) || empty($wizard['method'])) {
                    redirect('wallet/deposit', ['step' => 1]);
                }
                $txid   = trim($_POST['transaction_id'] ?? '');
                $sender = trim($_POST['sender_account'] ?? '');
                if (!$txid) $errors[] = 'Transaction ID is required.';

                // Handle screenshot upload (optional but recommended)
                $screenshotPath = null;
                if (!empty($_FILES['screenshot']['name']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                    $f = $_FILES['screenshot'];
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    $type = mime_content_type($f['tmp_name']) ?: '';
                    if (!isset($allowed[$type])) {
                        $errors[] = 'Screenshot must be a JPG, PNG or WEBP image.';
                    } elseif ($f['size'] > 5 * 1024 * 1024) {
                        $errors[] = 'Screenshot must be smaller than 5 MB.';
                    } else {
                        $dir = __DIR__ . '/../../public/uploads/deposits';
                        if (!is_dir($dir)) @mkdir($dir, 0755, true);
                        $name = 'd' . $u['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$type];
                        $target = $dir . '/' . $name;
                        if (move_uploaded_file($f['tmp_name'], $target)) {
                            $screenshotPath = 'uploads/deposits/' . $name;
                        } else {
                            $errors[] = 'Could not save screenshot. Please retry.';
                        }
                    }
                } elseif (!empty($_FILES['screenshot']['name'])) {
                    $errors[] = 'Screenshot upload failed. Please retry.';
                }

                if (!$errors) {
                    $depId = Deposit::create([
                        'user_id'        => (int)$u['id'],
                        'amount'         => (float)$wizard['amount'],
                        'method'         => $wizard['method'],
                        'transaction_id' => $txid,
                        'sender_account' => $sender,
                        'screenshot'     => $screenshotPath,
                        'envelope_used'  => 0.0,
                    ]);
                    unset($_SESSION['dep_wizard']);
                    flash_set('success', 'Deposit request submitted. Pending admin approval.');
                    redirect('wallet');
                }
            }
        }

        // Guard: step 2 / 3 without wizard data → back to step 1
        if ($step > 1 && (empty($wizard['amount']) || empty($wizard['method']))) {
            redirect('wallet/deposit', ['step' => 1]);
        }

        // v3: Red Envelope no longer discounts deposits — it credits the
        // wallet directly on CLAIM.  These variables stay for view
        // compatibility but are always zero for deposits.
        $envelopeClaim = null;
        $envelopeAmt   = 0.0;
        $envelopeName  = '';
        $payAmount     = (float)($wizard['amount'] ?? 0);

        // ---- Package upgrade context (session-driven from Packages page) ----
        $upgradeCtx = null;
        $pendingPid = (int)($_SESSION['pending_upgrade_to'] ?? 0);
        if ($pendingPid > 0) {
            $active = TaskPackage::activeForUser((int)$u['id']);
            $target = TaskPackage::find($pendingPid);
            if ($active && $target && (float)$target['price'] > (float)$active['price_paid']) {
                $diff = (float)$target['price'] - (float)$active['price_paid'];
                $upgradeCtx = [
                    'from_name'  => $active['pkg_name'] ?? 'Current',
                    'from_price' => (float)$active['price_paid'],
                    'to_name'    => (string)$target['name'],
                    'to_price'   => (float)$target['price'],
                    'diff'       => $diff,
                ];
                // Pre-fill the wizard amount with the exact difference so
                // step-1 shows the correct number immediately.
                if (empty($wizard['amount'])) {
                    $_SESSION['dep_wizard'] = $wizard = array_merge($wizard ?? [], ['amount' => $diff]);
                }
            } else {
                // No longer applicable → clear the intent
                unset($_SESSION['pending_upgrade_to']);
            }
        }

        // Look up the selected method for steps 2/3.  We prefer the stored
        // method_id (stable across renames / re-orders); fall back to a
        // case-insensitive name match for legacy wizard rows.
        $selected = null;
        $wizardId = (int)($wizard['method_id'] ?? 0);
        if ($wizardId > 0) {
            // Use ::find() so the method is found even if it was just
            // deactivated, but we still gate on "active" below for safety.
            $row = PaymentMethod::find($wizardId);
            if ($row && (int)$row['is_active'] === 1) {
                $selected = $row;
            }
        }
        if (!$selected && !empty($wizard['method'])) {
            foreach ($methods as $m) {
                if (strcasecmp($m['name'], $wizard['method']) === 0) {
                    $selected = $m;
                    // Heal the wizard so subsequent steps use the id.
                    $_SESSION['dep_wizard']['method_id'] = (int)$m['id'];
                    $_SESSION['dep_wizard']['method']    = $m['name'];
                    break;
                }
            }
        }

        $history = Deposit::forUser((int)$u['id']);
        view('user/deposit', compact('u','methods','errors','history','step','wizard','selected','envelopeClaim','envelopeAmt','envelopeName','payAmount','upgradeCtx'), 'app');
    }

    public function withdraw(): void {
        $u = require_user();
        // Always re-fetch the user so $u['balance'] reflects the latest value
        // (a fresh page load right after a prior withdrawal must show the
        // current balance, not the stale session copy).
        $fresh = User::find((int)$u['id']);
        if ($fresh) { $u = array_merge($u, $fresh); }

        $methods = PaymentMethod::active();

        // Fixed-slab withdrawals. The list of allowed slabs comes from the
        // user's active package (`min_withdrawal_ladder`) – or the system
        // default if they have no active package.
        //
        // NEW RULE (per product owner):  No per-tier lock, no ladder
        // progression. ANY slab can be withdrawn AS MANY TIMES AS the user
        // wants, the only restriction is that the user must have enough
        // withdrawable balance to cover the chosen slab amount.
        $ladderInfo = TaskPackage::withdrawalLadderFor((int)$u['id']);
        $slabs      = $ladderInfo['ladder'];          // ordered int[] e.g. [1500,7000,15000,...]
        $minSlab    = $slabs ? (float)$slabs[0] : 0.0;

        // Once-per-24-hours lock. Compute lockedUntil so the view can render
        // a live countdown + disable the fields.
        $lockRow = db()->prepare(
            'SELECT created_at FROM withdrawals WHERE user_id=? ORDER BY id DESC LIMIT 1'
        );
        $lockRow->execute([(int)$u['id']]);
        $lastAt = $lockRow->fetchColumn();
        $lockedUntilTs = 0;
        if ($lastAt) {
            $lockUntil = strtotime($lastAt) + 86400;
            if ($lockUntil > time()) $lockedUntilTs = $lockUntil;
        }
        $isLocked = $lockedUntilTs > 0;

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $amount = (float)($_POST['amount'] ?? 0);
            $method = trim($_POST['method'] ?? '');
            $accNum = trim($_POST['account_number'] ?? '');
            $accTit = trim($_POST['account_title'] ?? '');

            // Strict slab validation – the submitted amount MUST exactly match
            // one of the allowed slabs. Prevents tampered/typed amounts.
            $allowed = array_map('intval', $slabs);
            if (!in_array((int)$amount, $allowed, true)) {
                $errors[] = 'Please pick one of the allowed withdrawal amounts.';
            }
            // The ONLY balance rule: user must have at least the selected
            // amount in withdrawable balance. Same amount may be requested
            // again and again as long as this is true.
            if ($amount > (float)$u['balance']) {
                $errors[] = 'Insufficient balance for the selected amount.';
            }

            // Once-per-24-hours rule: users may submit at most ONE
            // withdrawal every 24 hours (rolling window).  Second attempt
            // is rejected with a friendly "try again in X hours Y minutes"
            // message so the user knows exactly when they can retry.
            if (!$errors) {
                $last = db()->prepare(
                    'SELECT created_at FROM withdrawals WHERE user_id=? ORDER BY id DESC LIMIT 1'
                );
                $last->execute([(int)$u['id']]);
                $lastAt = $last->fetchColumn();
                if ($lastAt) {
                    $unlockAt = strtotime($lastAt) + 86400;
                    $rem = $unlockAt - time();
                    if ($rem > 0) {
                        $h = floor($rem / 3600);
                        $m = floor(($rem % 3600) / 60);
                        $errors[] = 'You can only withdraw once per 24 hours. Please try again in '
                                  . ($h > 0 ? $h . ' hour' . ($h === 1 ? '' : 's') . ' ' : '')
                                  . $m . ' minute' . ($m === 1 ? '' : 's') . '.';
                    }
                }
            }
            if (!$method) $errors[] = 'Please select a payment method.';
            if (!$accNum || !$accTit) $errors[] = 'Account details are required.';

            if (!$errors) {
                // Reserve the amount from balance immediately (deducted on request)
                User::subtractBalance((int)$u['id'], $amount);
                Withdrawal::create([
                    'user_id'        => (int)$u['id'],
                    'amount'         => $amount,
                    'method'         => $method,
                    'account_number' => $accNum,
                    'account_title'  => $accTit,
                ]);
                Transaction::log((int)$u['id'], 'withdrawal', -$amount, "Withdrawal request – $method");
                flash_set('success', 'Withdrawal request submitted.');
                redirect('wallet');
            }
        }
        $history = Withdrawal::forUser((int)$u['id']);
        view('user/withdraw', compact('u','methods','errors','history','slabs','minSlab','lockedUntilTs','isLocked'), 'app');
    }
}
