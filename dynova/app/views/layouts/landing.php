<?php $flashes = flash_pull(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#04070f">
<title><?= e(setting('site_name', APP_NAME)) ?> – Rate. Earn. Refer.</title>
<meta name="description" content="DYNOVA NETWORK – Pakistan's premier video-rating earning platform. Rate sponsored videos in minutes, build a 3-level referral team, climb salary ranks and withdraw to JazzCash or EasyPaisa in PKR.">
<meta name="keywords" content="DYNOVA NETWORK, dynova, video rating, earn money online Pakistan, JazzCash, EasyPaisa, referral earning, MLM, PKR, task earning platform">
<meta property="og:title" content="DYNOVA NETWORK – Rate. Earn. Refer.">
<meta property="og:description" content="Rate videos, refer friends across 3 levels, climb ranks and earn monthly salary in PKR.">
<meta property="og:type" content="website">
<link rel="icon" type="image/jpeg" sizes="any" href="<?= asset('img/logo.jpg') ?>">
<link rel="shortcut icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/landing.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="landing-body">
<div class="bg-fx"></div>
<div class="bg-grid"></div>
<div class="bg-orbs"><span></span><span></span><span></span><span></span><span></span></div>

<header class="lp-nav" id="lpNav">
  <a href="<?= route_url('home') ?>" class="lp-brand">
    <img src="<?= asset('img/logo.jpg') ?>" alt="DYNOVA">
    <div>
      <b>DYNOVA</b>
      <span>NETWORK</span>
    </div>
  </a>
  <nav class="lp-nav-links">
    <a href="#how">How it works</a>
    <a href="#earn">Earnings</a>
    <a href="#ranks">Ranks</a>
    <a href="#payments">Payments</a>
    <a href="#faq">FAQ</a>
  </nav>
  <div class="lp-nav-cta">
    <a href="<?= route_url('auth/login') ?>" class="btn ghost inline" data-testid="lp-nav-login">Login</a>
    <a href="<?= route_url('auth/signup') ?>" class="btn inline" data-testid="lp-nav-signup">Get Started</a>
  </div>
  <button class="lp-burger" id="lpBurger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
</header>

<?php foreach ($flashes as $f): ?>
  <div class="lp-toast <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>

<?= $content ?>

<footer class="lp-footer">
  <div class="lp-container">
    <div class="lp-foot-grid">
      <div>
        <a href="<?= route_url('home') ?>" class="lp-brand">
          <img src="<?= asset('img/logo.jpg') ?>" alt="DYNOVA">
          <div><b>DYNOVA</b><span>NETWORK</span></div>
        </a>
        <p class="lp-foot-tag">Rate. Earn. Refer. <br>Your premier earning network.</p>
      </div>
      <div>
        <h5>Product</h5>
        <a href="#how">How it works</a>
        <a href="#earn">Earning streams</a>
        <a href="#ranks">Salary ranks</a>
      </div>
      <div>
        <h5>Account</h5>
        <a href="<?= route_url('auth/signup') ?>">Create account</a>
        <a href="<?= route_url('auth/login') ?>">Login</a>
        <a href="<?= route_url('admin/login') ?>">Admin panel</a>
      </div>
      <div>
        <h5>Connect</h5>
        <a href="#"><i class="fa-brands fa-whatsapp"></i> Support</a>
        <a href="#"><i class="fa-brands fa-facebook"></i> Facebook</a>
        <a href="#"><i class="fa-brands fa-instagram"></i> Instagram</a>
      </div>
    </div>
    <div class="lp-foot-base">
      <small>© <?= date('Y') ?> <?= e(setting('site_name', APP_NAME)) ?>. All rights reserved.</small>
      <small>Built with care.</small>
    </div>
  </div>
</footer>

<script src="<?= asset('js/landing.js') ?>"></script>
</body>
</html>
