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
