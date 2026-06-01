<div class="auth-card card">
  <h2 data-testid="signup-title">Create Account</h2>
  <div class="sub">Start earning daily by rating videos</div>
  <?php if (!empty($errors)): ?>
    <div class="alert error" data-testid="signup-error">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <form method="post" data-testid="signup-form">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>Your Name</label>
      <input class="input" type="text" name="name" placeholder="Full name (optional)" data-testid="signup-name">
    </div>
    <div class="form-group">
      <label>WhatsApp Number</label>
      <div class="input-group">
        <span class="prefix">+92</span>
        <input class="input" type="tel" name="whatsapp" placeholder="3001234567" required data-testid="signup-whatsapp">
      </div>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input class="input" type="password" name="password" minlength="6" required data-testid="signup-password">
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <input class="input" type="password" name="confirm" minlength="6" required data-testid="signup-confirm">
    </div>
    <div class="form-group">
      <label>Captcha: What is <?= (int)$a ?> + <?= (int)$b ?>?</label>
      <input class="input" type="number" name="captcha" required data-testid="signup-captcha">
    </div>
    <input type="hidden" name="ref" value="<?= e($refQuery) ?>">
    <button type="submit" class="btn" data-testid="signup-submit">Create Account</button>
  </form>
  <div class="swap">Already have an account? <a href="<?= route_url('auth/login') ?>" data-testid="goto-login">Login</a></div>
</div>
