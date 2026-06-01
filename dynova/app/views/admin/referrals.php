<div class="admin-h">
  <h1>Referral Tree</h1>
  <form method="get" class="flex" style="gap:8px">
    <input type="hidden" name="r" value="admin/referrals">
    <input class="input" type="number" name="user_id" placeholder="User ID" value="<?= e($user['id'] ?? '') ?>">
    <button class="btn inline" type="submit">View</button>
  </form>
</div>

<?php if (!$user): ?>
  <div class="card"><div class="empty">Enter a User ID above to view their 3-level referral tree.</div></div>
<?php else: ?>
  <div class="card" style="margin-bottom:14px">
    <b><?= e($user['name'] ?: $user['whatsapp']) ?></b> ·
    <span class="muted small">Code <?= e($user['referral_code']) ?> · Balance <?= money($user['balance']) ?></span>
  </div>

  <?php foreach (['Level 1 (A)'=>$teamA,'Level 2 (B)'=>$teamB,'Level 3 (C)'=>$teamC] as $label=>$members): ?>
    <div class="card" style="margin-bottom:14px">
      <h3 style="margin:0 0 10px"><?= e($label) ?> — <?= count($members) ?></h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>WhatsApp</th><th>Joined</th></tr></thead>
        <tbody>
        <?php if (!$members): ?><tr><td colspan="4" class="empty">No members</td></tr>
        <?php else: foreach ($members as $m): ?>
          <tr><td>#<?= (int)$m['id'] ?></td><td><?= e($m['name']) ?></td><td><?= e($m['whatsapp']) ?></td><td><?= e(date('M d, Y', strtotime($m['created_at']))) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
