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
      <div class="pw-wrap">
        <input class="input pw-input" type="password" name="password" minlength="6" required data-testid="signup-password">
        <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"
                data-testid="signup-password-toggle">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <div class="pw-wrap">
        <input class="input pw-input" type="password" name="confirm" minlength="6" required data-testid="signup-confirm">
        <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"
                data-testid="signup-confirm-toggle">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
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

<!-- Shared password "eye" toggle (used by login + signup forms) -->
<style>
.pw-wrap{ position:relative; }
.pw-wrap .pw-input{ padding-right:42px; }
.pw-wrap .pw-toggle{
  position:absolute; top:50%; right:8px; transform:translateY(-50%);
  width:32px; height:32px;
  display:flex; align-items:center; justify-content:center;
  background:transparent; border:0; cursor:pointer; padding:0;
  color:var(--txt-mute, #94a3b8);
  border-radius:8px; transition:color .15s ease, background .15s ease;
}
.pw-wrap .pw-toggle:hover{ color:var(--txt, #fff); background:rgba(255,255,255,.06); }
.pw-wrap .pw-toggle i{ font-size:14px; pointer-events:none; }
</style>
<script>
(function(){
  document.querySelectorAll('[data-pw-toggle]').forEach(function(btn){
    if (btn.__pwBound) return;
    btn.__pwBound = true;
    btn.addEventListener('click', function(){
      var inp = btn.parentElement && btn.parentElement.querySelector('.pw-input');
      if (!inp) return;
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      var ico  = btn.querySelector('i');
      if (ico){ ico.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'; }
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
})();
</script>
