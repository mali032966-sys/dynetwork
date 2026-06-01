<div class="topbar">
  <div class="greet"><b>My Wallet</b><div class="small muted">Transactions &amp; balance</div></div>
  <a href="<?= route_url('dashboard') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<div class="card balance-card stagger" data-testid="wallet-balance">
  <div class="balance-label">Available Balance</div>
  <div class="balance-amount"><?= money($u['balance']) ?></div>
  <div class="balance-trend" style="color:var(--amber)">Pending withdrawal: <?= money($pending) ?></div>
  <span class="pkr-badge"><i class="fa-solid fa-shield-halved"></i> Secured Wallet</span>
</div>

<div class="actions stagger">
  <a href="<?= route_url('wallet/deposit') ?>" class="action" data-testid="wallet-deposit">
    <span class="ico"><i class="fa-solid fa-circle-down"></i></span>
    <h4>Deposit</h4><small>JazzCash / EasyPesa</small>
  </a>
  <a href="<?= route_url('wallet/withdraw') ?>" class="action violet" data-testid="wallet-withdraw">
    <span class="ico"><i class="fa-solid fa-circle-up"></i></span>
    <h4>Withdraw</h4><small>Min: <?= money(setting('min_withdrawal', DEFAULT_MIN_WITHDRAWAL)) ?></small>
  </a>
</div>

<div class="list-title"><h3>Transaction History</h3></div>
<div class="card" data-testid="tx-list">
  <?php if (!$tx): ?>
    <div class="empty">No transactions yet.</div>
  <?php else: foreach ($tx as $r): ?>
    <div class="activity <?= e($r['type']) ?>">
      <div class="ico"><i class="fa-solid fa-<?=
        $r['type']==='deposit'?'circle-down':
        ($r['type']==='task'?'star':
        ($r['type']==='referral'?'users':
        ($r['type']==='salary'?'medal':
        ($r['type']==='withdrawal'?'circle-up':'gear')))) ?>"></i></div>
      <div class="meta">
        <b><?= e(ucfirst($r['type'])) ?></b>
        <small><?= e(date('M d, Y · H:i', strtotime($r['created_at']))) ?> · <?= e($r['meta']) ?></small>
      </div>
      <div class="amt <?= $r['amount']<0?'minus':'' ?>"><?= ($r['amount']>=0?'+':'') . money($r['amount']) ?></div>
    </div>
  <?php endforeach; endif; ?>
</div>
