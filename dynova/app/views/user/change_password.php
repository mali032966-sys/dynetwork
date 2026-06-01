<div class="topbar">
  <div class="greet"><b>Change Password</b></div>
  <a href="<?= route_url('profile') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>
<?php if (!empty($errors)): ?>
  <div class="alert error"><ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" class="card stagger" data-testid="password-form">
  <?= csrf_field() ?>
  <div class="form-group"><label>Current Password</label>
    <input class="input" type="password" name="current" required data-testid="pw-current"></div>
  <div class="form-group"><label>New Password</label>
    <input class="input" type="password" name="new" minlength="6" required data-testid="pw-new"></div>
  <div class="form-group"><label>Confirm New Password</label>
    <input class="input" type="password" name="confirm" minlength="6" required data-testid="pw-confirm"></div>
  <button class="btn" type="submit" data-testid="pw-submit">Update Password</button>
</form>
