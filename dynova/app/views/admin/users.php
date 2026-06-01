<div class="admin-h">
  <h1>Users (<?= count($users) ?>)</h1>
  <form method="get" class="flex" style="gap:8px">
    <input type="hidden" name="r" value="admin/users">
    <input class="input" type="text" name="q" placeholder="Search name, WhatsApp, code" value="<?= e($q) ?>" data-testid="user-search">
    <button class="btn inline" type="submit">Search</button>
  </form>
</div>

<div class="card" data-testid="admin-users-table">
  <table class="table">
    <thead><tr>
      <th>ID</th><th>Name</th><th>WhatsApp</th><th>Code</th><th>Balance</th><th>Refs</th><th>Status</th><th></th>
    </tr></thead>
    <tbody>
    <?php if (!$users): ?>
      <tr><td colspan="8" class="empty">No users found.</td></tr>
    <?php else: foreach ($users as $row): ?>
      <tr>
        <td><?= (int)$row['id'] ?></td>
        <td><?= e($row['name']) ?></td>
        <td><?= e($row['whatsapp']) ?></td>
        <td><span class="badge active"><?= e($row['referral_code']) ?></span></td>
        <td><?= money($row['balance']) ?></td>
        <td><?= (int)\User::countReferrals((int)$row['id'],1) ?></td>
        <td><?php if ((int)$row['is_blocked']===1): ?>
              <span class="badge blocked">Blocked</span>
            <?php else: ?><span class="badge approved">Active</span><?php endif; ?></td>
        <td>
          <a class="btn sm ghost" href="<?= route_url('admin/users/edit', ['id'=>$row['id']]) ?>" data-testid="user-edit-<?= (int)$row['id'] ?>">Manage</a>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
