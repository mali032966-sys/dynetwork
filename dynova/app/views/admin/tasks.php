<div class="admin-h"><h1>Tasks</h1></div>

<div class="card" style="margin-bottom:18px">
  <h3 style="margin:0 0 12px">Add New Task</h3>
  <p class="small muted" style="margin:0 0 14px">
    Just paste a video URL. The reward each user earns is driven by their
    <b>active package</b> (<code>earning_per_task</code>) — not by the task itself.
  </p>
  <form method="post" class="stagger" data-testid="add-task-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="form-group"><label>Video URL (YouTube)</label>
      <input class="input" type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required data-testid="task-url"></div>
    <label class="small muted" style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
      <input type="checkbox" name="is_active" value="1" checked> Active</label>
    <button class="btn inline" type="submit" data-testid="add-task-submit">Add Task</button>
  </form>
</div>

<div class="card">
  <h3 style="margin:0 0 12px">All Tasks (<?= count($tasks) ?>)</h3>
  <table class="table" data-testid="admin-tasks-table">
    <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>URL</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if (!$tasks): ?><tr><td colspan="5" class="empty">No tasks yet</td></tr>
    <?php else: foreach ($tasks as $t): ?>
      <tr>
        <td>#<?= (int)$t['id'] ?></td>
        <td><?= e($t['title']) ?></td>
        <td><?php if ($t['is_active']): ?><span class="badge approved">Active</span><?php else: ?><span class="badge rejected">Off</span><?php endif; ?></td>
        <td><a href="<?= e($t['video_url']) ?>" target="_blank" style="color:var(--blue);font-size:11px">↗ view</a></td>
        <td>
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button class="btn sm ghost" name="action" value="toggle">Toggle</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this task?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button class="btn sm danger" name="action" value="delete" data-testid="delete-task-<?= (int)$t['id'] ?>">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
