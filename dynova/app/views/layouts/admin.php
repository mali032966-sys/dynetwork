<?php
$current = $_GET['r'] ?? 'admin/dashboard';
$flashes = flash_pull();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#04070f">
<title><?= e(setting('site_name', APP_NAME)) ?> – Admin</title>
<meta name="description" content="DYNOVA NETWORK administration console.">
<link rel="icon" type="image/jpeg" sizes="any" href="<?= asset('img/logo.jpg') ?>">
<link rel="shortcut icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/extras.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="bg-fx"></div>
<div class="bg-grid"></div>
<div class="shell admin">
  <div class="admin-shell">
    <aside class="admin-side" data-testid="admin-sidebar">
      <div class="brand" style="margin-bottom:14px">
        <div class="logo" style="width:54px;height:54px;border-radius:14px"><img src="<?= asset('img/logo.jpg') ?>" alt=""></div>
        <div class="name" style="font-size:18px;letter-spacing:3px;margin-top:6px">DYNOVA</div>
        <div class="sub" style="font-size:9px;letter-spacing:5px">A D M I N</div>
      </div>
      <a href="<?= route_url('admin/dashboard') ?>" class="<?= $current==='admin/dashboard'||$current==='admin'?'active':'' ?>" data-testid="admin-nav-dashboard"><i class="fa-solid fa-grid-2"></i> Overview</a>
      <a href="<?= route_url('admin/users') ?>" class="<?= str_starts_with($current,'admin/users')?'active':'' ?>" data-testid="admin-nav-users"><i class="fa-solid fa-users"></i> Users</a>
      <a href="<?= route_url('admin/deposits') ?>" class="<?= $current==='admin/deposits'?'active':'' ?>" data-testid="admin-nav-deposits"><i class="fa-solid fa-money-bill-trend-up"></i> Deposits</a>
      <a href="<?= route_url('admin/withdrawals') ?>" class="<?= $current==='admin/withdrawals'?'active':'' ?>" data-testid="admin-nav-withdrawals"><i class="fa-solid fa-money-bill-transfer"></i> Withdrawals</a>
      <a href="<?= route_url('admin/referrals') ?>" class="<?= $current==='admin/referrals'?'active':'' ?>" data-testid="admin-nav-referrals"><i class="fa-solid fa-sitemap"></i> Referrals</a>
      <a href="<?= route_url('admin/transactions') ?>" class="<?= $current==='admin/transactions'?'active':'' ?>" data-testid="admin-nav-tx"><i class="fa-solid fa-list"></i> Transactions</a>
      <a href="<?= route_url('admin/settings') ?>" class="<?= $current==='admin/settings'?'active':'' ?>" data-testid="admin-nav-settings"><i class="fa-solid fa-gear"></i> Settings</a>
      <?php $devSection = in_array($current, ['admin/developer','admin/packages','admin/ranks','admin/tasks','admin/bonuses','admin/dev-unlock'], true); ?>
      <a href="<?= route_url('admin/developer') ?>" class="dev-nav-link <?= $devSection ? 'active' : '' ?>" data-testid="admin-nav-developer">
        <i class="fa-solid fa-shield-halved"></i> Developer
        <?php if (function_exists('dev_unlocked') && dev_unlocked()): ?>
          <span class="dev-nav-dot" title="Developer unlock active"></span>
        <?php endif; ?>
      </a>
      <a href="<?= route_url('admin/logout') ?>" data-testid="admin-nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </aside>
    <main class="admin-main">
      <?php foreach ($flashes as $f): ?>
        <div class="alert <?= e($f['type']) ?>" data-testid="admin-flash"><?= e($f['msg']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
