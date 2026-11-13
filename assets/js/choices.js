/* "What brings you here?" picker and the hero switch.
 * - Opens by itself (after the admin's delay) for visitors who have not chosen yet, unless they closed it earlier in this visit.
 * - Choosing saves the choice (POST /api/choice: a one-year cookie, and the profile when signed in), then reloads the page
 *   with that experience, or opens the page the admin chose for that card.
 * - On phones the sheet can be dragged down to close it.
 */
(function () {
  'use strict';
  var CH = window.CH;
  if (!CH) return;
  var dlg = document.getElementById('choice-picker');
  var LATER = 'chz_later';
  var busy = false;

  function store(k, v) { try { if (v === null) sessionStorage.removeItem(k); else sessionStorage.setItem(k, v); } catch (e) {} }
  function stored(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } }

  function open() {
    if (!dlg || dlg.open) return;
    dlg.classList.remove('is-closing');
    if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', '');
    document.documentElement.classList.add('chz-lock');
    requestAnimationFrame(function () { dlg.classList.add('is-open'); });
    // focus the panel itself, so no card looks chosen before the visitor picks one; Tab moves through the cards
    var panel = dlg.querySelector('[data-chz-panel]');
    if (panel) setTimeout(function () { panel.focus({ preventScroll: true }); }, 30);
  }

  function close() {
    if (!dlg || !dlg.open) return;
    dlg.classList.remove('is-open');
    dlg.classList.add('is-closing');
    document.documentElement.classList.remove('chz-lock');
    setTimeout(function () { if (dlg.open) dlg.close(); dlg.classList.remove('is-closing'); var p = dlg.querySelector('[data-chz-panel]'); if (p) p.style.transform = ''; }, CH.reduced && CH.reduced() ? 0 : 240);
  }

  /* Closing without choosing: ask again on the next visit, not during this one. */
  function dismiss() { store(LATER, '1'); close(); }

  function pick(mode, el) {
    if (busy) return;
    busy = true;
    var current = dlg ? dlg.getAttribute('data-current') : '';
    if (el) {
      el.classList.add('is-chosen');
      el.setAttribute('aria-pressed', 'true');
    }
    CH.api('/api/choice', { data: { mode: mode } }).then(function (j) {
      if (!j || !j.ok) {
        busy = false;
        if (el) el.classList.remove('is-chosen');
        CH.toast((j && j.error) || 'That did not save. Please try again.', 'error');
        return;
      }
      store(LATER, null);
      if (j.redirect) { location.href = j.redirect; return; }
      // "Show me everything" from a fresh visit changes nothing on the page: just close.
      if (mode === 'all' && (current === '' || current === 'all')) { busy = false; close(); return; }
      document.documentElement.classList.add('chz-switching');
      setTimeout(function () { location.reload(); }, CH.reduced && CH.reduced() ? 0 : 260);
    });
  }

  CH.delegate('click', '[data-chz-pick]', function (e, el) { e.preventDefault(); pick(el.getAttribute('data-chz-pick'), el.classList.contains('chz-card') ? el : null); });

  /* The chip next to Filters on Discover: a small menu of the same choices. */
  (function () {
    var wrap = document.querySelector('[data-xd-mode]');
    if (!wrap) return;
    var btn = wrap.querySelector('[data-xd-mode-btn]'), menu = wrap.querySelector('[data-xd-mode-menu]');
    if (!btn || !menu) return;
    function setOpen(on) {
      menu.hidden = !on;
      btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      wrap.classList.toggle('is-open', on);
      if (on) { var f = menu.querySelector('.xd-mode-opt'); if (f) f.focus(); }
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(menu.hidden); });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) setOpen(false); });
    wrap.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !menu.hidden) { setOpen(false); btn.focus(); }
    });
  })();
  CH.delegate('click', '[data-chz-open]', function (e) { e.preventDefault(); open(); });
  CH.delegate('click', '[data-chz-dismiss]', function (e) { e.preventDefault(); dismiss(); });

  if (!dlg) return;
  dlg.addEventListener('cancel', function (e) { e.preventDefault(); dismiss(); });
  dlg.addEventListener('click', function (e) { if (e.target === dlg) dismiss(); });

  /* Drag the sheet down to close it (phones). */
  var panel = dlg.querySelector('[data-chz-panel]');
  var y0 = null, dy = 0;
  if (panel) {
    panel.addEventListener('touchstart', function (e) {
      if (window.innerWidth > 640 || panel.scrollTop > 0) return;
      y0 = e.touches[0].clientY; dy = 0;
    }, { passive: true });
    panel.addEventListener('touchmove', function (e) {
      if (y0 === null) return;
      dy = Math.max(0, e.touches[0].clientY - y0);
      panel.style.transform = dy ? 'translateY(' + dy + 'px)' : '';
    }, { passive: true });
    panel.addEventListener('touchend', function () {
      if (y0 === null) return;
      if (dy > 110) dismiss(); else panel.style.transform = '';
      y0 = null; dy = 0;
    });
  }

  if (dlg.getAttribute('data-auto') === '1' && stored(LATER) !== '1' && !/[?&]nopicker\b/.test(location.search)) {
    var delay = Math.max(0, parseInt(dlg.getAttribute('data-delay') || '0', 10)) * 1000;
    // never pop over something the visitor already opened (a story, the sign-in card, another dialog): wait until it closes
    var tries = 0;
    var tryOpen = function () {
      var other = document.querySelector('dialog[open]:not(#choice-picker)') || document.body.classList.contains('sty-open');
      if (other && tries++ < 80) { setTimeout(tryOpen, 1500); return; }
      if (!other) open();
    };
    setTimeout(tryOpen, delay);
  }
})();
