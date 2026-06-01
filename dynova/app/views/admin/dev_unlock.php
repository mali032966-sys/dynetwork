<div class="admin-h" style="margin-bottom:20px">
  <h1><i class="fa-solid fa-shield-halved" style="color:var(--violet,#8d5bff)"></i> Developer Unlock</h1>
</div>

<div class="card" style="max-width:520px;padding:24px;margin-bottom:18px" data-testid="dev-unlock-card">
  <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:18px">
    <div style="width:46px;height:46px;border-radius:14px;display:grid;place-items:center;
                background:linear-gradient(135deg, rgba(141,91,255,.2), rgba(62,182,255,.2));
                color:#a78bfa;font-size:20px;flex-shrink:0">
      <i class="fa-solid fa-lock"></i>
    </div>
    <div>
      <h3 style="margin:0 0 4px;font-size:16px">Restricted action</h3>
      <div class="small muted" style="line-height:1.55">
        Adding, editing or deleting packages, ranks, joining bonuses, tasks, payment methods,
        settings or user records requires a one-time developer password. Once unlocked, the
        admin panel allows write actions for <b><?= (int)DEV_UNLOCK_TTL_MINUTES ?> minutes</b>.
      </div>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert error" data-testid="dev-unlock-error">
      <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" data-testid="dev-unlock-form">
    <?= csrf_field() ?>
    <?php if ($return): ?>
      <input type="hidden" name="return" value="<?= e($return) ?>">
    <?php endif; ?>

    <div class="form-group">
      <label>Developer password</label>
      <input class="input" type="password" name="dev_password" autocomplete="off"
             autofocus required placeholder="Enter the developer password"
             data-testid="dev-unlock-input">
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn inline" type="submit" data-testid="dev-unlock-submit">
        <i class="fa-solid fa-unlock"></i> Unlock admin write access
      </button>
      <a href="<?= route_url($return ?: 'admin/dashboard') ?>" class="btn ghost inline" data-testid="dev-unlock-cancel">
        <i class="fa-solid fa-arrow-left"></i> Back
      </a>
    </div>

    <div class="small muted" style="margin-top:14px;line-height:1.55">
      <i class="fa-solid fa-circle-info" style="color:var(--blue)"></i>
      Logging out, or simply waiting <?= (int)DEV_UNLOCK_TTL_MINUTES ?> minutes, automatically
      re-locks the panel.
    </div>
  </form>
</div>
