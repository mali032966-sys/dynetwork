<div class="admin-h"><h1>Settings</h1></div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 14px">General &amp; Referral Commissions</h3>
  <form method="post" data-testid="settings-general-form">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="general">
    <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="form-group"><label>L1 Referral %</label>
        <input class="input" type="number" step="0.01" name="referral_l1" value="<?= e($values['referral_l1']) ?>" data-testid="set-l1"></div>
      <div class="form-group"><label>L2 Referral %</label>
        <input class="input" type="number" step="0.01" name="referral_l2" value="<?= e($values['referral_l2']) ?>" data-testid="set-l2"></div>
      <div class="form-group"><label>L3 Referral %</label>
        <input class="input" type="number" step="0.01" name="referral_l3" value="<?= e($values['referral_l3']) ?>" data-testid="set-l3"></div>
      <div class="form-group"><label>Min Withdrawal (PKR)
          <span class="small muted" style="text-transform:none;letter-spacing:.2px;font-weight:400">— absolute floor; per-package ladder still applies on top.</span>
        </label>
        <input class="input" type="number" name="min_withdrawal" value="<?= e($values['min_withdrawal']) ?>" data-testid="set-min-wd"></div>
      <div class="form-group"><label>Site Tagline</label>
        <input class="input" type="text" name="site_tagline" value="<?= e($values['site_tagline']) ?>"></div>
    </div>
    <div class="small muted" style="margin:8px 0 14px;line-height:1.5">
      <i class="fa-solid fa-circle-info" style="color:var(--blue)"></i>
      Daily task limits are now configured per package (Packages page). The previous global limit no longer applies.
    </div>
    <button class="btn inline" type="submit" data-testid="save-general">Save Settings</button>
  </form>
</div>

<div class="card">
  <div class="flex between" style="margin-bottom:14px">
    <h3 style="margin:0">Payment Methods</h3>
  </div>

  <table class="table" data-testid="admin-pm-table">
    <thead><tr><th>Name</th><th>Account Title</th><th>Number</th><th>Active</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($methods as $m): ?>
      <tr>
        <td><b><?= e($m['name']) ?></b></td>
        <td><?= e($m['account_title']) ?></td>
        <td><code><?= e($m['account_number']) ?></code></td>
        <td><?php if ($m['is_active']): ?><span class="badge approved">Yes</span><?php else: ?><span class="badge rejected">No</span><?php endif; ?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="pm_delete">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn sm danger" type="submit">Delete</button>
          </form>
          <button class="btn sm ghost" type="button" onclick="document.getElementById('pm-edit-<?= (int)$m['id'] ?>').classList.toggle('hidden-row')">Edit</button>
        </td>
      </tr>
      <tr id="pm-edit-<?= (int)$m['id'] ?>" class="hidden-row" style="display:none">
        <td colspan="5">
          <form method="post" class="flex" style="flex-wrap:wrap;gap:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="pm_save">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <input class="input" type="text" name="name" value="<?= e($m['name']) ?>" placeholder="Name" required>
            <input class="input" type="text" name="account_title" value="<?= e($m['account_title']) ?>" placeholder="Account Title" required>
            <input class="input" type="text" name="account_number" value="<?= e($m['account_number']) ?>" placeholder="Number" required>
            <input class="input" type="text" name="instructions" value="<?= e($m['instructions']) ?>" placeholder="Instructions">
            <label class="small muted" style="display:flex;align-items:center;gap:6px">
              <input type="checkbox" name="is_active" value="1" <?= $m['is_active']?'checked':'' ?>> Active</label>
            <button class="btn sm" type="submit">Save</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h4 style="margin:18px 0 10px">Add Payment Method</h4>
  <form method="post" class="flex" style="flex-wrap:wrap;gap:8px" data-testid="add-pm-form">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="pm_save">
    <input class="input" type="text" name="name" placeholder="Name (JazzCash/EasyPesa)" required data-testid="pm-name">
    <input class="input" type="text" name="account_title" placeholder="Account title" required data-testid="pm-title">
    <input class="input" type="text" name="account_number" placeholder="Account number" required data-testid="pm-number">
    <input class="input" type="text" name="instructions" placeholder="Instructions (optional)">
    <label class="small muted" style="display:flex;align-items:center;gap:6px">
      <input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="btn inline" type="submit" data-testid="pm-add-submit">Add</button>
  </form>
</div>
<style>.hidden-row{display:none !important}</style>

<div class="card" style="margin-top:18px">
  <h3 style="margin:0 0 6px">Change Admin Password</h3>
  <p class="small muted" style="margin:0 0 14px">
    Rotate your admin password. You must know the current password — no developer unlock is needed.
  </p>
  <form method="post" data-testid="admin-password-form" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="admin_password">
    <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="form-group">
        <label>Current password</label>
        <input class="input" type="password" name="current_password"
               required autocomplete="current-password"
               data-testid="admin-pwd-current">
      </div>
      <div class="form-group">
        <label>New password
          <span class="small muted" style="text-transform:none;letter-spacing:.2px;font-weight:400">— min 8 characters</span>
        </label>
        <input class="input" type="password" name="new_password"
               required minlength="8" autocomplete="new-password"
               data-testid="admin-pwd-new">
      </div>
      <div class="form-group">
        <label>Confirm new password</label>
        <input class="input" type="password" name="confirm_password"
               required minlength="8" autocomplete="new-password"
               data-testid="admin-pwd-confirm">
      </div>
    </div>
    <button class="btn inline danger" type="submit" data-testid="admin-pwd-save">
      Update Password
    </button>
  </form>
</div>

<script>
document.querySelectorAll('[onclick*="pm-edit"]').forEach(b=>{
  b.addEventListener('click',()=>{
    const id=b.getAttribute('onclick').match(/pm-edit-(\d+)/)[1];
    const row=document.getElementById('pm-edit-'+id);
    if(row.style.display==='none'||row.classList.contains('hidden-row')){row.style.display='table-row';row.classList.remove('hidden-row');}
    else{row.style.display='none';row.classList.add('hidden-row');}
  });
});
</script>

<!-- ================================================================== -->
<!-- 🧧 RED ENVELOPE — single-use coupon, applied at DEPOSIT time (v2) -->
<!-- ================================================================== -->
<div class="card" style="margin-top:18px" data-testid="red-envelope-card">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px">
    <div style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;
                background:linear-gradient(135deg,#ff3b52,#c81f34);color:#fff;font-size:20px;flex-shrink:0">
      <i class="fa-solid fa-gift"></i>
    </div>
    <div>
      <h3 style="margin:0;font-size:16px">Red Envelope — deposit discount</h3>
      <div class="small muted" style="margin-top:2px">
        A one-time discount coupon offered to every user.
        User pays <b>(deposit amount − discount)</b>, but wallet is credited the full deposit amount on approval. One claim per user.
      </div>
    </div>
  </div>

  <form method="post" data-testid="red-envelope-form">
    <?= csrf_field() ?>
    <input type="hidden" name="section" value="red_envelope">

    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <label style="display:flex;align-items:center;gap:8px;padding:10px;border-radius:10px;
                     background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.10)">
        <input type="checkbox" name="red_envelope_enabled" value="1" <?= $reEnabled ? 'checked' : '' ?>
               data-testid="re-enabled">
        <span><b>Enable Red Envelope</b><br><span class="small muted">Master switch. Off = no discounts offered.</span></span>
      </label>
      <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.10)">
        <b>Discount mode</b>
        <div class="small muted" style="margin-bottom:6px">Fixed = each package has its own amount. Random = surprise picked from the pool below.</div>
        <label style="margin-right:14px"><input type="radio" name="red_envelope_mode" value="fixed"  <?= $reMode==='fixed'  ? 'checked':'' ?> data-testid="re-mode-fixed">  Fixed per package</label>
        <label><input type="radio" name="red_envelope_mode" value="random" <?= $reMode==='random' ? 'checked':'' ?> data-testid="re-mode-random"> Random surprise</label>
      </div>
    </div>

    <h4 style="margin:16px 0 8px;font-size:14px">Per-package discount amounts (PKR)</h4>
    <div class="small muted" style="margin-bottom:8px">
      Enter <b>0</b> or leave blank to skip a package. In <b>random</b> mode these amounts form the pool the surprise is picked from.
    </div>
    <table class="table" style="margin-bottom:12px">
      <thead><tr><th style="width:60%">Package</th><th>Discount (Rs)</th></tr></thead>
      <tbody>
      <?php if (empty($rePackages)): ?>
        <tr><td colspan="2" class="empty">No packages configured yet.</td></tr>
      <?php else: foreach ($rePackages as $rp):
        $amt = (float)($reMap[(string)$rp['id']] ?? $reMap[$rp['id']] ?? 0);
      ?>
        <tr>
          <td>
            <b><?= e($rp['name']) ?></b>
            <span class="small muted"> · <?= e(strtoupper($rp['tier'] ?: 'standard')) ?> · List price <?= money($rp['price']) ?></span>
          </td>
          <td>
            <input class="input" type="number" min="0" step="1"
                   name="red_envelope_amounts[<?= (int)$rp['id'] ?>]"
                   value="<?= $amt > 0 ? number_format($amt, 0, '.', '') : '' ?>"
                   placeholder="0"
                   data-testid="re-amount-<?= (int)$rp['id'] ?>"
                   style="max-width:180px">
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div class="small muted" style="margin:8px 0 12px;padding:10px 12px;border-radius:10px;
                                    background:rgba(62,182,255,.06);border:1px solid rgba(62,182,255,.20)">
      <i class="fa-solid fa-circle-info" style="color:#3eb6ff"></i>
      <b>How it decides the amount:</b>
      Users with <b>no active package</b> see "Up to Rs X off" (the highest amount above).
      Users <b>with an active package</b> see "Get Rs Y off when you upgrade to [next tier]" (the amount configured for that next tier).
      The discount is applied at <b>deposit time</b>. Wallet is credited the full deposit amount on approval.
    </div>

    <button class="btn inline" type="submit" data-testid="re-save">
      <i class="fa-solid fa-floppy-disk"></i> Save Red Envelope settings
    </button>
  </form>
</div>
