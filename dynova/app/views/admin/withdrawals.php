<div class="admin-h"><h1>Withdrawals</h1></div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 12px">Pending (<?= count($pending) ?>)</h3>
  <table class="table">
    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Account</th><th>Title</th><th>When</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$pending): ?><tr><td colspan="7" class="empty">No pending withdrawals</td></tr>
    <?php else: foreach ($pending as $w): ?>
      <tr>
        <td><?= e($w['name'] ?: $w['whatsapp']) ?></td>
        <td><?= money($w['amount']) ?></td>
        <td><?= e($w['method']) ?></td>
        <td><code><?= e($w['account_number']) ?></code></td>
        <td><?= e($w['account_title']) ?></td>
        <td><?= e(date('M d, H:i', strtotime($w['created_at']))) ?></td>
        <td>
          <form method="post" style="display:inline-flex;gap:6px">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
            <button class="btn sm success" name="action" value="paid" data-testid="mark-paid-<?= (int)$w['id'] ?>">Mark Paid</button>
            <button class="btn sm danger" name="action" value="reject" data-testid="reject-wd-<?= (int)$w['id'] ?>">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">All Withdrawals (last 200)</h3>
  <table class="table">
    <thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Method</th><th>Account</th><th>Status</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($all as $w): ?>
      <tr>
        <td>#<?= (int)$w['id'] ?></td>
        <td><?= e($w['name'] ?: $w['whatsapp']) ?></td>
        <td><?= money($w['amount']) ?></td>
        <td><?= e($w['method']) ?></td>
        <td><code><?= e($w['account_number']) ?></code></td>
        <td><span class="badge <?= e($w['status']) ?>"><?= e($w['status']) ?></span></td>
        <td><?= e(date('M d, H:i', strtotime($w['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
