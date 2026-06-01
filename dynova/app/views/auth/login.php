<div class="auth-card card">
  <h2 data-testid="login-title">Welcome Back</h2>
  <div class="sub">Login to continue earning</div>
  <?php if (!empty($errors)): ?>
    <div class="alert error" data-testid="login-error">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <form method="post" data-testid="login-form">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>WhatsApp Number</label>
      <div class="input-group">
        <span class="prefix">+92</span>
        <input class="input" type="tel" name="whatsapp" placeholder="3001234567" required data-testid="login-whatsapp">
      </div>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input class="input" type="password" name="password" required data-testid="login-password">
    </div>
    <div class="flex between" style="margin:-4px 0 14px">
      <label style="font-size:12px;color:var(--txt-mute);display:flex;align-items:center;gap:6px">
        <input type="checkbox" name="remember" value="1" data-testid="login-remember"> Remember me
      </label>
      <span class="small muted">Forgot? Contact support</span>
    </div>
    <button type="submit" class="btn" data-testid="login-submit">Login</button>
  </form>
  <div class="swap">Don't have an account? <a href="<?= route_url('auth/signup') ?>" data-testid="goto-signup">Sign Up</a></div>
  <div class="swap" style="margin-top:4px"><a href="<?= route_url('admin/login') ?>" style="color:var(--txt-mute);font-size:11px" data-testid="goto-admin">Admin login →</a></div>
</div>
