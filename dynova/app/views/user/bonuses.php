<div class="topbar topbar-flex">
  <div class="page-head">
    <h2 class="page-title" data-testid="page-title">Joining Bonus</h2>
    <div class="page-sub">Invite friends and earn a one-time welcome bonus the moment they activate their first package.</div>
  </div>
  <a href="<?= route_url('referrals') ?>" class="bell" data-testid="page-bell">
    <i class="fa-solid fa-user-plus"></i>
  </a>
</div>

<?php if (!$bonuses): ?>
  <div class="card empty">No bonus tiers are active yet. Please check back soon.</div>
<?php else: ?>
  <div class="bonus-grid stagger" data-testid="user-bonuses-grid"
       style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
    <?php foreach ($bonuses as $b):
      $tier = $b['tier'] ?: 'standard';
    ?>
    <div class="card bonus-card <?= e($tier) ?>" data-testid="bonus-<?= e(strtolower($b['pkg_name'])) ?>"
         style="padding:18px 18px 16px;display:flex;flex-direction:column;gap:12px">
      <div class="flex" style="justify-content:space-between;align-items:flex-start;gap:8px">
        <div>
          <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase"><?= e(strtoupper($tier)) ?> · Package</div>
          <h3 style="margin:2px 0 0;font-size:20px"><?= e($b['pkg_name']) ?></h3>
        </div>
        <div style="text-align:right">
          <div class="small muted" style="letter-spacing:1.2px;text-transform:uppercase">Price</div>
          <div style="font-size:18px;font-weight:800"><?= money($b['price']) ?></div>
        </div>
      </div>

      <div class="bonus-rows" style="display:grid;gap:8px">
        <div class="bonus-row" style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:rgba(96,165,250,.08);border:1px solid rgba(96,165,250,.18);border-radius:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="fa-solid fa-user-plus" style="color:#60a5fa"></i>
            <span>Inviter gets</span>
          </div>
          <b style="color:#60a5fa;font-size:16px"><?= money($b['referrer_bonus']) ?></b>
        </div>
        <div class="bonus-row" style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:12px">
          <div style="display:flex;align-items:center;gap:8px">
            <i class="fa-solid fa-gift" style="color:#10b981"></i>
            <span>You get (on join)</span>
          </div>
          <b style="color:#10b981;font-size:16px"><?= money($b['invitee_bonus']) ?></b>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card mt" style="margin-top:18px">
  <h3 style="margin:0 0 6px;font-size:15px">
    <i class="fa-solid fa-circle-info" style="color:var(--blue)"></i> How the joining bonus works
  </h3>
  <ul style="margin:6px 0 0;padding-left:20px;color:var(--txt-mute);font-size:13px;line-height:1.7">
    <li>Share your referral link from the <a href="<?= route_url('referrals') ?>">Referrals</a> page.</li>
    <li>When your invitee signs up and activates <b>their first package</b>, the bonus is credited automatically.</li>
    <li>The bonus depends on which package the invitee activates — higher tier = bigger bonus for both of you.</li>
    <li>You receive the inviter bonus into your <b>referral earnings</b>; your invitee receives theirs into their wallet balance.</li>
    <li>The welcome bonus is a one-time credit per user (paid on their <i>first ever</i> package activation).</li>
  </ul>
</div>
