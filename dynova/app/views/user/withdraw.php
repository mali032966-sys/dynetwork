<?php
// Default slabs match TaskPackage::defaultLadder() so this view still works
// even if it's deployed against an older controller that doesn't pass $slabs.
$slabs   = !empty($slabs) ? $slabs : [1500, 7000, 15000, 35000, 100000, 200000];
$balance = (float)($u['balance'] ?? 0);
$isLocked      = !empty($isLocked);
$lockedUntilTs = (int)($lockedUntilTs ?? 0);
$remaining = $isLocked ? max(0, $lockedUntilTs - time()) : 0;
$lockH     = floor($remaining / 3600);
$lockM     = floor(($remaining % 3600) / 60);
?>
<div class="topbar">
  <div class="greet">
    <b>Withdraw Funds</b>
    <div class="small muted">Pick one of the allowed amounts below</div>
  </div>
  <a href="<?= route_url('wallet') ?>" class="bell" data-testid="withdraw-back">
    <i class="fa-solid fa-arrow-left"></i>
  </a>
</div>

<div class="card stagger">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:1.4px">Available to withdraw</div>
  <div class="balance-amount" data-testid="withdraw-available"><?= money($balance) ?></div>
</div>

<?php if ($isLocked): ?>
  <div class="card stagger wd-lock" data-testid="wd-lock-card"
       data-unlock-at="<?= (int)$lockedUntilTs ?>">
    <div class="wd-lock-ic"><i class="fa-solid fa-clock-rotate-left"></i></div>
    <div class="wd-lock-body">
      <b>Withdrawal locked</b>
      <div class="small muted" id="wdLockMsg">
        You can only withdraw once per 24 hours. Please try again in
        <b id="wdLockRem"><?= $lockH ?> hour<?= $lockH===1?'':'s' ?> <?= $lockM ?> minute<?= $lockM===1?'':'s' ?></b>.
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert error" data-testid="withdraw-error">
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="card stagger <?= $isLocked ? 'wd-form-locked' : '' ?>"
      data-testid="withdraw-form" id="withdraw-form"
      onsubmit="<?= $isLocked ? "alert('Withdrawal is locked. Please try again after the countdown completes.'); return false;" : "" ?>">
  <?= csrf_field() ?>

  <fieldset <?= $isLocked ? 'disabled aria-disabled="true"' : '' ?> style="border:0;padding:0;margin:0">
    <div class="form-group">
      <label>Payment Method</label>
      <div class="pm-grid" data-testid="withdraw-method-grid">
        <?php foreach ($methods as $m):
          $checked = (($_POST['method'] ?? '') === $m['name']) ? 'checked' : '';
        ?>
          <label class="pm-card" data-testid="wd-pm-<?= e($m['name']) ?>">
            <input type="radio" name="method" value="<?= e($m['name']) ?>" <?= $checked ?> required>
            <div class="pm-card-inner">
              <?= payment_logo_html($m['name'], 'md') ?>
              <div>
                <b><?= e($m['name']) ?></b>
                <div class="small muted">Tap to select</div>
              </div>
              <div class="pm-check"><i class="fa-solid fa-circle-check"></i></div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group">
      <label>Select Withdrawal Amount</label>
      <div class="small muted" style="text-transform:none;letter-spacing:.2px;font-weight:400;margin:-4px 0 10px">
        Tap any amount you want to withdraw. You can withdraw the same amount any number of times as long as you have enough balance.
      </div>
      <div class="slab-grid" data-testid="withdraw-slab-grid">
        <?php foreach ($slabs as $amt):
          $amt = (int)$amt;
        ?>
          <label class="slab-card"
                 data-testid="wd-slab-<?= $amt ?>"
                 data-amount="<?= $amt ?>">
            <input type="radio" name="amount" value="<?= $amt ?>"
                   required
                   data-testid="wd-slab-input-<?= $amt ?>">
            <div class="slab-card-inner">
              <div class="slab-amt"><?= number_format($amt) ?></div>
              <div class="slab-meta"><i class="fa-solid fa-circle-check"></i> Available</div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="form-group">
      <label>Account Number</label>
      <input class="input" type="text" name="account_number" required
             data-testid="withdraw-account-number">
    </div>
    <div class="form-group">
      <label>Account Title</label>
      <input class="input" type="text" name="account_title" required
             data-testid="withdraw-account-title">
    </div>
  </fieldset>

  <button class="btn <?= $isLocked ? 'is-disabled' : '' ?>" type="submit"
          <?= $isLocked ? 'disabled' : '' ?> data-testid="withdraw-submit">
    <?php if ($isLocked): ?>
      ⏳ Withdrawals locked — try again in <span id="wdBtnRem"><?= $lockH ?>h <?= $lockM ?>m</span>
    <?php else: ?>
      Request Withdrawal
    <?php endif; ?>
  </button>
</form>

<div class="list-title"><h3>Withdrawal History</h3></div>
<div class="card" data-testid="withdraw-history">
<?php if (!$history): ?>
  <div class="empty">No withdrawals yet.</div>
<?php else: foreach ($history as $h): ?>
  <div class="activity withdrawal">
    <div class="ico"><i class="fa-solid fa-circle-up"></i></div>
    <div class="meta">
      <b><?= e($h['method']) ?> — <?= money($h['amount']) ?></b>
      <small><?= e($h['account_number']) ?> · <?= e(date('M d, H:i', strtotime($h['created_at']))) ?></small>
    </div>
    <span class="badge <?= e($h['status']) ?>"><?= e($h['status']) ?></span>
  </div>
<?php endforeach; endif; ?>
</div>

<style>
/* ------- One-click slab cards (compact, mobile-first) ------- */
.slab-grid{
  display:grid;
  grid-template-columns:repeat(3, minmax(0,1fr));
  gap:8px;
}
@media (min-width:520px){
  .slab-grid{ grid-template-columns:repeat(auto-fill, minmax(120px, 1fr)); gap:10px; }
}
.slab-card{ position:relative; cursor:pointer; display:block; }
.slab-card input[type=radio]{
  position:absolute;opacity:0;pointer-events:none;
}
.slab-card-inner{
  border:1.25px solid rgba(255,255,255,.10);
  background:rgba(255,255,255,.03);
  border-radius:11px;
  padding:9px 6px;
  text-align:center;
  transition:transform .12s ease, border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.slab-card:hover .slab-card-inner{
  transform:translateY(-1px);
  border-color:rgba(141,91,255,.45);
  background:rgba(141,91,255,.06);
}
.slab-card input:checked + .slab-card-inner{
  border-color:rgba(62,182,255,.85);
  background:linear-gradient(120deg, rgba(62,182,255,.16), rgba(141,91,255,.16));
  box-shadow:0 6px 16px -8px rgba(62,182,255,.55), 0 0 0 1px rgba(62,182,255,.35) inset;
}
.slab-amt{
  font-size:13px;font-weight:800;color:var(--txt,#fff);letter-spacing:.1px;
  line-height:1.15;white-space:nowrap;
}
@media (min-width:520px){ .slab-amt{ font-size:15px; } }
.slab-meta{
  margin-top:4px;font-size:9.5px;color:#10b981;font-weight:700;
  display:flex;align-items:center;justify-content:center;gap:4px;
  letter-spacing:.2px;text-transform:uppercase;
}
@media (min-width:520px){ .slab-meta{ font-size:10.5px; gap:5px; } }
.slab-meta i{ font-size:9px; }

/* Withdrawal lock UI */
.wd-lock{
  display:flex; gap:12px; align-items:center;
  border:1px solid rgba(255,181,71,.35);
  background:linear-gradient(120deg,rgba(255,181,71,.10),rgba(255,91,106,.06));
}
.wd-lock-ic{
  width:42px;height:42px;flex-shrink:0;border-radius:12px;
  display:grid;place-items:center;font-size:18px;color:#ffb547;
  background:rgba(255,181,71,.15);
}
.wd-lock-body b{ display:block;font-size:14px;color:#fff;margin-bottom:2px }
.wd-form-locked{ position:relative; }
.wd-form-locked::after{
  content:""; position:absolute; inset:0;
  background:rgba(4,7,15,.35); border-radius:inherit;
  pointer-events:none;
}
.wd-form-locked fieldset[disabled]{ opacity:.5; filter:grayscale(.3); }
.btn.is-disabled{
  background:linear-gradient(120deg,#4a5062,#3a3f4d) !important;
  color:#cbd5e1 !important; cursor:not-allowed;
  box-shadow:none !important;
}
</style>

<script>
(function(){
  var el = document.querySelector('[data-testid="wd-lock-card"]');
  if (!el) return;
  var until = parseInt(el.getAttribute('data-unlock-at') || '0', 10) * 1000;
  if (!until) return;
  var rem = document.getElementById('wdLockRem');
  var btn = document.getElementById('wdBtnRem');
  function tick(){
    var ms = until - Date.now();
    if (ms <= 0){ location.reload(); return; }
    var h = Math.floor(ms/3600000);
    var m = Math.floor((ms%3600000)/60000);
    if (rem) rem.textContent = h + ' hour' + (h===1?'':'s') + ' ' + m + ' minute' + (m===1?'':'s');
    if (btn) btn.textContent = h + 'h ' + m + 'm';
  }
  tick(); setInterval(tick, 30000);
})();
</script>
