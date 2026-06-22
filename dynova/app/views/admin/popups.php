<?php
$editing = $editing ?? null;
$row     = $editing ?: [
    'id' => 0, 'type' => 'text', 'title' => '', 'message' => '',
    'image_path' => '', 'start_at' => '', 'end_at' => '',
    'is_active' => 1,
];
$toLocal = function ($mysqlDt) {
    if (!$mysqlDt) return '';
    $ts = strtotime((string)$mysqlDt);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
};
?>

<div class="admin-h" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <h1>
    <i class="fa-solid fa-bullhorn" style="color:#ffb547"></i>
    Popup Messages
  </h1>
  <a href="<?= route_url('admin/developer') ?>" class="btn ghost inline"><i class="fa-solid fa-arrow-left"></i> Back to Developer</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="card" style="margin-bottom:14px"><div class="alert error" style="margin:0">
    <ul style="margin:0;padding-left:18px"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div></div>
<?php endif; ?>

<div class="popups-wrap">
  <!-- ============ FORM ============ -->
  <form method="post" enctype="multipart/form-data" class="card pop-form" data-testid="popup-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
    <input type="hidden" name="action" value="save">

    <h3 style="margin:0 0 14px"><?= $row['id'] ? 'Edit popup #'.(int)$row['id'] : 'Create a new popup' ?></h3>

    <div class="form-group">
      <label>Popup type</label>
      <div class="pop-type-row" data-testid="popup-type-row">
        <label class="pop-type" data-type="text">
          <input type="radio" name="type" value="text"  <?= $row['type']==='text' ? 'checked' : '' ?> data-testid="popup-type-text">
          <div class="pop-type-inner">
            <i class="fa-solid fa-align-left"></i>
            <b>Text message</b>
            <span class="small muted">Type a written announcement.</span>
          </div>
        </label>
        <label class="pop-type" data-type="image">
          <input type="radio" name="type" value="image" <?= $row['type']==='image' ? 'checked' : '' ?> data-testid="popup-type-image">
          <div class="pop-type-inner">
            <i class="fa-solid fa-image"></i>
            <b>Image / banner</b>
            <span class="small muted">Upload a JPG / PNG / WEBP / GIF (≤5 MB).</span>
          </div>
        </label>
      </div>
    </div>

    <div class="form-group">
      <label>Title <span class="small muted">(optional, shown above the message)</span></label>
      <input class="input" type="text" name="title" maxlength="160" value="<?= e($row['title']) ?>" placeholder="e.g. Eid Bonus — limited time"
             data-testid="popup-title">
    </div>

    <div class="form-group pop-only-text" data-pop-section="text">
      <label>Message</label>
      <textarea class="input" name="message" rows="5" placeholder="Type the message you want every user to see…"
                data-testid="popup-message"><?= e($row['message']) ?></textarea>
      <div class="small muted" style="margin-top:6px">Line breaks are preserved. Keep it short for mobile screens.</div>
    </div>

    <div class="form-group pop-only-image" data-pop-section="image">
      <label>Image</label>
      <?php if (!empty($row['image_path'])): ?>
        <div class="pop-img-preview" style="margin-bottom:8px">
          <img src="<?= asset(e($row['image_path'])) ?>" alt="current" data-testid="popup-current-image">
          <div class="small muted">Current image. Upload a new file to replace it.</div>
        </div>
      <?php endif; ?>
      <input class="input" type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif"
             data-testid="popup-image">
      <div class="small muted" style="margin-top:6px">Recommended: square or 4:3 banner, max 5 MB.</div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Start date / time <span class="small muted">(blank = now)</span></label>
        <input class="input" type="datetime-local" name="start_at" value="<?= e($toLocal($row['start_at'] ?? '')) ?>"
               data-testid="popup-start">
      </div>
      <div class="form-group">
        <label>End date / time <span class="small muted">(blank = no end)</span></label>
        <input class="input" type="datetime-local" name="end_at" value="<?= e($toLocal($row['end_at'] ?? '')) ?>"
               data-testid="popup-end">
      </div>
    </div>

    <label class="pop-active">
      <input type="checkbox" name="is_active" value="1" <?= !empty($row['is_active']) ? 'checked' : '' ?> data-testid="popup-active">
      <span><b>Active</b> &mdash; show this popup to users when inside the date range</span>
    </label>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
      <button class="btn inline" type="submit" data-testid="popup-save">
        <i class="fa-solid fa-floppy-disk"></i> <?= $row['id'] ? 'Update popup' : 'Create popup' ?>
      </button>
      <?php if ($row['id']): ?>
        <a href="<?= route_url('admin/popups') ?>" class="btn ghost inline">Cancel edit</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ============ LIST + PREVIEW ============ -->
  <div class="pop-side">
    <div class="card">
      <h3 style="margin:0 0 12px">All popups (<?= count($popups) ?>)</h3>
      <?php if (!$popups): ?>
        <div class="empty">No popups yet. Create one on the left.</div>
      <?php else: ?>
        <div class="pop-list" data-testid="popup-list">
          <?php foreach ($popups as $p):
            $isImg = $p['type'] === 'image';
            $within = (empty($p['start_at']) || strtotime($p['start_at']) <= time())
                   && (empty($p['end_at'])   || strtotime($p['end_at'])   >= time());
            $live   = (int)$p['is_active'] === 1 && $within;
          ?>
            <div class="pop-row" data-testid="popup-row-<?= (int)$p['id'] ?>">
              <div class="pop-row-thumb">
                <?php if ($isImg && !empty($p['image_path'])): ?>
                  <img src="<?= asset(e($p['image_path'])) ?>" alt="">
                <?php else: ?>
                  <i class="fa-solid fa-align-left"></i>
                <?php endif; ?>
              </div>
              <div class="pop-row-body">
                <div class="pop-row-head">
                  <b><?= e($p['title'] ?: ($isImg ? 'Image banner' : 'Text announcement')) ?></b>
                  <?php if ($live): ?>
                    <span class="badge approved" style="font-size:10px">LIVE</span>
                  <?php elseif (!(int)$p['is_active']): ?>
                    <span class="badge rejected" style="font-size:10px">OFF</span>
                  <?php else: ?>
                    <span class="badge pending" style="font-size:10px">scheduled</span>
                  <?php endif; ?>
                </div>
                <div class="small muted pop-row-meta">
                  <i class="fa-regular fa-calendar"></i>
                  <?= e($p['start_at'] ? date('M d, H:i', strtotime($p['start_at'])) : 'from now') ?>
                  &rarr;
                  <?= e($p['end_at']   ? date('M d, H:i', strtotime($p['end_at']))   : 'no end') ?>
                </div>
                <?php if (!$isImg && !empty($p['message'])): ?>
                  <div class="small muted pop-row-msg"><?= e(mb_substr($p['message'], 0, 120)) ?><?= mb_strlen($p['message']) > 120 ? '…' : '' ?></div>
                <?php endif; ?>
              </div>
              <div class="pop-row-actions">
                <a href="<?= route_url('admin/popups', ['edit' => (int)$p['id']]) ?>" class="btn sm ghost" title="Edit">
                  <i class="fa-regular fa-pen-to-square"></i>
                </a>
                <form method="post" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn sm ghost" type="submit" title="Toggle active">
                    <i class="fa-solid fa-power-off"></i>
                  </button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this popup?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn sm danger" type="submit" title="Delete">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card pop-preview-card" data-testid="popup-preview-card">
      <h3 style="margin:0 0 12px"><i class="fa-regular fa-eye"></i> Preview</h3>
      <div class="pop-preview" id="popPreview">
        <button type="button" class="pop-x" aria-label="Close" tabindex="-1"><i class="fa-solid fa-xmark"></i></button>
        <div class="pop-preview-image" id="popPrevImage" style="<?= !empty($row['image_path']) ? '' : 'display:none' ?>">
          <?php if (!empty($row['image_path'])): ?>
            <img src="<?= asset(e($row['image_path'])) ?>" alt="">
          <?php endif; ?>
        </div>
        <div class="pop-preview-text" id="popPrevText">
          <h4 id="popPrevTitle"><?= e($row['title'] ?: 'Title preview') ?></h4>
          <p id="popPrevMsg"><?= e($row['message'] ?: 'This is how the message will look to your users. Type in the message field on the left to see it update live here.') ?></p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.popups-wrap{display:grid;grid-template-columns:1.1fr .9fr;gap:18px;align-items:flex-start;}
@media (max-width:980px){ .popups-wrap{grid-template-columns:1fr} }
.pop-side{display:flex;flex-direction:column;gap:18px;}
.pop-type-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.pop-type{cursor:pointer;display:block;position:relative}
.pop-type input{position:absolute;opacity:0;pointer-events:none}
.pop-type-inner{
  display:flex;flex-direction:column;gap:4px;
  padding:14px;border-radius:12px;
  background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.10);
  transition:border-color .15s ease,background .15s ease;
}
.pop-type-inner i{font-size:18px;color:var(--violet,#8d5bff);margin-bottom:2px}
.pop-type input:checked + .pop-type-inner{
  border-color:rgba(141,91,255,.55);
  background:linear-gradient(120deg,rgba(141,91,255,.10),rgba(62,182,255,.10));
}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media (max-width:560px){ .form-row{grid-template-columns:1fr} .pop-type-row{grid-template-columns:1fr} }
.pop-active{display:flex;gap:8px;align-items:center;margin-top:4px;font-size:13px;color:var(--txt-mute,#94a3b8)}
.pop-img-preview img{max-width:160px;max-height:120px;border-radius:10px;border:1px solid rgba(255,255,255,.10)}
.pop-list{display:flex;flex-direction:column;gap:10px}
.pop-row{display:flex;gap:10px;padding:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.025);border-radius:12px}
.pop-row-thumb{width:48px;height:48px;border-radius:10px;flex:0 0 48px;display:flex;align-items:center;justify-content:center;background:rgba(141,91,255,.10);color:var(--violet,#8d5bff);overflow:hidden}
.pop-row-thumb img{width:100%;height:100%;object-fit:cover}
.pop-row-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
.pop-row-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pop-row-meta i{font-size:10px}
.pop-row-msg{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pop-row-actions{display:flex;gap:4px;align-items:flex-start}
/* preview frame mimics the user-side popup */
.pop-preview{
  position:relative;border-radius:18px;overflow:hidden;
  background:linear-gradient(160deg,#1a1a3a,#0d1024);border:1px solid rgba(255,255,255,.10);
  padding:20px 18px;
}
.pop-preview .pop-x{
  position:absolute;top:10px;right:10px;width:28px;height:28px;border-radius:50%;
  background:rgba(255,255,255,.10);border:0;color:#fff;cursor:default;
  display:flex;align-items:center;justify-content:center;font-size:13px
}
.pop-preview-image{margin-bottom:12px;border-radius:12px;overflow:hidden;background:rgba(255,255,255,.04)}
.pop-preview-image img{display:block;width:100%;height:auto}
.pop-preview h4{margin:0 0 6px;font-size:17px}
.pop-preview p{margin:0;font-size:13.5px;line-height:1.55;color:var(--txt-mute,#cbd5e1);white-space:pre-wrap}
</style>

<script>
(function(){
  // Show/hide text vs image fields based on the selected type
  function applyType(){
    var t = document.querySelector('input[name="type"]:checked');
    var type = t ? t.value : 'text';
    document.querySelectorAll('[data-pop-section]').forEach(function(el){
      el.style.display = (el.getAttribute('data-pop-section') === type) ? '' : 'none';
    });
  }
  document.querySelectorAll('input[name="type"]').forEach(function(r){
    r.addEventListener('change', applyType);
  });
  applyType();

  // Live preview
  var $title = document.querySelector('[data-testid="popup-title"]');
  var $msg   = document.querySelector('[data-testid="popup-message"]');
  var $img   = document.querySelector('[data-testid="popup-image"]');
  var pT = document.getElementById('popPrevTitle');
  var pM = document.getElementById('popPrevMsg');
  var pI = document.getElementById('popPrevImage');
  if ($title) $title.addEventListener('input', function(){ pT.textContent = $title.value || 'Title preview'; });
  if ($msg)   $msg.addEventListener('input', function(){ pM.textContent = $msg.value || 'This is how the message will look to your users.'; });
  if ($img)   $img.addEventListener('change', function(){
    var f = $img.files && $img.files[0];
    if (!f) return;
    var r = new FileReader();
    r.onload = function(ev){
      pI.style.display = '';
      pI.innerHTML = '<img src="'+ev.target.result+'" alt="">';
    };
    r.readAsDataURL(f);
  });
})();
</script>
