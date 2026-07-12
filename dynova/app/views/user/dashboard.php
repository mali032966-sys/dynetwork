<div class="topbar">
  <div class="page-head">
    <h2 class="page-title" data-testid="dash-title">Dashboard</h2>
    <div class="page-sub">Your earnings at a glance</div>
  </div>
  <a href="<?= route_url('profile') ?>" class="bell" data-testid="dash-bell">
    <i class="fa-solid fa-bell"></i><span class="dot"></span>
  </a>
</div>

<?php
// ------------------------------------------------------------------
// 🧧 Red Envelope — coupon CSS + burst/popup JS.
//   The visible coupon markup is rendered later inside the balance
//   /coupon grid (.dash-hero) so it sits beside the Total Balance
//   card on desktop and stacks below it on mobile.
// ------------------------------------------------------------------
$reOn    = red_envelope_enabled();
$reClaim = $reOn ? RedEnvelope::activeClaim((int)$u['id']) : null;
$reReady = $reClaim !== null;
if ($reOn):
?>

<style>
/* ============ Coupon / voucher — red style ============ */
.coupon-wrap{ margin: 0 0 16px; }
.coupon{
  position:relative; display:flex; align-items:stretch;
  background:linear-gradient(135deg,#e73443 0%, #b81528 100%);
  border-radius:14px;
  box-shadow:
     0 18px 40px -18px rgba(231,52,67,.7),
     0 0 0 1px rgba(255,255,255,.08) inset,
     0 0 0 3px rgba(255,255,255,.05) inset;
  overflow:hidden; min-height:104px;
}
.coupon-stub{
  width:52px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(0,0,0,.10));
}
.coupon-stub-text{
  writing-mode:vertical-rl; transform:rotate(180deg);
  color:#fff; font-weight:900; letter-spacing:8px; font-size:13px;
  text-shadow:0 1px 0 rgba(0,0,0,.15);
}
.coupon-dashed{
  width:0; border-left:2px dashed rgba(255,255,255,.55);
  margin: 12px 0;
}
.coupon-body{
  flex:1; min-width:0; padding:12px 20px 12px 16px;
  display:flex; flex-direction:column; justify-content:center; gap:4px;
}
.coupon-kicker{
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.6px;
  color:rgba(255,255,255,.85);
}
.coupon-headline{
  font-size:11px; font-weight:700; letter-spacing:2px;
  color:rgba(255,255,255,.75);
  margin-top:1px;
}
.coupon-save{
  font-size:22px; font-weight:900; color:#fff;
  line-height:1.1; letter-spacing:.3px;
  text-shadow:0 2px 12px rgba(0,0,0,.25);
}
.coupon-save .coupon-amt{
  background:linear-gradient(90deg,#ffd54a,#fff);
  -webkit-background-clip:text; background-clip:text; color:transparent;
}
.coupon-note{
  font-size:11.5px; color:rgba(255,255,255,.78);
  margin-top:2px; margin-bottom:6px;
}
.coupon-btn{
  display:inline-flex; align-items:center; gap:8px;
  align-self:flex-start;
  padding:9px 22px; border-radius:999px;
  background:#fff; color:#c81f34; border:0; cursor:pointer;
  font-weight:900; font-size:13px; letter-spacing:1.5px; text-decoration:none;
  box-shadow:0 8px 22px -6px rgba(255,255,255,.55), 0 0 0 1px rgba(200,31,52,.10);
  transition:transform .12s ease, box-shadow .15s ease;
}
.coupon-btn:hover{ transform:translateY(-1px) scale(1.02); box-shadow:0 12px 26px -6px rgba(255,255,255,.7); }
.coupon-btn i{ font-size:12px; }

/* Notched edges = ticket look */
.coupon-notches{
  position:absolute; top:0; bottom:0; width:6px;
  display:flex; flex-direction:column; justify-content:space-around;
  padding:6px 0;
}
.coupon-notches.left{  left: 52px; transform:translateX(-3px); }
.coupon-notches.right{ right:0;   transform:translateX(3px); }
.coupon-notches span{
  width:10px; height:10px; border-radius:50%;
  background:var(--bg, #0a0d22);
}

/* Mobile */
@media (max-width:520px){
  .coupon{ min-height:96px; border-radius:12px; }
  .coupon-stub{ width:44px; }
  .coupon-stub-text{ letter-spacing:6px; font-size:11.5px; }
  .coupon-body{ padding:11px 16px 11px 14px; }
  .coupon-save{ font-size:19px; }
  .coupon-btn{ padding:8px 18px; font-size:12.5px; }
  .coupon-notches.left{ left:44px; }
}

/* Burst animation */
.coupon-wrap.bursting .coupon{ animation:reBounce .55s ease; }
@keyframes reBounce{
  0%{ transform:scale(1); }
  30%{ transform:scale(1.05) rotate(-1deg); }
  60%{ transform:scale(.98) rotate(1deg); }
  100%{ transform:scale(1); }
}
.coupon-burst{
  position:fixed; inset:0; z-index:10000; pointer-events:none;
  overflow:hidden;
}
.coupon-burst span{
  position:absolute; top:50%; left:50%;
  width:12px; height:12px; border-radius:50%;
  opacity:0;
  animation: reFly 1.1s cubic-bezier(.15,.8,.35,1) forwards;
}
@keyframes reFly{
  0%   { transform: translate(0,0) scale(.2); opacity:0; }
  15%  { opacity:1; }
  100% { transform: translate(var(--dx), var(--dy)) scale(1); opacity:0; }
}

/* Popup */
.re-popup-backdrop{
  position:fixed; inset:0; z-index:10001;
  display:flex; align-items:center; justify-content:center; padding:20px;
  background:rgba(4,7,15,.78); backdrop-filter:blur(8px);
  animation:reFadeIn .22s ease forwards;
}
.re-popup-backdrop[hidden]{ display:none; }
.re-popup{
  position:relative; width:100%; max-width:420px; text-align:center;
  padding:26px 22px 22px; border-radius:22px;
  background:linear-gradient(160deg,#22112a,#0a0d22 80%);
  border:1px solid rgba(255,91,106,.35);
  box-shadow:0 30px 80px -20px rgba(255,91,106,.55);
  animation:rePop .28s cubic-bezier(.18,.85,.32,1.18) forwards;
}
.re-popup-x{
  position:absolute; top:10px; right:10px;
  width:32px; height:32px; border-radius:50%;
  background:rgba(255,255,255,.10); border:0; color:#fff; cursor:pointer;
  display:grid; place-items:center; font-size:13px;
}
.re-popup .celebrate{
  font-size:44px; line-height:1; margin-bottom:8px;
  filter:drop-shadow(0 8px 16px rgba(255,181,71,.35));
  animation:reWobble 1.3s ease-in-out infinite;
}
.re-popup h3{
  margin:2px 0 6px; font-size:20px; font-weight:800;
  background:linear-gradient(90deg,#ffd54a,#ff8a95);
  -webkit-background-clip:text; background-clip:text; color:transparent;
}
.re-popup .re-amount{
  display:inline-block; padding:12px 22px; border-radius:14px;
  background:linear-gradient(120deg,rgba(255,91,106,.15),rgba(255,181,71,.10));
  border:1px dashed rgba(255,181,71,.45);
  font-size:28px; font-weight:900; color:#fff; letter-spacing:.5px;
  margin:8px 0 12px;
}
.re-popup p{ margin:6px 0; font-size:13.5px; color:#cbd5e1; line-height:1.55; }
.re-popup .re-cta{
  display:inline-flex; align-items:center; gap:8px; margin-top:14px;
  padding:11px 24px; border-radius:999px; font-weight:800; font-size:14px;
  background:linear-gradient(120deg,#3eb6ff,#8d5bff); color:#fff;
  text-decoration:none; border:0; cursor:pointer;
  box-shadow:0 12px 26px -10px rgba(141,91,255,.7);
  transition:transform .12s ease;
}
.re-popup .re-cta:hover{ transform:translateY(-1px); }
@keyframes reFadeIn{ from{opacity:0} to{opacity:1} }
@keyframes rePop{
  from{ transform:translateY(14px) scale(.96); opacity:0; }
  to  { transform:translateY(0) scale(1);      opacity:1; }
}
@keyframes reWobble{
  0%,100%{ transform:rotate(-6deg); }
  50%    { transform:rotate(6deg);  }
}
</style>

<?php if ($reReady && empty($_SESSION['re_popup_seen'])):
    $_SESSION['re_popup_seen'] = 1;
    // Show the range on the popup (or a single amount if only one is configured)
    $popupLbl = red_envelope_min_amount() > 0 && red_envelope_min_amount() !== red_envelope_max_amount()
        ? 'Rs ' . number_format(red_envelope_min_amount()) . ' – Rs ' . number_format(red_envelope_max_amount())
        : 'Rs ' . number_format(red_envelope_max_amount());
?>
<!-- Fresh-claim popup, rendered ONCE per session right after claiming -->
<div class="re-popup-backdrop" id="rePopup" data-testid="re-popup">
  <div class="re-popup" role="dialog">
    <button type="button" class="re-popup-x" data-close data-testid="re-popup-close" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="celebrate">🎉</div>
    <h3>Congratulations! You've unlocked a discount!</h3>
    <div class="re-amount" data-testid="re-popup-amount"><?= e($popupLbl) ?></div>
    <p>Your discount will be calculated based on your <b>deposit amount</b>.</p>
    <p style="opacity:.75;margin-top:10px">
      Each package tier has its own discount. When you deposit, we'll automatically apply the discount that matches the amount.
      Your wallet will still receive the <b>full deposit amount</b>.
    </p>
    <a href="<?= route_url('wallet/deposit') ?>" class="re-cta" data-testid="re-popup-deposit-cta">
      <i class="fa-solid fa-wallet"></i> Proceed to Deposit
    </a>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  var wrap = document.querySelector('.coupon-wrap[data-state="unclaimed"]');
  if (wrap){
    var btn = wrap.querySelector('#reClaimBtn');
    if (btn){
      btn.addEventListener('click', function(ev){
        // Play the burst animation THEN allow the form to submit.
        ev.preventDefault();
        wrap.classList.add('bursting');
        launchBurst();
        setTimeout(function(){ btn.closest('form').submit(); }, 620);
      });
    }
  }
  function launchBurst(){
    var host = document.createElement('div');
    host.className = 'coupon-burst';
    var colors = ['#ffd54a','#ff5b6a','#3eb6ff','#8d5bff','#10b981','#ff8a95','#fff'];
    var emojis = ['🎉','✨','💰','🧧','⭐'];
    for (var i=0;i<38;i++){
      var s = document.createElement('span');
      var angle = Math.random()*Math.PI*2;
      var dist  = 140 + Math.random()*220;
      s.style.setProperty('--dx', (Math.cos(angle)*dist).toFixed(1)+'px');
      s.style.setProperty('--dy', (Math.sin(angle)*dist).toFixed(1)+'px');
      s.style.animationDelay = (Math.random()*.10)+'s';
      if (Math.random() < .35) {
        s.textContent = emojis[i % emojis.length];
        s.style.width='auto'; s.style.height='auto';
        s.style.fontSize=(16+Math.random()*10)+'px';
        s.style.background='transparent';
      } else {
        s.style.background = colors[i % colors.length];
      }
      host.appendChild(s);
    }
    document.body.appendChild(host);
    setTimeout(function(){ host.remove(); }, 1400);
  }
  // Popup dismiss
  var pop = document.getElementById('rePopup');
  if (pop){
    function closePop(){ pop.hidden = true; pop.remove(); }
    pop.querySelector('[data-close]').addEventListener('click', closePop);
    pop.addEventListener('click', function(e){ if (e.target === pop) closePop(); });
    document.addEventListener('keydown', function(e){ if (e.key==='Escape') closePop(); });
  }
})();
</script>
<?php endif; ?>

<?php
// CTA banner for users who haven't activated any package yet — they can't
// earn from tasks, team, or salary until they do.
$activePkg = TaskPackage::activeForUser((int)$u['id']);
?>
<?php if (!$activePkg): ?>
  <div class="card stagger pkg-cta-card" data-testid="dash-no-pkg-cta">
    <div class="pkg-cta-icon"><i class="fa-solid fa-rocket"></i></div>
    <div class="pkg-cta-body">
      <div class="pkg-cta-title">Activate a package to start earning</div>
      <div class="pkg-cta-desc">Tasks, team commissions, joining bonuses and monthly salary all unlock the moment you activate any package.</div>
    </div>
    <a href="<?= route_url('packages') ?>" class="btn pkg-cta-btn" data-testid="dash-pkg-cta-btn">
      <i class="fa-solid fa-box-open"></i> Choose a Plan
    </a>
  </div>
<?php endif; ?>

<?php
// Legacy v1 red-envelope block removed — the new coupon-style card
// above handles the entire feature. Left this comment so anyone diffing
// against the previous release can see the swap point.
?>

<?php
// ------------------------------------------------------------------
// 🧧 Red Envelope — coupon voucher (v2.2 dynamic per-package).
//   Coupon shows a RANGE of possible savings.  The exact discount is
//   determined at deposit time based on which package price the user
//   deposits for (e.g. Rs 10,000 → Rs 700, Rs 15,000 → Rs 1,500).
// ------------------------------------------------------------------
$reOn       = red_envelope_enabled();
$reClaim    = $reOn ? RedEnvelope::activeClaim((int)$u['id']) : null;
$reEver     = $reOn ? RedEnvelope::hasEverClaimed((int)$u['id']) : false;
$reMax      = $reOn ? red_envelope_max_amount() : 0.0;
$reMin      = $reOn ? red_envelope_min_amount() : 0.0;
$reCanClaim = $reOn && !$reEver && $reMax > 0;
$reReady    = $reClaim !== null;
if ($reOn && ($reCanClaim || $reReady)):
    // Copy strings — differ for the "range" preview vs the post-claim state.
    if ($reReady) {
        $reHeadline = 'DISCOUNT UNLOCKED';
        $reAmountLbl = ($reMin > 0 && $reMin !== $reMax)
            ? 'Rs ' . number_format($reMin) . ' – Rs ' . number_format($reMax)
            : 'Rs ' . number_format($reMax);
        $reNote  = 'Discount is calculated from your deposit amount and applied automatically.';
    } else {
        $reHeadline = 'DISCOUNT VOUCHER';
        $reAmountLbl = ($reMin > 0 && $reMin !== $reMax)
            ? 'Rs ' . number_format($reMin) . ' – Rs ' . number_format($reMax) . ' OFF!'
            : 'Rs ' . number_format($reMax) . ' OFF!';
        $reNote  = 'The exact discount depends on your deposit amount.';
    }
?>
<div class="dash-hero" data-testid="dash-hero-grid">
  <div class="card balance-card stagger" data-testid="balance-card">
    <div class="balance-label">Total Balance</div>
    <div class="balance-amount" data-testid="balance-amount"><?= money($u['balance']) ?></div>
  <?php if ($todayEarnings > 0): ?>
    <div class="balance-trend">+ <?= money($todayEarnings) ?> today</div>
  <?php else: ?>
    <div class="balance-trend" style="color:var(--txt-mute)">Start earning today</div>
  <?php endif; ?>
  <span class="pkr-badge"><i class="fa-solid fa-bolt"></i> PKR Wallet · <?= e($u['referral_code']) ?></span>
  </div>

  <!-- Coupon voucher — sits beside the balance card on desktop -->
  <div class="coupon-wrap" data-testid="red-envelope-card"
       data-state="<?= $reReady ? 'ready' : 'unclaimed' ?>"
       data-range-min="<?= (int)$reMin ?>"
       data-range-max="<?= (int)$reMax ?>">
    <div class="coupon">
      <div class="coupon-stub"><span class="coupon-stub-text">COUPON</span></div>
      <div class="coupon-dashed"></div>
      <div class="coupon-body">
        <div class="coupon-kicker"><i class="fa-solid fa-gift"></i> Red Envelope</div>
        <div class="coupon-headline"><?= e($reHeadline) ?></div>
        <div class="coupon-save"><span class="coupon-amt"><?= e($reAmountLbl) ?></span></div>
        <div class="coupon-note"><?= e($reNote) ?></div>
        <?php if ($reReady): ?>
          <a href="<?= route_url('wallet/deposit') ?>" class="coupon-btn" data-testid="re-proceed-deposit">
            Proceed to Deposit <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php else: ?>
          <form method="post" action="<?= route_url('wallet/red-envelope-claim') ?>" style="display:contents">
            <?= csrf_field() ?>
            <button type="submit" class="coupon-btn" id="reClaimBtn" data-testid="re-claim-btn">
              CLAIM <i class="fa-solid fa-hand-pointer"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
      <div class="coupon-notches left">
        <?php for ($i=0;$i<8;$i++) echo '<span></span>'; ?>
      </div>
      <div class="coupon-notches right">
        <?php for ($i=0;$i<8;$i++) echo '<span></span>'; ?>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card balance-card stagger" data-testid="balance-card">
  <div class="balance-label">Total Balance</div>
  <div class="balance-amount" data-testid="balance-amount"><?= money($u['balance']) ?></div>
  <?php if ($todayEarnings > 0): ?>
    <div class="balance-trend">+ <?= money($todayEarnings) ?> today</div>
  <?php else: ?>
    <div class="balance-trend" style="color:var(--txt-mute)">Start earning today</div>
  <?php endif; ?>
  <span class="pkr-badge"><i class="fa-solid fa-bolt"></i> PKR Wallet · <?= e($u['referral_code']) ?></span>
</div>
<?php endif; ?>

<style>
/* Balance + Coupon side-by-side on desktop, stacked on mobile */
.dash-hero{
  display:grid; gap:14px; margin:0 0 16px;
  grid-template-columns:minmax(0, 1.05fr) minmax(0, .95fr);
  align-items:stretch;
}
.dash-hero .balance-card{ margin:0; }
.dash-hero .coupon-wrap { margin:0; height:100%; display:flex; }
.dash-hero .coupon-wrap .coupon{ width:100%; }
@media (max-width:760px){
  .dash-hero{ grid-template-columns:1fr; }
}
</style>

<!-- Referral link share card -->
<div class="card ref-link-card stagger" data-testid="dash-referral-card">
  <div class="ref-link-head">
    <div class="ref-link-icon"><i class="fa-solid fa-link"></i></div>
    <div>
      <div class="ref-link-title">Your referral link</div>
      <div class="ref-link-sub">Invite friends and earn on every join + 3 levels of team activity.</div>
    </div>
  </div>
  <div class="ref-link-row">
    <input type="text" class="ref-link-input" id="dashRefLink" readonly
           value="<?= e($referralLink) ?>" data-testid="dash-ref-input">
    <button type="button" class="ref-link-btn copy-btn" data-copy="#dashRefLink"
            data-testid="dash-ref-copy" aria-label="Copy referral link">
      <i class="fa-solid fa-copy"></i>
      <span>Copy</span>
    </button>
  </div>
  <div class="ref-link-foot">
    <span><i class="fa-solid fa-hashtag"></i> Code: <b><?= e($u['referral_code']) ?></b></span>
    <a href="<?= route_url('referrals') ?>" data-testid="dash-ref-more">See referrals →</a>
  </div>
</div>

<div class="stat-grid stagger">
  <div class="stat" data-testid="stat-today"><div class="ico"><i class="fa-solid fa-coins"></i></div>
    <div class="lbl">Today's Earnings</div><div class="val"><?= money($todayEarnings) ?></div></div>
  <div class="stat v" data-testid="stat-team"><div class="ico"><i class="fa-solid fa-users"></i></div>
    <div class="lbl">Total Team</div><div class="val"><?= (int)$teamCount ?></div></div>
  <div class="stat p" data-testid="stat-pending"><div class="ico"><i class="fa-solid fa-hourglass-half"></i></div>
    <div class="lbl">Pending Withdrawal</div><div class="val"><?= money($pendingWd) ?></div></div>
  <div class="stat g" data-testid="stat-tasks"><div class="ico"><i class="fa-solid fa-star"></i></div>
    <div class="lbl">Tasks Completed</div><div class="val"><?= (int)$completedToday ?>/<?= (int)$dailyLimit ?></div></div>
</div>

<div class="actions stagger">
  <a href="<?= route_url('wallet/deposit') ?>" class="action" data-testid="action-deposit">
    <span class="ico"><i class="fa-solid fa-circle-down"></i></span>
    <h4>Deposit Funds</h4><small>JazzCash / EasyPesa</small>
  </a>
  <a href="<?= route_url('wallet/withdraw') ?>" class="action violet" data-testid="action-withdraw">
    <span class="ico"><i class="fa-solid fa-circle-up"></i></span>
    <h4>Withdraw Funds</h4><small>Min: <?= money(setting('min_withdrawal', DEFAULT_MIN_WITHDRAWAL)) ?></small>
  </a>
</div>

<div class="list-title">
  <h3>Recent Activity</h3>
  <a href="<?= route_url('wallet') ?>">See all →</a>
</div>
<div class="card" data-testid="recent-activity">
  <?php if (!$recent): ?>
    <div class="empty">No activity yet. Complete a task to see your earnings here.</div>
  <?php else: foreach ($recent as $r): ?>
    <div class="activity <?= e($r['type']) ?>">
      <div class="ico"><i class="fa-solid fa-<?=
        $r['type']==='deposit'?'circle-down':
        ($r['type']==='task'?'star':
        ($r['type']==='referral'?'users':
        ($r['type']==='salary'?'medal':
        ($r['type']==='withdrawal'?'circle-up':'gear')))) ?>"></i></div>
      <div class="meta">
        <b><?= e(ucfirst($r['type'])) ?></b>
        <small><?= e(date('M d, H:i', strtotime($r['created_at']))) ?> · <?= e($r['meta']) ?></small>
      </div>
      <div class="amt <?= $r['amount']<0?'minus':'' ?>"><?= ($r['amount']>=0?'+':'') . money($r['amount']) ?></div>
    </div>
  <?php endforeach; endif; ?>
</div>
