<div class="admin-h"><h1>Joining Bonuses</h1></div>

<?php $editing = isset($_GET['edit']) ? JoiningBonus::find((int)$_GET['edit']) : null; ?>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 4px"><?= $editing ? 'Edit Bonus #' . (int)$editing['id'] : 'Add / Update Joining Bonus' ?></h3>
  <div class="small muted" style="margin-bottom:14px">
    When a referred user joins via someone's link and activates a package for the
    first time, both sides get a one-time welcome bonus. Configure the amounts per package below.
  </div>

  <form method="post" class="stagger" data-testid="bonus-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
      <div class="form-group"><label>Package</label>
        <select class="input" name="package_id" required data-testid="bonus-package">
          <option value="">— pick a package —</option>
          <?php foreach ($packages as $p):
            $sel = $editing && (int)$editing['package_id'] === (int)$p['id'] ? 'selected' : '';
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $sel ?>>
              <?= e($p['name']) ?> · <?= money($p['price']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group"><label>Referrer bonus (PKR)</label>
        <input class="input" type="number" name="referrer_bonus" step="0.01" min="0" required
               value="<?= e($editing['referrer_bonus'] ?? '') ?>" placeholder="100"
               data-testid="bonus-referrer">
      </div>

      <div class="form-group"><label>Invitee bonus (PKR)</label>
        <input class="input" type="number" name="invitee_bonus" step="0.01" min="0" required
               value="<?= e($editing['invitee_bonus'] ?? '') ?>" placeholder="300"
               data-testid="bonus-invitee">
      </div>
    </div>

    <div class="flex" style="gap:18px;margin:10px 0 14px">
      <label class="small muted" style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" name="is_active" value="1"
               <?= !isset($editing) || (int)($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
        Active (visible to users and paid on activation)
      </label>
    </div>

    <div class="flex" style="gap:10px">
      <button class="btn inline" type="submit" data-testid="bonus-save">
        <i class="fa-solid fa-floppy-disk"></i> <?= $editing ? 'Save changes' : 'Save Bonus' ?>
      </button>
      <?php if ($editing): ?>
        <a href="<?= route_url('admin/bonuses') ?>" class="btn ghost inline">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">All Joining Bonuses (<?= count($bonuses) ?>)</h3>
  <div style="overflow-x:auto">
    <table class="table" data-testid="admin-bonuses-table">
      <thead><tr>
        <th>ID</th><th>Package</th><th>Package Price</th>
        <th>Referrer gets</th><th>Invitee gets</th>
        <th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if (!$bonuses): ?>
        <tr><td colspan="7" class="empty">No bonuses configured yet — add one above.</td></tr>
      <?php else: foreach ($bonuses as $b): ?>
        <tr>
          <td>#<?= (int)$b['id'] ?></td>
          <td><b><?= e($b['pkg_name']) ?></b>
            <span class="badge active" style="margin-left:6px"><?= e($b['tier']) ?></span>
          </td>
          <td><?= money($b['price']) ?></td>
          <td><b style="color:var(--blue,#60a5fa)"><?= money($b['referrer_bonus']) ?></b></td>
          <td><b style="color:var(--green,#10b981)"><?= money($b['invitee_bonus']) ?></b></td>
          <td><?php if ($b['is_active']): ?>
                <span class="badge approved">Active</span>
              <?php else: ?>
                <span class="badge rejected">Hidden</span>
              <?php endif; ?></td>
          <td>
            <a href="<?= route_url('admin/bonuses', ['edit' => $b['id']]) ?>"
               class="btn sm ghost" data-testid="bonus-edit-<?= (int)$b['id'] ?>">Edit</a>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn sm ghost" name="action" value="toggle">Toggle</button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this bonus rule?')">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn sm danger" name="action" value="delete"
                      data-testid="bonus-delete-<?= (int)$b['id'] ?>">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
