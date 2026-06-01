<?php $flashes = flash_pull(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#04070f">
<title><?= e(setting('site_name', APP_NAME)) ?> – Welcome</title>
<meta name="description" content="Sign in or create your DYNOVA NETWORK account – rate videos, refer friends, earn in PKR.">
<link rel="icon" type="image/jpeg" sizes="any" href="<?= asset('img/logo.jpg') ?>">
<link rel="shortcut icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="bg-fx"></div>
<div class="bg-grid"></div>
<div class="bg-orbs"><span></span><span></span><span></span><span></span><span></span></div>
<div class="auth-wrap page-anim">
  <div class="brand">
    <div class="logo"><img src="<?= asset('img/logo.jpg') ?>" alt="Logo"></div>
    <div class="name">DYNOVA</div>
    <div class="sub">N E T W O R K</div>
  </div>
  <?php foreach ($flashes as $f): ?>
    <div class="alert <?= e($f['type']) ?>" style="width:100%;margin-top:18px"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
