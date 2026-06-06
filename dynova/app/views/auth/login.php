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
      <div class="pw-wrap">
        <input class="input pw-input" type="password" name="password" required data-testid="login-password">
        <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password"
                data-testid="login-password-toggle">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>
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
