<?php
$remaining = function_exists('dev_unlock_remaining') ? dev_unlock_remaining() : 0;
$mins = (int) ceil($remaining / 60);

$sections = [
    [
        'route'  => 'admin/packages',
        'title'  => 'Packages',
        'desc'   => 'Create, edit and delete task packages. Set daily tasks, earning per task, prices and the withdrawal-ladder.',
        'icon'   => 'fa-box-open',
        'color'  => '#3eb6ff',
        'bg'     => 'rgba(62,182,255,.12)',
        'testid' => 'dev-card-packages',
    ],
    [
        'route'  => 'admin/ranks',
        'title'  => 'Salary Ranks',
        'desc'   => 'Bronze / Silver / Gold / Diamond rules: per-level team members, per-level business, monthly salary amount.',
        'icon'   => 'fa-medal',
        'color'  => '#ffb547',
        'bg'     => 'rgba(255,181,71,.12)',
        'testid' => 'dev-card-ranks',
    ],
    [
        'route'  => 'admin/tasks',
        'title'  => 'Tasks',
        'desc'   => 'Add, edit or remove the videos users rate. Toggle availability and set per-task rewards (fallback for users without an active package).',
        'icon'   => 'fa-star',
        'color'  => '#8d5bff',
        'bg'     => 'rgba(141,91,255,.12)',
        'testid' => 'dev-card-tasks',
    ],
    [
        'route'  => 'admin/bonuses',
        'title'  => 'Joining Bonuses',
        'desc'   => 'One-time welcome bonus per package — referrer amount + invitee amount, paid on the invitee\'s first activation.',
        'icon'   => 'fa-gift',
        'color'  => '#10b981',
        'bg'     => 'rgba(16,185,129,.12)',
        'testid' => 'dev-card-bonuses',
    ],
    [
        'route'  => 'admin/popups',
        'title'  => 'Popup Messages',
        'desc'   => 'Schedule a text or image popup that every user sees on their dashboard. Set start / end dates and toggle on/off any time.',
        'icon'   => 'fa-bullhorn',
        'color'  => '#ffb547',
        'bg'     => 'rgba(255,181,71,.12)',
        'testid' => 'dev-card-popups',
    ],
];
?>

<div class="admin-h" style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <h1 style="margin:0">
    <i class="fa-solid fa-shield-halved" style="color:#10b981"></i>
    Developer Area
  </h1>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <div class="small" style="color:#10b981;display:flex;align-items:center;gap:6px;padding:6px 12px;
                              background:rgba(16,185,129,.10);border:1px solid rgba(16,185,129,.30);border-radius:10px">
      <i class="fa-solid fa-unlock"></i> Unlocked · auto-locks in <?= $mins ?> min<?= $mins===1?'':'s' ?>
    </div>
    <a href="<?= route_url('admin/dev-lock') ?>" class="btn ghost inline" data-testid="dev-hub-lock-btn">
      <i class="fa-solid fa-lock"></i> Lock now
    </a>
  </div>
</div>

<div class="card" style="margin-bottom:18px;padding:16px 18px;
     background:linear-gradient(135deg, rgba(16,185,129,.07), rgba(62,182,255,.07));
     border-color:rgba(16,185,129,.25)" data-testid="dev-hub-intro">
  <div style="display:flex;align-items:flex-start;gap:14px">
    <div style="width:42px;height:42px;border-radius:13px;display:grid;place-items:center;
                background:rgba(16,185,129,.18);color:#10b981;font-size:18px;flex-shrink:0">
      <i class="fa-solid fa-code"></i>
    </div>
    <div>
      <div style="font-weight:700;font-size:15.5px;margin-bottom:2px">Welcome, developer.</div>
      <div class="small muted" style="line-height:1.6">
        This area is hidden from the admin until you enter the developer password. It groups the four areas the admin
        can <b>view</b> but cannot <b>change</b>: Packages, Salary Ranks, Tasks, and Joining Bonuses. Pick a section below.
        The unlock auto-expires after <b><?= (int)DEV_UNLOCK_TTL_MINUTES ?> minutes</b> — or hit <i>Lock now</i> the moment you're done.
      </div>
    </div>
  </div>
</div>

<div class="dev-hub-grid" data-testid="dev-hub-grid">
  <?php foreach ($sections as $s): ?>
    <a href="<?= route_url($s['route']) ?>" class="dev-hub-card" data-testid="<?= e($s['testid']) ?>">
      <div class="dev-hub-ic" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>">
        <i class="fa-solid <?= $s['icon'] ?>"></i>
      </div>
      <div class="dev-hub-body">
        <div class="dev-hub-title"><?= e($s['title']) ?></div>
        <div class="dev-hub-desc"><?= e($s['desc']) ?></div>
      </div>
      <div class="dev-hub-chev"><i class="fa-solid fa-arrow-right"></i></div>
    </a>
  <?php endforeach; ?>
</div>

<?php
// =========================================================================
// SYSTEM LOCK — kill-switch for every user-side action.
// =========================================================================
$__locked = setting('lock_user_actions') === '1';
?>
<div class="card sys-lock-card <?= $__locked ? 'is-locked' : '' ?>" data-testid="sys-lock-card"
     style="margin-top:18px;padding:18px;border-radius:18px;
            border:1px solid <?= $__locked ? 'rgba(255,91,106,.45)' : 'rgba(255,181,71,.30)' ?>;
            background:linear-gradient(135deg,
              <?= $__locked ? 'rgba(255,91,106,.10), rgba(255,181,71,.05)' : 'rgba(255,181,71,.07), rgba(141,91,255,.05)' ?>);">
  <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap">
    <div style="width:46px;height:46px;border-radius:13px;display:grid;place-items:center;flex-shrink:0;
                background:<?= $__locked ? 'rgba(255,91,106,.18)' : 'rgba(255,181,71,.18)' ?>;
                color:<?= $__locked ? '#ff5b6a' : '#ffb547' ?>;font-size:20px">
      <i class="fa-solid <?= $__locked ? 'fa-lock' : 'fa-power-off' ?>"></i>
    </div>
    <div style="flex:1;min-width:240px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div style="font-weight:800;font-size:16px">System Lock</div>
        <?php if ($__locked): ?>
          <span class="badge rejected" style="font-size:10.5px" data-testid="sys-lock-state">LOCKED · users read-only</span>
        <?php else: ?>
          <span class="badge approved" style="font-size:10.5px" data-testid="sys-lock-state">LIVE · all actions enabled</span>
        <?php endif; ?>
      </div>
      <div class="small muted" style="line-height:1.6;margin-top:6px">
        Master kill-switch for every user-side action: <b>deposits</b>, <b>withdrawals</b>, <b>rating tasks</b>,
        <b>activating packages</b>, <b>new signups</b>, and <b>profile updates</b>.
        Users can still <b>sign in</b> and <b>browse their dashboard</b> — they just can't submit anything while this is on.
        Admin and Developer functions are <b>never</b> affected.
      </div>
    </div>
    <form method="post" action="<?= route_url('admin/system-lock') ?>" data-testid="sys-lock-form"
          style="flex-shrink:0">
      <?= csrf_field() ?>
      <button type="submit" class="sys-lock-switch <?= $__locked ? 'on' : '' ?>"
              data-testid="sys-lock-toggle"
              title="<?= $__locked ? 'Click to UNLOCK user actions' : 'Click to LOCK all user actions' ?>">
        <span class="sys-lock-knob"><i class="fa-solid <?= $__locked ? 'fa-lock' : 'fa-check' ?>"></i></span>
        <span class="sys-lock-text"><?= $__locked ? 'LOCKED' : 'LIVE' ?></span>
      </button>
    </form>
  </div>
</div>

<style>
.sys-lock-switch{
  position:relative; display:inline-flex; align-items:center; gap:10px;
  padding:6px 18px 6px 6px; border-radius:999px;
  border:1px solid rgba(16,185,129,.45);
  background:linear-gradient(120deg, rgba(16,185,129,.18), rgba(62,182,255,.10));
  color:#10b981; font-weight:800; font-size:13px; letter-spacing:.6px;
  cursor:pointer; transition:all .2s ease; min-width:130px; justify-content:flex-start;
}
.sys-lock-switch:hover{ transform:translateY(-1px); box-shadow:0 10px 22px -10px rgba(16,185,129,.55); }
.sys-lock-knob{
  width:30px; height:30px; border-radius:50%;
  background:#10b981; color:#08111a;
  display:grid; place-items:center; font-size:13px;
  box-shadow:0 0 0 4px rgba(16,185,129,.15);
  transition:all .2s ease;
}
.sys-lock-switch.on{
  border-color:rgba(255,91,106,.55);
  background:linear-gradient(120deg, rgba(255,91,106,.18), rgba(255,181,71,.10));
  color:#ff5b6a; justify-content:flex-end; padding:6px 6px 6px 18px;
}
.sys-lock-switch.on .sys-lock-knob{
  background:#ff5b6a; color:#08111a; order:2;
  box-shadow:0 0 0 4px rgba(255,91,106,.18);
}
.sys-lock-switch.on .sys-lock-text{ order:1; }
@media (max-width:520px){
  .sys-lock-switch{ width:100%; }
}
</style>
