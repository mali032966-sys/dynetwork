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
// ------------------------------------------------------------------
// 🧧 Red Envelope card
//   Fixed mode  → shows "up to Rs XXX off" (max configured amount).
//   Random mode → user opens the envelope to reveal a surprise amount
//                 that is saved in their session and applied to the
//                 next package activation / upgrade.
// ------------------------------------------------------------------
$reOn      = red_envelope_enabled();
$reMode    = red_envelope_mode();
$reMax     = red_envelope_max_discount();
$rePicked  = (float)($_SESSION['red_envelope_picked'] ?? 0);
if ($reOn && ($reMax > 0 || $rePicked > 0)):
?>
<div class="card red-env-card stagger" data-testid="red-envelope-card"
     data-mode="<?= e($reMode) ?>"
     data-picked="<?= $rePicked > 0 ? '1' : '0' ?>">
  <div class="red-env-inner">
    <!-- Closed envelope (SVG) -->
    <div class="red-env-visual" id="redEnvVisual">
      <svg viewBox="0 0 120 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <defs>
          <linearGradient id="reBody" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0"   stop-color="#ff5b6a"/>
            <stop offset="1"   stop-color="#c81f34"/>
          </linearGradient>
          <linearGradient id="reFlap" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0"   stop-color="#ff8a95"/>
            <stop offset="1"   stop-color="#e13040"/>
          </linearGradient>
        </defs>
        <rect x="4" y="18" width="112" height="66" rx="10" fill="url(#reBody)"/>
        <polygon class="re-flap" points="4,18 60,58 116,18" fill="url(#reFlap)"/>
        <circle cx="60" cy="52" r="12" fill="#ffd54a" stroke="#b47b09" stroke-width="1.5"/>
        <text x="60" y="57" text-anchor="middle" font-size="12" font-weight="900" fill="#8a5b00">福</text>
      </svg>
    </div>

    <div class="red-env-body">
      <div class="red-env-kicker">🧧 Red Envelope</div>
      <?php if ($rePicked > 0): ?>
        <div class="red-env-title" data-testid="re-picked-amount">
          You have <span class="glow"><?= money($rePicked) ?></span> off your next package!
        </div>
        <div class="red-env-sub">Applied automatically at checkout. Head over to the Packages page to use it.</div>
        <a href="<?= route_url('packages') ?>" class="btn red-env-btn" data-testid="re-go-packages">
          <i class="fa-solid fa-box-open"></i> Use it now
        </a>
      <?php elseif ($reMode === 'random'): ?>
        <div class="red-env-title">You've got a surprise waiting</div>
        <div class="red-env-sub">Tap the envelope to reveal your discount on package activation.</div>
        <form method="post" action="<?= route_url('packages') ?>" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="open_envelope">
          <button type="submit" class="btn red-env-btn" id="redEnvOpenBtn" data-testid="re-open-btn">
            <i class="fa-solid fa-gift"></i> Open Envelope
          </button>
        </form>
      <?php else: ?>
        <div class="red-env-title">
          Get up to <span class="glow"><?= money($reMax) ?></span> off
        </div>
        <div class="red-env-sub">Automatic discount on package activation &amp; upgrades — no code needed.</div>
        <a href="<?= route_url('packages') ?>" class="btn red-env-btn" data-testid="re-go-packages">
          <i class="fa-solid fa-box-open"></i> See packages
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.red-env-card{
  padding:0; overflow:hidden;
  background:linear-gradient(135deg, rgba(255,91,106,.14), rgba(200,31,52,.06));
  border:1px solid rgba(255,91,106,.35);
}
.red-env-inner{ display:flex; gap:14px; align-items:center; padding:16px; }
.red-env-visual{ width:96px; flex-shrink:0; filter:drop-shadow(0 10px 20px rgba(255,91,106,.35)); }
.red-env-visual svg{ width:100%; height:auto; display:block; }
.red-env-visual .re-flap{
  transform-origin: 60px 18px;
  transition: transform .55s cubic-bezier(.6,-.2,.3,1.4);
}
.red-env-card.opening .red-env-visual .re-flap{ transform: rotateX(180deg); }
.red-env-card.opening .red-env-visual{ animation:reShake .5s ease; }
@keyframes reShake{
  0%,100%{transform:translateY(0)}
  20%{transform:translateY(-3px) rotate(-2deg)}
  40%{transform:translateY(2px) rotate(1deg)}
  60%{transform:translateY(-2px) rotate(-1deg)}
  80%{transform:translateY(1px) rotate(1deg)}
}
.red-env-body{ flex:1; min-width:0; }
.red-env-kicker{ font-size:11px; letter-spacing:1.2px; text-transform:uppercase; color:#ff8a95; font-weight:700; }
.red-env-title{ font-size:16px; font-weight:800; color:#fff; margin-top:4px; line-height:1.3; }
.red-env-title .glow{
  background:linear-gradient(90deg,#ffd54a,#ff8a95);
  -webkit-background-clip:text; background-clip:text; color:transparent;
  padding:0 2px;
}
.red-env-sub{ font-size:12.5px; color:var(--txt-mute,#94a3b8); margin:4px 0 10px; line-height:1.5; }
.red-env-btn{
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 16px; font-size:13px; font-weight:700; border-radius:999px;
  background:linear-gradient(120deg,#ff5b6a,#c81f34);
  color:#fff; border:0; cursor:pointer; text-decoration:none;
  box-shadow:0 10px 22px -10px rgba(255,91,106,.65);
  transition:transform .12s ease, box-shadow .15s ease;
}
.red-env-btn:hover{ transform:translateY(-1px); box-shadow:0 14px 26px -10px rgba(255,91,106,.8); }
@media (max-width:420px){
  .red-env-visual{ width:72px; }
  .red-env-title{ font-size:14.5px; }
}
</style>

<script>
(function(){
  var card = document.querySelector('[data-testid="red-envelope-card"]');
  if (!card) return;
  var mode   = card.getAttribute('data-mode');
  var picked = card.getAttribute('data-picked') === '1';
  if (mode !== 'random' || picked) return;
  var btn = document.getElementById('redEnvOpenBtn');
  var vis = document.getElementById('redEnvVisual');
  if (!btn || !vis) return;
  // Also allow tapping the envelope itself to submit the form
  vis.style.cursor = 'pointer';
  vis.addEventListener('click', function(){ btn.click(); });
  btn.addEventListener('click', function(){
    card.classList.add('opening');
    // let the animation play; form submits normally afterwards
  });
})();
</script>
<?php endif; ?>

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
