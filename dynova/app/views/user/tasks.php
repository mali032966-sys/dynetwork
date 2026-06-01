<?php $rewardPerTask = $rewardPerTask ?? 0.0; ?>
<div class="topbar">
  <div class="greet"><b>Video Tasks</b><div class="small muted">Rate &amp; earn PKR instantly</div></div>
  <a href="<?= route_url('dashboard') ?>" class="bell"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($rewardPerTask <= 0): ?>
<div class="card mt-lg" data-testid="no-package">
  <div class="empty">
    <i class="fa-solid fa-lock" style="font-size:42px;color:var(--cyan);margin-bottom:10px"></i>
    <div style="font-size:18px;color:var(--txt);margin-bottom:6px">Activate a package to start earning</div>
    <div>Your per-task reward is set by the package you activate. Pick one to unlock tasks.</div>
    <a class="btn inline mt-md" href="<?= route_url('packages') ?>" data-testid="go-packages">View Packages</a>
  </div>
</div>
<?php else: ?>

<div class="card stagger" style="display:flex;justify-content:space-between;align-items:center" data-testid="tasks-progress">
  <div>
    <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Daily Progress</div>
    <div style="font-size:22px;font-weight:800;margin-top:4px"><?= (int)$done ?> / <?= (int)$limit ?></div>
  </div>
  <div style="text-align:right">
    <div class="small muted" style="letter-spacing:1.4px;text-transform:uppercase">Per Task</div>
    <div style="font-size:22px;font-weight:800;color:var(--cyan)" data-testid="reward-per-task"><?= money($rewardPerTask) ?></div>
  </div>
</div>

<?php if ($next): ?>
<div class="task-card mt-lg page-anim" data-testid="task-card">
  <div class="header">
    <span class="id">Task #<?= (int)$next['id'] ?> · <?= e($next['title']) ?></span>
    <span class="reward" data-testid="task-reward"><?= money($rewardPerTask) ?></span>
  </div>
  <div class="video" data-yt="<?= e($next['video_url']) ?>">
    <div class="video-fallback"><div class="play"><i class="fa-solid fa-play"></i></div></div>
  </div>
  <div class="body">
    <h3><?= e($next['title']) ?></h3>
    <?php if ($next['description']): ?><div class="desc"><?= e($next['description']) ?></div><?php endif; ?>
  <a href="<?= route_url('tasks/submit') ?>" style="display:none"></a>
    <form method="post" action="<?= route_url('tasks/submit') ?>" data-testid="rating-form">
      <?= csrf_field() ?>
      <input type="hidden" name="task_id" value="<?= (int)$next['id'] ?>">
      <div class="muted small center">Rate this video (1-5 stars)</div>
      <div class="stars">
        <input type="radio" id="s5" name="rating" value="5"><label for="s5" data-testid="star-5">★</label>
        <input type="radio" id="s4" name="rating" value="4"><label for="s4" data-testid="star-4">★</label>
        <input type="radio" id="s3" name="rating" value="3" checked><label for="s3" data-testid="star-3">★</label>
        <input type="radio" id="s2" name="rating" value="2"><label for="s2" data-testid="star-2">★</label>
        <input type="radio" id="s1" name="rating" value="1"><label for="s1" data-testid="star-1">★</label>
      </div>
      <button class="btn" type="submit" data-testid="submit-rating">
        Submit Rating · Earn <?= money($rewardPerTask) ?>
      </button>
    </form>
  </div>
</div>

<?php if ($upcoming): ?>
<div class="list-title"><h3>Upcoming</h3></div>
<div class="card" data-testid="upcoming-tasks">
  <?php foreach ($upcoming as $t): ?>
    <div class="activity">
      <div class="ico"><i class="fa-solid fa-lock"></i></div>
      <div class="meta"><b><?= e($t['title']) ?></b><small>Unlocks after current task</small></div>
      <div class="amt"><?= money($rewardPerTask) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card mt-lg" data-testid="no-tasks">
  <div class="empty">
    <i class="fa-solid fa-circle-check" style="font-size:42px;color:var(--green);margin-bottom:10px"></i>
    <div style="font-size:18px;color:var(--txt);margin-bottom:6px">All done for today!</div>
    <div>You've completed all available tasks. Come back tomorrow for more.</div>
  </div>
</div>
<?php endif; ?>

<?php endif; /* rewardPerTask > 0 */ ?>
