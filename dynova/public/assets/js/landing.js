/* =====================================================================
   DYNOVA NETWORK – Landing page interactions
   ===================================================================== */
(function () {
  'use strict';

  // ---------- Sticky nav state ----------
  var nav = document.getElementById('lpNav');
  if (nav) {
    var onScroll = function () {
      if (window.scrollY > 24) nav.classList.add('scrolled');
      else nav.classList.remove('scrolled');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    var burger = document.getElementById('lpBurger');
    if (burger) {
      burger.addEventListener('click', function () {
        nav.classList.toggle('open');
      });
      // Close on link click
      nav.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () { nav.classList.remove('open'); });
      });
    }
  }

  // ---------- Scroll-reveal ----------
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('in');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  // ---------- Animated counters ----------
  function formatShort(n) {
    if (n >= 1e9) return (n / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
    return Math.round(n).toLocaleString();
  }

  var counters = document.querySelectorAll('.counter');
  if (counters.length && 'IntersectionObserver' in window) {
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target;
        co.unobserve(el);
        var target = parseFloat(el.getAttribute('data-target') || '0');
        var fmt = el.getAttribute('data-format') || '';
        var dur = 1600;
        var start = performance.now();
        var step = function (now) {
          var p = Math.min((now - start) / dur, 1);
          // ease-out-cubic
          var eased = 1 - Math.pow(1 - p, 3);
          var v = target * eased;
          el.textContent = fmt === 'short' ? formatShort(v) : Math.round(v).toLocaleString();
          if (p < 1) requestAnimationFrame(step);
          else el.textContent = fmt === 'short' ? formatShort(target) : Math.round(target).toLocaleString();
        };
        requestAnimationFrame(step);
      });
    }, { threshold: 0.3 });
    counters.forEach(function (c) { co.observe(c); });
  }

  // ---------- Smooth anchor scroll ----------
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
      var id = a.getAttribute('href');
      if (id.length < 2) return;
      var target = document.querySelector(id);
      if (!target) return;
      ev.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
})();
