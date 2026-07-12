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
                        // 🧧 Red Envelope: attach the currently-active claim
                        //    so admin approval credits the FULL amount but
                        //    the user only paid (amount - envelope_used).
                        'envelope_used'  => (float)($wizard['envelope'] ?? 0),
                    ]);
                    // Close the claim now so the user cannot double-spend it
                    // on multiple simultaneous deposit requests.
                    $claimId = (int)($wizard['envelope_claim_id'] ?? 0);
                    if ($claimId > 0 && !empty($wizard['envelope'])) {
                        RedEnvelope::markUsed($claimId, $depId);
                    }
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

        // 🧧 Red Envelope — v2.2: the discount is looked up FRESH at
        //    deposit time based on the deposit amount matching a
        //    package's list price.  The claim table is used only as an
        //    eligibility flag (one-time-per-user unlock).
        $envelopeClaim = RedEnvelope::activeClaim((int)$u['id']);
        $envelopeAmt   = 0.0;
        $envelopeName  = '';
        if ($envelopeClaim && !empty($wizard['amount'])) {
            [$envelopeAmt, $envelopeName] = red_envelope_discount_for_amount((float)$wizard['amount']);
            $_SESSION['dep_wizard']['envelope']          = $envelopeAmt;
            $_SESSION['dep_wizard']['envelope_claim_id'] = (int)$envelopeClaim['id'];
            $wizard = $_SESSION['dep_wizard'];
        }
        // Effective amount user actually pays for this deposit.
        $payAmount = max(0.0, (float)($wizard['amount'] ?? 0) - $envelopeAmt);

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
        view('user/deposit', compact('u','methods','errors','history','step','wizard','selected','envelopeClaim','envelopeAmt','envelopeName','payAmount'), 'app');
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

            // Once-per-day rule: users may only submit ONE withdrawal
            // request per calendar day (server time).  Prevents spam and
            // gives operators a clear 24-hour cadence.
            if (!$errors) {
                $todayCountStmt = db()->prepare(
                    'SELECT COUNT(*) FROM withdrawals
                      WHERE user_id = ? AND DATE(created_at) = CURDATE()'
                );
                $todayCountStmt->execute([(int)$u['id']]);
                if ((int)$todayCountStmt->fetchColumn() > 0) {
                    $errors[] = 'You have already submitted a withdrawal today. Please try again tomorrow.';
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
        view('user/withdraw', compact('u','methods','errors','history','slabs','minSlab'), 'app');
    }
}
