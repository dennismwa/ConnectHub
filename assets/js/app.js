/* ConnectHub core script. No dependencies. Exposes window.CH. */
(function () {
  'use strict';
  var doc = document, root = doc.documentElement;
  var cfgEl = doc.getElementById('ch-config');
  var CFG = {};
  try { CFG = JSON.parse(cfgEl ? cfgEl.textContent : '{}'); } catch (e) { CFG = {}; }
  var CH = window.CH = { config: CFG, base: CFG.base || '', user: CFG.user || null };

  function $(s, c) { return (c || doc).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
  function on(el, ev, fn, opt) { el && el.addEventListener(ev, fn, opt); }
  function delegate(ev, sel, fn) {
    doc.addEventListener(ev, function (e) {
      var t = e.target.closest ? e.target.closest(sel) : null;
      if (t && doc.contains(t)) fn.call(t, e, t);
    });
  }
  CH.$ = $; CH.$$ = $$; CH.on = on; CH.delegate = delegate;
  CH.url = function (p) { return CH.base + (p.charAt(0) === '/' ? p : '/' + p); };
  CH.esc = function (s) { var d = doc.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
  CH.reduced = function () { return root.dataset.motion === 'off' || (root.dataset.respectMotion === '1' && window.matchMedia('(prefers-reduced-motion: reduce)').matches); };

  /* ---------------- Network ---------------- */
  CH.api = function (url, opts) {
    opts = opts || {};
    var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-Token': CFG.csrf || '' };
    var init = { method: opts.method || (opts.data ? 'POST' : 'GET'), headers: headers, credentials: 'same-origin' };
    if (opts.data instanceof FormData) {
      init.body = opts.data;
    } else if (opts.data) {
      headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(opts.data);
    }
    return fetch(/^https?:/.test(url) ? url : CH.url(url), init).then(function (r) {
      return r.text().then(function (t) {
        var j = null;
        try { j = JSON.parse(t); } catch (e) { j = { ok: false, error: 'Unexpected response. Please try again.' }; }
        j._status = r.status;
        if (r.status === 401 && j.login && !opts.quiet) { location.href = j.login; }
        if (j.redirect && opts.followRedirect !== false && r.status === 403) { location.href = j.redirect; }
        return j;
      });
    }).catch(function () { return { ok: false, error: 'You appear to be offline. Check your connection and try again.', _status: 0 }; });
  };

  /* ---------------- Toasts ---------------- */
  CH.toast = function (message, type, ms) {
    var stack = $('#toast-stack'); if (!stack) return;
    var t = doc.createElement('div');
    t.className = 'toast toast-' + (type || 'info');
    t.setAttribute('role', type === 'error' ? 'alert' : 'status');
    var ico = type === 'error' ? 'circle-x' : (type === 'success' ? 'circle-check' : 'info');
    t.innerHTML = '<svg class="icon w-5 h-5" aria-hidden="true"><use href="' + CH.url('/assets/icons/sprite.svg') + '#i-' + ico + '"></use></svg><span class="flex-1">' + CH.esc(message) + '</span>';
    stack.appendChild(t);
    setTimeout(function () { t.classList.add('is-leaving'); setTimeout(function () { t.remove(); }, 260); }, ms || (type === 'error' ? 6000 : 3800));
    while (stack.children.length > 3) stack.firstChild.remove();
  };

  /* ---------------- Modals (native <dialog>) ---------------- */
  CH.modal = {
    open: function (id) { var d = typeof id === 'string' ? doc.getElementById(id) : id; if (d && d.showModal && !d.open) { d.showModal(); var f = $('[autofocus], input:not([type=hidden]), button.btn-primary', d); f && setTimeout(function () { f.focus({ preventScroll: true }); }, 60); } return d; },
    close: function (id) { var d = typeof id === 'string' ? doc.getElementById(id) : id; if (d && d.open) d.close(); }
  };
  delegate('click', '[data-modal-open]', function (e, el) { e.preventDefault(); CH.modal.open(el.getAttribute('data-modal-open')); });
  delegate('click', '[data-modal-close]', function (e, el) { e.preventDefault(); var d = el.closest('dialog'); d && d.close(); });
  doc.addEventListener('click', function (e) { var d = e.target; if (d && d.tagName === 'DIALOG' && d.classList.contains('modal') && !d.hasAttribute('data-modal-static')) { d.close(); } });

  /* Confirm dialog (replaces window.confirm). Returns a Promise<boolean>. */
  CH.confirm = function (o) {
    o = typeof o === 'string' ? { message: o } : (o || {});
    return new Promise(function (resolve) {
      var d = doc.createElement('dialog'); d.className = 'modal';
      d.innerHTML = '<div class="modal-panel" role="alertdialog" aria-labelledby="cf-t"><h2 class="modal-title" id="cf-t">' + CH.esc(o.title || 'Are you sure?') + '</h2><p class="text-ink-2 mt-2">' + CH.esc(o.message || '') + '</p><div class="modal-actions"><button type="button" class="btn btn-ghost" data-r="0">' + CH.esc(o.cancel || 'Cancel') + '</button><button type="button" class="btn ' + (o.danger === false ? 'btn-primary' : 'btn-danger') + '" data-r="1">' + CH.esc(o.confirm || 'Confirm') + '</button></div></div>';
      doc.body.appendChild(d);
      var done = function (v) { d.close(); setTimeout(function () { d.remove(); }, 50); resolve(v); };
      on(d, 'click', function (e) { var r = e.target.getAttribute && e.target.getAttribute('data-r'); if (r !== null && r !== undefined && e.target.tagName === 'BUTTON') done(r === '1'); else if (e.target === d) done(false); });
      on(d, 'cancel', function (e) { e.preventDefault(); done(false); });
      d.showModal(); $('[data-r="1"]', d).focus();
    });
  };
  /* data-confirm="message" on a form or button asks before submitting */
  doc.addEventListener('submit', function (e) {
    var f = e.target; if (!f.matches || !f.hasAttribute('data-confirm') || f.dataset.confirmed === '1') return;
    e.preventDefault();
    CH.confirm({ message: f.getAttribute('data-confirm'), confirm: f.getAttribute('data-confirm-label') || 'Yes, continue', title: f.getAttribute('data-confirm-title') || undefined }).then(function (ok) { if (ok) { f.dataset.confirmed = '1'; f.requestSubmit ? f.requestSubmit(e.submitter || undefined) : f.submit(); } });
  }, true);
  delegate('click', 'button[data-confirm], a[data-confirm]', function (e, el) {
    if (el.dataset.confirmed === '1') return; e.preventDefault(); e.stopPropagation();
    CH.confirm({ message: el.getAttribute('data-confirm'), confirm: el.getAttribute('data-confirm-label') || 'Yes, continue' }).then(function (ok) {
      if (!ok) return; el.dataset.confirmed = '1';
      if (el.tagName === 'A') location.href = el.href; else el.click();
    });
  });

  /* ---------------- Theme ---------------- */
  function applyTheme(pref) {
    var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    root.dataset.theme = dark ? 'dark' : 'light';
  }
  delegate('click', '[data-theme-toggle]', function () {
    var next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('ch-theme', next); } catch (e) {}
    applyTheme(next);
    if (CH.user) CH.api('/api/theme', { data: { theme: next }, quiet: true });
  });
  try { window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () { if ((localStorage.getItem('ch-theme') || root.dataset.defaultTheme) === 'system') applyTheme('system'); }); } catch (e) {}

  /* ---------------- Header, drawers, menus ---------------- */
  var header = $('[data-site-header]');
  if (header) {
    var setTop = function () { header.classList.toggle('is-top', window.scrollY < 24); };
    setTop(); on(window, 'scroll', setTop, { passive: true });
  }
  $$('.drawer').forEach(function (d) { d.inert = !d.classList.contains('is-open'); });
  function drawer(id, open) {
    var d = doc.getElementById(id); if (!d) return;
    d.classList.toggle('is-open', open); d.setAttribute('aria-hidden', open ? 'false' : 'true'); d.inert = !open;
    doc.body.style.overflow = open ? 'hidden' : '';
  }
  delegate('click', '[data-drawer-open]', function (e, el) { e.preventDefault(); drawer(el.getAttribute('data-drawer-open'), true); });
  delegate('click', '[data-drawer-close]', function (e, el) { var d = el.closest('.drawer'); if (d) drawer(d.id, false); });
  delegate('click', '[data-drawer-open-admin]', function () { $('#admin-sidebar').classList.add('is-open'); $('.admin-scrim').classList.add('is-open'); });
  delegate('click', '[data-drawer-close-admin]', function () { $('#admin-sidebar').classList.remove('is-open'); $('.admin-scrim').classList.remove('is-open'); });
  on(doc, 'keydown', function (e) {
    if (e.key === 'Escape') { $$('.drawer.is-open').forEach(function (d) { drawer(d.id, false); }); closeMenus(); }
  });
  function closeMenus(except) {
    $$('.menu-panel:not([hidden])').forEach(function (p) { if (!except || !except.contains(p)) { p.hidden = true; var b = p._btn || $('[data-menu-toggle]', p.parentNode); b && b.setAttribute('aria-expanded', 'false'); } });
  }
  var phone = window.matchMedia ? window.matchMedia('(max-width: 639px)') : { matches: false };
  delegate('click', '[data-menu-toggle]', function (e, el) {
    e.stopPropagation();
    var panel = el._panel || $('.menu-panel', el.parentNode); if (!panel) return;
    // a bottom sheet is moved to <body> on phones so no parent can clip it or shift its fixed position
    if (panel.classList.contains('sheet')) {
      if (!panel._home) { panel._home = el.parentNode; panel._btn = el; el._panel = panel; }
      if (phone.matches && panel.parentNode !== doc.body) doc.body.appendChild(panel);
      else if (!phone.matches && panel.parentNode === doc.body) panel._home.appendChild(panel);
    }
    var willOpen = panel.hidden; closeMenus(); panel.hidden = !willOpen; el.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });
  on(doc, 'click', function (e) {
    if (e.target.classList && e.target.classList.contains('sheet')) closeMenus();
    else if (!e.target.closest('.menu, .menu-panel.sheet')) closeMenus();
    else if (e.target.closest('.menu-panel.sheet .menu-item')) setTimeout(closeMenus, 0);
  });

  /* Quick amounts on payout forms: <button data-fill="#input" data-value="500"> */
  delegate('click', '[data-fill]', function (e, el) {
    var t = $(el.getAttribute('data-fill')); if (!t) return;
    e.preventDefault(); t.value = el.getAttribute('data-value'); t.dispatchEvent(new Event('input', { bubbles: true })); t.focus();
  });

  /* ---------------- Reveal on scroll ---------------- */
  function initReveal() {
    var els = $$('[data-reveal]:not(.is-in)'); if (!els.length) return;
    $$('[data-reveal-group]').forEach(function (g) { $$('[data-reveal]', g).forEach(function (el, i) { el.style.setProperty('--i', i); }); });
    if (!('IntersectionObserver' in window) || root.dataset.reveal === 'off') { els.forEach(function (e) { e.classList.add('is-in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    els.forEach(function (e) { io.observe(e); });
  }
  CH.initReveal = initReveal;

  /* ---------------- Forms ---------------- */
  delegate('submit', 'form', function (e, f) {
    if (e.defaultPrevented) return;
    if (f.hasAttribute('data-ajax-form')) return;
    var btn = e.submitter || $('button[type=submit]', f);
    if (btn && !btn.hasAttribute('data-no-loading')) { setTimeout(function () { btn.classList.add('is-loading'); btn.disabled = true; }, 0); setTimeout(function () { btn.classList.remove('is-loading'); btn.disabled = false; }, 15000); }
  });
  on(window, 'pageshow', function (e) { if (e.persisted) $$('.btn.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.disabled = false; }); });
  delegate('submit', 'form[data-ajax-form]', function (e, f) {
    e.preventDefault();
    var btn = $('button[type=submit]', f); btn && (btn.classList.add('is-loading'), btn.disabled = true);
    CH.api(f.getAttribute('action') || location.pathname, { method: (f.getAttribute('method') || 'POST').toUpperCase(), data: new FormData(f) }).then(function (j) {
      btn && (btn.classList.remove('is-loading'), btn.disabled = false);
      if (j.ok) {
        CH.toast(j.message || 'Done', 'success');
        if (f.hasAttribute('data-reset')) f.reset();
        if (j.redirect) { location.href = j.redirect; }
        f.dispatchEvent(new CustomEvent('ch:success', { detail: j, bubbles: true }));
      } else {
        CH.toast(j.error || 'Something went wrong.', 'error');
        f.dispatchEvent(new CustomEvent('ch:error', { detail: j, bubbles: true }));
      }
    });
  });
  /* data-post="/url" data-confirm? on any button: POST and react */
  delegate('click', '[data-post]', function (e, el) {
    e.preventDefault();
    var run = function () {
      el.disabled = true;
      var body = {}; try { body = JSON.parse(el.getAttribute('data-body') || '{}'); } catch (x) {}
      CH.api(el.getAttribute('data-post'), { data: body }).then(function (j) {
        el.disabled = false;
        if (j.ok) {
          if (j.message) CH.toast(j.message, 'success');
          if (j.redirect) location.href = j.redirect; else if (el.hasAttribute('data-reload')) location.reload();
          el.dispatchEvent(new CustomEvent('ch:success', { detail: j, bubbles: true }));
        } else CH.toast(j.error || 'Something went wrong.', 'error');
      });
    };
    if (el.hasAttribute('data-ask')) CH.confirm({ message: el.getAttribute('data-ask'), confirm: el.getAttribute('data-ask-label') || 'Yes' }).then(function (ok) { ok && run(); }); else run();
  });
  delegate('click', '[data-toggle-password]', function (e, el) {
    var input = $(el.getAttribute('data-toggle-password')); if (!input) return;
    var show = input.type === 'password'; input.type = show ? 'text' : 'password';
    el.setAttribute('aria-label', show ? 'Hide password' : 'Show password'); el.classList.toggle('is-on', show);
  });
  function pwScore(p) { var s = 0; if (p.length >= 8) s++; if (p.length >= 12) s++; if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++; if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) s++; return Math.min(4, s); }
  delegate('input', '[data-pw-input]', function (e, el) {
    var m = $(el.getAttribute('data-pw-input')); if (m) m.setAttribute('data-s', el.value ? pwScore(el.value) : 0);
  });
  delegate('input', '[data-counter]', function (e, el) {
    var t = $(el.getAttribute('data-counter')); if (t) t.textContent = el.value.length + ' / ' + (el.maxLength > 0 ? el.maxLength : '');
  });
  /* A mouse wheel over a focused number field must scroll the page, never change the value. */
  doc.addEventListener('wheel', function (e) {
    var t = e.target;
    if (t && t.matches && t.matches('input[type=number]') && doc.activeElement === t) t.blur();
  }, { passive: true });
  doc.addEventListener('keydown', function (e) {
    var t = e.target;
    if (t && t.matches && t.matches('input[type=number][data-no-arrows]') && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) e.preventDefault();
  });
  /* data-copy="text" copies it. data-copy-label sets the message ("Account number copied"). Works on http too (fallback). */
  CH.copy = function (txt) {
    function legacy() {
      return new Promise(function (ok, no) {
        var ta = doc.createElement('textarea'); ta.value = txt; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.top = '-1000px'; ta.style.opacity = '0';
        doc.body.appendChild(ta); ta.select(); ta.setSelectionRange(0, txt.length);
        var done = false; try { done = doc.execCommand('copy'); } catch (x) {}
        ta.remove(); done ? ok() : no();
      });
    }
    return navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(txt).catch(legacy) : legacy();
  };
  delegate('click', '[data-copy]', function (e, el) {
    e.preventDefault();
    var txt = el.getAttribute('data-copy') || '';
    CH.copy(txt).then(function () {
      CH.toast(el.getAttribute('data-copy-label') || 'Copied', 'success', 2200);
      el.classList.add('is-copied');
      var lab = el.querySelector('span'); var was = lab ? lab.textContent : '';
      if (lab && was) lab.textContent = 'Copied';
      if (navigator.vibrate) { try { navigator.vibrate(12); } catch (x) {} }
      clearTimeout(el._cp); el._cp = setTimeout(function () { el.classList.remove('is-copied'); if (lab && was) lab.textContent = was; }, 1800);
    }, function () { CH.toast('Copy did not work. Press and hold the number to copy it.', 'error'); });
  });
  /* file drop with preview */
  doc.addEventListener('change', function (e) {
    var inp = e.target; if (!inp.matches || !inp.matches('input[type=file][data-preview]')) return;
    var box = $(inp.getAttribute('data-preview')); if (!box || !inp.files) return;
    box.innerHTML = '';
    Array.prototype.forEach.call(inp.files, function (file) {
      if (!/^image\//.test(file.type)) return;
      var img = doc.createElement('img'); img.className = 'w-20 h-20 rounded-xl object-cover'; img.alt = ''; img.src = URL.createObjectURL(file); box.appendChild(img);
    });
  });
  ['dragenter', 'dragover'].forEach(function (ev) { doc.addEventListener(ev, function (e) { var z = e.target.closest && e.target.closest('.file-drop'); if (z) { e.preventDefault(); z.classList.add('is-over'); } }); });
  ['dragleave', 'drop'].forEach(function (ev) { doc.addEventListener(ev, function (e) { var z = e.target.closest && e.target.closest('.file-drop'); if (z) { z.classList.remove('is-over'); if (ev === 'drop') { e.preventDefault(); var inp = $('input[type=file]', z); if (inp && e.dataTransfer.files.length) { inp.files = e.dataTransfer.files; inp.dispatchEvent(new Event('change', { bubbles: true })); } } } }); });

  /* ---------------- Simple tabs: <div data-tabs><button data-tab="a">…</button><div data-panel="a">…</div> ---------------- */
  delegate('click', '[data-tab]', function (e, el) {
    var wrap = el.closest('[data-tabs]'); if (!wrap) return; e.preventDefault();
    $$('[data-tab]', wrap).forEach(function (t) { var on_ = t === el; t.classList.toggle('is-active', on_); t.setAttribute('aria-selected', on_ ? 'true' : 'false'); });
    $$('[data-panel]', wrap).forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== el.getAttribute('data-tab'); });
  });

  /* ---------------- Geolocation ---------------- */
  CH.locate = function () {
    return new Promise(function (resolve) {
      if (!navigator.geolocation) return resolve({ ok: false, error: 'Your browser cannot share its location.' });
      navigator.geolocation.getCurrentPosition(function (p) {
        CH.api('/api/location', { data: { lat: p.coords.latitude, lng: p.coords.longitude } }).then(resolve);
      }, function (err) {
        resolve({ ok: false, error: err && err.code === 1 ? 'Location permission was denied. You can pick your town instead.' : 'We could not find your location.' });
      }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 });
    });
  };
  delegate('click', '[data-locate]', function (e, el) {
    e.preventDefault(); el.classList.add('is-loading');
    CH.locate().then(function (j) {
      el.classList.remove('is-loading');
      if (j.ok) { CH.toast('Location updated: ' + (j.label || 'saved'), 'success'); el.dispatchEvent(new CustomEvent('ch:located', { detail: j, bubbles: true })); if (el.hasAttribute('data-reload')) location.reload(); }
      else CH.toast(j.error || 'Could not get your location.', 'error');
    });
  });

  /* ---------------- Badge polling ---------------- */
  if (CH.user) {
    var poll = function () {
      if (doc.hidden) return;
      CH.api('/api/badges', { quiet: true }).then(function (j) {
        if (!j || !j.ok) return;
        $$('[data-badge]').forEach(function (b) { var n = j.counts[b.getAttribute('data-badge')] || 0; b.textContent = n > 99 ? '99+' : n; b.hidden = n === 0; });
        $$('[data-notif-count]').forEach(function (b) { var n = j.counts.notifications || 0; b.textContent = n > 99 ? '99+' : n; b.hidden = n === 0; });
      });
    };
    setInterval(poll, 60000);
  }

  /* ---------------- Cookie notice, analytics ---------------- */
  function loadGA() {
    if (CFG.gtm && !window.__gtmLoaded) {
      window.__gtmLoaded = true; window.dataLayer = window.dataLayer || []; window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
      var g = doc.createElement('script'); g.async = true; g.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(CFG.gtm); doc.head.appendChild(g);
    }
    if (!CFG.ga || window.__gaLoaded) return; window.__gaLoaded = true;
    var s = doc.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(CFG.ga); doc.head.appendChild(s);
    window.dataLayer = window.dataLayer || []; window.gtag = function () { window.dataLayer.push(arguments); }; gtag('js', new Date()); gtag('config', CFG.ga, { anonymize_ip: true });
  }
  (function () {
    var b = $('#cookie-banner'), c = null;
    try { c = localStorage.getItem('ch-cookie'); } catch (e) {}
    if (c === 'all') loadGA();
    if (b && !c) setTimeout(function () { b.hidden = false; }, 900);
    delegate('click', '[data-cookie]', function (e, el) { var v = el.getAttribute('data-cookie'); try { localStorage.setItem('ch-cookie', v); } catch (x) {} b && (b.hidden = true); if (v === 'all') loadGA(); });
  })();

  /* ---------------- PWA ---------------- */
  if (CFG.pwa && 'serviceWorker' in navigator) {
    on(window, 'load', function () { navigator.serviceWorker.register(CH.url('/sw.js'), { scope: CH.base + '/' }).catch(function () {}); });
    var deferred = null;
    on(window, 'beforeinstallprompt', function (e) {
      e.preventDefault(); deferred = e;
      var visits = 0; try { visits = (parseInt(localStorage.getItem('ch-visits') || '0', 10) || 0); } catch (x) {}
      var dismissed = false; try { dismissed = localStorage.getItem('ch-install-dismissed') === '1'; } catch (x) {}
      if (!CFG.installPrompt || dismissed || visits < (CFG.installAfter || 3)) return;
      var bar = doc.createElement('div'); bar.className = 'cookie-banner'; bar.style.bottom = 'calc(var(--tabbar-h) + 12px)';
      bar.innerHTML = '<p><strong>Add ' + CH.esc(CFG.siteName) + ' to your home screen</strong> for quicker access.</p><div class="flex gap-2"><button class="btn btn-ghost btn-sm" data-x>Not now</button><button class="btn btn-primary btn-sm" data-i>Install</button></div>';
      doc.body.appendChild(bar);
      on($('[data-x]', bar), 'click', function () { bar.remove(); try { localStorage.setItem('ch-install-dismissed', '1'); } catch (x) {} });
      on($('[data-i]', bar), 'click', function () { deferred.prompt(); deferred.userChoice.finally(function () { bar.remove(); }); });
    });
    try { localStorage.setItem('ch-visits', String((parseInt(localStorage.getItem('ch-visits') || '0', 10) || 0) + 1)); } catch (x) {}
  }

  /* ---------------- Confetti (match celebration) ---------------- */
  CH.confetti = function () {
    if (CH.reduced() || !CFG.confetti) return;
    var c = doc.createElement('canvas'); c.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:300';
    doc.body.appendChild(c); var x = c.getContext('2d'); var w = c.width = innerWidth, h = c.height = innerHeight;
    var cols = ['#E8523F', '#F4A63A', '#6C5CE7', '#F7C35A', '#FF8A5B'];
    var ps = Array.from({ length: 120 }, function () { return { x: w / 2, y: h * 0.55, vx: (Math.random() - 0.5) * 16, vy: -Math.random() * 16 - 4, s: Math.random() * 8 + 4, r: Math.random() * 6, vr: (Math.random() - 0.5) * 0.4, c: cols[(Math.random() * cols.length) | 0] }; });
    var t0 = performance.now();
    (function frame(t) {
      x.clearRect(0, 0, w, h);
      ps.forEach(function (p) { p.vy += 0.38; p.x += p.vx; p.y += p.vy; p.r += p.vr; x.save(); x.translate(p.x, p.y); x.rotate(p.r); x.fillStyle = p.c; x.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2); x.restore(); });
      if (t - t0 < 2600) requestAnimationFrame(frame); else c.remove();
    })(t0);
  };

  /* ---------------- Flip card auth ---------------- */
  CH.flip = {
    init: function (rootEl) {
      var scene = rootEl; if (!scene || scene.__flip) return; scene.__flip = true;
      var faces = { login: $('[data-face="login"]', scene), register: $('[data-face="register"]', scene) };
      function size() {
        var side = scene.getAttribute('data-side') || 'login';
        var f = faces[side]; if (f) scene.style.setProperty('--face-h', f.scrollHeight + 'px');
      }
      function setSide(side, push) {
        if (!faces[side]) return;
        scene.setAttribute('data-side', side);
        Object.keys(faces).forEach(function (k) { faces[k].setAttribute('aria-hidden', k === side ? 'false' : 'true'); faces[k].inert = k !== side; });
        size();
        var inp = $('input:not([type=hidden]):not(.hp)', faces[side]);
        setTimeout(function () { inp && !matchMedia('(pointer: coarse)').matches && inp.focus({ preventScroll: true }); }, CH.reduced() || scene.getAttribute('data-style') === 'off' ? 0 : 520);
        if (push && scene.hasAttribute('data-sync-url')) { try { history.replaceState(null, '', CH.url('/' + side) + location.search); } catch (e) {} }
        if (scene.getAttribute('data-title-sync') === '1') doc.title = (side === 'login' ? 'Sign in' : 'Create account') + ' · ' + CFG.siteName;
      }
      delegate('click', '[data-flip-to]', function (e, el) { if (!scene.contains(el)) return; e.preventDefault(); setSide(el.getAttribute('data-flip-to'), true); });
      on(window, 'resize', size);
      if (window.ResizeObserver) { var ro = new ResizeObserver(size); Object.keys(faces).forEach(function (k) { ro.observe(faces[k]); }); }
      setSide(scene.getAttribute('data-side') || 'login', false);
      scene.classList.add('is-ready');
      scene.__setSide = setSide;
    }
  };
  CH.accountTabs = function (tabsRoot) {
    $$('[data-type]', tabsRoot).forEach(function (t) {
      on(t, 'click', function () {
        var type = t.getAttribute('data-type');
        tabsRoot.setAttribute('data-active', type);
        $$('[data-type]', tabsRoot).forEach(function (o) { o.setAttribute('aria-selected', o === t ? 'true' : 'false'); });
        var form = tabsRoot.closest('form') || doc; var hid = $('input[name=account_type]', form); if (hid) hid.value = type;
        $$('[data-for-type]', form).forEach(function (el) { var show = el.getAttribute('data-for-type') === type; el.hidden = !show; $$('input,select,textarea', el).forEach(function (i) { if (i.hasAttribute('data-req')) i.required = show; }); });
        tabsRoot.dispatchEvent(new CustomEvent('ch:type', { detail: type, bubbles: true }));
      });
    });
  };

  /* ---------------- Auth modal (landing page) ---------------- */
  delegate('click', '[data-auth-open]', function (e, el) {
    var modal = $('#auth-modal'); if (!modal) return;
    e.preventDefault();
    var side = el.getAttribute('data-auth-open');
    var scene = $('[data-flip-root]', modal); CH.modal.open(modal);
    CH.flip.init(scene); scene.__setSide && scene.__setSide(side, false);
    if (el.hasAttribute('data-type-preset')) { var t = $('[data-type="' + el.getAttribute('data-type-preset') + '"]', modal); t && t.click(); }
  });

  /* ---------------- Plan period switch (weekly / monthly / yearly) ---------------- */
  delegate('click', '[data-period-btn]', function (e, btn) {
    var root = btn.closest('[data-period-root]'); if (!root) return;
    var per = btn.getAttribute('data-period-btn');
    var btns = $$('[data-period-btn]', root);
    root.setAttribute('data-period', per);
    btns.forEach(function (o) { var on = o === btn; o.classList.toggle('is-on', on); o.setAttribute('aria-pressed', on ? 'true' : 'false'); });
    var sw = btn.closest('.pc-switch'); if (sw) sw.style.setProperty('--i', String(btns.indexOf(btn)));
    if (root.hasAttribute('data-period-url')) {
      try { var u = new URL(location.href); if (per === 'monthly') u.searchParams.delete('period'); else u.searchParams.set('period', per); history.replaceState(null, '', u.toString()); } catch (er) { /* ignore */ }
    }
  });

  /* ---------------- Boot ---------------- */
  function boot() {
    initReveal();
    $$('[data-flip-root]').forEach(function (s) { if (!s.closest('dialog')) CH.flip.init(s); });
    $$('[data-type-tabs]').forEach(CH.accountTabs);
    $$('[data-autosize]').forEach(function (t) { var f = function () { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 220) + 'px'; }; on(t, 'input', f); f(); });
    doc.dispatchEvent(new CustomEvent('ch:ready'));
  }
  if (doc.readyState === 'loading') on(doc, 'DOMContentLoaded', boot); else boot();
})();
