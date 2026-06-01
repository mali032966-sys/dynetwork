<?php
// Default slabs match TaskPackage::defaultLadder() so this view still works
// even if it's deployed against an older controller that doesn't pass $slabs.
$slabs      = !empty($slabs) ? $slabs : [1500, 7000, 15000, 35000, 100000, 200000];
$balance    = (float)($u['balance'] ?? 0);
$minSlab    = (float)($minSlab ?? ($slabs[0] ?? 0));
$canAnything= $balance >= $minSlab;
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

<?php if (!empty($errors)): ?>
  <div class="alert error" data-testid="withdraw-error">
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if (!$canAnything): ?>
  <div class="card stagger" data-testid="withdraw-blocked" style="text-align:center;padding:22px 18px">
    <i class="fa-solid fa-circle-info" style="font-size:34px;color:var(--blue,#60a5fa);margin-bottom:8px"></i>
    <div style="font-size:16px;font-weight:700;margin-bottom:4px">You can't withdraw yet</div>
    <div class="small muted" style="line-height:1.5">
      The smallest allowed withdrawal is <b><?= money($minSlab) ?></b>.
      You need <b><?= money($minSlab - $balance) ?></b> more before you can submit a request.
    </div>
  </div>
<?php endif; ?>

<form method="post" class="card stagger" data-testid="withdraw-form" id="withdraw-form">
  <?= csrf_field() ?>

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

  <!-- One-click slab picker -->
  <div class="form-group">
    <label>Select Withdrawal Amount</label>
    <div class="small muted" style="text-transform:none;letter-spacing:.2px;font-weight:400;margin:-4px 0 10px">
      Tap one of the allowed amounts. Greyed-out options need more balance.
    </div>
    <div class="slab-grid" data-testid="withdraw-slab-grid">
      <?php foreach ($slabs as $i => $amt):
        $amt        = (int)$amt;
        $affordable = $balance >= $amt;
      ?>
        <label class="slab-card <?= $affordable ? '' : 'is-disabled' ?>"
               data-testid="wd-slab-<?= $amt ?>"
               data-amount="<?= $amt ?>">
          <input type="radio" name="amount" value="<?= $amt ?>"
                 <?= $affordable ? '' : 'disabled' ?>
                 required
                 data-testid="wd-slab-input-<?= $amt ?>">
          <div class="slab-card-inner">
            <div class="slab-amt"><?= number_format($amt) ?></div>
            <?php if ($affordable): ?>
              <div class="slab-meta"><i class="fa-solid fa-circle-check"></i> Available</div>
            <?php else: ?>
              <div class="slab-meta locked">
                <i class="fa-solid fa-lock"></i> Locked
              </div>
            <?php endif; ?>
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

  <button class="btn" type="submit" data-testid="withdraw-submit"
          <?= $canAnything ? '' : 'disabled' ?>>
    Request Withdrawal
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
.slab-card:hover:not(.is-disabled) .slab-card-inner{
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
.slab-meta.locked{ color:#fbbf24; text-transform:none; letter-spacing:0; font-weight:600; }
.slab-card.is-disabled{ cursor:not-allowed; opacity:.55; }
.slab-card.is-disabled .slab-card-inner{
  border-style:dashed;
  background:rgba(255,255,255,.02);
  padding:9px 4px;
}
.slab-card.is-disabled:hover .slab-card-inner{ transform:none; }
</style>
