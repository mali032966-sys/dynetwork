<div class="topbar">
  <div class="greet"><b>Deposit Funds</b><div class="small muted">JazzCash · EasyPaisa · Bank · Step <?= (int)$step ?> of 3</div></div>
  <a href="<?= route_url('wallet') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<!-- Stepper indicator -->
<div class="stepper" data-testid="deposit-stepper">
  <?php for ($i=1; $i<=3; $i++):
    $titles = [1=>'Amount', 2=>'Pay To', 3=>'Confirm'];
    $cls = $i < $step ? 'done' : ($i == $step ? 'active' : '');
  ?>
    <div class="step <?= $cls ?>" data-testid="step-indicator-<?= $i ?>">
      <div class="dot"><?= $i < $step ? '<i class="fa-solid fa-check"></i>' : $i ?></div>
      <div class="lbl"><?= $titles[$i] ?></div>
    </div>
    <?php if ($i<3): ?><div class="bar <?= $i < $step ? 'done' : '' ?>"></div><?php endif; ?>
  <?php endfor; ?>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert error" data-testid="deposit-error">
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<?php if ($step === 1): ?>
  <!-- =================== STEP 1: amount + method =================== -->

  <?php if (!empty($envelopeClaim) && (float)$envelopeAmt > 0): ?>
    <div class="card stagger re-banner" data-testid="deposit-re-banner">
      <div class="re-banner-ic"><i class="fa-solid fa-gift"></i></div>
      <div class="re-banner-body">
        <b>🧧 Red Envelope active — <?= money($envelopeAmt) ?> off this deposit</b>
        <div class="small muted">You'll pay <b style="color:#ffd54a">(your deposit amount − <?= money($envelopeAmt) ?>)</b>. Your wallet will still be credited the <b>full amount</b> once admin approves.</div>
      </div>
    </div>
  <?php endif; ?>
  <form method="post" class="card stagger" data-testid="deposit-step-1">
    <?= csrf_field() ?>
    <input type="hidden" name="step" value="1">
    <h3 style="margin:0 0 4px;font-size:16px">Enter Amount & Method</h3>
    <div class="small muted" style="margin-bottom:14px">How much would you like to deposit?</div>

    <div class="form-group">
      <label>Amount (PKR)</label>
      <input class="input" type="number" name="amount" min="100" step="1"
             placeholder="e.g. 1000"
             value="<?= e($wizard['amount'] ?? '') ?>" required
             data-testid="deposit-amount" autofocus>
    </div>

    <div class="form-group">
      <label>Payment Method</label>
      <div class="pm-grid" data-testid="deposit-method-grid">
        <?php if (!$methods): ?>
          <div class="muted small">No active payment methods. Contact support.</div>
        <?php else: foreach ($methods as $m):
          // Match by method ID (stable across admin renames). Fall back to
          // case-insensitive name match for legacy wizard rows.
          $wid     = (int)($wizard['method_id'] ?? 0);
          $isMatch = $wid > 0
              ? ((int)$m['id'] === $wid)
              : (strcasecmp($m['name'], (string)($wizard['method'] ?? '')) === 0);
          $checked = $isMatch ? 'checked' : '';
          // Mask the long account number in the card preview so it stays
          // readable while still hinting WHICH account this is when admin
          // adds multiple JazzCash / EasyPaisa entries.
          $acct    = (string)($m['account_number'] ?? '');
          $tail    = $acct !== '' ? '••••' . substr($acct, -4) : '';
        ?>
          <label class="pm-card" data-testid="pm-radio-<?= (int)$m['id'] ?>">
            <input type="radio" name="method_id" value="<?= (int)$m['id'] ?>" <?= $checked ?> required>
            <div class="pm-card-inner">
              <?= payment_logo_html($m['name'], 'md') ?>
              <div>
                <b><?= e($m['name']) ?></b>
                <div class="small muted">
                  <?= e($m['account_title'] ?: 'Tap to select') ?>
                  <?php if ($tail !== ''): ?> · <?= e($tail) ?><?php endif; ?>
                </div>
              </div>
              <div class="pm-check"><i class="fa-solid fa-circle-check"></i></div>
            </div>
          </label>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <div class="wizard-actions">
      <a href="<?= route_url('wallet') ?>" class="btn ghost" data-testid="deposit-cancel">Cancel</a>
      <button type="submit" name="action" value="next" class="btn" data-testid="deposit-next-1">
        Next <i class="fa-solid fa-arrow-right" style="margin-left:6px"></i>
      </button>
    </div>
  </form>

<?php elseif ($step === 2): ?>
  <!-- =================== STEP 2: show payment details =================== -->
  <div class="card stagger" data-testid="deposit-step-2">
    <?php $eff = (float)$payAmount; $env = (float)$envelopeAmt; $wallet = (float)$wizard['amount']; ?>
    <h3 style="margin:0 0 4px;font-size:16px">Send Payment</h3>
    <div class="small muted" style="margin-bottom:14px">
      Send <b style="color:var(--cyan)"><?= money($eff) ?></b> via
      <b style="color:var(--cyan)"><?= e($wizard['method']) ?></b> to the account below.
    </div>

    <?php if ($env > 0): ?>
      <div class="re-summary" data-testid="deposit-re-summary">
        <div class="re-summary-row"><span>Requested deposit</span><b><?= money($wallet) ?></b></div>
        <div class="re-summary-row"><span>🧧 Red Envelope discount</span><b style="color:#ffd54a">−<?= money($env) ?></b></div>
        <div class="re-summary-row big"><span>You pay now</span><b><?= money($eff) ?></b></div>
        <div class="re-summary-row"><span>Wallet will receive</span><b style="color:#10b981"><?= money($wallet) ?></b></div>
      </div>
    <?php endif; ?>

    <?php if ($selected): ?>
      <div class="pm-detail" data-testid="pm-detail">
        <div class="pm-detail-row">
          <?= payment_logo_html($selected['name'], 'md') ?>
          <div style="flex:1">
            <div class="small muted">Account Title</div>
            <b data-testid="pm-account-title"><?= e($selected['account_title']) ?></b>
          </div>
        </div>

        <div class="account-number-box">
          <div class="small muted">Account Number — tap to copy</div>
          <div class="account-number" data-copy="<?= e($selected['account_number']) ?>"
               data-testid="pm-account-number">
            <span><?= e($selected['account_number']) ?></span>
            <button type="button" data-copy="<?= e($selected['account_number']) ?>" data-testid="copy-account-number">
              <i class="fa-solid fa-copy"></i> Copy
            </button>
          </div>
        </div>

        <div class="amount-box">
          <div class="small muted">Amount to send<?= $env > 0 ? ' (after Red Envelope discount)' : '' ?></div>
          <div class="amount-big" data-testid="pm-amount-display"><?= money($eff) ?></div>
          <?php if ($env > 0): ?>
            <div class="small muted" style="margin-top:6px">
              <s><?= money($wallet) ?></s> · you save <?= money($env) ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($selected['instructions'])): ?>
          <div class="instructions">
            <div class="small muted" style="text-transform:uppercase;letter-spacing:1.2px;margin-bottom:6px">
              <i class="fa-solid fa-circle-info"></i> Instructions
            </div>
            <div data-testid="pm-instructions"><?= nl2br(e($selected['instructions'])) ?></div>
          </div>
        <?php endif; ?>

        <ol class="howto" data-testid="howto-list">
          <li>Open your <b><?= e($selected['name']) ?></b> mobile app.</li>
          <li>Choose <b>Send Money</b> and paste the account number above.</li>
          <li>Enter the exact amount: <b><?= money($eff) ?></b><?= $env > 0 ? ' <span class="small muted">(discounted from '.money($wallet).')</span>' : '' ?></li>
          <li>Complete the transaction and <b>save the Transaction ID (TID)</b> from the confirmation SMS.</li>
          <li>Take a screenshot of the success page — you'll upload it in the next step.</li>
        </ol>
      </div>
    <?php else: ?>
      <div class="alert error">Selected payment method is no longer available.</div>
    <?php endif; ?>

    <form method="post" class="wizard-actions">
      <?= csrf_field() ?>
      <input type="hidden" name="step" value="2">
      <button type="submit" name="action" value="back" class="btn ghost" data-testid="deposit-back-2">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px"></i> Back
      </button>
      <button type="submit" name="action" value="next" class="btn" data-testid="deposit-next-2">
        I've Paid · Next <i class="fa-solid fa-arrow-right" style="margin-left:6px"></i>
      </button>
    </form>
  </div>

<?php elseif ($step === 3): ?>
  <!-- =================== STEP 3: TID + screenshot =================== -->
  <form method="post" enctype="multipart/form-data" class="card stagger" data-testid="deposit-step-3">
    <?= csrf_field() ?>
    <input type="hidden" name="step" value="3">
    <h3 style="margin:0 0 4px;font-size:16px">Submit Proof of Payment</h3>
    <div class="small muted" style="margin-bottom:14px">
      Paid <b style="color:var(--cyan)"><?= money($payAmount ?? $wizard['amount']) ?></b> via <b><?= e($wizard['method']) ?></b>?
      <?php if (!empty($envelopeAmt) && (float)$envelopeAmt > 0): ?>
        <br><span class="small" style="color:#ffd54a">
          🧧 Your wallet will be credited the full <b><?= money($wizard['amount']) ?></b> once admin approves.
        </span>
      <?php endif; ?>
      Enter the TID and upload the screenshot.
    </div>

    <div class="form-group">
      <label>Transaction ID (TID)</label>
      <input class="input" type="text" name="transaction_id" required
             placeholder="e.g. 1234567890ABC" autofocus
             data-testid="deposit-tid">
    </div>

    <div class="form-group">
      <label>Your Sender Account (optional)</label>
      <input class="input" type="text" name="sender_account"
             placeholder="03001234567"
             data-testid="deposit-sender">
    </div>

    <div class="form-group">
      <label>Payment Screenshot <span class="muted">(JPG, PNG, WEBP · max 5 MB)</span></label>
      <label for="screenshot-input" class="upload-drop" data-testid="screenshot-drop">
        <div class="upload-ico"><i class="fa-solid fa-cloud-arrow-up"></i></div>
        <div><b>Tap to upload screenshot</b></div>
        <div class="small muted">Required for fast approval</div>
        <div class="upload-preview" id="ss-preview"></div>
      </label>
      <input id="screenshot-input" type="file" name="screenshot" accept="image/jpeg,image/png,image/webp"
             style="display:none" data-testid="deposit-screenshot">
    </div>

    <div class="wizard-actions">
      <button type="submit" name="action" value="back" class="btn ghost" formnovalidate data-testid="deposit-back-3">
        <i class="fa-solid fa-arrow-left" style="margin-right:6px"></i> Back
      </button>
      <button type="submit" name="action" value="submit" class="btn" data-testid="deposit-submit">
        Submit Request
      </button>
    </div>
  </form>
  <script>
    (function(){
      var inp = document.getElementById('screenshot-input');
      var pv  = document.getElementById('ss-preview');
      if (!inp || !pv) return;
      inp.addEventListener('change', function(){
        pv.innerHTML = '';
        var f = inp.files && inp.files[0];
        if (!f) return;
        var img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.alt = 'preview';
        pv.appendChild(img);
        var caption = document.createElement('div');
        caption.className = 'small muted';
        caption.style.marginTop = '6px';
        caption.textContent = f.name + ' · ' + (f.size/1024).toFixed(1) + ' KB';
        pv.appendChild(caption);
      });
    })();
  </script>
<?php endif; ?>

<div class="list-title"><h3>Deposit History</h3></div>
<div class="card" data-testid="deposit-history">
<?php if (!$history): ?>
  <div class="empty">No deposits yet.</div>
<?php else: foreach ($history as $h): ?>
  <div class="activity deposit">
    <div class="ico"><i class="fa-solid fa-circle-down"></i></div>
    <div class="meta">
      <b><?= e($h['method']) ?> — <?= money($h['amount']) ?></b>
      <small>TID: <?= e($h['transaction_id']) ?> · <?= e(date('M d, H:i', strtotime($h['created_at']))) ?></small>
    </div>
    <span class="badge <?= e($h['status']) ?>"><?= e($h['status']) ?></span>
  </div>
<?php endforeach; endif; ?>
</div>

<style>
/* 🧧 Red Envelope banner + summary on the deposit page */
.re-banner{
  display:flex; gap:12px; align-items:center;
  padding:12px 14px; margin-bottom:12px;
  background:linear-gradient(120deg, rgba(255,91,106,.14), rgba(255,181,71,.06));
  border:1px solid rgba(255,91,106,.35);
}
.re-banner-ic{
  width:38px; height:38px; border-radius:12px; flex-shrink:0;
  display:grid; place-items:center; color:#fff; font-size:16px;
  background:linear-gradient(135deg, #ff5b6a, #c81f34);
}
.re-banner-body{ flex:1; min-width:0; line-height:1.5; }
.re-summary{
  padding:14px 16px; margin:-4px 0 14px;
  background:linear-gradient(160deg, rgba(255,91,106,.09), rgba(255,181,71,.05));
  border:1px dashed rgba(255,181,71,.45); border-radius:14px;
}
.re-summary-row{
  display:flex; justify-content:space-between; align-items:baseline; gap:8px;
  padding:6px 0; font-size:13.5px; color:var(--txt-mute, #cbd5e1);
}
.re-summary-row b{ color:#fff; font-size:14px; }
.re-summary-row + .re-summary-row{ border-top:1px solid rgba(255,255,255,.05); }
.re-summary-row.big{ font-size:15px; }
.re-summary-row.big b{ font-size:20px; color:#ffd54a; }
@media (max-width:520px){
  .re-summary-row.big b{ font-size:18px; }
}
</style>
