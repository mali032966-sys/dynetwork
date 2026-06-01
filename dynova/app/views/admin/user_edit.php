<div class="admin-h">
  <h1>User #<?= (int)$u['id'] ?> — <?= e($u['name'] ?: $u['whatsapp']) ?></h1>
  <a class="btn ghost inline" href="<?= route_url('admin/users') ?>">← Back</a>
</div>

<div class="admin-grid">
  <div class="kpi"><div class="l">Balance</div><div class="v"><?= money($u['balance']) ?></div></div>
  <div class="kpi"><div class="l">Task Earnings</div><div class="v"><?= money($u['task_earnings']) ?></div></div>
  <div class="kpi"><div class="l">Referral Earnings</div><div class="v"><?= money($u['referral_earnings']) ?></div></div>
  <div class="kpi"><div class="l">Total Deposits</div><div class="v"><?= money($u['deposit_total']) ?></div></div>
</div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 12px">Quick Actions</h3>
  <div class="flex" style="flex-wrap:wrap;gap:10px">
    <form method="post" action="<?= route_url('admin/users/edit') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
      <?php if ((int)$u['is_blocked']===1): ?>
        <button class="btn success inline" name="action" value="unblock" data-testid="unblock-user">Unblock</button>
      <?php else: ?>
        <button class="btn danger inline" name="action" value="block" data-testid="block-user">Block</button>
      <?php endif; ?>
    </form>
    <form method="post" action="<?= route_url('admin/users/edit') ?>" class="flex" style="gap:8px">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
      <input type="hidden" name="action" value="adjust">
      <input class="input" type="number" step="0.01" name="amount" placeholder="Amount (- to subtract)" required data-testid="adjust-amount">
      <input class="input" type="text" name="note" placeholder="Note (optional)" data-testid="adjust-note">
      <button class="btn inline" type="submit" data-testid="adjust-balance">Adjust Balance</button>
    </form>
  </div>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">Recent Transactions</h3>
  <table class="table">
    <thead><tr><th>Type</th><th>Amount</th><th>Meta</th><th>When</th></tr></thead>
    <tbody>
    <?php if (!$tx): ?><tr><td colspan="4" class="empty">No transactions</td></tr>
    <?php else: foreach ($tx as $r): ?>
      <tr><td><?= e($r['type']) ?></td>
        <td style="color:<?= $r['amount']<0?'var(--red)':'var(--green)' ?>"><?= ($r['amount']>=0?'+':'') . money($r['amount']) ?></td>
        <td><?= e($r['meta']) ?></td>
        <td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
