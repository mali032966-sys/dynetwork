<?php
// Build a helper URL for pagination links that preserves the current search
// query and per-page selection.
function _users_page_url(int $p, string $q, int $perPage): string {
    return route_url('admin/users', ['page' => $p, 'per_page' => $perPage] + ($q !== '' ? ['q' => $q] : []));
}
?>
<div class="admin-h">
  <h1>Users
    <span class="small muted" data-testid="users-total-count">
      · <?= number_format((int)($total ?? count($users))) ?> total
    </span>
  </h1>
  <form method="get" class="flex" style="gap:8px;flex-wrap:wrap">
    <input type="hidden" name="r" value="admin/users">
    <input type="hidden" name="per_page" value="<?= (int)($perPage ?? 50) ?>">
    <input class="input" type="text" name="q" placeholder="Search name, WhatsApp, code"
           value="<?= e($q) ?>" data-testid="user-search">
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

  <?php
    // --- Pagination footer ---
    $page       = (int)($page ?? 1);
    $perPage    = (int)($perPage ?? 50);
    $totalPages = (int)($totalPages ?? 1);
    $q          = (string)($q ?? '');
  ?>
  <div class="users-pager" data-testid="users-pager"
       style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;
              padding:14px 4px 0;margin-top:10px;border-top:1px solid rgba(255,255,255,.06)">
    <div class="small muted">
      Page <b><?= (int)$page ?></b> of <b><?= (int)$totalPages ?></b>
      · showing <?= min($perPage, count($users)) ?> of <?= number_format((int)($total ?? count($users))) ?>
    </div>

    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <label class="small muted" style="margin-right:4px">Per page:</label>
      <?php foreach ([25, 50, 100] as $pp): ?>
        <a class="btn sm <?= $perPage === $pp ? '' : 'ghost' ?>"
           href="<?= route_url('admin/users', ['page' => 1, 'per_page' => $pp] + ($q !== '' ? ['q' => $q] : [])) ?>"
           data-testid="users-per-<?= $pp ?>"><?= $pp ?></a>
      <?php endforeach; ?>
    </div>

    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <a class="btn sm ghost <?= $page <= 1 ? 'is-disabled' : '' ?>"
         href="<?= $page > 1 ? _users_page_url(1, $q, $perPage) : '#' ?>"
         data-testid="users-first"><i class="fa-solid fa-angles-left"></i></a>
      <a class="btn sm ghost <?= $page <= 1 ? 'is-disabled' : '' ?>"
         href="<?= $page > 1 ? _users_page_url($page - 1, $q, $perPage) : '#' ?>"
         data-testid="users-prev"><i class="fa-solid fa-angle-left"></i></a>
      <?php
        // Show up to 5 numbered links centred on the current page
        $from = max(1, $page - 2);
        $to   = min($totalPages, $from + 4);
        $from = max(1, $to - 4);
        for ($i = $from; $i <= $to; $i++):
      ?>
        <a class="btn sm <?= $i === $page ? '' : 'ghost' ?>"
           href="<?= _users_page_url($i, $q, $perPage) ?>"
           data-testid="users-page-<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
      <a class="btn sm ghost <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
         href="<?= $page < $totalPages ? _users_page_url($page + 1, $q, $perPage) : '#' ?>"
         data-testid="users-next"><i class="fa-solid fa-angle-right"></i></a>
      <a class="btn sm ghost <?= $page >= $totalPages ? 'is-disabled' : '' ?>"
         href="<?= $page < $totalPages ? _users_page_url($totalPages, $q, $perPage) : '#' ?>"
         data-testid="users-last"><i class="fa-solid fa-angles-right"></i></a>
    </div>
  </div>
</div>

<style>
.users-pager .btn.is-disabled{ opacity:.35; pointer-events:none; }
</style>
