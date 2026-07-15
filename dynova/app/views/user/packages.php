<?php
$currency = APP_CURRENCY_SYMBOL;
// Compact money formatter for the small stat boxes inside package cards
function pkg_money($amount) {
    $n = (float) $amount;
    $f = $n == (int) $n ? number_format($n) : number_format($n, 2);
    return APP_CURRENCY_SYMBOL . ' ' . $f;
}
// 🧧 Red envelope state — used to preview the effective price on each card.
$reOn      = red_envelope_enabled();
$reMode    = red_envelope_mode();
$rePicked  = (float)($_SESSION['red_envelope_picked'] ?? 0);
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

<?php
// Persistent "you started an upgrade" nudge — appears after the user
// diverted to the deposit page (session var set by PackageController).
$pendingUpgradeId = (int)($_SESSION['pending_upgrade_to'] ?? 0);
$pendingUpgrade   = $pendingUpgradeId ? TaskPackage::find($pendingUpgradeId) : null;
if ($pendingUpgrade && $active):
    $__diff = (float)$pendingUpgrade['price'] - (float)$active['price_paid'];
    $__canFinish = (float)$u['balance'] >= $__diff && $__diff > 0;
?>
<div class="card stagger" style="border:1px solid rgba(255,181,71,.4);
     background:linear-gradient(120deg,rgba(255,181,71,.10),rgba(255,91,106,.06));
     padding:14px 16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center"
     data-testid="pkg-pending-upgrade">
  <div style="width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:rgba(255,181,71,.18);color:#ffb547;font-size:18px;flex-shrink:0">
    <i class="fa-solid fa-arrows-up-to-line"></i>
  </div>
  <div style="flex:1;min-width:220px">
    <b>Upgrade in progress → <?= e($pendingUpgrade['name']) ?></b>
    <div class="small muted" style="margin-top:2px">
      Difference required: <b style="color:#ffd54a"><?= money($__diff) ?></b>.
      <?= $__canFinish
          ? 'Your wallet has enough balance — click below to complete the upgrade.'
          : 'Deposit the exact difference to complete this upgrade.' ?>
    </div>
  </div>
  <?php if ($__canFinish): ?>
    <form method="post" style="margin:0">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upgrade">
      <input type="hidden" name="package_id" value="<?= (int)$pendingUpgrade['id'] ?>">
      <button class="btn" data-testid="pkg-finish-upgrade" style="background:linear-gradient(120deg,#ffb547,#ff5b6a);color:#08111a;font-weight:800">
        <i class="fa-solid fa-check"></i> Complete Upgrade
      </button>
    </form>
  <?php else: ?>
    <a href="<?= route_url('wallet/deposit') ?>" class="btn" data-testid="pkg-finish-deposit">
      <i class="fa-solid fa-wallet"></i> Deposit <?= money($__diff) ?>
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($active): ?>
  <div class="card active-pkg-card stagger" data-testid="active-package">
    <div class="active-pkg-body" style="padding:18px 20px">
      <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Active package</div>
      <h3 style="margin:2px 0 6px;font-size:20px"><?= e($active['pkg_name']) ?></h3>
      <div class="active-pkg-meta">
        <span><i class="fa-solid fa-star"></i> <?= (int)$active['daily_tasks'] ?> tasks/day</span>
        <span><i class="fa-solid fa-coins"></i> <?= money($active['daily_earning']) ?> /day</span>
        <span><i class="fa-solid fa-receipt"></i> Paid <?= money($active['price_paid']) ?></span>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($rePicked > 0): ?>
  <div class="card stagger" style="background:linear-gradient(120deg,rgba(255,91,106,.14),rgba(255,181,71,.06));border:1px dashed rgba(255,91,106,.45);padding:12px 16px" data-testid="re-active-banner">
    <b style="color:#ffd54a">🧧 Red Envelope active:</b>
    <span class="small muted">A <b style="color:#fff"><?= money($rePicked) ?></b> discount will be applied automatically to your next activation or upgrade.</span>
  </div>
<?php endif; ?>

<div class="packages-grid stagger" data-testid="packages-grid">
  <?php foreach ($packages as $p):
    $tier = $p['tier'] ?: 'standard';
    $isFeatured = (int)$p['is_featured'] === 1;
    $isCurrent = $active && (int)$active['package_id'] === (int)$p['id'];

    // ------ Discount + upgrade math ------
    $listPrice = (float)$p['price'];
    // Effective discount (per-package fixed, or session-picked random)
    $reFixed   = 0.0;
    if ($reOn) {
        if ($reMode === 'fixed') {
            $map = red_envelope_discounts();
            $reFixed = (float)($map[(string)$p['id']] ?? $map[$p['id']] ?? 0);
        } elseif ($reMode === 'random' && $rePicked > 0) {
            $reFixed = $rePicked;
        }
    }

    // Upgrade cost (pro-rata): pay the price difference vs currently active row
    $upgradeCost = null;
    $upgradeAvailable = false;
    if ($active && !$isCurrent && $listPrice > (float)$active['price_paid']) {
        $diff = $listPrice - (float)$active['price_paid'];
        $upgradeCost = max(0.0, $diff - $reFixed);
        $upgradeAvailable = true;
    }
    $effectiveActivate = max(0.0, $listPrice - $reFixed);
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
      <?php if ($reFixed > 0 && !$isCurrent): ?>
        <div class="pkg-re-badge" title="Red Envelope discount">
          🧧 −<?= number_format($reFixed) ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="pkg-price">
      <?php if ($reFixed > 0 && !$isCurrent): ?>
        <span class="cur">Rs</span>
        <span class="amt"><?= number_format($effectiveActivate) ?></span>
        <span class="per"><s style="opacity:.55"><?= number_format($listPrice) ?></s> one-time</span>
      <?php else: ?>
        <span class="cur">Rs</span>
        <span class="amt"><?= number_format($listPrice) ?></span>
        <span class="per">one-time</span>
      <?php endif; ?>
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
    <?php elseif ($upgradeAvailable): ?>
      <div class="pkg-upgrade-note" data-testid="pkg-upgrade-note-<?= (int)$p['id'] ?>">
        <i class="fa-solid fa-arrows-up-to-line"></i>
        Pay the difference: <b><?= money($upgradeCost) ?></b>
        <?php if ($reFixed > 0): ?>
          <span class="small muted"> (Rs <?= number_format($listPrice - (float)$active['price_paid']) ?> − 🧧 <?= number_format($reFixed) ?>)</span>
        <?php endif; ?>
      </div>
      <form method="post" onsubmit="return confirm('Upgrade to <?= e($p['name']) ?>?\n\nYou will pay the difference: <?= money($upgradeCost) ?>. Your current plan will be replaced immediately.')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upgrade">
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn pkg-btn" data-testid="pkg-upgrade-<?= (int)$p['id'] ?>">
          <i class="fa-solid fa-rocket"></i> Upgrade — pay <?= money($upgradeCost) ?>
        </button>
      </form>
    <?php elseif ($active): ?>
      <!-- Currently on a plan but this card is cheaper/equal — treat as a fresh activation which replaces the current plan. -->
      <form method="post" onsubmit="return confirm('Switch to <?= e($p['name']) ?> for <?= money($effectiveActivate) ?>?\n\nThis is a full activation, not a pro-rata upgrade.')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn pkg-btn ghost" data-testid="pkg-switch-<?= (int)$p['id'] ?>">
          <i class="fa-solid fa-arrow-right-arrow-left"></i> Switch to <?= e($p['name']) ?>
        </button>
      </form>
    <?php else: ?>
      <form method="post" onsubmit="return confirm('Activate <?= e($p['name']) ?> for <?= money($effectiveActivate) ?>?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="activate">
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn pkg-btn" data-testid="pkg-activate-<?= (int)$p['id'] ?>">
          <i class="fa-solid fa-rocket"></i> Activate — pay <?= money($effectiveActivate) ?>
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
    <li><b>Upgrading</b> to a higher-priced tier only charges you the price difference — pay less, unlock more instantly.</li>
    <li>🧧 Red Envelope discounts, when active, are applied automatically at checkout.</li>
  </ul>
</div>

<style>
.pkg-re-badge{
  display:inline-flex; align-items:center; gap:4px;
  padding:4px 9px; border-radius:999px; font-size:11px; font-weight:800;
  color:#fff; background:linear-gradient(120deg,#ff5b6a,#c81f34);
  box-shadow:0 6px 14px -6px rgba(255,91,106,.55);
  letter-spacing:.3px;
}
.pkg-upgrade-note{
  margin:8px 0 10px; padding:8px 12px; border-radius:10px;
  background:rgba(62,182,255,.08); border:1px solid rgba(62,182,255,.25);
  font-size:12.5px; color:#cdebff;
}
.pkg-upgrade-note b{ color:#fff; }
.pkg-upgrade-note i{ margin-right:4px; color:#3eb6ff; }
.pkg-btn.ghost{ background:transparent; color:var(--txt); border:1px solid rgba(255,255,255,.14); }
</style>
