<div class="admin-h">
  <h1>Transactions</h1>
  <form method="get" class="flex" style="gap:8px">
    <input type="hidden" name="r" value="admin/transactions">
    <select class="input" name="type">
      <option value="">All types</option>
      <?php foreach ($allowed as $t): ?>
        <option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn inline" type="submit">Filter</button>
  </form>
</div>

<div class="card">
  <table class="table" data-testid="admin-tx-table">
    <thead><tr><th>ID</th><th>User</th><th>Type</th><th>Amount</th><th>Meta</th><th>When</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="6" class="empty">No transactions</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td>#<?= (int)$r['id'] ?></td>
        <td><?= e($r['name'] ?: $r['whatsapp']) ?></td>
        <td><span class="badge active"><?= e($r['type']) ?></span></td>
        <td style="color:<?= $r['amount']<0?'var(--red)':'var(--green)' ?>"><?= ($r['amount']>=0?'+':'') . money($r['amount']) ?></td>
        <td><?= e($r['meta']) ?></td>
        <td><?= e(date('M d, H:i', strtotime($r['created_at']))) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
