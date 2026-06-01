<div class="admin-h">
  <h1>Overview</h1>
  <div class="small muted"><?= date('l, M d, Y') ?></div>
</div>

<div class="admin-grid" data-testid="admin-kpis">
  <div class="kpi"><div class="l">Total Users</div><div class="v"><?= (int)$stats['total_users'] ?></div></div>
  <div class="kpi"><div class="l">Total Deposits</div><div class="v"><?= money($stats['total_deposits']) ?></div></div>
  <div class="kpi"><div class="l">Today's Earnings</div><div class="v"><?= money($stats['today_earnings']) ?></div></div>
  <div class="kpi"><div class="l">Pending Withdrawals</div><div class="v"><?= (int)$stats['pending_wd'] ?></div></div>
</div>

<div class="card" style="margin-bottom:18px">
  <div class="flex between"><h3 style="margin:0">Daily Earnings (Last 7 days)</h3>
    <div class="small muted">Tasks + Referrals + Salary</div></div>
  <?php $max = max(1, max($chart)); ?>
  <div class="chart">
    <?php foreach ($chart as $d=>$v): $h = (int)max(6, ($v/$max)*140); ?>
      <div class="bar" style="height:<?= $h ?>px" title="<?= e($d) ?> · <?= money($v) ?>">
        <span><?= e(date('D', strtotime($d))) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="flex between" style="margin-bottom:10px"><h3 style="margin:0">Recent Pending Withdrawals</h3>
    <a class="small" style="color:var(--blue)" href="<?= route_url('admin/withdrawals') ?>">View all →</a></div>
  <table class="table" data-testid="admin-pending-wd">
    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$pendingWd): ?>
      <tr><td colspan="5" class="empty">No pending withdrawals</td></tr>
    <?php else: foreach (array_slice($pendingWd,0,8) as $w): ?>
      <tr>
        <td><?= e($w['name'] ?: $w['whatsapp']) ?></td>
        <td><?= money($w['amount']) ?></td>
        <td><?= e($w['method']) ?></td>
        <td><span class="badge pending">Pending</span></td>
        <td>
          <form method="post" action="<?= route_url('admin/withdrawals') ?>" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
            <button class="btn sm success" name="action" value="paid" data-testid="quick-paid-<?= (int)$w['id'] ?>">Mark Paid</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
