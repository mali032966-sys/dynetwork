<?php
// Format the user's referral code (with prefix/suffix) for clipboard.
// The referral_code column already carries the canonical "DN……" code, so
// we just trust that. Falls back to a "DN<id>" placeholder if missing.
function adm_ref_code(array $u): string {
    $code = trim((string)($u['referral_code'] ?? ''));
    if ($code !== '') return $code;
    return 'DN' . (int)($u['id'] ?? 0);
}
function adm_mask_phone(string $p): string {
    $p = preg_replace('/\D+/', '', $p) ?? '';
    $n = strlen($p);
    if ($n <= 6) return str_repeat('•', max(0, $n - 2)) . substr($p, -2);
    return substr($p, 0, 4) . str_repeat('•', max(0, $n - 7)) . substr($p, -3);
}
?>
<div class="admin-h">
  <h1>Referral Tree</h1>
  <form method="get" class="flex" style="gap:8px;flex-wrap:wrap" data-testid="admin-ref-search">
    <input type="hidden" name="r" value="admin/referrals">
    <input class="input" type="text" name="q"
           placeholder="User ID, mobile, or code (e.g. 12 or 0300… or DN1234ABC)"
           value="<?= e($q ?? '') ?>"
           style="min-width:320px"
           data-testid="admin-ref-search-input"
           autocomplete="off">
    <button class="btn inline" type="submit" data-testid="admin-ref-search-btn">View</button>
  </form>
</div>

<?php if (!empty($err)): ?>
  <div class="card" style="margin-bottom:14px" data-testid="admin-ref-error">
    <div class="alert error" style="margin:0"><?= e($err) ?></div>
  </div>
<?php endif; ?>

<?php if (!$user): ?>
  <div class="card">
    <div class="empty" style="line-height:1.6">
      <b>Search a user to view their 3-level referral tree.</b><br>
      <span class="small muted">Accepted: numeric <b>User ID</b>, full <b>WhatsApp number</b> (with or without country code), or <b>referral code</b> (e.g. <code>DN2345672AD</code>).</span>
    </div>
  </div>
<?php else: ?>
  <!-- Header card: user identity with copy-able ID and referral code -->
  <div class="card" style="margin-bottom:14px" data-testid="admin-ref-user-card">
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
      <div class="avatar"
           style="width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;background:linear-gradient(135deg,#3eb6ff,#8d5bff)">
        <?= e(strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)$user['name']) ?? '', 0, 2)) ?: '??') ?>
      </div>
      <div style="flex:1;min-width:220px">
        <div style="font-size:16px;font-weight:700"><?= e($user['name'] ?: 'Member #'.(int)$user['id']) ?></div>
        <div class="small muted" style="margin-top:2px">
          <i class="fa-brands fa-whatsapp"></i> <?= e($user['whatsapp']) ?>
          &middot; Balance <b style="color:var(--green,#10b981)"><?= money($user['balance']) ?></b>
        </div>
      </div>
      <div class="adm-id-chips">
        <span class="adm-chip">
          <span class="adm-chip-k">User ID</span>
          <span class="adm-chip-v" data-testid="admin-ref-user-id">#<?= (int)$user['id'] ?></span>
          <button type="button" class="adm-copy" data-copy="<?= (int)$user['id'] ?>"
                  data-testid="copy-user-id" title="Copy User ID">
            <i class="fa-regular fa-copy"></i>
          </button>
        </span>
        <span class="adm-chip">
          <span class="adm-chip-k">Referral Code</span>
          <span class="adm-chip-v" data-testid="admin-ref-user-code"><?= e(adm_ref_code($user)) ?></span>
          <button type="button" class="adm-copy" data-copy="<?= e(adm_ref_code($user)) ?>"
                  data-testid="copy-ref-code" title="Copy referral code">
            <i class="fa-regular fa-copy"></i>
          </button>
        </span>
      </div>
    </div>
  </div>

  <?php
  $teams = [
    ['lbl'=>'Level 1 (A) — direct',     'badge'=>'L1', 'rows'=>$teamA],
    ['lbl'=>'Level 2 (B) — indirect',   'badge'=>'L2', 'rows'=>$teamB],
    ['lbl'=>'Level 3 (C) — sub-indirect','badge'=>'L3','rows'=>$teamC],
  ];
  foreach ($teams as $t): $rows = $t['rows']; $cnt = count($rows);
  ?>
    <div class="card" style="margin-bottom:14px" data-testid="admin-team-<?= e($t['badge']) ?>">
      <h3 style="margin:0 0 10px;display:flex;align-items:center;gap:10px">
        <?= e($t['lbl']) ?>
        <span class="badge active" style="font-size:11px"><?= $cnt ?> member<?= $cnt === 1 ? '' : 's' ?></span>
      </h3>
      <table class="table">
        <thead>
          <tr>
            <th style="width:34px">#</th>
            <th>User ID</th>
            <th>Referral Code</th>
            <th>Name</th>
            <th>WhatsApp</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="empty">No members at this level</td></tr>
        <?php else: $n = 0; foreach ($rows as $m): $n++;
          $rcode = adm_ref_code($m);
        ?>
          <tr data-testid="admin-team-row-<?= e($t['badge']) ?>-<?= (int)$m['id'] ?>">
            <td><b>#<?= $n ?></b></td>
            <td>
              <span class="adm-inline-id">#<?= (int)$m['id'] ?></span>
              <button type="button" class="adm-copy sm" data-copy="<?= (int)$m['id'] ?>" title="Copy ID">
                <i class="fa-regular fa-copy"></i>
              </button>
            </td>
            <td>
              <code class="adm-code"><?= e($rcode) ?></code>
              <button type="button" class="adm-copy sm" data-copy="<?= e($rcode) ?>" title="Copy code">
                <i class="fa-regular fa-copy"></i>
              </button>
            </td>
            <td><?= e($m['name']) ?: '<span class="small muted">—</span>' ?></td>
            <td>
              <span title="<?= e($m['whatsapp']) ?>"><?= e($m['whatsapp']) ?></span>
              <button type="button" class="adm-copy sm" data-copy="<?= e($m['whatsapp']) ?>" title="Copy WhatsApp">
                <i class="fa-regular fa-copy"></i>
              </button>
            </td>
            <td><?= e(!empty($m['created_at']) ? date('M d, Y', strtotime($m['created_at'])) : '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<style>
.adm-id-chips{display:flex;gap:8px;flex-wrap:wrap;}
.adm-chip{
  display:inline-flex;align-items:center;gap:8px;
  padding:6px 10px;border-radius:10px;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.10);
}
.adm-chip-k{font-size:10.5px;letter-spacing:1px;text-transform:uppercase;color:var(--txt-mute,#94a3b8);}
.adm-chip-v{font-size:13px;font-weight:700;color:var(--txt,#fff);}
.adm-code{
  font-family:ui-monospace,Menlo,Consolas,monospace;
  font-size:12px;padding:2px 8px;border-radius:6px;
  background:rgba(62,182,255,.12);color:#cdebff;
}
.adm-inline-id{font-weight:700;}
.adm-copy{
  background:transparent;border:0;cursor:pointer;padding:4px 6px;
  color:var(--txt-mute,#94a3b8);border-radius:6px;
  transition:color .12s ease,background .12s ease,transform .12s ease;
}
.adm-copy:hover{color:#fff;background:rgba(141,91,255,.18);}
.adm-copy.sm i{font-size:11px;}
.adm-copy.copied{color:#10b981;transform:scale(1.05);}
.adm-copy.copied i:before{content:"\f00c";} /* fa-check */
</style>

<script>
(function(){
  function flash(btn){
    btn.classList.add('copied');
    setTimeout(function(){ btn.classList.remove('copied'); }, 1200);
  }
  function copyText(txt){
    if (navigator.clipboard && window.isSecureContext){
      return navigator.clipboard.writeText(txt);
    }
    return new Promise(function(res){
      var ta=document.createElement('textarea');
      ta.value=txt; ta.style.position='fixed'; ta.style.opacity='0';
      document.body.appendChild(ta); ta.select();
      try{document.execCommand('copy');}catch(e){}
      document.body.removeChild(ta); res();
    });
  }
  document.querySelectorAll('.adm-copy').forEach(function(btn){
    btn.addEventListener('click', function(){
      var v = btn.getAttribute('data-copy') || '';
      if (!v) return;
      copyText(v).then(function(){ flash(btn); });
    });
  });
})();
</script>
