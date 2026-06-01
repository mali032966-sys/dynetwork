<div class="topbar">
  <div class="greet"><b>Profile</b><div class="small muted">Your account</div></div>
  <a href="<?= route_url('dashboard') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<div class="card" style="padding:0" data-testid="profile-card">
  <div class="prof-head">
    <div class="pic"><?= e(strtoupper(substr($u['name'] ?: $u['whatsapp'], 0, 2))) ?></div>
    <div>
      <b><?= e($u['name'] ?: 'Member') ?></b>
      <small><?= e($u['whatsapp']) ?></small>
      <?php if ($rank): ?>
        <div class="rank-badge"><?= e($rank['emoji']) ?> <?= e($rank['name']) ?> Member</div>
      <?php else: ?>
        <div class="small muted" style="margin-top:4px">Rank: build your team to unlock salary ranks</div>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= route_url('profile/password') ?>" class="menu-row" data-testid="menu-password">
    <div class="mi"><i class="fa-solid fa-key"></i></div>
    <div><b>Change Password</b><small>Update your account password</small></div>
    <i class="fa-solid fa-chevron-right chev"></i>
  </a>
  <a href="<?= route_url('referrals') ?>" class="menu-row" data-testid="menu-referral">
    <div class="mi"><i class="fa-solid fa-users"></i></div>
    <div><b>Referral Teams</b><small><?= e($u['referral_code']) ?> · L1 referrals: <?= (int)$refCount1 ?></small></div>
    <i class="fa-solid fa-chevron-right chev"></i>
  </a>
  <a href="<?= route_url('wallet') ?>" class="menu-row" data-testid="menu-wallet">
    <div class="mi"><i class="fa-solid fa-wallet"></i></div>
    <div><b>Wallet</b><small>Balance: <?= money($u['balance']) ?> · Team business: <?= money($business) ?></small></div>
    <i class="fa-solid fa-chevron-right chev"></i>
  </a>
  <a href="<?= route_url('auth/logout') ?>" class="menu-row" data-testid="menu-logout">
    <div class="mi" style="background:rgba(255,91,106,.12);color:var(--red)"><i class="fa-solid fa-right-from-bracket"></i></div>
    <div><b>Logout</b><small>Sign out of your account</small></div>
    <i class="fa-solid fa-chevron-right chev"></i>
  </a>
</div>
