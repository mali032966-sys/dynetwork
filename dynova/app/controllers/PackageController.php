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
                $res = TaskPackage::upgrade((int) $u['id'], $pid);
                if ($res['ok']) {
                    $msg = 'Package upgraded! You paid ' . money($res['cost']) . ' (difference).';
                    if (!empty($res['discount']) && $res['discount'] > 0) {
                        $msg .= ' 🧧 Red Envelope discount: ' . money($res['discount']) . '.';
                    }
                    flash_set('success', $msg);
                } else {
                    flash_set('error', $res['error'] ?? 'Upgrade failed.');
                }
                redirect('packages');
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
