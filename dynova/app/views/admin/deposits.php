<div class="admin-h"><h1>Deposits</h1></div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 12px">Pending (<?= count($pending) ?>)</h3>
  <table class="table" data-testid="admin-pending-deposits">
    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>TID</th><th>Proof</th><th>When</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$pending): ?><tr><td colspan="7" class="empty">No pending deposits</td></tr>
    <?php else: foreach ($pending as $d): ?>
      <tr>
        <td><?= e($d['name'] ?: $d['whatsapp']) ?></td>
        <td><?= money($d['amount']) ?></td>
        <td><?= e($d['method']) ?></td>
        <td><code><?= e($d['transaction_id']) ?></code></td>
        <td>
          <?php if (!empty($d['screenshot'])): ?>
            <a href="<?= url($d['screenshot']) ?>" target="_blank" data-testid="deposit-ss-<?= (int)$d['id'] ?>">
              <i class="fa-solid fa-image" style="color:var(--cyan)"></i> View
            </a>
          <?php else: ?>
            <span class="muted small">—</span>
          <?php endif; ?>
        </td>
        <td><?= e(date('M d, H:i', strtotime($d['created_at']))) ?></td>
        <td>
          <form method="post" style="display:inline-flex;gap:6px">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
            <button class="btn sm success" name="action" value="approve" data-testid="approve-deposit-<?= (int)$d['id'] ?>">Approve</button>
            <button class="btn sm danger" name="action" value="reject" data-testid="reject-deposit-<?= (int)$d['id'] ?>">Reject</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">Recent (last 200)</h3>
  <table class="table">
    <thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Method</th><th>TID</th><th>Status</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($all as $d): ?>
      <tr>
        <td>#<?= (int)$d['id'] ?></td>
        <td><?= e($d['name'] ?: $d['whatsapp']) ?></td>
        <td><?= money($d['amount']) ?></td>
        <td><?= e($d['method']) ?></td>
        <td><code><?= e($d['transaction_id']) ?></code></td>
        <td><span class="badge <?= e($d['status']) ?>"><?= e($d['status']) ?></span></td>
        <td><?= e(date('M d, H:i', strtotime($d['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
