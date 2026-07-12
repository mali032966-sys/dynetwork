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

<!-- 🧧 Red Envelope status + admin controls -->
<div class="card" style="margin-top:18px" data-testid="user-envelope-card">
  <h3 style="margin:0 0 10px">
    <i class="fa-solid fa-gift" style="color:#ff5b6a"></i> Red Envelope
  </h3>
  <?php $reClaim = $reClaim ?? null; $reHistory = $reHistory ?? []; ?>
  <?php if ($reClaim): ?>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:10px 14px;
                background:linear-gradient(120deg,rgba(255,91,106,.14),rgba(255,181,71,.06));
                border:1px solid rgba(255,91,106,.35);border-radius:12px;margin-bottom:12px">
      <div>
        <div class="small muted">Active claim (unused)</div>
        <div style="font-size:20px;font-weight:800;color:#ffd54a" data-testid="user-re-active-amount">
          <?= money($reClaim['amount']) ?>
        </div>
      </div>
      <div class="small muted" style="flex:1;min-width:180px">
        Claimed on <?= e(date('M d, Y H:i', strtotime($reClaim['claimed_at']))) ?>. Will be applied to the next approved deposit.
      </div>
    </div>
  <?php else: ?>
    <div class="empty" style="margin-bottom:10px">No active envelope for this user.</div>
  <?php endif; ?>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
    <form method="post" style="display:inline">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
      <button class="btn sm" name="action" value="re_grant" data-testid="user-re-grant">
        <i class="fa-solid fa-plus"></i> Issue new envelope
      </button>
    </form>
    <?php if ($reClaim): ?>
      <form method="post" style="display:inline" onsubmit="return confirm('Revoke this user\'s active envelope?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
        <button class="btn sm ghost danger" name="action" value="re_reset" data-testid="user-re-reset">
          <i class="fa-solid fa-ban"></i> Revoke
        </button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (!empty($reHistory)): ?>
    <details style="margin-top:6px">
      <summary class="small muted" style="cursor:pointer">History (<?= count($reHistory) ?>)</summary>
      <table class="table" style="margin-top:8px">
        <thead><tr><th>Amount</th><th>Status</th><th>Claimed</th><th>Used</th><th>Deposit</th></tr></thead>
        <tbody>
          <?php foreach ($reHistory as $h): ?>
            <tr>
              <td><?= money($h['amount']) ?></td>
              <td>
                <?php $st = strtolower($h['status']); ?>
                <?php if ($st === 'used'): ?><span class="badge approved">Used</span>
                <?php elseif ($st === 'revoked'): ?><span class="badge rejected">Revoked</span>
                <?php else: ?><span class="badge pending">Unused</span><?php endif; ?>
              </td>
              <td class="small muted"><?= e(date('M d, H:i', strtotime($h['claimed_at']))) ?></td>
              <td class="small muted"><?= $h['used_at'] ? e(date('M d, H:i', strtotime($h['used_at']))) : '—' ?></td>
              <td class="small muted"><?= $h['deposit_id'] ? '#'.(int)$h['deposit_id'] : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>
  <?php endif; ?>
</div>

<!-- 📦 Package History (activation + pro-rata upgrades) -->
<div class="card" style="margin-top:18px" data-testid="user-package-history">
  <h3 style="margin:0 0 12px"><i class="fa-solid fa-box-open"></i> Package History</h3>
  <?php $pkgHistory = $pkgHistory ?? []; ?>
  <?php if (!$pkgHistory): ?>
    <div class="empty">No packages activated yet.</div>
  <?php else: ?>
    <table class="table">
      <thead><tr>
        <th>Package</th><th>Status</th><th>Upgraded from</th><th>Paid</th>
        <th>Started</th><th>Expires</th><th>Upgraded at</th>
      </tr></thead>
      <tbody>
      <?php foreach ($pkgHistory as $ph): ?>
        <tr>
          <td>
            <b><?= e($ph['pkg_name']) ?></b>
            <span class="small muted"> · <?= e(strtoupper($ph['pkg_tier'] ?: 'standard')) ?></span>
          </td>
          <td>
            <?php $st = strtolower((string)($ph['status'] ?? 'active')); ?>
            <?php if ($st === 'active'): ?>
              <span class="badge approved">Active</span>
            <?php elseif ($st === 'expired'): ?>
              <span class="badge rejected"><?= !empty($ph['upgraded_at']) ? 'Upgraded' : 'Expired' ?></span>
            <?php else: ?>
              <span class="badge pending"><?= e(ucfirst($st)) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($ph['upgraded_from_package_id']) && !empty($ph['from_name'])): ?>
              <span class="badge active"><i class="fa-solid fa-arrow-up-right-from-square"></i> <?= e($ph['from_name']) ?></span>
            <?php else: ?>
              <span class="small muted">—</span>
            <?php endif; ?>
          </td>
          <td><?= money($ph['price_paid']) ?></td>
          <td class="small muted"><?= e(date('M d, Y H:i', strtotime($ph['created_at']))) ?></td>
          <td class="small muted"><?= e(date('M d, Y', strtotime($ph['expires_at']))) ?></td>
          <td class="small muted"><?= !empty($ph['upgraded_at']) ? e(date('M d, Y H:i', strtotime($ph['upgraded_at']))) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
