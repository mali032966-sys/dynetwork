<?php
class PackageController {
    public function index(): void {
        $u = require_user();
        // Make sure the upgrade-history columns exist before we query the page
        TaskPackage::ensureUpgradeColumns();

        $packages = TaskPackage::active();
        $active   = TaskPackage::activeForUser((int) $u['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'activate') {
                $pid = (int) ($_POST['package_id'] ?? 0);
                $res = TaskPackage::activate((int) $u['id'], $pid);
                if ($res['ok']) {
                    $msg = 'Package activated! Welcome to a new tier.';
                    if (!empty($res['discount']) && $res['discount'] > 0) {
                        $msg .= ' 🧧 Red Envelope saved you ' . money($res['discount']) . '!';
                    }
                    flash_set('success', $msg);
                } else {
                    flash_set('error', $res['error'] ?? 'Activation failed.');
                }
                redirect('packages');
            }

            if ($action === 'upgrade') {
                $pid = (int) ($_POST['package_id'] ?? 0);
                // NEW FLOW: don't run the upgrade here. Divert the user
                // to the Deposit page with the pre-computed price
                // difference so they can top-up their wallet first.
                // The actual upgrade is auto-run when the deposit is
                // approved (see WalletController + Admin approval).
                $active = TaskPackage::activeForUser((int)$u['id']);
                $target = TaskPackage::find($pid);
                if (!$active || !$target) {
                    flash_set('error', 'Upgrade not available right now.');
                    redirect('packages');
                }
                if ((float)$target['price'] <= (float)$active['price_paid']) {
                    flash_set('error', 'You can only upgrade to a higher-priced package.');
                    redirect('packages');
                }
                $need = (float)$target['price'] - (float)$active['price_paid'];
                // If the user already has enough balance, run the upgrade immediately.
                if ((float)$u['balance'] >= $need) {
                    $res = TaskPackage::upgrade((int)$u['id'], $pid);
                    if ($res['ok']) {
                        unset($_SESSION['pending_upgrade_to']);
                        flash_set('success', 'Package upgraded! You paid ' . money($res['cost']) . '.');
                    } else {
                        flash_set('error',   $res['error'] ?? 'Upgrade failed.');
                    }
                    redirect('packages');
                }
                // Otherwise route to the Deposit page with the intent
                // stored in the session (see WalletController::deposit).
                $_SESSION['pending_upgrade_to'] = $pid;
                flash_set('success', 'Deposit ' . money($need) . ' to complete your upgrade to ' . $target['name'] . '.');
                redirect('wallet/deposit');
            }

            if ($action === 'open_envelope') {
                // Random-mode: user is opening the envelope. Pick an amount
                // and stash it in the session so it applies to the next
                // activation / upgrade.
                if (red_envelope_enabled() && red_envelope_mode() === 'random') {
                    $picked = red_envelope_pick_random();
                    if ($picked > 0) {
                        flash_set('success', '🧧 You unwrapped ' . money($picked) . ' off your next package!');
                    } else {
                        flash_set('error', 'No discounts configured. Please contact support.');
                    }
                }
                redirect('packages');
            }
        }

        view('user/packages', compact('u', 'packages', 'active'), 'app');
    }
}
