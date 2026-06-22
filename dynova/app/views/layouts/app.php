<?php
$current = $_GET['r'] ?? 'dashboard';
$flashes = flash_pull();
$u = current_user();

// Build an "alphabetic" badge from the user's name / whatsapp — used only as a
// decorative mobile header (no greeting text, no name display).
$src = $u ? trim($u['name'] ?: $u['whatsapp']) : '';
$initial = $src !== '' ? strtoupper(mb_substr($src, 0, 1, 'UTF-8')) : '·';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="theme-color" content="#04070f">
<title><?= e(setting('site_name', APP_NAME)) ?></title>
<meta name="description" content="DYNOVA NETWORK user dashboard – rate videos, track referrals, climb salary ranks and withdraw earnings.">
<link rel="icon" type="image/jpeg" sizes="any" href="<?= asset('img/logo.jpg') ?>">
<link rel="shortcut icon" type="image/jpeg" href="<?= asset('img/logo.jpg') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/logo.jpg') ?>">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link rel="stylesheet" href="<?= asset('css/desktop.css') ?>">
<link rel="stylesheet" href="<?= asset('css/extras.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Outfit:wght@400;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="user-panel">
<div class="bg-fx"></div>
<div class="bg-grid"></div>
<div class="bg-orbs"><span></span><span></span><span></span><span></span><span></span></div>

<!-- ============ Desktop sidebar (>=1024px) ============ -->
<aside class="nav-desktop" data-testid="desktop-sidenav">
  <a href="<?= route_url('dashboard') ?>" class="nd-brand">
    <img src="<?= asset('img/logo.jpg') ?>" alt="DYNOVA">
    <div><b>DYNOVA</b><span>NETWORK</span></div>
  </a>
  <a href="<?= route_url('dashboard') ?>" class="nd-link <?= $current==='dashboard'?'active':'' ?>" data-testid="dnav-home"><i class="fa-solid fa-house"></i> Dashboard</a>
  <a href="<?= route_url('tasks') ?>" class="nd-link <?= str_starts_with($current,'tasks')?'active':'' ?>" data-testid="dnav-tasks"><i class="fa-solid fa-star"></i> Tasks</a>
  <a href="<?= route_url('packages') ?>" class="nd-link <?= $current==='packages'?'active':'' ?>" data-testid="dnav-packages"><i class="fa-solid fa-box-open"></i> Packages</a>
  <a href="<?= route_url('ranks') ?>" class="nd-link <?= $current==='ranks'?'active':'' ?>" data-testid="dnav-ranks"><i class="fa-solid fa-medal"></i> Salary Ranks</a>
  <a href="<?= route_url('bonuses') ?>" class="nd-link <?= $current==='bonuses'?'active':'' ?>" data-testid="dnav-bonuses"><i class="fa-solid fa-gift"></i> Joining Bonus</a>
  <a href="<?= route_url('referrals') ?>" class="nd-link <?= $current==='referrals'?'active':'' ?>" data-testid="dnav-referrals"><i class="fa-solid fa-users"></i> Referrals</a>
  <a href="<?= route_url('wallet') ?>" class="nd-link <?= str_starts_with($current,'wallet')?'active':'' ?>" data-testid="dnav-wallet"><i class="fa-solid fa-wallet"></i> Wallet</a>
  <a href="<?= route_url('profile') ?>" class="nd-link <?= str_starts_with($current,'profile')?'active':'' ?>" data-testid="dnav-profile"><i class="fa-solid fa-user"></i> Profile</a>
  <div class="nd-footer">
    <a href="<?= route_url('auth/logout') ?>" data-testid="dnav-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
  </div>
</aside>

<!-- ============ Mobile alphabetic header (< 1024px) ============ -->
<header class="mob-header" data-testid="mobile-header">
  <a href="<?= route_url('dashboard') ?>" class="mob-mark" data-testid="mob-mark">
    <span class="mob-mark-glyph"><?= e($initial) ?></span>
    <span class="mob-mark-dot"></span>
  </a>
  <a href="<?= route_url('dashboard') ?>" class="mob-brand" data-testid="mob-brand">
    <img src="<?= asset('img/logo.jpg') ?>" alt="DYNOVA">
    <span class="mob-brand-name">DYNOVA</span>
  </a>
  <a href="<?= route_url('profile') ?>" class="mob-bell" data-testid="mob-bell">
    <i class="fa-solid fa-bell"></i>
    <span class="dot"></span>
  </a>
</header>

<div class="shell page-anim">
<?php foreach ($flashes as $f): ?>
  <div class="alert <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
<?= $content ?>
</div>

<!-- ============ Mobile bottom nav (< 1024px) ============ -->
<nav class="nav-bottom" data-testid="bottom-nav">
  <a href="<?= route_url('dashboard') ?>" class="<?= $current==='dashboard'?'active':'' ?>" data-testid="nav-home"><i class="fa-solid fa-house"></i>Home</a>
  <a href="<?= route_url('tasks') ?>" class="<?= str_starts_with($current,'tasks')?'active':'' ?>" data-testid="nav-tasks"><i class="fa-solid fa-star"></i>Tasks</a>
  <a href="<?= route_url('packages') ?>" class="<?= $current==='packages'?'active':'' ?>" data-testid="nav-packages"><i class="fa-solid fa-box-open"></i>Plans</a>
  <a href="<?= route_url('ranks') ?>" class="<?= $current==='ranks'?'active':'' ?>" data-testid="nav-salary"><i class="fa-solid fa-medal"></i>Salary</a>
  <button type="button" class="nav-more-btn <?= in_array($current, ['wallet','wallet/deposit','wallet/withdraw','profile','profile/password','referrals','bonuses'], true) ? 'active' : '' ?>"
          id="navMoreBtn" data-testid="nav-more" aria-haspopup="true" aria-expanded="false">
    <i class="fa-solid fa-ellipsis"></i>More
  </button>
</nav>

<!-- ============ Drop-up "More" sheet (mobile) ============ -->
<div class="more-backdrop" id="moreBackdrop" data-testid="more-backdrop" aria-hidden="true"></div>
<div class="more-sheet" id="moreSheet" data-testid="more-sheet" role="dialog" aria-label="More menu" aria-hidden="true">
  <div class="more-sheet-handle"></div>
  <div class="more-sheet-head">
    <div>
      <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Quick menu</div>
      <h3 style="margin:2px 0 0;font-size:18px">More options</h3>
    </div>
    <button type="button" class="more-close" id="moreClose" data-testid="more-close" aria-label="Close menu">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  <div class="more-grid">
    <a href="<?= route_url('wallet') ?>" class="more-item <?= str_starts_with($current,'wallet')?'is-active':'' ?>" data-testid="more-wallet">
      <span class="mi-icon" style="background:rgba(61,220,151,.14);color:var(--green,#3ddc97)"><i class="fa-solid fa-wallet"></i></span>
      <span class="mi-label">Wallet</span>
    </a>
    <a href="<?= route_url('wallet/deposit') ?>" class="more-item" data-testid="more-deposit">
      <span class="mi-icon" style="background:rgba(62,182,255,.14);color:var(--blue,#3eb6ff)"><i class="fa-solid fa-circle-down"></i></span>
      <span class="mi-label">Deposit</span>
    </a>
    <a href="<?= route_url('wallet/withdraw') ?>" class="more-item" data-testid="more-withdraw">
      <span class="mi-icon" style="background:rgba(141,91,255,.14);color:var(--violet,#8d5bff)"><i class="fa-solid fa-circle-up"></i></span>
      <span class="mi-label">Withdraw</span>
    </a>
    <a href="<?= route_url('referrals') ?>" class="more-item <?= $current==='referrals'?'is-active':'' ?>" data-testid="more-referrals">
      <span class="mi-icon" style="background:rgba(91,240,255,.14);color:var(--cyan,#5bf0ff)"><i class="fa-solid fa-users"></i></span>
      <span class="mi-label">Referrals</span>
    </a>
    <a href="<?= route_url('bonuses') ?>" class="more-item <?= $current==='bonuses'?'is-active':'' ?>" data-testid="more-bonuses">
      <span class="mi-icon" style="background:rgba(16,185,129,.14);color:#10b981"><i class="fa-solid fa-gift"></i></span>
      <span class="mi-label">Joining Bonus</span>
    </a>
    <a href="<?= route_url('profile') ?>" class="more-item <?= str_starts_with($current,'profile')?'is-active':'' ?>" data-testid="more-profile">
      <span class="mi-icon" style="background:rgba(255,181,71,.14);color:var(--amber,#ffb547)"><i class="fa-solid fa-user"></i></span>
      <span class="mi-label">Profile</span>
    </a>
    <a href="<?= route_url('profile/password') ?>" class="more-item" data-testid="more-password">
      <span class="mi-icon" style="background:rgba(255,255,255,.07);color:var(--txt,#fff)"><i class="fa-solid fa-lock"></i></span>
      <span class="mi-label">Password</span>
    </a>
    <a href="<?= route_url('auth/logout') ?>" class="more-item more-item-danger" data-testid="more-logout">
      <span class="mi-icon" style="background:rgba(255,91,106,.14);color:var(--red,#ff5b6a)"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
      <span class="mi-label">Logout</span>
    </a>
  </div>
</div>

<div class="copy-toast" id="copyToast">Copied to clipboard</div>

<?php
// =========================================================
// Maintenance banner — visible only when the dev-only
// "System Lock" toggle is ON.
// =========================================================
if (setting('lock_user_actions') === '1' && current_user()):
?>
<div class="sys-lock-banner" data-testid="sys-lock-banner" role="status">
  <i class="fa-solid fa-triangle-exclamation"></i>
  <span><b>Platform under maintenance.</b> Deposits, withdrawals, tasks and other actions are temporarily paused. You can still view your dashboard.</span>
</div>
<style>
.sys-lock-banner{
  position:fixed; left:50%; bottom:14px; transform:translateX(-50%);
  z-index:999; max-width:calc(100vw - 24px);
  display:flex; align-items:center; gap:10px;
  padding:10px 16px; border-radius:999px;
  background:linear-gradient(120deg, rgba(255,91,106,.94), rgba(255,128,80,.94));
  color:#fff; font-size:12.5px; line-height:1.45; font-weight:600;
  box-shadow:0 14px 38px -10px rgba(255,91,106,.55);
  border:1px solid rgba(255,255,255,.18);
}
.sys-lock-banner i{ font-size:14px; flex-shrink:0; }
@media (max-width:520px){
  .sys-lock-banner{ font-size:11.5px; padding:8px 14px; bottom:10px; }
}
</style>
<?php endif; ?>

<?php
// =========================================================
// Site-wide popup announcement (admin → Developer → Popups)
// =========================================================
$__popup = class_exists('Popup') ? Popup::activeForView() : null;
if ($__popup):
    $__pkey = 'dnv_popup_' . (int)$__popup['id'];
?>
<div class="dnv-popup-backdrop" id="dnvPopupBackdrop"
     data-pop-key="<?= e($__pkey) ?>"
     data-testid="dnv-popup" hidden>
  <div class="dnv-popup" role="dialog" aria-modal="true" aria-labelledby="dnvPopupTitle">
    <button type="button" class="dnv-popup-x" id="dnvPopupClose" aria-label="Close"
            data-testid="dnv-popup-close">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <?php if ($__popup['type'] === 'image' && !empty($__popup['image_path'])): ?>
      <div class="dnv-popup-image">
        <img src="<?= asset(e($__popup['image_path'])) ?>"
             alt="<?= e($__popup['title'] ?: 'Announcement') ?>"
             data-testid="dnv-popup-image">
      </div>
    <?php endif; ?>

    <?php if (!empty($__popup['title']) || (!empty($__popup['message']) && $__popup['type'] === 'text')): ?>
      <div class="dnv-popup-body">
        <?php if (!empty($__popup['title'])): ?>
          <h3 id="dnvPopupTitle" class="dnv-popup-title" data-testid="dnv-popup-title">
            <?= e($__popup['title']) ?>
          </h3>
        <?php endif; ?>
        <?php if ($__popup['type'] === 'text' && !empty($__popup['message'])): ?>
          <p class="dnv-popup-msg" data-testid="dnv-popup-message"><?= nl2br(e($__popup['message'])) ?></p>
        <?php endif; ?>
        <div class="dnv-popup-actions">
          <button type="button" class="dnv-popup-cta" id="dnvPopupOk" data-testid="dnv-popup-ok">Got it</button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
.dnv-popup-backdrop{
  position:fixed; inset:0; z-index:9999;
  display:flex; align-items:center; justify-content:center;
  padding:20px;
  background:rgba(4,7,15,.78);
  backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
  animation:dnvFadeIn .22s ease forwards;
}
.dnv-popup-backdrop[hidden]{display:none;}
.dnv-popup{
  position:relative; width:100%; max-width:440px;
  max-height:calc(100dvh - 40px); overflow:auto;
  border-radius:22px;
  background:linear-gradient(160deg, #15193c 0%, #0a0d22 60%, #08091a 100%);
  border:1px solid rgba(141,91,255,.32);
  box-shadow:0 30px 80px -20px rgba(141,91,255,.40),
             0 0 0 1px rgba(255,255,255,.04) inset;
  animation:dnvPop .26s cubic-bezier(.18,.85,.32,1.18) forwards;
}
.dnv-popup-x{
  position:absolute; top:10px; right:10px; z-index:2;
  width:34px; height:34px; border-radius:50%;
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.16);
  color:#fff; font-size:14px; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:background .15s ease, transform .15s ease;
}
.dnv-popup-x:hover{ background:rgba(255,91,106,.22); transform:rotate(90deg); }
.dnv-popup-image{ width:100%; background:#000; }
.dnv-popup-image img{ display:block; width:100%; height:auto; max-height:60vh; object-fit:cover; }
.dnv-popup-body{ padding:22px 22px 20px; }
.dnv-popup-title{
  margin:0 0 10px; font-size:20px; line-height:1.25; font-weight:800;
  color:#fff; padding-right:40px;
  background:linear-gradient(90deg,#3eb6ff,#8d5bff);
  -webkit-background-clip:text; background-clip:text; color:transparent;
}
.dnv-popup-msg{
  margin:0; color:#cbd5e1; font-size:14.5px; line-height:1.62;
  white-space:pre-wrap; word-break:break-word;
}
.dnv-popup-actions{ display:flex; justify-content:flex-end; margin-top:18px; }
.dnv-popup-cta{
  appearance:none; border:0; cursor:pointer;
  padding:10px 22px; border-radius:999px;
  font-weight:700; font-size:13.5px; color:#fff; letter-spacing:.3px;
  background:linear-gradient(120deg,#3eb6ff 0%,#8d5bff 100%);
  box-shadow:0 12px 26px -10px rgba(141,91,255,.7);
  transition:transform .12s ease, box-shadow .15s ease;
}
.dnv-popup-cta:hover{ transform:translateY(-1px); box-shadow:0 16px 30px -10px rgba(141,91,255,.85); }

/* Image-only popup (no body section): the close button needs more contrast */
.dnv-popup:not(:has(.dnv-popup-body)) .dnv-popup-x{
  background:rgba(0,0,0,.55); border-color:rgba(255,255,255,.30);
}

@keyframes dnvFadeIn{ from{opacity:0} to{opacity:1} }
@keyframes dnvPop{
  from{ transform:translateY(14px) scale(.96); opacity:0; }
  to  { transform:translateY(0)    scale(1);   opacity:1; }
}

/* Mobile tweaks */
@media (max-width:520px){
  .dnv-popup-backdrop{ padding:12px; }
  .dnv-popup{ max-width:100%; border-radius:18px; }
  .dnv-popup-body{ padding:18px 18px 16px; }
  .dnv-popup-title{ font-size:18px; padding-right:36px; }
  .dnv-popup-msg{ font-size:13.5px; }
  .dnv-popup-image img{ max-height:50vh; }
}
</style>

<script>
(function(){
  var bd   = document.getElementById('dnvPopupBackdrop');
  if (!bd) return;
  var key  = bd.getAttribute('data-pop-key') || 'dnv_popup';
  // Respect a per-session dismissal so the same popup isn't shown on every
  // page navigation; admin can roll out a new popup by saving with a new id.
  try { if (sessionStorage.getItem(key) === '1') return; } catch (e) {}

  bd.hidden = false;
  document.documentElement.style.overflow = 'hidden';

  function close(){
    bd.hidden = true;
    document.documentElement.style.overflow = '';
    try { sessionStorage.setItem(key, '1'); } catch (e) {}
  }
  document.getElementById('dnvPopupClose').addEventListener('click', close);
  var ok = document.getElementById('dnvPopupOk');
  if (ok) ok.addEventListener('click', close);
  bd.addEventListener('click', function(e){ if (e.target === bd) close(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !bd.hidden) close(); });
})();
</script>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
