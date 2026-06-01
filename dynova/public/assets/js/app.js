/* DYNOVA NETWORK – small JS helpers */
(function(){
  // Copy to clipboard
  document.querySelectorAll('[data-copy]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const txt = btn.getAttribute('data-copy');
      const fallback = ()=>{
        const ta=document.createElement('textarea');ta.value=txt;document.body.appendChild(ta);ta.select();
        try{document.execCommand('copy');}catch(e){}ta.remove();
      };
      if(navigator.clipboard){
        navigator.clipboard.writeText(txt).catch(fallback);
      }else fallback();
      const t = document.getElementById('copyToast');
      if(t){t.classList.add('show');setTimeout(()=>t.classList.remove('show'),1400);}
    });
  });

  // Period toggle (preserves query)
  // handled via real links – nothing extra here

  // Star rating: show selected
  document.querySelectorAll('.stars').forEach(s=>{
    const inputs = s.querySelectorAll('input');
    inputs.forEach(inp=>{
      inp.addEventListener('change', ()=>{
        s.dataset.value = inp.value;
      });
    });
  });

  // Embed YouTube videos
  document.querySelectorAll('[data-yt]').forEach(box=>{
    const url = box.getAttribute('data-yt');
    const m = url.match(/(?:v=|youtu\.be\/|embed\/)([\w-]{6,})/);
    if(m){
      const id = m[1];
      box.innerHTML = '<iframe src="https://www.youtube.com/embed/'+id+'?rel=0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
    }
  });

  // Bottom-nav active pulse on tap
  document.querySelectorAll('.nav-bottom a').forEach(a=>{
    a.addEventListener('click', ()=>{
      a.style.transform='scale(.92)';
      setTimeout(()=>a.style.transform='',150);
    });
  });

  // ---------- Mobile "More" drop-up sheet ----------
  function initMoreSheet(){
    const moreBtn      = document.getElementById('navMoreBtn');
    const moreSheet    = document.getElementById('moreSheet');
    const moreBackdrop = document.getElementById('moreBackdrop');
    const moreClose    = document.getElementById('moreClose');
    if(!moreBtn || !moreSheet) return;     // page doesn't use the layout (e.g. admin)

    function openMore(){
      moreSheet.classList.add('is-open');
      moreSheet.setAttribute('aria-hidden','false');
      moreBackdrop && moreBackdrop.classList.add('is-open');
      moreBtn.classList.add('is-open');
      moreBtn.setAttribute('aria-expanded','true');
      document.body.style.overflow='hidden';
    }
    function closeMore(){
      moreSheet.classList.remove('is-open');
      moreSheet.setAttribute('aria-hidden','true');
      moreBackdrop && moreBackdrop.classList.remove('is-open');
      moreBtn.classList.remove('is-open');
      moreBtn.setAttribute('aria-expanded','false');
      document.body.style.overflow='';
    }
    moreBtn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();
      if(moreSheet.classList.contains('is-open')) closeMore(); else openMore();
    });
    if (moreClose)    moreClose.addEventListener('click', closeMore);
    if (moreBackdrop) moreBackdrop.addEventListener('click', closeMore);
    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') closeMore();
    });
  }
  // Run now if DOM is ready, otherwise wait for it.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMoreSheet);
  } else {
    initMoreSheet();
  }

  // ---------- Copy button for any [data-copy="#selector"] target ----------
  document.querySelectorAll('[data-copy]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const target = document.querySelector(btn.getAttribute('data-copy'));
      if(!target) return;
      const txt = target.value || target.textContent || '';
      const fallback = ()=>{
        target.select && target.select();
        try{ document.execCommand('copy'); }catch(e){}
      };
      if(navigator.clipboard){
        navigator.clipboard.writeText(txt).catch(fallback);
      }else fallback();
      const t = document.getElementById('copyToast');
      if(t){ t.classList.add('show'); setTimeout(()=>t.classList.remove('show'), 1400); }
      // small visual feedback on the button itself
      const old = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Copied</span>';
      setTimeout(()=>{ btn.innerHTML = old; }, 1400);
    });
  });
})();

/* ---------- Admin package form: live Daily / Weekly / Monthly preview ---------- */
(function(){
  const form = document.querySelector('[data-testid="package-form"]');
  if(!form) return;
  const tasksEl = form.querySelector('[data-testid="pkg-daily-tasks"]');
  const perEl   = form.querySelector('[data-testid="pkg-per-task"]');
  const out = {
    daily:   form.querySelector('[data-target="daily"]'),
    weekly:  form.querySelector('[data-target="weekly"]'),
    monthly: form.querySelector('[data-target="monthly"]'),
  };
  if(!tasksEl || !perEl || !out.daily) return;
  function fmt(n){
    if (!isFinite(n) || n < 0) n = 0;
    return 'Rs ' + Math.round(n * 100) / 100;
  }
  function recalc(){
    const t = parseFloat(tasksEl.value || '0');
    const p = parseFloat(perEl.value || '0');
    const d = t * p;
    out.daily.textContent   = fmt(d);
    out.weekly.textContent  = fmt(d * 7);
    out.monthly.textContent = fmt(d * 30);
  }
  ['input','change','keyup'].forEach(ev => {
    tasksEl.addEventListener(ev, recalc);
    perEl.addEventListener(ev, recalc);
  });
  recalc();
})();
