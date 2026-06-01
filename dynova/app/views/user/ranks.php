<div class="topbar topbar-flex">
  <div class="page-head">
    <h2 class="page-title" data-testid="page-title">Salary Ranks</h2>
    <div class="page-sub">Build your team across three levels. Hit the targets. Earn a fixed monthly salary on top of everything else.</div>
  </div>
  <a href="<?= route_url('profile') ?>" class="bell" data-testid="page-bell">
    <i class="fa-solid fa-bell"></i><span class="dot"></span>
  </a>
</div>

<!-- Your current snapshot -->
<div class="card stagger" data-testid="ranks-snapshot" style="margin-bottom:18px">
  <div class="flex" style="justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
    <div>
      <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Your current rank</div>
      <h3 style="margin:4px 0 0;font-size:22px">
        <?php if ($currentRank): ?>
          <?= e($currentRank['emoji']) ?> <?= e($currentRank['name']) ?>
          <small style="opacity:.7;font-size:13px"> · <?= money($currentRank['monthly_salary']) ?>/month</small>
        <?php else: ?>
          <span style="color:var(--txt-mute)">Unranked yet</span>
        <?php endif; ?>
      </h3>
    </div>
    <a href="<?= route_url('referrals') ?>" class="btn ghost inline" data-testid="ranks-invite">
      <i class="fa-solid fa-user-plus"></i> Invite to climb up
    </a>
  </div>

  <div class="ranks-snap-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-top:14px">
    <div class="snap-tile">
      <div class="snap-k">L1 Members</div>
      <div class="snap-v" data-testid="snap-l1-members"><?= (int)$stats['l1_members'] ?></div>
    </div>
    <div class="snap-tile">
      <div class="snap-k">L2 Members</div>
      <div class="snap-v" data-testid="snap-l2-members"><?= (int)$stats['l2_members'] ?></div>
    </div>
    <div class="snap-tile">
      <div class="snap-k">L3 Members</div>
      <div class="snap-v" data-testid="snap-l3-members"><?= (int)$stats['l3_members'] ?></div>
    </div>
    <div class="snap-tile">
      <div class="snap-k">L1 Business</div>
      <div class="snap-v" data-testid="snap-l1-business"><?= money($stats['l1_business']) ?></div>
    </div>
    <div class="snap-tile">
      <div class="snap-k">L2 Business</div>
      <div class="snap-v" data-testid="snap-l2-business"><?= money($stats['l2_business']) ?></div>
    </div>
    <div class="snap-tile">
      <div class="snap-k">L3 Business</div>
      <div class="snap-v" data-testid="snap-l3-business"><?= money($stats['l3_business']) ?></div>
    </div>
  </div>
</div>

<!-- Ranks ladder -->
<?php if (!$ranks): ?>
  <div class="card empty">No ranks configured yet. Check back soon.</div>
<?php else: ?>
  <div class="ranks-list stagger" data-testid="user-ranks-list" style="display:grid;gap:14px">
    <?php foreach ($ranks as $r):
      $achieved = $stats['l1_members']  >= (int)$r['min_l1_members']
               && $stats['l2_members']  >= (int)$r['min_l2_members']
               && $stats['l3_members']  >= (int)$r['min_l3_members']
               && $stats['l1_business'] >= (float)$r['min_l1_business']
               && $stats['l2_business'] >= (float)$r['min_l2_business']
               && $stats['l3_business'] >= (float)$r['min_l3_business'];
    ?>
    <div class="card rank-row <?= $achieved ? 'rank-achieved' : '' ?>" data-testid="rank-<?= e(strtolower($r['name'])) ?>">
      <div class="rank-row-head" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:14px">
          <div class="rank-row-emoji" style="font-size:34px;line-height:1"><?= e($r['emoji'] ?: '🏅') ?></div>
          <div>
            <div style="font-size:20px;font-weight:800;letter-spacing:.4px"><?= e($r['name']) ?></div>
            <div class="small muted" style="margin-top:2px">
              <?php if ($achieved): ?>
                <span style="color:var(--green,#10b981)"><i class="fa-solid fa-check"></i> Achieved</span>
              <?php else: ?>
                <span><i class="fa-solid fa-lock"></i> Locked</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div style="text-align:right">
          <div class="small muted" style="letter-spacing:1.2px;text-transform:uppercase">Monthly salary</div>
          <div style="font-size:22px;font-weight:800;background:linear-gradient(120deg,#60a5fa,#a78bfa);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">
            <?= money($r['monthly_salary']) ?>
          </div>
        </div>
      </div>

      <div class="rank-row-reqs" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:14px">
        <?php
          $reqs = [
            ['L1 Members',  (int)$r['min_l1_members'],   $stats['l1_members'],  false],
            ['L2 Members',  (int)$r['min_l2_members'],   $stats['l2_members'],  false],
            ['L3 Members',  (int)$r['min_l3_members'],   $stats['l3_members'],  false],
            ['L1 Business', (float)$r['min_l1_business'],$stats['l1_business'], true],
            ['L2 Business', (float)$r['min_l2_business'],$stats['l2_business'], true],
            ['L3 Business', (float)$r['min_l3_business'],$stats['l3_business'], true],
          ];
          foreach ($reqs as [$label, $need, $have, $isMoney]):
            $ok = $have >= $need;
            $pct = $need > 0 ? min(100, ($have / $need) * 100) : 100;
        ?>
          <div class="req-tile <?= $ok ? 'req-ok' : '' ?>">
            <div class="req-k"><?= e($label) ?></div>
            <div class="req-v">
              <?= $isMoney ? money($have) : (int)$have ?>
              <small style="opacity:.55"> / <?= $isMoney ? money($need) : (int)$need ?></small>
            </div>
            <div class="req-bar"><span style="width:<?= number_format($pct,1) ?>%"></span></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card mt" style="margin-top:18px">
  <h3 style="margin:0 0 6px;font-size:15px"><i class="fa-solid fa-circle-info" style="color:var(--blue)"></i> How salary ranks work</h3>
  <ul style="margin:6px 0 0;padding-left:20px;color:var(--txt-mute);font-size:13px;line-height:1.7">
    <li>Refer new users and grow your team across <b>Level 1, Level 2 and Level 3</b>.</li>
    <li>"Business" at each level = the sum of <b>deposits only</b> from users at that exact level (task earnings are not counted).</li>
    <li>The moment you meet <b>every</b> requirement of a rank, you unlock its monthly salary.</li>
    <li>Salary is paid automatically on the 1st of each month, on top of your task and referral earnings.</li>
  </ul>
</div>

<style>
.snap-tile{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:12px 14px}
.snap-k{font-size:11px;letter-spacing:1.4px;text-transform:uppercase;color:var(--txt-mute,#7d869b)}
.snap-v{font-size:18px;font-weight:800;margin-top:4px}
.req-tile{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:10px 12px}
.req-tile.req-ok{border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.07)}
.req-k{font-size:11px;letter-spacing:1.3px;text-transform:uppercase;color:var(--txt-mute,#7d869b)}
.req-v{font-size:14px;font-weight:700;margin-top:3px}
.req-bar{height:4px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:8px;overflow:hidden}
.req-bar>span{display:block;height:100%;background:linear-gradient(90deg,#60a5fa,#a78bfa);border-radius:2px;transition:width .5s ease}
.req-tile.req-ok .req-bar>span{background:linear-gradient(90deg,#10b981,#34d399)}
.rank-row.rank-achieved{border-color:rgba(16,185,129,.35);box-shadow:0 0 0 1px rgba(16,185,129,.18) inset}
</style>
