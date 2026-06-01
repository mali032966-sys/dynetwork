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
$teams = [
  ['title'=>'Team A — Level 1','badge'=>'L1','pct'=>$percents['L1'],'members'=>$teamA,'earn'=>$earnA],
  ['title'=>'Team B — Level 2','badge'=>'L2','pct'=>$percents['L2'],'members'=>$teamB,'earn'=>$earnB],
  ['title'=>'Team C — Level 3','badge'=>'L3','pct'=>$percents['L3'],'members'=>$teamC,'earn'=>$earnC],
];
foreach ($teams as $i => $t):
?>
<div class="card team-card stagger" data-testid="team-card-<?= e($t['badge']) ?>">
  <h4><?= e($t['title']) ?> <span class="lvl-badge"><?= e($t['pct']) ?>%</span></h4>
  <div class="meta">
    <span><?= count($t['members']) ?> Members</span>
    <span>Earnings: <b style="color:var(--green)"><?= money($t['earn']) ?></b></span>
  </div>
  <div class="avatars">
    <?php $shown = array_slice($t['members'], 0, 5); foreach ($shown as $m):
      $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $m['name'] ?: $m['whatsapp']), 0, 2)); ?>
      <div class="avatar" title="<?= e($m['name'] ?: $m['whatsapp']) ?>"><?= e($initials ?: '??') ?></div>
    <?php endforeach;
    $more = count($t['members']) - count($shown); if ($more > 0): ?>
      <div class="avatar more">+<?= (int)$more ?></div>
    <?php endif; ?>
    <?php if (!$t['members']): ?>
      <div class="small muted">No members at this level yet.</div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
