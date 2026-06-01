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
