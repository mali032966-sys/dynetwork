<div class="admin-h">
  <h1>Salary Ranks</h1>
  <form method="post" style="display:inline">
    <?= csrf_field() ?>
    <button class="btn inline" name="action" value="pay_now" type="submit"
            onclick="return confirm('Pay this month\'s salaries to all eligible users now?')"
            data-testid="pay-now">
      <i class="fa-solid fa-money-bill-trend-up"></i> Run Monthly Payout Now
    </button>
  </form>
</div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 12px">Existing Ranks</h3>
  <div style="overflow-x:auto">
    <table class="table" data-testid="admin-ranks-table">
      <thead>
        <tr>
          <th>Rank</th>
          <th>L1 Members</th><th>L2 Members</th><th>L3 Members</th>
          <th>L1 Business</th><th>L2 Business</th><th>L3 Business</th>
          <th>Monthly Salary</th>
          <th>Order</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($ranks as $r): ?>
        <tr>
          <td><?= e($r['emoji']) ?> <b><?= e($r['name']) ?></b></td>
          <td><?= (int)$r['min_l1_members'] ?></td>
          <td><?= (int)$r['min_l2_members'] ?></td>
          <td><?= (int)$r['min_l3_members'] ?></td>
          <td><?= money($r['min_l1_business']) ?></td>
          <td><?= money($r['min_l2_business']) ?></td>
          <td><?= money($r['min_l3_business']) ?></td>
          <td><b><?= money($r['monthly_salary']) ?></b><small style="opacity:.6"> /mo</small></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this rank?')">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn sm danger" name="action" value="delete">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">Add / Edit Rank</h3>
  <form method="post" data-testid="rank-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="form-group"><label>ID (blank = new)</label>
        <input class="input" type="number" name="id" placeholder="leave blank to add"></div>
      <div class="form-group"><label>Name</label>
        <input class="input" type="text" name="name" required data-testid="rank-name" placeholder="Bronze / Silver / Gold"></div>
      <div class="form-group"><label>Emoji</label>
        <input class="input" type="text" name="emoji" placeholder="🥉 🥈 🥇 💎"></div>
    </div>

    <h4 style="margin:18px 0 8px;font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--txt-mute)">
      <i class="fa-solid fa-users"></i> Team members required (per level)
    </h4>
    <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="form-group"><label>Level 1 members</label>
        <input class="input" type="number" name="min_l1_members" value="0" min="0" required data-testid="rank-l1-members"></div>
      <div class="form-group"><label>Level 2 members</label>
        <input class="input" type="number" name="min_l2_members" value="0" min="0" required data-testid="rank-l2-members"></div>
      <div class="form-group"><label>Level 3 members</label>
        <input class="input" type="number" name="min_l3_members" value="0" min="0" required data-testid="rank-l3-members"></div>
    </div>

    <h4 style="margin:18px 0 8px;font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--txt-mute)">
      <i class="fa-solid fa-chart-line"></i> Business required per level (PKR)
    </h4>
    <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="form-group"><label>Level 1 business</label>
        <input class="input" type="number" name="min_l1_business" step="0.01" min="0" value="0" required data-testid="rank-l1-business"></div>
      <div class="form-group"><label>Level 2 business</label>
        <input class="input" type="number" name="min_l2_business" step="0.01" min="0" value="0" required data-testid="rank-l2-business"></div>
      <div class="form-group"><label>Level 3 business</label>
        <input class="input" type="number" name="min_l3_business" step="0.01" min="0" value="0" required data-testid="rank-l3-business"></div>
    </div>

    <h4 style="margin:18px 0 8px;font-size:13px;letter-spacing:1.5px;text-transform:uppercase;color:var(--txt-mute)">
      <i class="fa-solid fa-money-bill-trend-up"></i> Payout
    </h4>
    <div class="admin-grid" style="grid-template-columns:repeat(2,1fr)">
      <div class="form-group"><label>Monthly Salary (PKR)</label>
        <input class="input" type="number" name="monthly_salary" step="0.01" min="0" value="0" required data-testid="rank-monthly-salary"></div>
      <div class="form-group"><label>Sort Order</label>
        <input class="input" type="number" name="sort_order" value="0"></div>
    </div>

    <button class="btn inline" type="submit" data-testid="rank-save">
      <i class="fa-solid fa-floppy-disk"></i> Save Rank
    </button>
  </form>
</div>
