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
                $amount = (float)($_POST['amount'] ?? 0);
                $method = trim($_POST['method'] ?? '');
                if ($amount < 100)  $errors[] = 'Minimum deposit is Rs 100.';
                if (!$method)       $errors[] = 'Please select a payment method.';
                if (!$errors) {
                    $_SESSION['dep_wizard'] = [
                        'amount' => $amount,
                        'method' => $method,
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
                    Deposit::create([
                        'user_id'        => (int)$u['id'],
                        'amount'         => (float)$wizard['amount'],
                        'method'         => $wizard['method'],
                        'transaction_id' => $txid,
                        'sender_account' => $sender,
                        'screenshot'     => $screenshotPath,
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

        // Look up the selected method for steps 2/3
        $selected = null;
        if (!empty($wizard['method'])) {
            foreach ($methods as $m) {
                if ($m['name'] === $wizard['method']) { $selected = $m; break; }
            }
        }

        $history = Deposit::forUser((int)$u['id']);
        view('user/deposit', compact('u','methods','errors','history','step','wizard','selected'), 'app');
    }

    public function withdraw(): void {
        $u = require_user();
        $methods = PaymentMethod::active();

        // Fixed-slab withdrawals. The list of allowed slabs comes from the
        // user's active package (`min_withdrawal_ladder`) – or the system
        // default if they have no active package. Users pick exactly one
        // slab per request via one-click cards on the form, every time.
        $ladderInfo = TaskPackage::withdrawalLadderFor((int)$u['id']);
        $slabs      = $ladderInfo['ladder'];          // ordered int[] e.g. [1500,7000,15000,...]
        $minSlab    = $slabs ? (float)$slabs[0] : 0.0; // absolute floor for any withdrawal

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
            if ($amount > (float)$u['balance']) {
                $errors[] = 'Insufficient balance for the selected amount.';
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
