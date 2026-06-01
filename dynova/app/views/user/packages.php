<?php
$currency = APP_CURRENCY_SYMBOL;
// Compact money formatter for the small stat boxes inside package cards
function pkg_money($amount) {
    $n = (float) $amount;
    $f = $n == (int) $n ? number_format($n) : number_format($n, 2);
    return APP_CURRENCY_SYMBOL . ' ' . $f;
}
?>
<div class="topbar topbar-flex">
  <div class="page-head">
    <h2 class="page-title" data-testid="page-title">Task Packages</h2>
    <div class="page-sub">Pick a plan. Earn daily. Cash out anytime.</div>
  </div>
  <a href="<?= route_url('profile') ?>" class="bell" data-testid="dash-bell">
    <i class="fa-solid fa-bell"></i><span class="dot"></span>
  </a>
</div>

<?php if ($active): ?>
  <div class="card active-pkg-card stagger" data-testid="active-package">
    <div class="active-pkg-body" style="padding:18px 20px">
      <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Active package</div>
      <h3 style="margin:2px 0 6px;font-size:20px"><?= e($active['pkg_name']) ?></h3>
      <div class="active-pkg-meta">
        <span><i class="fa-solid fa-star"></i> <?= (int)$active['daily_tasks'] ?> tasks/day</span>
        <span><i class="fa-solid fa-coins"></i> <?= money($active['daily_earning']) ?> /day</span>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="packages-grid stagger" data-testid="packages-grid">
  <?php foreach ($packages as $p):
    $tier = $p['tier'] ?: 'standard';
    $isFeatured = (int)$p['is_featured'] === 1;
    $isCurrent = $active && (int)$active['package_id'] === (int)$p['id'];
  ?>
  <div class="pkg-card <?= e($tier) ?> <?= $isFeatured ? 'featured' : '' ?>" data-testid="package-<?= e(strtolower($p['name'])) ?>">
    <?php if ($isFeatured): ?>
      <div class="pkg-ribbon"><i class="fa-solid fa-fire"></i> Most popular</div>
    <?php endif; ?>
    <div class="pkg-head" style="margin-bottom:14px">
      <div>
        <div class="pkg-name"><?= e($p['name']) ?></div>
        <div class="pkg-tier"><?= e(strtoupper($tier)) ?> · PACKAGE</div>
      </div>
    </div>

    <div class="pkg-price">
      <span class="cur">Rs</span>
      <span class="amt"><?= number_format((float)$p['price']) ?></span>
      <span class="per">one-time</span>
    </div>

    <?php
      $dailyTasks = (int)$p['daily_tasks'];
      $perTask    = (float)($p['earning_per_task'] ?? 0);
      $dailyAmt   = $dailyTasks * $perTask;
      $weeklyAmt  = $dailyAmt * 7;
      $monthlyAmt = $dailyAmt * 30;
    ?>
    <ul class="pkg-stats">
      <li>
        <span class="i"><i class="fa-solid fa-star"></i></span>
        <span><b><?= $dailyTasks ?></b><small>Daily tasks</small></span>
      </li>
      <li>
        <span class="i grad"><i class="fa-solid fa-bullseye"></i></span>
        <span><b><?= pkg_money($perTask) ?></b><small>Per task</small></span>
      </li>
      <li>
        <span class="i grad"><i class="fa-solid fa-coins"></i></span>
        <span><b><?= pkg_money($dailyAmt) ?></b><small>Daily earning</small></span>
      </li>
      <li>
        <span class="i grad2"><i class="fa-solid fa-calendar-week"></i></span>
        <span><b><?= pkg_money($weeklyAmt) ?></b><small>Weekly earning</small></span>
      </li>
      <li>
        <span class="i grad2"><i class="fa-solid fa-arrow-trend-up"></i></span>
        <span><b><?= pkg_money($monthlyAmt) ?></b><small>Monthly earning</small></span>
      </li>
    </ul>

    <?php if ($isCurrent): ?>
      <button class="btn pkg-btn ghost" disabled data-testid="pkg-current-<?= (int)$p['id'] ?>">
        <i class="fa-solid fa-check"></i> Current plan
      </button>
    <?php elseif ($active): ?>
      <form method="post" onsubmit="return confirm('Activate <?= e($p['name']) ?> for <?= money($p['price']) ?>?\n\nYour current plan will be replaced.')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn pkg-btn" data-testid="pkg-upgrade-<?= (int)$p['id'] ?>">
          <i class="fa-solid fa-rocket"></i> Upgrade to <?= e($p['name']) ?>
        </button>
      </form>
    <?php else: ?>
      <form method="post" onsubmit="return confirm('Activate <?= e($p['name']) ?> for <?= money($p['price']) ?>?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn pkg-btn" data-testid="pkg-activate-<?= (int)$p['id'] ?>">
          <i class="fa-solid fa-rocket"></i> Activate Package
        </button>
      </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$packages): ?>
  <div class="card empty">No packages available right now. Please check back soon.</div>
<?php endif; ?>

<div class="card mt" style="margin-top:18px">
  <h3 style="margin:0 0 6px;font-size:15px"><i class="fa-solid fa-circle-info" style="color:var(--blue)"></i> How packages work</h3>
  <ul style="margin:6px 0 0;padding-left:20px;color:var(--txt-mute);font-size:13px;line-height:1.7">
    <li>Activating a package debits the price from your <b>wallet balance</b>.</li>
    <li>You instantly unlock the package's daily task limit and earning rate.</li>
    <li>Earnings are credited as you complete tasks every day.</li>
    <li>Upgrade any time — activating a higher tier replaces your current plan.</li>
  </ul>
</div>
