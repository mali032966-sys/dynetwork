<?php
class PackageController {
    public function index(): void {
        $u = require_user();
        $packages = TaskPackage::active();
        $active   = TaskPackage::activeForUser((int) $u['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'activate') {
                $pid = (int) ($_POST['package_id'] ?? 0);
                $res = TaskPackage::activate((int) $u['id'], $pid);
                if ($res['ok']) {
                    flash_set('success', 'Package activated! Welcome to a new tier.');
                } else {
                    flash_set('error', $res['error'] ?? 'Activation failed.');
                }
                redirect('packages');
            }
        }

        view('user/packages', compact('u', 'packages', 'active'), 'app');
    }
}
