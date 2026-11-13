/* ConnectHub admin core (builder D1): charts, settings screen, live previews, bulk tables,
   photo review, tools and small helpers. Vanilla, one global: window.AD. Needs app.js (window.CH). */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH || {};
  var AD = window.AD = window.AD || {};

  function $(s, c) { return (c || doc).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
  function on(el, ev, fn, o) { if (el) el.addEventListener(ev, fn, o); }
  function delegate(ev, sel, fn) {
    doc.addEventListener(ev, function (e) {
      var t = e.target && e.target.closest ? e.target.closest(sel) : null;
      if (t && doc.contains(t)) fn.call(t, e, t);
    });
  }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
  function raf(fn) { var q = false; return function () { if (q) return; q = true; requestAnimationFrame(function () { q = false; fn(); }); }; }
  function jattr(el, name, fb) { try { return JSON.parse(el.getAttribute(name) || ''); } catch (e) { return fb; } }
  function toast(m, t, ms) { if (CH.toast) CH.toast(m, t || 'info', ms); }
  function api(url, data, opts) { return CH.api(url, Object.assign({ data: data }, opts || {})); }
  function sprite(name) { return (CH.url ? CH.url('/assets/icons/sprite.svg') : '/assets/icons/sprite.svg') + '#i-' + name; }
  function icon(name, cls) { return '<svg class="icon ' + (cls || 'w-4 h-4') + '" aria-hidden="true"><use href="' + sprite(name) + '"></use></svg>'; }
  AD.$ = $; AD.$$ = $$; AD.esc = esc; AD.debounce = debounce;

  /* =====================================================================
     Colour maths (port of color_ramp() in includes/view.php)
     ===================================================================== */
  function hexRgb(h) {
    h = String(h || '').replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    if (!/^[0-9a-f]{6}$/i.test(h)) return null;
    return [parseInt(h.substr(0, 2), 16), parseInt(h.substr(2, 2), 16), parseInt(h.substr(4, 2), 16)];
  }
  function rgbHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0, d = max - min;
    if (d !== 0) {
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
      else if (max === g) h = (b - r) / d + 2;
      else h = (r - g) / d + 4;
    }
    return [h / 6, s, l];
  }
  function hslRgb(h, s, l) {
    if (s === 0) { var v = Math.round(l * 255); return [v, v, v]; }
    var q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
    function f(t) {
      if (t < 0) t += 1; if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    }
    return [Math.round(f(h + 1 / 3) * 255), Math.round(f(h) * 255), Math.round(f(h - 1 / 3) * 255)];
  }
  function colorRamp(hex) {
    var rgb = hexRgb(hex); if (!rgb) return null;
    var hsl = rgbHsl(rgb[0], rgb[1], rgb[2]), h = hsl[0], s = hsl[1], l = hsl[2], out = {};
    var lights = { 50: 0.96, 100: 0.92, 200: 0.84, 300: 0.74, 400: 0.63 }, factors = { 600: 0.88, 700: 0.74, 800: 0.6, 900: 0.46 };
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900].forEach(function (shade) {
      if (shade === 500) { out[shade] = rgb.join(' '); return; }
      var c;
      if (factors[shade]) c = hslRgb(h, Math.min(1, s * 1.02), Math.max(0.08, l * factors[shade]));
      else c = hslRgb(h, Math.min(1, s * (shade <= 100 ? 0.9 : 1)), Math.max(l, lights[shade]));
      out[shade] = c.join(' ');
    });
    return out;
  }
  AD.colorRamp = colorRamp;

  /* =====================================================================
     Lightbox (any [data-lightbox][data-src])
     ===================================================================== */
  var lbDialog = null;
  AD.lightbox = function (src, caption) {
    if (!lbDialog) {
      lbDialog = doc.createElement('dialog');
      lbDialog.className = 'mod-lightbox';
      lbDialog.setAttribute('aria-label', 'Image preview');
      lbDialog.innerHTML = '<figure class="m-0"><img alt=""><figcaption class="text-center text-sm mt-3" style="color:#fff"></figcaption></figure>';
      doc.body.appendChild(lbDialog);
      on(lbDialog, 'click', function () { lbDialog.close(); });
    }
    $('img', lbDialog).src = src;
    $('figcaption', lbDialog).textContent = caption || '';
    if (!lbDialog.open) lbDialog.showModal();
  };
  delegate('click', '[data-lightbox]', function (e, el) {
    e.preventDefault();
    AD.lightbox(el.getAttribute('data-src') || el.getAttribute('href'), el.getAttribute('data-caption') || '');
  });

  /* =====================================================================
     Charts: <figure data-chart="{...}"> drawn as plain SVG
     ===================================================================== */
  function niceStep(raw) {
    var exp = Math.floor(Math.log(raw) / Math.LN10), f = raw / Math.pow(10, exp);
    return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * Math.pow(10, exp);
  }
  function compact(n) {
    var a = Math.abs(n);
    if (a >= 1e6) return (n / 1e6).toFixed(a >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
    if (a >= 1e3) return (n / 1e3).toFixed(a >= 1e4 ? 0 : 1).replace(/\.0$/, '') + 'k';
    return String(Math.round(n * 10) / 10);
  }
  function fmtVal(cfg, v, short) {
    if (cfg.format === 'money') return (cfg.symbol || '') + ' ' + (short ? compact(v) : Math.round(v).toLocaleString('en-US'));
    if (cfg.format === 'pct') return (Math.round(v * 10) / 10) + '%';
    return short ? compact(v) : (Math.round(v * 10) / 10).toLocaleString('en-US');
  }
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  function fmtLabel(cfg, l, full) {
    if (cfg.labelFormat === 'date' && /^\d{4}-\d{2}-\d{2}/.test(l)) {
      var d = new Date(l.substr(0, 10) + 'T00:00:00');
      return full ? DOW[d.getDay()] + ', ' + d.getDate() + ' ' + MON[d.getMonth()] + ' ' + d.getFullYear() : d.getDate() + ' ' + MON[d.getMonth()];
    }
    return l;
  }
  function color(cfg, i) { return cfg.series[i].color || 'var(--viz-' + ((i % 8) + 1) + ')'; }

  function drawChart(fig) {
    var cfg = fig.__cfg, canvas = $('.cp-chart-canvas', fig);
    var W = canvas.clientWidth, H = cfg.height || 220;
    if (W < 40) return;
    var n = cfg.labels.length, S = cfg.series.length;
    if (!n || !S) { canvas.innerHTML = '<p class="cp-empty-sm">No data for this period.</p>'; return; }
    var padL = 44, padR = 10, padT = 10, padB = 26, pw = W - padL - padR, ph = H - padT - padB;
    var max = 0;
    if (cfg.type === 'stack') {
      for (var i = 0; i < n; i++) { var t = 0; for (var s = 0; s < S; s++) t += Math.max(0, cfg.series[s].values[i] || 0); if (t > max) max = t; }
    } else {
      cfg.series.forEach(function (se) { se.values.forEach(function (v) { if (v > max) max = v; }); });
    }
    if (max <= 0) { canvas.innerHTML = '<p class="cp-empty-sm">Nothing recorded in this period.</p>'; return; }
    var step = max > 0 ? niceStep(max / 4) : 1;
    if (step < 1 && cfg.format !== 'pct') step = 1;
    var top = Math.max(step, step * Math.ceil(max / step - 1e-9)), ticks = Math.max(1, Math.round(top / step)), svg = '';
    if (max <= 0) { top = step * 4; ticks = 4; }
    function y(v) { return padT + ph - (v / top) * ph; }
    svg += '<svg viewBox="0 0 ' + W + ' ' + H + '" width="' + W + '" height="' + H + '" focusable="false">';
    for (var k = 0; k <= ticks; k++) {
      var tv = top / ticks * k, ty = y(tv);
      svg += '<line class="' + (k === 0 ? 'ax-line' : 'grid-line') + '" x1="' + padL + '" x2="' + (W - padR) + '" y1="' + ty + '" y2="' + ty + '"/>';
      svg += '<text x="' + (padL - 8) + '" y="' + (ty + 4) + '" text-anchor="end">' + esc(fmtVal(cfg, tv, true)) + '</text>';
    }
    var isBars = cfg.type === 'bar' || cfg.type === 'stack';
    var band = pw / n;
    function xAt(i) { return isBars ? padL + band * i + band / 2 : (n === 1 ? padL + pw / 2 : padL + (pw * i) / (n - 1)); }
    var longest = 0;
    if (cfg.labelFormat !== 'date') cfg.labels.forEach(function (l) { longest = Math.max(longest, String(l).length); });
    var lw = cfg.labelFormat === 'date' ? 70 : Math.max(44, longest * 6.4 + 14);
    var every = Math.max(1, Math.ceil(n / Math.max(1, Math.floor(pw / lw))));
    for (var li = 0; li < n; li++) {
      if (li % every !== 0 && li !== n - 1) continue;
      if (li !== n - 1 && n - 1 - li < every / 2) continue;
      svg += '<text x="' + xAt(li) + '" y="' + (H - 6) + '" text-anchor="middle">' + esc(fmtLabel(cfg, cfg.labels[li], false)) + '</text>';
    }
    var gid = 'g' + Math.random().toString(36).slice(2, 8);
    if (cfg.type === 'line') {
      cfg.series.forEach(function (se, si) {
        var pts = se.values.map(function (v, i) { return xAt(i).toFixed(1) + ',' + y(v || 0).toFixed(1); });
        if (S === 1) {
          svg += '<defs><linearGradient id="' + gid + '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:' + color(cfg, si) + ';stop-opacity:.22"/><stop offset="1" style="stop-color:' + color(cfg, si) + ';stop-opacity:0"/></linearGradient></defs>';
          svg += '<path d="M' + xAt(0).toFixed(1) + ',' + (padT + ph) + ' L' + pts.join(' L') + ' L' + xAt(n - 1).toFixed(1) + ',' + (padT + ph) + ' Z" fill="url(#' + gid + ')"/>';
        }
        svg += '<polyline class="ln" style="stroke:' + color(cfg, si) + '" points="' + pts.join(' ') + '"/>';
      });
      svg += '<line class="cross" x1="0" x2="0" y1="' + padT + '" y2="' + (padT + ph) + '"/>';
      cfg.series.forEach(function (se, si) { svg += '<circle class="dot" r="4.5" cx="0" cy="0" style="fill:' + color(cfg, si) + '"/>'; });
    } else if (cfg.type === 'bar') {
      var gw = Math.min(band * 0.7, 18 * S + 6), bw = Math.max(3, (gw - (S - 1) * 2) / S);
      for (var bi = 0; bi < n; bi++) {
        for (var bs = 0; bs < S; bs++) {
          var bv = cfg.series[bs].values[bi] || 0, bh = Math.max(bv > 0 ? 2 : 0, (bv / top) * ph);
          var bx = xAt(bi) - gw / 2 + bs * (bw + 2);
          svg += '<path class="bar" data-i="' + bi + '" style="fill:' + color(cfg, bs) + '" d="' + roundTop(bx, padT + ph - bh, bw, bh, 4) + '"/>';
        }
      }
    } else {
      var sw = Math.min(band * 0.7, 34);
      for (var ci = 0; ci < n; ci++) {
        var acc = 0;
        for (var cs = 0; cs < S; cs++) {
          var cv = Math.max(0, cfg.series[cs].values[ci] || 0);
          if (cv <= 0) continue;
          var ch = (cv / top) * ph, cy = padT + ph - acc - ch;
          svg += '<rect class="bar" data-i="' + ci + '" style="fill:' + color(cfg, cs) + ';stroke:rgb(var(--card));stroke-width:1.5" x="' + (xAt(ci) - sw / 2).toFixed(1) + '" y="' + cy.toFixed(1) + '" width="' + sw.toFixed(1) + '" height="' + Math.max(1, ch).toFixed(1) + '" rx="3"/>';
          acc += ch;
        }
      }
    }
    svg += '<rect class="hit" x="' + padL + '" y="' + padT + '" width="' + pw + '" height="' + ph + '" fill="transparent" style="cursor:crosshair"/></svg><div class="cp-tip" role="presentation"></div>';
    canvas.innerHTML = svg;

    var tip = $('.cp-tip', canvas), hit = $('.hit', canvas), cross = $('.cross', canvas), dots = $$('.dot', canvas), bars = $$('.bar', canvas);
    function show(i, px) {
      var html = '<b>' + esc(fmtLabel(cfg, cfg.labels[i], true)) + '</b>';
      cfg.series.forEach(function (se, si) {
        html += '<span><span><i style="background:' + color(cfg, si) + '"></i>' + esc(S > 1 ? se.name : (se.name || '')) + '</span><strong>' + esc(fmtVal(cfg, se.values[i] || 0, false)) + '</strong></span>';
      });
      if (cfg.type === 'stack' && S > 1) {
        var tot = 0; cfg.series.forEach(function (se) { tot += se.values[i] || 0; });
        html += '<span><span>Total</span><strong>' + esc(fmtVal(cfg, tot, false)) + '</strong></span>';
      }
      tip.innerHTML = html;
      var tw = tip.offsetWidth, left = px + 14;
      if (left + tw > W - 4) left = px - tw - 14;
      tip.style.left = Math.max(4, left) + 'px'; tip.style.top = '8px';
      tip.classList.add('is-on'); canvas.classList.add('is-hover');
      if (cross) { cross.setAttribute('x1', xAt(i)); cross.setAttribute('x2', xAt(i)); }
      dots.forEach(function (d, si) { d.setAttribute('cx', xAt(i)); d.setAttribute('cy', y(cfg.series[si].values[i] || 0)); });
      bars.forEach(function (b) { b.classList.toggle('is-on', +b.getAttribute('data-i') === i); });
    }
    function hide() { tip.classList.remove('is-on'); canvas.classList.remove('is-hover'); bars.forEach(function (b) { b.classList.remove('is-on'); }); }
    function move(e) {
      var r = hit.getBoundingClientRect(), px = e.clientX - r.left, i;
      if (isBars) i = Math.floor(px / band); else i = n === 1 ? 0 : Math.round((px / pw) * (n - 1));
      i = Math.max(0, Math.min(n - 1, i));
      show(i, padL + (isBars ? band * i + band / 2 : (n === 1 ? pw / 2 : (pw * i) / (n - 1))));
    }
    on(hit, 'pointermove', move); on(hit, 'pointerdown', move); on(hit, 'pointerleave', hide);
  }
  function roundTop(x, y, w, h, r) {
    r = Math.min(r, w / 2, h);
    if (h <= 0) return 'M0,0';
    return 'M' + x + ',' + (y + h) + ' V' + (y + r) + ' Q' + x + ',' + y + ' ' + (x + r) + ',' + y + ' H' + (x + w - r) + ' Q' + (x + w) + ',' + y + ' ' + (x + w) + ',' + (y + r) + ' V' + (y + h) + ' Z';
  }
  function initCharts() {
    $$('[data-chart]').forEach(function (fig) {
      if (fig.__cfg) return;
      fig.__cfg = jattr(fig, 'data-chart', null);
      if (!fig.__cfg) return;
      var redraw = raf(function () { drawChart(fig); });
      if (window.ResizeObserver) new ResizeObserver(redraw).observe($('.cp-chart-canvas', fig)); else on(window, 'resize', redraw);
      redraw();
    });
  }

  /* =====================================================================
     Tables: select all + bulk bar, row links, auto-submit filters
     ===================================================================== */
  function initBulk(form) {
    var items = function () { return $$('[data-bulk-item]', form); };
    var all = $('[data-bulk-all]', form), bar = $('[data-bulk-bar]', form), count = $('[data-bulk-count]', form);
    function update() {
      var sel = items().filter(function (c) { return c.checked; });
      items().forEach(function (c) { var tr = c.closest('tr, .mod-card'); if (tr) tr.classList.toggle('is-selected', c.checked); });
      if (all) { all.checked = sel.length > 0 && sel.length === items().length; all.indeterminate = sel.length > 0 && sel.length < items().length; }
      if (bar) bar.hidden = sel.length === 0;
      if (count) count.textContent = sel.length + ' selected';
    }
    on(form, 'change', function (e) {
      if (e.target === all) items().forEach(function (c) { c.checked = all.checked; });
      if (e.target === all || (e.target.matches && e.target.matches('[data-bulk-item]'))) update();
    });
    var lastIdx = -1;
    on(form, 'click', function (e) {
      var c = e.target.closest ? e.target.closest('[data-bulk-item]') : null;
      if (!c) return;
      var list = items(), idx = list.indexOf(c);
      if (e.shiftKey && lastIdx > -1) {
        var a = Math.min(idx, lastIdx), b = Math.max(idx, lastIdx);
        for (var i = a; i <= b; i++) list[i].checked = c.checked;
        update();
      }
      lastIdx = idx;
    });
    $$('[data-bulk-action]', form).forEach(function (btn) {
      on(btn, 'click', function (e) {
        e.preventDefault();
        var act = btn.getAttribute('data-bulk-action'), n = items().filter(function (c) { return c.checked; }).length;
        if (!n) return;
        var go = function (reason) {
          var hid = $('input[name=action]', form), rs = $('input[name=reason]', form);
          if (hid) hid.value = act; if (rs) rs.value = reason || '';
          form.submit();
        };
        if (btn.hasAttribute('data-needs-reason')) {
          var dlg = $('#bulk-reason'); if (!dlg) return go('');
          $('[data-bulk-reason-title]', dlg).textContent = btn.getAttribute('data-title') || 'Add a reason';
          var ta = $('textarea', dlg); ta.value = '';
          dlg.__go = function () { var v = ta.value.trim(); if (!v) { ta.focus(); return false; } go(v); return true; };
          CH.modal.open(dlg); return;
        }
        CH.confirm({ title: btn.getAttribute('data-title') || 'Are you sure?', message: (btn.getAttribute('data-message') || 'Apply this to the selected members?').replace('{n}', n), confirm: btn.getAttribute('data-label') || 'Yes, continue', danger: btn.hasAttribute('data-danger') }).then(function (ok) { if (ok) go(''); });
      });
    });
    update();
  }
  delegate('click', '[data-bulk-reason-go]', function (e, el) {
    e.preventDefault();
    var dlg = el.closest('dialog'); if (dlg && dlg.__go && dlg.__go()) dlg.close();
  });
  delegate('click', 'tr[data-href]', function (e, tr) {
    if (e.target.closest('a, button, input, select, label, textarea, .menu')) return;
    var href = tr.getAttribute('data-href');
    if (e.metaKey || e.ctrlKey) window.open(href, '_blank'); else location.href = href;
  });
  delegate('change', '[data-autosubmit]', function (e, el) { var f = el.form || el.closest('form'); if (f) f.requestSubmit ? f.requestSubmit() : f.submit(); });

  /* Typed confirmation: the submit button stays disabled until the word matches */
  delegate('input', '[data-confirm-word]', function (e, el) {
    var f = el.form; if (!f) return;
    var ok = el.value.trim() === el.getAttribute('data-confirm-word');
    $$('[data-confirm-target]', f).forEach(function (b) { b.disabled = !ok; });
  });

  /* Forms that warn before the page is left with unsaved edits */
  function initDirtyGuard(form) {
    var ser = function () { var parts = []; new FormData(form).forEach(function (v, k) { if (k !== '_csrf' && k !== 'csrf') parts.push(k + '=' + (typeof v === 'string' ? v : v.name)); }); return parts.join('&'); };
    var base = ser(), submitting = false;
    on(form, 'submit', function () { submitting = true; });
    on(window, 'beforeunload', function (e) { if (!submitting && ser() !== base) { e.preventDefault(); e.returnValue = ''; } });
  }

  /* Tabs that can be opened with #hash (uses the data-tabs markup from app.js) */
  function initHashTabs() {
    var h = (location.hash || '').replace('#', '');
    if (!h) return;
    var t = $('[data-tabs] [data-tab="' + h + '"]');
    if (t) t.click();
  }

  /* Roles matrix quick set */
  delegate('click', '[data-matrix-set]', function (e, el) {
    e.preventDefault();
    var m = el.closest('[data-matrix]'); if (!m) return;
    var level = el.getAttribute('data-matrix-set'), group = el.getAttribute('data-matrix-group');
    $$('input[type=radio][value="' + level + '"]', m).forEach(function (r) {
      if (r.disabled) return;
      if (group && r.closest('tr').getAttribute('data-group') !== group) return;
      r.checked = true;
    });
    m.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* Logs: client side line filter and jump to the end */
  function initLog(pre) {
    pre.scrollTop = pre.scrollHeight;
    var f = $('[data-log-filter]');
    if (f) on(f, 'input', debounce(function () {
      var q = f.value.toLowerCase();
      $$('[data-ln]', pre).forEach(function (l) { l.hidden = q !== '' && l.textContent.toLowerCase().indexOf(q) < 0; });
    }, 120));
  }

  /* =====================================================================
     Settings screen
     ===================================================================== */
  function initSettings(root) {
    var panels = $$('.set-panel', root), navLinks = $$('[data-set-tab]'), search = $('[data-set-search]'), changedBox = $('[data-set-changed]');
    var emptyEl = $('.set-empty', root), bar = $('[data-set-bar]'), current = root.getAttribute('data-active') || (panels[0] && panels[0].getAttribute('data-panel'));
    var snap = {}, saving = false;

    function rowFor(k) { return root.querySelector('.set-row[data-key="' + k + '"]'); }
    function inputsOf(row) { return $$('input, select, textarea', row).filter(function (i) { return i.type !== 'file' && !(i.type === 'range'); }); }

    function getValue(key) {
      var row = typeof key === 'string' ? rowFor(key) : key;
      if (!row) return '';
      var type = row.getAttribute('data-type');
      if (type === 'image') { var im = $('.set-image', row); return im ? im.getAttribute('data-path') || '' : ''; }
      if (type === 'bool') { var cb = $('input[type=checkbox]', row); return cb && cb.checked ? '1' : '0'; }
      if (type === 'matrix') { var mc = $('input[type=checkbox]', row); return mc && mc.checked ? 'visible' : 'hidden'; }
      if (type === 'multiselect') return $$('input[type=checkbox]:checked', row).map(function (c) { return c.value; }).filter(Boolean);
      if (type === 'secret') { var si = $('input[type=password]', row), cl = $('input[type=checkbox]', row); return (si ? si.value : '') + (cl && cl.checked ? '|clear' : ''); }
      if (type === 'select') { var rd = $('input[type=radio]:checked', row); if (rd) return rd.value; var sl = $('select', row); return sl ? sl.value : ''; }
      var i = $('input:not([type=hidden]):not([type=range]):not([type=color]), textarea', row);
      return i ? i.value : '';
    }
    function setValue(row, val) {
      var type = row.getAttribute('data-type');
      if (type === 'bool') { var cb = $('input[type=checkbox]', row); if (cb) { cb.checked = val === true || val === 1 || val === '1'; syncState(cb); } return; }
      if (type === 'matrix') { var mc = $('input[type=checkbox]', row); if (mc) mc.checked = val === 'visible'; return; }
      if (type === 'multiselect') { var vals = (Array.isArray(val) ? val : []).map(String); $$('input[type=checkbox]', row).forEach(function (c) { c.checked = vals.indexOf(c.value) > -1; }); return; }
      if (type === 'select') { var rd = $$('input[type=radio]', row); if (rd.length) { rd.forEach(function (r) { r.checked = r.value === String(val); }); } else { var sl = $('select', row); if (sl) sl.value = String(val); } return; }
      if (type === 'secret' || type === 'image') return;
      var i = $('input:not([type=hidden]):not([type=range]):not([type=color]), textarea', row);
      if (!i) return;
      i.value = val == null ? '' : String(val);
      if (type === 'number') { var rg = $('input[type=range]', row); if (rg) rg.value = i.value; }
      if (type === 'color') { var pk = $('.set-color-pick', row); var c = hexRgb(i.value); if (pk && c) pk.value = '#' + c.map(function (n) { return ('0' + n.toString(16)).slice(-2); }).join(''); i.value = String(i.value).toUpperCase(); drawRamp(row); }
      syncCounter(i);
    }
    function ser(row) { var t = row.getAttribute('data-type'); return t === 'image' ? '' : JSON.stringify(getValue(row)); }

    function takeSnap(scope) { $$('.set-row', scope || root).forEach(function (r) { snap[r.getAttribute('data-key')] = ser(r); }); }
    function dirtyRows(form) { return $$('.set-row', form).filter(function (r) { return r.getAttribute('data-type') !== 'image' && snap[r.getAttribute('data-key')] !== ser(r); }); }
    function updateDirty() {
      var total = 0, secs = 0;
      $$('[data-settings-form]', root).forEach(function (f) {
        var n = dirtyRows(f).length, tag = $('[data-dirty]', f);
        if (tag) tag.hidden = n === 0;
        f.classList.toggle('is-dirty', n > 0);
        total += n; if (n) secs++;
      });
      if (bar) {
        bar.hidden = total === 0;
        var tx = $('[data-set-bar-text]', bar);
        if (tx) tx.textContent = total + (total === 1 ? ' unsaved change' : ' unsaved changes') + (secs > 1 ? ' in ' + secs + ' sections' : '');
      }
      return total;
    }

    /* ---- derived widgets ---- */
    function syncState(cb) { var t = cb.getAttribute('data-state-label'); if (t) { var el = $(t); if (el) el.textContent = cb.checked ? 'On' : 'Off'; } }
    function syncCounter(inp) {
      if (!inp.id) return;
      var c = $('[data-count-for="' + inp.id + '"]');
      if (c) c.textContent = inp.value.length + ' / ' + c.getAttribute('data-max');
    }
    function drawRamp(row) {
      var strip = $('.set-ramp', row); if (!strip) return;
      var hex = $('.set-color-hex', row).value, r = colorRamp(hex);
      if (!r) return;
      strip.innerHTML = Object.keys(r).map(function (k) { return '<i style="background:rgb(' + r[k] + ')" title="' + k + '"></i>'; }).join('');
    }
    function sums() {
      $$('[data-sum-prefix]', root).forEach(function (f) {
        var p = f.getAttribute('data-sum-prefix'), target = +f.getAttribute('data-sum-target') || 100, t = 0;
        $$('input[type=number]', f).forEach(function (i) { if (i.name.indexOf('v[' + p) === 0) t += parseFloat(i.value) || 0; });
        var out = $('[data-sum-out]', f); if (!out) return;
        out.textContent = 'Weights add up to ' + t + ' of ' + target + (t === target ? '.' : '. They should total ' + target + '.');
        out.classList.toggle('is-ok', t === target); out.classList.toggle('is-bad', t !== target);
      });
    }
    function tabCounts() {
      panels.forEach(function (p) {
        var n = $$('.set-row[data-custom="1"]', p).length, slug = p.getAttribute('data-panel');
        var b = $('[data-tab-count="' + slug + '"]'); if (b) { b.textContent = n; b.hidden = n === 0; }
      });
    }
    function markCustom(key, on_) { var r = rowFor(key); if (r) r.setAttribute('data-custom', on_ ? '1' : '0'); }

    function evalDeps() {
      $$('.set-row[data-depends]', root).forEach(function (row) {
        var deps = jattr(row, 'data-depends', []), ok = true;
        deps.forEach(function (d) {
          var v = getValue(d[0]), op = d[1], vals = (d[2] || []).map(String);
          if (op === 'on') ok = ok && v === '1';
          else if (op === 'off') ok = ok && v !== '1';
          else if (op === 'in') ok = ok && vals.indexOf(String(v)) > -1;
          else if (op === 'not_in') ok = ok && vals.indexOf(String(v)) < 0;
        });
        row.classList.toggle('is-hidden-dep', !ok);
      });
    }

    /* ---- errors ---- */
    function setError(row, msg) {
      if (!row) return;
      row.classList.add('has-error');
      var el = $('[data-error]', row); if (el) el.textContent = msg;
    }
    function clearError(row) { row.classList.remove('has-error'); var el = $('[data-error]', row); if (el) el.textContent = ''; }

    /* ---- input handling ---- */
    var refreshPreview = raf(function () { root.dispatchEvent(new CustomEvent('ad:preview')); });
    function onInput(e) {
      var t = e.target, row = t.closest ? t.closest('.set-row') : null;
      if (!row || !root.contains(row)) return;
      if (t.matches('input[type=range][data-range-for]')) {
        var num = doc.getElementById(t.getAttribute('data-range-for')); if (num) { num.value = t.value; num.dispatchEvent(new Event('input', { bubbles: true })); }
        return;
      }
      if (t.matches('.set-color-pick')) {
        var hx = $('.set-color-hex', row); hx.value = t.value.toUpperCase(); hx.dispatchEvent(new Event('input', { bubbles: true })); return;
      }
      if (t.matches('.set-color-hex')) {
        var v = t.value.trim(), m = /^#?([0-9a-f]{6})$/i.exec(v);
        if (m) { var pk = $('.set-color-pick', row); if (pk) pk.value = '#' + m[1].toLowerCase(); drawRamp(row); clearError(row); }
        else if (v !== '') setError(row, 'Use a colour like #E8523F.'); else clearError(row);
      } else if (t.type === 'number') {
        var rg = $('input[type=range]', row); if (rg && t.value !== '') rg.value = t.value;
        t.classList.remove('is-invalid');
      } else {
        if (row.classList.contains('has-error')) clearError(row);
      }
      if (t.type === 'checkbox') syncState(t);
      if (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA') syncCounter(t);
      if (t.matches('input[type=password]') && t.value) { var ck = $('input[type=checkbox]', row); if (ck) ck.checked = false; }
      if (t.matches('[name^="clear["]') && t.checked) { var pw = $('input[type=password]', row); if (pw) pw.value = ''; }
      evalDeps(); sums(); updateDirty(); refreshPreview();
    }
    on(root, 'input', onInput);
    on(root, 'change', onInput);
    on(root, 'blur', function (e) {
      var t = e.target;
      if (t.matches && t.matches('.set-color-hex')) { var m = /^#?([0-9a-f]{6})$/i.exec(t.value.trim()); if (m) { t.value = '#' + m[1].toUpperCase(); } }
      if (t.matches && t.matches('input[type=number]') && t.value !== '') {
        var mn = t.getAttribute('min'), mx = t.getAttribute('max'), v = parseFloat(t.value);
        if (!isNaN(v)) { if (mn !== null && v < +mn) t.value = mn; if (mx !== null && v > +mx) t.value = mx; t.dispatchEvent(new Event('input', { bubbles: true })); }
      }
    }, true);

    /* ---- apply typed values coming back from the server ---- */
    function applyValues(values, keepCustom, urls) {
      Object.keys(values || {}).forEach(function (k) {
        var row = rowFor(k); if (!row) return;
        if (row.getAttribute('data-type') === 'image') {
          var wrap = $('.set-image', row);
          if (values[k] && urls && urls[k]) { wrap.setAttribute('data-path', values[k]); wrap.setAttribute('data-url', urls[k]); renderImage(wrap, urls[k], String(values[k]).split('/').pop(), wrap.getAttribute('data-kind')); }
          else clearImage(wrap);
        }
        else setValue(row, values[k]);
        if (!keepCustom) markCustom(k, false);
        clearError(row);
        snap[k] = ser(row);
      });
      evalDeps(); sums(); tabCounts(); updateDirty(); refreshPreview();
    }

    /* ---- save ---- */
    function pill(form) {
      var head = $('.set-section-head .flex.items-center.gap-2.shrink-0', form) || $('.set-section-head', form);
      var old = $('.set-saved', form); if (old) old.remove();
      var p = doc.createElement('span'); p.className = 'set-saved'; p.innerHTML = icon('circle-check', 'w-4 h-4') + 'Saved';
      head.insertBefore(p, head.firstChild);
      setTimeout(function () { p.remove(); }, 3200);
    }
    function setSecret(key, isSet) {
      var row = rowFor(key); if (!row) return;
      var inp = $('input[type=password]', row), badge = $('[data-secret-state]', row);
      if (inp) { inp.value = ''; inp.placeholder = isSet ? 'Saved. Type to replace it' : 'Not set'; }
      if (badge) { badge.textContent = isSet ? 'Set' : 'Not set'; badge.classList.toggle('badge-success', isSet); }
      var cl = $('input[type=checkbox][name^="clear["]', row);
      if (cl) { cl.checked = false; if (!isSet) cl.closest('label').hidden = true; else cl.closest('label').hidden = false; }
      else if (isSet) {
        var lab = doc.createElement('label'); lab.className = 'check text-xs mt-2';
        lab.innerHTML = '<input type="checkbox" name="clear[' + key + ']" value="1"><span>Remove the saved value</span>';
        $('.set-secret', row).appendChild(lab);
      }
    }
    function swapTheme(css) {
      var st = $$('head style').filter(function (s) { return s.textContent.indexOf('--primary-500') > -1; })[0];
      if (st) st.textContent = css;
    }
    function saveForm(form) {
      var btn = $('[data-save]', form);
      $$('.set-row.has-error', form).forEach(clearError);
      if (btn) { btn.disabled = true; btn.classList.add('is-loading'); }
      return api(form.action, new FormData(form)).then(function (j) {
        if (btn) { btn.disabled = false; btn.classList.remove('is-loading'); }
        var errKeys = Object.keys(j.errors || {});
        errKeys.forEach(function (k) { setError(rowFor(k), j.errors[k]); });
        if (j.values) applyValues(j.values, true);
        if (j.custom) Object.keys(j.custom).forEach(function (k) { markCustom(k, j.custom[k]); });
        if (j.secrets) Object.keys(j.secrets).forEach(function (k) { setSecret(k, j.secrets[k]); });
        $$('.set-row', form).forEach(function (r) { var k = r.getAttribute('data-key'); if (errKeys.indexOf(k) < 0) snap[k] = ser(r); });
        if (j.theme) swapTheme(j.theme);
        evalDeps(); tabCounts(); updateDirty(); refreshPreview();
        if (errKeys.length) {
          var first = rowFor(errKeys[0]);
          if (first) { var sec = first.closest('.set-panel'); if (sec && sec.hidden) showTab(sec.getAttribute('data-panel'), true); first.scrollIntoView({ behavior: 'smooth', block: 'center' }); var fi = $('input, select, textarea', first); fi && fi.focus({ preventScroll: true }); }
          toast(j.saved ? 'Saved ' + j.saved + ', but ' + errKeys.length + (errKeys.length === 1 ? ' field needs' : ' fields need') + ' attention.' : 'Fix the highlighted ' + (errKeys.length === 1 ? 'field' : 'fields') + ' and save again.', 'error');
        } else if (j.ok === false) {
          toast(j.error || 'Could not save.', 'error');
        } else {
          pill(form);
          toast(j.saved ? 'Saved ' + j.saved + (j.saved === 1 ? ' setting' : ' settings') : 'Nothing to save. Everything is up to date.', j.saved ? 'success' : 'info');
        }
        return j;
      });
    }
    on(root, 'submit', function (e) {
      var form = e.target; if (!form.matches || !form.matches('[data-settings-form]')) return;
      e.preventDefault(); e.stopImmediatePropagation();
      if (saving) return; saving = true;
      saveForm(form).then(function () { saving = false; }, function () { saving = false; });
    }, true);
    if (bar) {
      on($('[data-set-save-all]', bar), 'click', function () {
        var forms = $$('[data-settings-form]', root).filter(function (f) { return dirtyRows(f).length; }), i = 0;
        (function next() { if (i >= forms.length) return; saveForm(forms[i++]).then(next); })();
      });
      on($('[data-set-discard]', bar), 'click', function () {
        CH.confirm({ title: 'Discard changes?', message: 'Everything you changed since the last save is lost.', confirm: 'Discard' }).then(function (ok) { if (ok) { allowLeave = true; location.reload(); } });
      });
    }
    var allowLeave = false;
    on(window, 'beforeunload', function (e) { if (!allowLeave && updateDirty() > 0) { e.preventDefault(); e.returnValue = ''; } });
    on(doc, 'keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        var f = doc.activeElement && doc.activeElement.closest ? doc.activeElement.closest('[data-settings-form]') : null;
        if (!f) f = $$('[data-settings-form]', root).filter(function (x) { return dirtyRows(x).length; })[0];
        if (f) { e.preventDefault(); f.requestSubmit ? f.requestSubmit() : saveForm(f); }
      }
    });

    /* ---- resets ---- */
    function resetKeys(keys) {
      return api('/api/admin/settings/reset', { keys: keys }).then(function (j) {
        if (!j.ok) { toast(j.error || 'Could not reset.', 'error'); return j; }
        applyValues(j.values || {}, false, j.urls || {});
        if (j.theme) swapTheme(j.theme);
        toast(keys.length === 1 ? 'Back to the default.' : 'Section reset to defaults.', 'success');
        return j;
      });
    }
    delegate('click', '[data-reset]', function (e, el) {
      if (!root.contains(el)) return;
      e.preventDefault();
      var k = el.getAttribute('data-reset');
      resetKeys([k]);
    });
    delegate('click', '[data-reset-section]', function (e, el) {
      if (!root.contains(el)) return;
      e.preventDefault();
      var f = el.closest('form'), keys = jattr($('[data-section-keys]', f), 'data-section-keys', []);
      CH.confirm({ title: 'Reset this section?', message: 'Every setting in "' + ($('.admin-card-title', f).textContent || 'this section') + '" goes back to its default. Uploaded files in it are removed.', confirm: 'Reset section' }).then(function (ok) { if (ok) resetKeys(keys); });
    });

    /* ---- file settings ---- */
    function imgRow(wrap) { return wrap.closest('.set-row'); }
    function renderImage(wrap, url, name, kind) {
      var prev = $('[data-img-preview]', wrap), has = !!url;
      prev.innerHTML = has ? (kind === 'video' ? '<video src="' + esc(url) + '" muted loop playsinline preload="metadata"></video>' : '<img src="' + esc(url) + '" alt="">') : '<span class="set-image-empty">' + icon(kind === 'video' ? 'video' : 'image', 'w-6 h-6') + '</span>';
      $('[data-img-name]', wrap).textContent = has ? name : 'Nothing uploaded';
      var lab = $('[data-img-upload-label]', wrap); if (lab) lab.textContent = has ? 'Replace' : 'Upload';
      var rm = $('[data-img-remove]', wrap); if (rm) rm.hidden = !has;
    }
    function clearImage(wrap) { if (!wrap) return; wrap.setAttribute('data-path', ''); wrap.setAttribute('data-url', ''); renderImage(wrap, '', '', wrap.getAttribute('data-kind')); }
    delegate('click', '[data-img-upload]', function (e, el) { if (!root.contains(el)) return; e.preventDefault(); var i = $('[data-img-input]', el.closest('.set-image')); i && i.click(); });
    on(root, 'change', function (e) {
      var inp = e.target; if (!inp.matches || !inp.matches('[data-img-input]')) return;
      var file = inp.files && inp.files[0]; if (!file) return;
      var wrap = inp.closest('.set-image'), row = imgRow(wrap), prev = $('[data-img-preview]', wrap), kind = wrap.getAttribute('data-kind');
      clearError(row);
      var max = +wrap.getAttribute('data-max-mb') || (kind === 'video' ? 40 : 8);
      if (file.size > max * 1024 * 1024) { setError(row, 'That file is larger than ' + max + ' MB.'); inp.value = ''; return; }
      // Show the new file straight away in the previews, then confirm with the server.
      var local = URL.createObjectURL(file), before = { path: wrap.getAttribute('data-path'), url: wrap.getAttribute('data-url') };
      renderImage(wrap, local, file.name, kind); wrap.setAttribute('data-url', local); refreshPreview();
      prev.classList.add('is-busy');
      var fd = new FormData(); fd.append('key', row.getAttribute('data-key')); fd.append('file', file);
      api('/api/admin/settings/image', fd).then(function (j) {
        prev.classList.remove('is-busy'); inp.value = '';
        if (j.ok) {
          wrap.setAttribute('data-path', j.path); wrap.setAttribute('data-url', j.url);
          renderImage(wrap, j.url, j.name || j.path.split('/').pop(), kind);
          row.setAttribute('data-custom', '1'); tabCounts(); refreshPreview();
          toast('Saved', 'success', 2200);
        } else {
          wrap.setAttribute('data-path', before.path || ''); wrap.setAttribute('data-url', before.url || '');
          renderImage(wrap, before.url, (before.path || '').split('/').pop(), kind); refreshPreview();
          setError(row, j.error || 'Upload failed.'); toast(j.error || 'Upload failed.', 'error');
        }
      });
    });
    delegate('click', '[data-img-pick]', function (e, el) {
      if (!root.contains(el)) return;
      e.preventDefault();
      var wrap = el.closest('.set-image'), row = imgRow(wrap), kind = wrap.getAttribute('data-kind'), prev = $('[data-img-preview]', wrap);
      if (!AD.mediaPicker) { toast('The media picker did not load. Refresh the page and try again.', 'error'); return; }
      AD.mediaPicker({
        kind: kind === 'video' ? 'video' : 'image', current: wrap.getAttribute('data-path') || '',
        title: 'Choose for "' + $('.setting-label', row).textContent.trim() + '"',
        onPick: function (it) {
          if (it.path === wrap.getAttribute('data-path')) return;
          clearError(row);
          var before = { path: wrap.getAttribute('data-path'), url: wrap.getAttribute('data-url') };
          prev.classList.add('is-busy');
          var fd = new FormData(); fd.append('key', row.getAttribute('data-key')); fd.append('pick', it.path);
          api('/api/admin/settings/image', fd).then(function (j) {
            prev.classList.remove('is-busy');
            if (j.ok) {
              wrap.setAttribute('data-path', j.path); wrap.setAttribute('data-url', j.url);
              renderImage(wrap, j.url, it.title || j.path.split('/').pop(), kind);
              row.setAttribute('data-custom', '1'); tabCounts(); refreshPreview();
              toast('Saved', 'success', 2200);
            } else {
              wrap.setAttribute('data-path', before.path || ''); wrap.setAttribute('data-url', before.url || '');
              setError(row, j.error || 'Could not use that file.'); toast(j.error || 'Could not use that file.', 'error');
            }
          });
        }
      });
    });
    delegate('click', '[data-img-remove]', function (e, el) {
      if (!root.contains(el)) return;
      e.preventDefault();
      var wrap = el.closest('.set-image'), row = imgRow(wrap);
      CH.confirm({ title: 'Remove this file?', message: 'The site goes back to its default for "' + $('.setting-label', row).textContent.trim() + '".', confirm: 'Remove' }).then(function (ok) {
        if (!ok) return;
        var fd = new FormData(); fd.append('key', row.getAttribute('data-key')); fd.append('remove', '1');
        api('/api/admin/settings/image', fd).then(function (j) {
          if (j.ok) { clearImage(wrap); row.setAttribute('data-custom', '0'); tabCounts(); refreshPreview(); toast('Removed', 'success', 2200); }
          else toast(j.error || 'Could not remove the file.', 'error');
        });
      });
    });
    ['dragenter', 'dragover'].forEach(function (ev) { on(root, ev, function (e) { var w = e.target.closest && e.target.closest('.set-image'); if (w && !w.querySelector('[data-img-input]:disabled')) { e.preventDefault(); w.classList.add('is-over'); } }); });
    ['dragleave', 'drop'].forEach(function (ev) {
      on(root, ev, function (e) {
        var w = e.target.closest && e.target.closest('.set-image'); if (!w) return;
        w.classList.remove('is-over');
        if (ev === 'drop') {
          e.preventDefault(); var i = $('[data-img-input]', w);
          if (i && e.dataTransfer && e.dataTransfer.files.length) { i.files = e.dataTransfer.files; i.dispatchEvent(new Event('change', { bubbles: true })); }
        }
      });
    });

    /* ---- tabs ---- */
    function showTab(slug, push) {
      if (!$('.set-panel[data-panel="' + slug + '"]', root)) return false;
      current = slug; root.setAttribute('data-active', slug);
      navLinks.forEach(function (a) { var on_ = a.getAttribute('data-set-tab') === slug; a.classList.toggle('is-active', on_); if (on_) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current'); });
      applyFilter();
      if (push) { try { history.pushState({ tab: slug }, '', CH.url('/admin/settings/' + slug) + location.search); } catch (x) {} }
      if (push && !(search && search.value)) { var top = $('.set-layout', root.parentNode) || root; var y = top.getBoundingClientRect().top + window.scrollY - 76; if (window.scrollY > y) window.scrollTo({ top: Math.max(0, y), behavior: 'auto' }); }
      root.dispatchEvent(new CustomEvent('ad:tab', { detail: slug }));
      return true;
    }
    navLinks.forEach(function (a) {
      on(a, 'click', function (e) {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button) return;
        if (showTab(a.getAttribute('data-set-tab'), true)) e.preventDefault();
      });
    });
    on(window, 'popstate', function () { var m = /\/admin\/settings\/([a-z0-9_-]+)/.exec(location.pathname); showTab(m ? m[1] : (panels[0] && panels[0].getAttribute('data-panel')), false); });

    /* ---- search and "changed only" ---- */
    function applyFilter() {
      var q = search ? search.value.trim().toLowerCase() : '', toks = q ? q.split(/\s+/) : [], only = !!(changedBox && changedBox.checked), active = toks.length > 0 || only, shown = 0;
      root.classList.toggle('is-searching', active);
      if (search) search.parentNode.classList.toggle('has-value', q !== '');
      panels.forEach(function (p) {
        var vis = 0;
        $$('.set-section', p).forEach(function (sec) {
          var secVis = 0;
          $$('.set-row', sec).forEach(function (r) {
            var ok = true;
            if (toks.length) { var kw = r.getAttribute('data-keywords') || ''; ok = toks.every(function (t) { return kw.indexOf(t) > -1; }); }
            if (ok && only) ok = r.getAttribute('data-custom') === '1';
            if (r.tagName === 'TD') { if (ok) secVis++; return; }
            r.classList.toggle('is-filtered', !ok);
            if (ok) secVis++;
          });
          sec.hidden = active && secVis === 0;
          if (!sec.hidden) vis++;
        });
        p.hidden = active ? vis === 0 : p.getAttribute('data-panel') !== current;
        if (!p.hidden) shown += vis;
      });
      if (emptyEl) { emptyEl.classList.toggle('is-on', active && shown === 0); var qs = $('[data-empty-q]', emptyEl); if (qs) qs.textContent = q || 'your filter'; }
    }
    if (search) {
      on(search, 'input', debounce(applyFilter, 90));
      on(search, 'keydown', function (e) { if (e.key === 'Escape') { search.value = ''; applyFilter(); search.blur(); } });
      on(doc, 'keydown', function (e) {
        if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test((e.target.tagName || '')) && !e.target.isContentEditable) { e.preventDefault(); search.focus(); search.select(); }
      });
    }
    if (changedBox) on(changedBox, 'change', applyFilter);

    /* ---- previews ---- */
    initPreviews(root, getValue, rowFor);

    /* ---- boot ---- */
    $$('.set-row', root).forEach(function (r) { if (r.getAttribute('data-type') === 'color') drawRamp(r); });
    $$('.set-row input[type=number]', root).forEach(function (i) { var r = $('input[type=range]', i.closest('.set-row')); if (r && i.value !== '') r.value = i.value; });
    takeSnap(); evalDeps(); sums(); tabCounts(); updateDirty(); showTab(current, false);
    var hash = (location.hash || '').replace('#', '');
    if (hash) { var target = doc.getElementById(hash) || doc.getElementById('set-' + hash); if (target) { var pn = target.closest('.set-panel'); if (pn && pn.hidden) showTab(pn.getAttribute('data-panel'), false); setTimeout(function () { target.scrollIntoView({ block: 'center' }); if (target.classList.contains('set-row')) target.classList.add('is-flash'); }, 80); } }
    root.classList.add('is-ready');
    AD.settings = { getValue: function (k) { return getValue(k); }, rowFor: rowFor };
  }

  /* =====================================================================
     Live previews (logo placements, appearance, hero)
     ===================================================================== */
  function initPreviews(root, getValue, rowFor) {
    function imgUrl(key) { var r = rowFor(key); var w = r && r.querySelector('.set-image'); return w ? w.getAttribute('data-url') || '' : ''; }
    function num(key, d) { var v = parseFloat(getValue(key)); return isNaN(v) ? d : v; }

    // --- Logo placements
    var lp = $('[data-logo-preview]');
    if (lp) {
      // Each tile is one placement in one theme. "chain" is the order of logo settings tried (the same order logo_html() uses in PHP).
      var BASE = ['logo_compact', 'logo_full', 'logo_icon'];
      var PL = {
        header_top: { dim: 'h', size: 'logo_h_header', bg: 'hero', unit: 'high', def: 40, chain: ['logo_white', 'logo_header_scrolled'].concat(BASE) },
        header: { dim: 'h', size: 'logo_h_header_scrolled', bg: 'light', unit: 'high', def: 34, chain: ['logo_header_scrolled'].concat(BASE) },
        header_dark: { dim: 'h', size: 'logo_h_header_scrolled', bg: 'dark', unit: 'high', def: 34, chain: ['logo_header_scrolled_dark', 'logo_dark', 'logo_white'].concat(BASE) },
        mobile: { dim: 'h', size: 'logo_h_header_mobile', bg: 'light', unit: 'high', def: 28, chain: ['logo_mobile', 'logo_icon', 'logo_compact', 'logo_full'] },
        mobile_dark: { dim: 'h', size: 'logo_h_header_mobile', bg: 'dark', unit: 'high', def: 28, chain: ['logo_mobile_dark', 'logo_mobile', 'logo_icon', 'logo_dark', 'logo_white', 'logo_compact', 'logo_full'] },
        mobile_top: { dim: 'h', size: 'logo_h_header_mobile', bg: 'hero', unit: 'high', def: 28, chain: ['logo_mobile_white', 'logo_white', 'logo_icon'] },
        auth: { dim: 'w', size: 'logo_w_auth', bg: 'light', unit: 'wide', def: 200, chain: ['logo_full', 'logo_compact', 'logo_icon'] },
        side: { dim: 'w', size: 'logo_w_sidepanel', bg: 'hero', unit: 'wide', def: 140, chain: ['logo_white'].concat(BASE) },
        footer: { dim: 'w', size: 'logo_w_footer', bg: 'light', unit: 'wide', def: 140, chain: ['logo_footer'].concat(BASE) },
        footer_dark: { dim: 'w', size: 'logo_w_footer', bg: 'dark', unit: 'wide', def: 140, chain: ['logo_footer_dark', 'logo_dark', 'logo_white'].concat(BASE) },
        admin: { dim: 'h', size: 'logo_h_admin', bg: 'dark', unit: 'high', def: 32, chain: ['logo_white'].concat(BASE) }
      };
      var forced = 'natural';
      var drawLogos = function () {
        var pad = num('logo_padding', 8), wrapBg = getValue('logo_bg'), wordmark = getValue('logo_show_wordmark') === '1';
        $$('[data-lp]', lp).forEach(function (st) {
          var pl = PL[st.getAttribute('data-lp')]; if (!pl) return;
          var bg = forced === 'natural' ? pl.bg : forced, size = num(pl.size, pl.def), src = '';
          for (var i = 0; i < pl.chain.length && !src; i++) src = imgUrl(pl.chain[i]);
          st.setAttribute('data-bg', bg);
          st.classList.toggle('is-center', st.getAttribute('data-lp') === 'auth');
          var style = pl.dim === 'h' ? 'height:' + size + 'px;width:auto;' : 'width:' + size + 'px;height:auto;';
          var inner;
          if (src) inner = '<img src="' + esc(src) + '" alt="" style="' + style + 'max-width:100%">';
          else {
            var m = pl.dim === 'h' ? size : Math.round(size / 4.2);
            inner = '<span class="lp-mark" style="width:' + m + 'px;height:' + m + 'px">' + icon('heart-handshake', 'w-1/2 h-1/2') + '</span>' + (wordmark ? '<span class="lp-word">' + esc((CH.config && CH.config.siteName) || 'ConnectHub') + '</span>' : '');
          }
          st.innerHTML = '<span class="lp-wrap' + (wrapBg !== 'none' ? ' bg-' + wrapBg : '') + '" style="--lp-pad:' + pad + 'px">' + inner + '</span>';
          var cap = st.parentNode.querySelector('[data-lp-size]'); if (cap) cap.textContent = size + 'px ' + pl.unit;
        });
      };
      $$('[data-lp-bg]', lp).forEach(function (b) {
        on(b, 'click', function () {
          forced = b.getAttribute('data-lp-bg');
          $$('[data-lp-bg]', lp).forEach(function (o) { o.classList.toggle('is-on', o === b); o.setAttribute('aria-pressed', o === b ? 'true' : 'false'); });
          drawLogos();
        });
      });
      on(root, 'ad:preview', drawLogos); drawLogos();
    }

    // --- Appearance preview
    var ap = $('[data-appearance-preview]');
    if (ap) {
      var data = jattr(ap, 'data-pv', {}), pv = $('.pv', ap);
      var drawApp = function () {
        ['primary', 'secondary', 'tertiary'].forEach(function (n) {
          var r = colorRamp(getValue('color_' + n)); if (!r) return;
          Object.keys(r).forEach(function (s) { pv.style.setProperty('--' + n + '-' + s, r[s]); });
        });
        var g = (data.gradients || {})[getValue('hero_gradient')], m = (data.meshes || {})[getValue('mesh_style')];
        if (g) g.forEach(function (h, i) { var c = hexRgb(h); if (c) pv.style.setProperty('--grad-' + (i + 1), c.join(' ')); });
        if (m) m.forEach(function (h, i) { var c = hexRgb(h); if (c) pv.style.setProperty('--mesh-' + (i + 1), c.join(' ')); });
        var fh = (data.fonts || {})[getValue('font_heading')], fb = (data.fonts || {})[getValue('font_body')];
        if (fh) pv.style.setProperty('--font-heading', fh + ', ui-sans-serif, system-ui, sans-serif');
        if (fb) pv.style.setProperty('--font-body', fb + ', ui-sans-serif, system-ui, sans-serif');
        pv.style.setProperty('--heading-tracking', (data.tracking || {})[getValue('heading_tracking')] || '-0.02em');
        pv.style.setProperty('--pv-r', (data.radius || {})[getValue('radius_scale')] || 1);
        pv.style.fontSize = Math.round(13 * (num('base_font_size', 16) / 16) * 10) / 10 + 'px';
      };
      on(root, 'ad:preview', drawApp); drawApp();
    }

    // --- Hero preview
    var hp = $('[data-hero-preview]');
    if (hp) {
      var stage = $('.pv-stage', hp), slide = hp.getAttribute('data-slide') || '';
      var drawHero = function () {
        var type = getValue('hero_bg_type'), bg = '', media = '';
        var grad = 'linear-gradient(135deg, rgb(var(--grad-1)), rgb(var(--grad-2)) 55%, rgb(var(--grad-3)))';
        if (type === 'image' || type === 'parallax') { var u = imgUrl('hero_image'); bg = u ? 'url(' + u + ')' : grad; }
        else if (type === 'slider') bg = slide ? 'url(' + slide + ')' : grad;
        else if (type === 'video') { var v = imgUrl('hero_video'); bg = grad; if (v) media = '<video class="pv-bg" src="' + esc(v) + '" muted playsinline preload="metadata" style="width:100%;height:100%;object-fit:cover"></video>'; }
        else bg = grad;
        var oc = hexRgb(getValue('hero_overlay_color')) || [15, 14, 13], ot = getValue('hero_overlay_type'), os = num('hero_overlay_start', 70) / 100, oe = num('hero_overlay_end', 25) / 100, ov = '';
        if (ot === 'gradient') ov = 'linear-gradient(to top, rgba(' + oc.join(',') + ',' + os + '), rgba(' + oc.join(',') + ',' + oe + '))';
        else if (ot === 'solid') ov = 'rgba(' + oc.join(',') + ',' + os + ')';
        var center = getValue('hero_align') === 'center', dark = getValue('hero_text_color') === 'dark';
        stage.classList.toggle('is-center', center); stage.classList.toggle('is-dark-text', dark);
        var second = getValue('hero_cta_secondary_text'), badge = getValue('hero_badge'), loc = getValue('hero_show_location') === '1';
        stage.innerHTML = (media || '<div class="pv-bg" style="background-image:' + bg.replace(/"/g, '&quot;') + '"></div>')
          + '<div class="pv-ov" style="background:' + ov + '"></div>'
          + '<div class="pv-nav"><span>' + esc((CH.config && CH.config.siteName) || 'ConnectHub') + '</span><span>' + esc(getValue('header_cta_join') || 'Join free') + '</span></div>'
          + '<div class="pv-in">' + (badge ? '<span class="pv-badge2">' + esc(badge) + '</span>' : '')
          + '<h5>' + esc(getValue('hero_headline')) + '</h5><p>' + esc(getValue('hero_subheadline')) + '</p>'
          + '<div class="pv-ctas"><span class="pv-cta">' + esc(getValue('hero_cta_primary_text') || 'Get started') + '</span>' + (second ? '<span class="pv-cta is-2">' + esc(second) + '</span>' : '') + '</div>'
          + (loc ? '<div class="pv-search">' + icon('map-pin', 'w-3 h-3') + esc(getValue('hero_location_prompt') || 'Where are you?') + '</div>' : '') + '</div>';
        var note = $('[data-hero-note]', hp);
        if (note) note.textContent = type === 'slider' && !slide ? 'No slides yet, so the animated gradient shows. Add slides under Hero and landing.' : (type === 'video' && !imgUrl('hero_video') ? 'Upload a video to see it here. The gradient shows until then.' : ((type === 'image' || type === 'parallax') && !imgUrl('hero_image') ? 'Upload an image to see it here.' : ''));
      };
      on(root, 'ad:preview', drawHero); drawHero();
    }
  }

  /* =====================================================================
     Photo review (keyboard friendly grid)
     ===================================================================== */
  function initModeration(grid) {
    var wrap = grid.closest('[data-mod]') || doc, focusEl = null, rejectIds = [];
    var bar = $('[data-mod-bar]', wrap), countEl = $('[data-mod-selected]', wrap), emptyEl = $('[data-mod-empty]', wrap);
    function cards() { return $$('.mod-card:not(.is-gone)', grid); }
    function selected() { return cards().filter(function (c) { var k = $('.mod-check', c); return k && k.checked; }); }
    function setFocus(c, scroll) {
      if (focusEl) focusEl.classList.remove('is-focus');
      focusEl = c; if (!c) return;
      c.classList.add('is-focus'); c.focus({ preventScroll: true });
      if (scroll !== false) c.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
    function updateBar() {
      var n = selected().length;
      if (bar) bar.hidden = n === 0;
      if (countEl) countEl.textContent = n + ' selected';
      cards().forEach(function (c) { var k = $('.mod-check', c); c.classList.toggle('is-selected', !!(k && k.checked)); });
    }
    function after(j, ids) {
      var done = (j.done || []).map(String);
      done.forEach(function (id) {
        var c = $('.mod-card[data-id="' + id + '"]', grid); if (!c) return;
        var all = cards(), i = all.indexOf(c);
        c.classList.add('is-gone');
        if (focusEl === c) { var nxt = all[i + 1] || all[i - 1]; focusEl = null; setTimeout(function () { setFocus(nxt && !nxt.classList.contains('is-gone') ? nxt : null, false); }, 40); }
        setTimeout(function () { c.remove(); if (!cards().length && emptyEl) { emptyEl.hidden = false; grid.hidden = true; } updateBar(); }, 280);
      });
      var upd = j.updated || {};
      Object.keys(upd).forEach(function (id) {
        var c = $('.mod-card[data-id="' + id + '"]', grid); if (!c) return;
        var v = upd[id].vip ? '1' : '0';
        c.setAttribute('data-vip', v);
        var b = $('[data-mod-vip-btn]', c);
        if (b) { b.setAttribute('data-mod-act', v === '1' ? 'unvip' : 'vip'); b.setAttribute('aria-pressed', v === '1' ? 'true' : 'false'); b.classList.toggle('is-on', v === '1'); b.title = v === '1' ? 'VIP only. Click to show everyone.' : 'Show only to VIP members'; }
        var bd = $('[data-mod-vip-badge]', c); if (bd) bd.hidden = v !== '1';
      });
      if (typeof j.remaining === 'number') $$('[data-mod-remaining]').forEach(function (el) { el.textContent = j.remaining; });
      updateBar();
      var failed = Object.keys(j.failed || {});
      if (failed.length) toast(failed.length + ' could not be updated: ' + j.failed[failed[0]], 'error');
      else if (done.length || Object.keys(upd).length) toast(j.message || ((done.length || Object.keys(upd).length) + ((done.length || Object.keys(upd).length) === 1 ? ' photo updated' : ' photos updated')), 'success', 2400);
    }
    function act(ids, action, reason) {
      if (!ids.length) return Promise.resolve();
      return api('/api/admin/moderation/action', { ids: ids, action: action, reason: reason || '' }).then(function (j) {
        if (!j.ok) { toast(j.error || 'Something went wrong.', 'error'); return; }
        after(j, ids);
      });
    }
    function idsOf(list) { return list.map(function (c) { return +c.getAttribute('data-id'); }); }
    function target() { var s = selected(); return s.length ? s : (focusEl ? [focusEl] : []); }
    function openReject(ids) {
      if (!ids.length) return;
      rejectIds = ids;
      var dlg = $('#mod-reject'); if (!dlg) return;
      $('[data-reject-n]', dlg).textContent = ids.length === 1 ? 'this photo' : ids.length + ' photos';
      var sel = $('select', dlg); if (sel) sel.selectedIndex = 0;
      var ta = $('textarea', dlg); if (ta) ta.value = '';
      CH.modal.open(dlg);
    }
    function run(action, list) {
      list = list || target(); if (!list.length) return;
      if (action === 'reject') return openReject(idsOf(list));
      if (action === 'delete') {
        return CH.confirm({ title: 'Delete ' + (list.length === 1 ? 'this photo' : list.length + ' photos') + '?', message: 'The files are removed for good and the member is not notified.', confirm: 'Delete' }).then(function (ok) { if (ok) act(idsOf(list), 'delete'); });
      }
      act(idsOf(list), action);
    }
    on(grid, 'click', function (e) {
      var b = e.target.closest ? e.target.closest('[data-mod-act]') : null;
      if (b) { e.preventDefault(); var c = b.closest('.mod-card'); setFocus(c, false); run(b.getAttribute('data-mod-act'), [c]); return; }
      var m = e.target.closest ? e.target.closest('[data-mod-open]') : null;
      if (m) { var cc = m.closest('.mod-card'); setFocus(cc, false); AD.lightbox(m.getAttribute('data-src'), m.getAttribute('data-caption') || ''); }
    });
    on(grid, 'change', function (e) { if (e.target.matches && e.target.matches('.mod-check')) updateBar(); });
    on(grid, 'focusin', function (e) { var c = e.target.closest ? e.target.closest('.mod-card') : null; if (c && c !== focusEl) { if (focusEl) focusEl.classList.remove('is-focus'); focusEl = c; c.classList.add('is-focus'); } });
    $$('[data-mod-bulk]', wrap).forEach(function (b) { on(b, 'click', function () { run(b.getAttribute('data-mod-bulk'), selected()); }); });
    var all = $('[data-mod-all]', wrap);
    if (all) on(all, 'change', function () { cards().forEach(function (c) { var k = $('.mod-check', c); if (k) k.checked = all.checked; }); updateBar(); });
    var dlg = $('#mod-reject');
    if (dlg) on($('form', dlg), 'submit', function (e) {
      e.preventDefault();
      var preset = $('select', dlg).value, note = $('textarea', dlg).value.trim(), reason = note || preset;
      if (!reason) { $('textarea', dlg).focus(); return; }
      dlg.close(); act(rejectIds, 'reject', reason);
    });
    on(doc, 'keydown', function (e) {
      if (e.ctrlKey || e.metaKey || e.altKey) return;
      if (/^(INPUT|TEXTAREA|SELECT)$/.test(e.target.tagName) && !(e.target.classList && e.target.classList.contains('mod-check'))) return;
      if (doc.querySelector('dialog[open]')) return;
      var list = cards(), i = focusEl ? list.indexOf(focusEl) : -1, k = e.key;
      function cols() { if (list.length < 2) return 1; var t0 = list[0].offsetTop, c = 0; for (var q = 0; q < list.length; q++) { if (list[q].offsetTop === t0) c++; else break; } return Math.max(1, c); }
      if (!list.length) return;
      if (k === 'ArrowRight' || k === 'j' || k === 'J') { e.preventDefault(); setFocus(list[Math.min(list.length - 1, i + 1)] || list[0]); }
      else if (k === 'ArrowLeft' || k === 'k' || k === 'K') { e.preventDefault(); setFocus(list[Math.max(0, i - 1)] || list[0]); }
      else if (k === 'ArrowDown') { e.preventDefault(); setFocus(list[Math.min(list.length - 1, (i < 0 ? -cols() : i) + cols())]); }
      else if (k === 'ArrowUp') { e.preventDefault(); setFocus(list[Math.max(0, (i < 0 ? 0 : i) - cols())]); }
      else if (k === 'a' || k === 'A') { e.preventDefault(); run('approve'); }
      else if (k === 'r' || k === 'R') { e.preventDefault(); run('reject'); }
      else if (k === 'v' || k === 'V') { e.preventDefault(); run(focusEl && focusEl.getAttribute('data-vip') === '1' ? 'unvip' : 'vip', selected().length ? selected() : (focusEl ? [focusEl] : [])); }
      else if (k === 'Delete' || k === 'Backspace') { e.preventDefault(); run('delete'); }
      else if (k === ' ' || k === 'x' || k === 'X') { if (focusEl) { e.preventDefault(); var ck = $('.mod-check', focusEl); if (ck) { ck.checked = !ck.checked; updateBar(); } } }
      else if (k === 'Enter' || k === 'z' || k === 'Z') { if (focusEl) { var m = $('[data-mod-open]', focusEl); if (m) { e.preventDefault(); AD.lightbox(m.getAttribute('data-src'), m.getAttribute('data-caption') || ''); } } }
      else if (k === 'Escape') { cards().forEach(function (c) { var q = $('.mod-check', c); if (q) q.checked = false; }); updateBar(); }
    });
    var first = cards()[0]; if (first) { focusEl = first; first.classList.add('is-focus'); }
    updateBar();
  }

  /* =====================================================================
     Tools: batched blurred-copy rebuild with progress
     ===================================================================== */
  function initBlur(box) {
    var btn = $('[data-blur-run]', box), bar = $('.cp-progress > span', box), label = $('[data-blur-label]', box), running = false;
    on(btn, 'click', function () {
      if (running) return;
      CH.confirm({ title: 'Rebuild blurred copies?', message: 'This reads every member photo and writes a new blurred copy. It can take a few minutes on a large site. Keep this page open.', confirm: 'Start', danger: false }).then(function (ok) {
        if (!ok) return;
        running = true; btn.disabled = true; btn.classList.add('is-loading');
        var done = 0, total = 0, errors = 0, offset = 0;
        (function step() {
          api('/api/admin/tools/blur', { offset: offset, batch: 25 }).then(function (j) {
            if (!j.ok) { running = false; btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = j.error || 'Stopped because of an error.'; toast(j.error || 'The rebuild stopped.', 'error'); return; }
            total = j.total; done += j.processed; errors += j.errors || 0; offset = j.offset;
            bar.style.width = (total ? Math.min(100, Math.round(done / total * 100)) : 100) + '%';
            label.textContent = done + ' of ' + total + ' photos' + (errors ? ' (' + errors + ' skipped)' : '');
            if (j.done) { running = false; btn.disabled = false; btn.classList.remove('is-loading'); label.textContent = 'Done. ' + done + ' of ' + total + ' photos rebuilt' + (errors ? ', ' + errors + ' skipped.' : '.'); toast('Blurred copies rebuilt.', 'success'); }
            else step();
          });
        })();
      });
    });
  }

  /* =====================================================================
     Roles: permission matrix helpers (set a column, set a group, copy from another role, live summary)
     ===================================================================== */
  function initMatrix(form) {
    var summary = $('[data-perm-summary]', form);
    function radios() { return $$('input[type="radio"][name^="perm["]', form).filter(function (r) { return !r.disabled; }); }
    function setLevel(level, group) {
      radios().forEach(function (r) {
        if (r.value !== level) return;
        if (group && r.closest('tr').getAttribute('data-group') !== group) return;
        if (!r.checked) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
      });
      update();
    }
    function update() {
      if (!summary) return;
      var c = { none: 0, view: 0, edit: 0, full: 0 };
      $$('input[type="radio"][name^="perm["]:checked', form).forEach(function (r) { c[r.value] = (c[r.value] || 0) + 1; });
      summary.innerHTML = '<span><b>' + c.full + '</b> full</span><span><b>' + c.edit + '</b> edit</span><span><b>' + c.view + '</b> view only</span><span><b>' + c.none + '</b> no access</span>';
    }
    on(form, 'click', function (e) {
      var b = e.target.closest ? e.target.closest('[data-perm-set]') : null;
      if (!b) return;
      e.preventDefault();
      setLevel(b.getAttribute('data-perm-set'), b.getAttribute('data-group') || '');
    });
    var copy = $('[data-perm-copy]', form);
    if (copy) on(copy, 'change', function () {
      var opt = copy.options[copy.selectedIndex], map = opt ? jattr(opt, 'data-perms', null) : null;
      if (!map) return;
      $$('tr[data-module]', form).forEach(function (tr) {
        var m = tr.getAttribute('data-module'), lv = map[m] || map['*'] || 'none';
        var r = $('input[type="radio"][value="' + lv + '"]', tr);
        if (r && !r.disabled && !r.checked) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
      });
      copy.selectedIndex = 0;
      update();
      toast('Copied. Review the matrix, then save.', 'info', 2200);
    });
    on(form, 'change', function (e) { if (e.target.matches && e.target.matches('input[type="radio"]')) update(); });
    update();
  }

  /* =====================================================================
     Dialog helpers: fill hidden fields when a button opens a dialog, copy a preset reason into a textarea
     ===================================================================== */
  function initFill() {
    delegate('click', '[data-fill]', function (e, el) {
      var map = jattr(el, 'data-fill', {}), dlg = doc.getElementById(el.getAttribute('data-modal-open') || '');
      if (!dlg) return;
      Object.keys(map).forEach(function (k) {
        $$('[name="' + k + '"]', dlg).forEach(function (i) { i.value = map[k]; });
      });
      var tt = el.getAttribute('data-dialog-title'), h = tt ? $('.modal-title', dlg) : null; if (h) h.textContent = tt;
      var st = el.getAttribute('data-dialog-submit'), sb = st ? $('[data-dialog-submit-target]', dlg) : null; if (sb) sb.textContent = st;
      var t = $('textarea', dlg); if (t) { t.value = ''; setTimeout(function () { t.focus(); }, 60); }
      var pre = $('select[data-fill-target]', dlg); if (pre) pre.selectedIndex = 0;
    });
    // Forms whose action carries a placeholder id (/0/) get the id from the hidden "id_hidden" field.
    delegate('submit', 'form[data-vform]', function (e, form) {
      var idv = form.querySelector('[name="id_hidden"]');
      if (idv && idv.value) form.setAttribute('action', form.getAttribute('action').replace(/\/0\/action$/, '/' + encodeURIComponent(idv.value) + '/action'));
    });
    // Verification history: show the pictures of one request in a dialog.
    delegate('click', '[data-vreview]', function (e, el) {
      var id = el.getAttribute('data-vreview'), name = el.getAttribute('data-name') || '', hasId = el.getAttribute('data-has-id') === '1';
      var base = (CH.url ? CH.url('/admin/files/verification/') : '/admin/files/verification/') + encodeURIComponent(id);
      var body = $('[data-vview-body]'); if (!body) return;
      function fig(label, which, doc) {
        return '<figure><figcaption><span>' + esc(label) + '</span></figcaption><div class="frame' + (doc ? ' is-doc' : '') + '"><img src="' + base + '/' + which + '" alt="' + esc(label + ' from ' + name) + '"></div></figure>';
      }
      var ref = el.getAttribute('data-ref') || '';
      function refFig() { return '<figure><figcaption><span>Pose to copy</span></figcaption><div class="frame"><img src="' + esc(ref) + '" alt="Reference pose"></div></figure>'; }
      body.innerHTML = (ref ? refFig() : '') + fig(ref ? 'Photo sent' : 'Selfie', 'selfie', false) + (hasId ? fig('ID document', 'id', true) : '');
      var h = $('#d-vview-t'); if (h) h.textContent = name ? 'Pictures from ' + name : 'Verification pictures';
      CH.modal.open('d-vview');
    });
    delegate('change', '[data-fill-target]', function (e, el) {
      var t = $(el.getAttribute('data-fill-target'));
      if (!t || !el.value) return;
      t.value = el.value; t.dispatchEvent(new Event('input', { bubbles: true })); t.focus();
    });
  }

  /* =====================================================================
     Test email (Settings, Email)
     ===================================================================== */
  delegate('submit', 'form[data-mail-test]', function (e, f) {
    e.preventDefault();
    var out = $('[data-mail-test-result]', f.parentNode), btn = $('button[type=submit]', f);
    var to = f.elements.to.value.trim();
    if (!to) { f.elements.to.focus(); return; }
    btn.disabled = true; var label = btn.textContent; btn.textContent = 'Sending...';
    if (out) { out.textContent = ''; out.className = 'text-sm mt-2'; }
    api('/admin/email/test', { to: to }).then(function (j) {
      btn.disabled = false; btn.textContent = label;
      if (out) { out.textContent = j.message || j.error || 'Something went wrong.'; out.className = 'text-sm mt-2 ' + (j.ok ? 'mail-ok' : 'mail-bad'); }
    });
  });

  /* =====================================================================
     Boot
     ===================================================================== */
  function boot() {
    initCharts();
    $$('form[data-bulk]').forEach(initBulk);
    $$('form[data-dirty-guard]').forEach(initDirtyGuard);
    $$('form[data-matrix]').forEach(initMatrix);
    var sr = $('[data-settings-root]'); if (sr) initSettings(sr);
    var mg = $('[data-mod-grid]'); if (mg) initModeration(mg);
    var bl = $('[data-blur-box]'); if (bl) initBlur(bl);
    $$('.cp-log').forEach(initLog);
    initHashTabs();
    initFill();
  }
  if (doc.readyState === 'loading') on(doc, 'DOMContentLoaded', boot); else boot();
})();
