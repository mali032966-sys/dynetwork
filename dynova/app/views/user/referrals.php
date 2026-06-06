<div class="topbar">
  <div class="greet"><b>Referral Program</b><div class="small muted">Earn from 3 levels of referrals</div></div>
  <a href="<?= route_url('dashboard') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<div class="card stagger" data-testid="referral-link-card">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:1.4px;margin-bottom:8px">Your Referral Link</div>
  <div class="ref-link">
    <code data-testid="referral-link"><?= e($refUrl) ?></code>
    <button type="button" data-copy="<?= e($refUrl) ?>" data-testid="copy-referral"><i class="fa-solid fa-copy"></i></button>
  </div>
  <div class="small muted" style="margin-top:10px">
    Code: <b style="color:var(--cyan)"><?= e($u['referral_code']) ?></b> &middot;
    L1: <?= e($percents['L1']) ?>%, L2: <?= e($percents['L2']) ?>%, L3: <?= e($percents['L3']) ?>%
  </div>
</div>

<div class="period-toggle" data-testid="period-toggle">
  <a href="<?= route_url('referrals', ['period'=>'daily']) ?>"  class="<?= $period==='daily'?'active':'' ?>"  data-testid="period-daily">Daily</a>
  <a href="<?= route_url('referrals', ['period'=>'weekly']) ?>" class="<?= $period==='weekly'?'active':'' ?>" data-testid="period-weekly">Weekly</a>
  <a href="<?= route_url('referrals', ['period'=>'yearly']) ?>" class="<?= $period==='yearly'?'active':'' ?>" data-testid="period-yearly">Yearly</a>
</div>

<div class="card stagger" style="text-align:center" data-testid="total-referral-earn">
  <div class="small muted" style="text-transform:uppercase;letter-spacing:1.4px">Total earned (<?= e($period) ?>)</div>
  <div style="font-size:30px;font-weight:800;margin-top:6px" class="balance-amount"><?= money($earnTotal) ?></div>
</div>

<?php
// Mask a WhatsApp number so we never leak full contacts inside the downline view.
// Keeps the country dialer + last 3 digits, masks the middle.  Example:
//    03001234567  ->  0300***4567
function ref_mask_phone(string $p): string {
    $p = preg_replace('/\D+/', '', $p) ?? '';
    $n = strlen($p);
    if ($n <= 6) return str_repeat('•', max(0, $n - 2)) . substr($p, -2);
    return substr($p, 0, 4) . str_repeat('•', max(0, $n - 7)) . substr($p, -3);
}

$teams = [
  ['title'=>'Level 1 — Team A','badge'=>'L1','pct'=>$percents['L1'],'members'=>$teamA,'earn'=>$earnA],
  ['title'=>'Level 2 — Team B','badge'=>'L2','pct'=>$percents['L2'],'members'=>$teamB,'earn'=>$earnB],
  ['title'=>'Level 3 — Team C','badge'=>'L3','pct'=>$percents['L3'],'members'=>$teamC,'earn'=>$earnC],
];
foreach ($teams as $t):
  $count = count($t['members']);
?>
<div class="card team-card stagger" data-testid="team-card-<?= e($t['badge']) ?>">
  <h4>
    <?= e($t['title']) ?>
    <span class="lvl-badge"><?= e($t['pct']) ?>%</span>
  </h4>
  <div class="meta">
    <span><b data-testid="team-count-<?= e($t['badge']) ?>"><?= (int)$count ?></b> Members</span>
    <span>Earnings: <b style="color:var(--green)"><?= money($t['earn']) ?></b></span>
  </div>

  <?php if ($count === 0): ?>
    <div class="ref-empty" data-testid="team-empty-<?= e($t['badge']) ?>">
      <i class="fa-solid fa-user-plus"></i>
      No members at this level yet. Share your link to start growing.
    </div>
  <?php else: ?>
    <!-- Member list with details (name, whatsapp, joined) -->
    <div class="ref-members" data-testid="team-list-<?= e($t['badge']) ?>">
      <?php $idx = 0; foreach ($t['members'] as $m): $idx++;
        $name      = trim((string)($m['name'] ?? ''));
        $wa        = (string)($m['whatsapp'] ?? '');
        $display   = $name !== '' ? $name : 'Member #' . (int)$m['id'];
        $initials  = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name ?: $wa) ?? '', 0, 2)) ?: '??';
        $joined    = !empty($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : '';
      ?>
        <div class="ref-row" data-testid="team-row-<?= e($t['badge']) ?>-<?= (int)$m['id'] ?>">
          <div class="ref-row-no"><?= (int)$idx ?></div>
          <div class="avatar"><?= e($initials) ?></div>
          <div class="ref-row-meta">
            <b class="ref-row-name"><?= e($display) ?></b>
            <span class="small muted">
              <i class="fa-brands fa-whatsapp"></i> <?= e(ref_mask_phone($wa)) ?>
              <?php if ($joined !== ''): ?>
                <span class="dot-sep">·</span>
                <i class="fa-regular fa-calendar"></i> Joined <?= e($joined) ?>
              <?php endif; ?>
            </span>
          </div>
          <div class="ref-row-id small muted">#<?= (int)$m['id'] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<style>
/* Detailed member list inside each team card */
.ref-members{
  display:flex; flex-direction:column; gap:8px;
  margin-top:14px;
}
.ref-row{
  display:flex; align-items:center; gap:10px;
  padding:10px 12px;
  border:1px solid rgba(255,255,255,.08);
  background:rgba(255,255,255,.025);
  border-radius:12px;
  transition:transform .12s ease, border-color .15s ease, background .15s ease;
}
.ref-row:hover{
  transform:translateX(2px);
  border-color:rgba(141,91,255,.35);
  background:rgba(141,91,255,.05);
}
.ref-row-no{
  width:22px; height:22px; flex:0 0 22px;
  display:flex; align-items:center; justify-content:center;
  font-size:11px; font-weight:700; color:var(--txt-mute, #94a3b8);
  background:rgba(255,255,255,.05); border-radius:50%;
}
.ref-row .avatar{
  width:34px; height:34px; flex:0 0 34px;
  border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:12px; font-weight:800; color:#fff;
  background:linear-gradient(135deg, #3eb6ff 0%, #8d5bff 100%);
  letter-spacing:.5px;
}
.ref-row-meta{ flex:1; min-width:0; display:flex; flex-direction:column; gap:2px; }
.ref-row-name{
  font-size:14px; color:var(--txt, #fff);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.ref-row-meta .small{ display:inline-flex; align-items:center; gap:4px; font-size:11.5px; flex-wrap:wrap; }
.ref-row-meta .small i{ font-size:11px; opacity:.85; }
.dot-sep{ opacity:.55; margin:0 2px; }
.ref-row-id{ font-size:11px; opacity:.6; }

.ref-empty{
  margin-top:12px; padding:14px;
  border:1px dashed rgba(255,255,255,.12);
  border-radius:12px;
  text-align:center; color:var(--txt-mute, #94a3b8);
  font-size:13px;
}
.ref-empty i{ display:block; font-size:22px; margin-bottom:6px; opacity:.7; }
</style>
