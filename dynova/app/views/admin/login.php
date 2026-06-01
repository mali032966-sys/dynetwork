<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – <?= e(setting('site_name', APP_NAME)) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="bg-fx"></div>
<div class="bg-grid"></div>
<div class="bg-orbs"><span></span><span></span><span></span><span></span><span></span></div>
<div class="auth-wrap">
  <div class="brand">
    <div class="logo"><img src="<?= asset('img/logo.jpg') ?>" alt="Logo"></div>
    <div class="name">DYNOVA</div>
    <div class="sub" style="color:var(--violet)">A D M I N</div>
  </div>
  <?php if (!empty($errors)): ?>
    <div class="alert error" style="width:100%;margin-top:14px" data-testid="admin-login-error">
      <ul><?php foreach($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <div class="auth-card card">
    <h2>Admin Panel</h2>
    <div class="sub">Restricted area</div>
    <form method="post" data-testid="admin-login-form">
      <?= csrf_field() ?>
      <div class="form-group"><label>Email</label>
        <input class="input" type="email" name="email" value="admin@dynova.com" required data-testid="admin-login-email"></div>
      <div class="form-group"><label>Password</label>
        <input class="input" type="password" name="password" required data-testid="admin-login-password"></div>
      <button class="btn" type="submit" data-testid="admin-login-submit">Enter Admin</button>
    </form>
    <div class="swap"><a href="<?= route_url('auth/login') ?>">← User login</a></div>
  </div>
</div>
</body>
</html>
