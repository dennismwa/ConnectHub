/* ConnectHub admin: content + money pages (D2). Vanilla JS, no globals except window.CMAdmin. */
(function () {
  'use strict';
  var doc = document;
  function $(s, c) { return (c || doc).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
  function on(el, ev, fn, opt) { el && el.addEventListener(ev, fn, opt); }
  function delegate(ev, sel, fn) {
    doc.addEventListener(ev, function (e) {
      var t = e.target && e.target.closest ? e.target.closest(sel) : null;
      if (t && doc.contains(t)) fn.call(t, e, t);
    });
  }
  function ch() { return window.CH || {}; }
  function toast(msg, type) { if (ch().toast) ch().toast(msg, type || 'info'); }
  function api(url, opts) { return ch().api(url, opts); }
  function spriteUrl() { var d = $('#cm-icon-dialog'); return d ? d.getAttribute('data-sprite') : (ch().url ? ch().url('/assets/icons/sprite.svg') : '/assets/icons/sprite.svg'); }
  function iconSvg(name, cls) {
    return '<svg class="icon ' + (cls || 'w-5 h-5') + '" aria-hidden="true"><use href="' + spriteUrl().split('#')[0] + '#i-' + name + '"></use></svg>';
  }
  function slugify(s) {
    return String(s || '').normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
  }
  function fmtMoney(n) { return (window.CMAdmin && CMAdmin.symbol || 'KSh') + ' ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 }); }
  window.CMAdmin = window.CMAdmin || {};
  CMAdmin.slugify = slugify;

  /* ---------------------------------------------------------------- filters */
  delegate('change', '[data-autosubmit]', function (e, el) { var f = el.form || el.closest('form'); if (f) f.submit(); });

  /* ---------------------------------------------------------------- AJAX forms with inline errors */
  function clearErrors(form) {
    $$('.error-text[data-js]', form).forEach(function (n) { n.remove(); });
    $$('.has-error', form).forEach(function (n) { n.classList.remove('has-error'); n.removeAttribute('aria-invalid'); });
  }
  function fieldByPath(form, path) {
    var parts = String(path).split('.');
    var name = parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join('');
    return form.querySelector('[name="' + name.replace(/"/g, '\\"') + '"]') || form.querySelector('[name="' + name + '[]"]');
  }
  function showErrors(form, errors) {
    var first = null;
    Object.keys(errors || {}).forEach(function (k) {
      if (k === '_') return;
      var inp = fieldByPath(form, k);
      var msg = errors[k];
      var host = inp ? (inp.closest('.field') || inp.parentNode) : form.querySelector('[data-error-for="' + k + '"]');
      if (!host) return;
      if (inp) { inp.classList.add('has-error'); inp.setAttribute('aria-invalid', 'true'); }
      var p = doc.createElement('p'); p.className = 'error-text'; p.setAttribute('data-js', '1'); p.textContent = msg;
      host.appendChild(p);
      if (!first) first = inp || host;
    });
    if (first && first.scrollIntoView) { try { first.scrollIntoView({ block: 'center', behavior: 'smooth' }); first.focus && first.focus({ preventScroll: true }); } catch (e) {} }
  }
  // A field that was flagged loses its message as soon as the person works on it
  function dropFieldError(e) {
    var el = e.target; if (!el || !el.classList || !el.classList.contains('has-error')) return;
    el.classList.remove('has-error'); el.removeAttribute('aria-invalid');
    var host = el.closest('.field') || el.parentNode;
    if (host) $$('.error-text[data-js]', host).forEach(function (n) { n.remove(); });
  }
  doc.addEventListener('input', dropFieldError); doc.addEventListener('change', dropFieldError);
  doc.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('form[data-cm-form]')) return;
    e.preventDefault(); e.stopPropagation();
    if (f.dispatchEvent(new CustomEvent('cm:before', { bubbles: true, cancelable: true })) === false) return;
    var btn = e.submitter || $('button[type=submit]', f);
    clearErrors(f); var rep0 = $('[data-report]', f); if (rep0) rep0.hidden = true;
    if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
    var data = new FormData(f);
    if (e.submitter && e.submitter.name) data.set(e.submitter.name, e.submitter.value);
    api(f.getAttribute('action') || location.pathname + location.search, { method: 'POST', data: data }).then(function (j) {
      if (btn) { btn.classList.remove('is-loading'); btn.disabled = false; }
      if (j && j.ok) {
        f.dispatchEvent(new CustomEvent('cm:success', { bubbles: true, detail: j }));
        var rep = $('[data-report]', f); if (rep && j.report) { rep.textContent = j.report; rep.hidden = false; }
        if (f.hasAttribute('data-stay') || j.stay) { toast(j.message || 'Done', 'success'); return; }
        if (j.redirect) location.href = j.redirect; else location.reload();
      } else {
        showErrors(f, (j && j.errors) || {});
        toast((j && j.error) || 'Something went wrong. Please try again.', 'error');
        f.dispatchEvent(new CustomEvent('cm:error', { bubbles: true, detail: j }));
      }
    });
  }, true);

  /* ---------------------------------------------------------------- modal create / edit */
  function fillForm(form, row) {
    $$('[name]', form).forEach(function (el) {
      var name = el.getAttribute('name');
      if (!(name in row) || el.type === 'file' || el.type === 'password') return;
      var v = row[name];
      if (el.type === 'checkbox') { el.checked = v === true || v === 1 || v === '1'; }
      else if (el.type === 'radio') { el.checked = String(el.value) === String(v); }
      else { el.value = v == null ? '' : v; }
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    $$('[data-cm-image]', form).forEach(function (box) {
      var inp = $('input[type=file]', box); if (!inp) return;
      setImage(box, row[inp.name + '_url'] || '');
    });
    $$('[data-cm-icon]', form).forEach(function (box) {
      var h = $('[data-icon-value]', box); setIcon(box, h ? h.value : '');
    });
    $$('[data-fill]', form.closest('dialog') || form).forEach(function (el) { var k = el.getAttribute('data-fill'); if (k in row) el.textContent = row[k]; });
  }
  function resetForm(form) {
    form.reset(); clearErrors(form);
    $$('[data-cm-image]', form).forEach(function (box) { setImage(box, ''); });
    $$('[data-cm-icon]', form).forEach(function (box) { setIcon(box, ''); });
    $$('input[name=id]', form).forEach(function (i) { i.value = ''; });
    $$('[data-fill]', form.closest('dialog') || form).forEach(function (el) { el.textContent = ''; });
  }
  function openDialog(id, mode, row) {
    var d = doc.getElementById(id); if (!d) return;
    var form = $('form', d);
    resetForm(form);
    if (row) fillForm(form, row);
    var t = $('[data-title-new]', d);
    if (t) t.textContent = mode === 'edit' ? (t.getAttribute('data-title-edit') || 'Edit') : (t.getAttribute('data-title-new') || 'Add');
    $$('[data-only-edit]', d).forEach(function (n) { n.hidden = mode !== 'edit'; });
    $$('[data-only-new]', d).forEach(function (n) { n.hidden = mode === 'edit'; });
    d.dispatchEvent(new CustomEvent('cm:open', { detail: { mode: mode, row: row || null } }));
    ch().modal && ch().modal.open(d);
  }
  delegate('click', '[data-cm-new]', function (e, el) { e.preventDefault(); var row = null; try { row = JSON.parse(el.getAttribute('data-row') || 'null'); } catch (x) {} openDialog(el.getAttribute('data-cm-new'), 'new', row); });
  delegate('click', '[data-cm-edit]', function (e, el) {
    e.preventDefault(); var row = {}; try { row = JSON.parse(el.getAttribute('data-row') || '{}'); } catch (x) {}
    openDialog(el.getAttribute('data-cm-edit'), 'edit', row);
  });
  delegate('click', '[data-cm-open]', function (e, el) { e.preventDefault(); var row = {}; try { row = JSON.parse(el.getAttribute('data-row') || '{}'); } catch (x) {} openDialog(el.getAttribute('data-cm-open'), 'edit', row); });

  /* ---------------------------------------------------------------- toggles */
  delegate('change', 'input[data-cm-toggle]', function (e, el) {
    var url = el.getAttribute('data-url') || location.pathname + location.search;
    var body = { action: 'toggle', id: +el.getAttribute('data-id'), field: el.getAttribute('data-field') || 'is_active', value: el.checked ? 1 : 0 };
    var extra = el.getAttribute('data-extra'); if (extra) { try { Object.assign(body, JSON.parse(extra)); } catch (x) {} }
    el.disabled = true;
    api(url, { method: 'POST', data: body }).then(function (j) {
      el.disabled = false;
      if (j && j.ok) {
        var row = el.parentNode.closest('[data-id]'); if (row && /is_(active|enabled|published)/.test(body.field)) row.classList.toggle('is-off', !el.checked);
        // keep the edit button's copy of the row in step, so editing right after a toggle does not undo it
        if (row) $$('[data-row]', row).forEach(function (b) { try { var d = JSON.parse(b.getAttribute('data-row')); if (d && body.field in d) { d[body.field] = body.value; b.setAttribute('data-row', JSON.stringify(d)); } } catch (x) {} });
        toast(j.message || 'Saved', 'success');
      } else { el.checked = !el.checked; toast((j && j.error) || 'Could not save that change.', 'error'); }
    });
  });

  /* ---------------------------------------------------------------- sortable lists */
  function initSortable(list) {
    var url = list.getAttribute('data-reorder-url') || location.pathname;
    var extra = {}; try { extra = JSON.parse(list.getAttribute('data-reorder-extra') || '{}'); } catch (x) {}
    var itemSel = list.getAttribute('data-item') || ':scope > [data-id]';
    var grid = list.hasAttribute('data-grid');
    var live = $('[data-sort-live]', list.parentNode) || null;
    var timer = null;
    function items() { return $$(itemSel, list); }
    function announce(item) {
      var all = items(); var n = all.indexOf(item) + 1;
      var msg = 'Moved to position ' + n + ' of ' + all.length;
      var l = list.querySelector('[data-sort-live]') || live;
      if (l) l.textContent = msg;
    }
    function save() {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var ids = items().map(function (el) { return +el.getAttribute('data-id'); });
        api(url, { method: 'POST', data: Object.assign({ action: 'reorder', ids: ids }, extra) }).then(function (j) {
          if (j && j.ok) toast(j.message || 'Order saved', 'success'); else toast((j && j.error) || 'Could not save the new order.', 'error');
        });
      }, 350);
    }
    function move(item, dir) {
      var all = items(); var i = all.indexOf(item);
      if (dir < 0 && i > 0) list.insertBefore(item, all[i - 1]);
      else if (dir > 0 && i < all.length - 1) list.insertBefore(all[i + 1], item);
      else return false;
      announce(item); save(); return true;
    }
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-move]'); if (!b || !list.contains(b)) return;
      var item = b.closest('[data-id]'); if (!item) return;
      move(item, b.getAttribute('data-move') === 'up' ? -1 : 1);
      b.focus();
    });
    list.addEventListener('keydown', function (e) {
      var h = e.target.closest('[data-handle]'); if (!h) return;
      if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault(); var item = h.closest('[data-id]'); if (item && move(item, e.key === 'ArrowUp' ? -1 : 1)) h.focus();
      }
    });
    var drag = null;
    list.addEventListener('pointerdown', function (e) {
      var h = e.target.closest('[data-handle]'); if (!h || (e.button && e.button !== 0)) return;
      var item = h.closest('[data-id]'); if (!item) return;
      e.preventDefault();
      drag = { item: item, h: h, moved: false };
      item.classList.add('is-dragging');
      try { h.setPointerCapture(e.pointerId); } catch (x) {}
    });
    list.addEventListener('pointermove', function (e) {
      if (!drag) return;
      var item = drag.item, x = e.clientX, y = e.clientY;
      var over = items().filter(function (el) { if (el === item) return false; var r = el.getBoundingClientRect(); return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom; })[0];
      if (!over) return;
      var r = over.getBoundingClientRect();
      var before = grid ? (x < r.left + r.width / 2) : (y < r.top + r.height / 2);
      var all = items(); var from = all.indexOf(item), to = all.indexOf(over);
      if (before && from === to - 1) return;
      if (!before && from === to + 1) return;
      list.insertBefore(item, before ? over : over.nextSibling);
      drag.moved = true;
    });
    function end() {
      if (!drag) return; var d = drag; drag = null;
      d.item.classList.remove('is-dragging');
      if (d.moved) { announce(d.item); save(); }
    }
    list.addEventListener('pointerup', end); list.addEventListener('pointercancel', end);
  }
  function initAll() { $$('[data-cm-sortable]').forEach(function (l) { if (!l.__sort) { l.__sort = true; initSortable(l); } }); }

  /* ---------------------------------------------------------------- image fields */
  function setImage(box, src) {
    var img = $('[data-image-preview]', box), empty = $('[data-image-empty]', box), rm = $('[data-image-remove]', box);
    var lbl = $('label.btn', box);
    if (img) { img.setAttribute('data-original', src || ''); if (src) { img.src = src; img.hidden = false; } else { img.removeAttribute('src'); img.hidden = true; } }
    if (empty) empty.hidden = !!src;
    if (rm) { rm.checked = false; var holder = rm.closest('.cm-remove'); if (holder) holder.hidden = !src; }
    var inp = $('input[type=file]', box); if (inp) inp.value = '';
    if (lbl) lbl.lastChild.textContent = ' ' + (src ? 'Replace' : 'Upload');
    var pk = $('[data-image-pick]', box); if (pk) pk.value = '';
    box.dispatchEvent(new CustomEvent('cm:image', { bubbles: true, detail: { src: src || '' } }));
  }
  CMAdmin.setImage = setImage;
  doc.addEventListener('change', function (e) {
    var inp = e.target;
    if (inp.matches && inp.matches('[data-image-input]')) {
      var box = inp.closest('[data-cm-image]'); var f = inp.files && inp.files[0]; if (!f) return;
      if (!/^image\//.test(f.type)) { toast('Choose a JPG, PNG or WebP image.', 'error'); inp.value = ''; return; }
      var img = $('[data-image-preview]', box), empty = $('[data-image-empty]', box), rm = $('[data-image-remove]', box);
      if (img.__url) URL.revokeObjectURL(img.__url);
      img.__url = URL.createObjectURL(f); img.src = img.__url; img.hidden = false; if (empty) empty.hidden = true;
      if (rm) rm.checked = false;
      box.dispatchEvent(new CustomEvent('cm:image', { bubbles: true, detail: { src: img.src, file: f } }));
    }
    if (inp.matches && inp.matches('[data-image-remove]')) {
      var b = inp.closest('[data-cm-image]'); var im = $('[data-image-preview]', b), em = $('[data-image-empty]', b), fi = $('[data-image-input]', b);
      if (inp.checked) { im.hidden = true; if (em) em.hidden = false; if (fi) fi.value = ''; b.dispatchEvent(new CustomEvent('cm:image', { bubbles: true, detail: { src: '' } })); }
      else { var o = im.getAttribute('data-original'); if (o) { im.src = o; im.hidden = false; if (em) em.hidden = true; b.dispatchEvent(new CustomEvent('cm:image', { bubbles: true, detail: { src: o } })); } }
    }
  });

  /* ---------------------------------------------------------------- icon picker */
  var iconTarget = null;
  function setIcon(box, name) {
    var h = $('[data-icon-value]', box), pv = $('[data-icon-preview]', box), lb = $('[data-icon-label]', box);
    if (h) h.value = name || '';
    if (pv) pv.innerHTML = name ? iconSvg(name, 'w-5 h-5') : '';
    if (lb) lb.textContent = name || 'Choose icon';
    if (h) h.dispatchEvent(new Event('change', { bubbles: true }));
  }
  delegate('click', '[data-icon-pick]', function (e, el) {
    e.preventDefault(); iconTarget = el.closest('[data-cm-icon]');
    var d = $('#cm-icon-dialog'); if (!d) return;
    var s = $('[data-icon-search]', d); if (s) s.value = '';
    $$('[data-icon]', d).forEach(function (b) { b.hidden = false; });
    var cur = $('[data-icon-value]', iconTarget).value;
    $$('[data-icon]', d).forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-icon') === cur); b.setAttribute('aria-selected', b.getAttribute('data-icon') === cur ? 'true' : 'false'); });
    var em = $('[data-icon-empty]', d); if (em) em.hidden = true;
    ch().modal.open(d); if (s) setTimeout(function () { s.focus(); }, 80);
  });
  delegate('click', '#cm-icon-dialog [data-icon]', function (e, el) {
    if (iconTarget) setIcon(iconTarget, el.getAttribute('data-icon'));
    var d = $('#cm-icon-dialog'); d && d.close();
  });
  delegate('click', '#cm-icon-dialog [data-icon-clear]', function () { if (iconTarget) setIcon(iconTarget, ''); var d = $('#cm-icon-dialog'); d && d.close(); });
  delegate('input', '[data-icon-search]', function (e, el) {
    var q = el.value.trim().toLowerCase(), n = 0, d = $('#cm-icon-dialog');
    $$('[data-icon]', d).forEach(function (b) { var hit = !q || b.getAttribute('data-icon').indexOf(q) !== -1; b.hidden = !hit; if (hit) n++; });
    var em = $('[data-icon-empty]', d); if (em) em.hidden = n > 0;
  });

  /* ---------------------------------------------------------------- slug + counters + select all */
  function initSlug(inp) {
    var src = $(inp.getAttribute('data-slug-from')); if (!src) return;
    var touched = inp.value !== '';
    on(inp, 'input', function (e) { if (e.isTrusted) touched = true; });
    on(src, 'input', function () { if (!touched) { inp.value = slugify(src.value); inp.dispatchEvent(new Event('input')); } });
    if (inp.form) on(inp.form, 'reset', function () { touched = false; });
  }
  function bulkState(wrap) {
    var items = $$('[data-bulk-item]', wrap), n = items.filter(function (i) { return i.checked; }).length;
    var bar = $('[data-bulk-bar]', wrap), cnt = $('[data-bulk-count]', wrap), all = $('[data-bulk-all]', wrap);
    if (bar) bar.hidden = n === 0;
    if (cnt) cnt.textContent = n + ' selected';
    if (all) { all.checked = n > 0 && n === items.length; all.indeterminate = n > 0 && n < items.length; }
  }
  delegate('change', '[data-bulk-all]', function (e, el) {
    var wrap = el.closest('[data-bulk]'); $$('[data-bulk-item]', wrap).forEach(function (i) { i.checked = el.checked; }); bulkState(wrap);
  });
  delegate('change', '[data-bulk-item]', function (e, el) { bulkState(el.closest('[data-bulk]')); });
  delegate('click', '[data-bulk-clear]', function (e, el) { var wrap = el.closest('[data-bulk]'); $$('[data-bulk-item],[data-bulk-all]', wrap).forEach(function (i) { i.checked = false; }); bulkState(wrap); });
  doc.addEventListener('submit', function (e) {
    var f = e.target; if (!f.matches || !f.hasAttribute('data-bulk')) return;
    if (!$$('[data-bulk-item]:checked', f).length) { e.preventDefault(); e.stopPropagation(); toast('Select at least one row first.', 'error'); }
    else if (!$('[name=bulk]', f).value) { e.preventDefault(); e.stopPropagation(); toast('Choose an action to apply.', 'error'); }
    else if ($('[name=bulk]', f).value === 'delete' && f.getAttribute('data-bulk-confirmed') !== '1') {
      e.preventDefault(); e.stopPropagation();
      var n = $$('[data-bulk-item]:checked', f).length;
      ch().confirm({ message: 'Delete ' + n + (n === 1 ? ' item' : ' items') + '? This cannot be undone.', confirm: 'Delete' }).then(function (ok) {
        if (!ok) return; f.setAttribute('data-bulk-confirmed', '1'); f.requestSubmit ? f.requestSubmit() : f.submit();
      });
    }
  }, true);

  /* ---------------------------------------------------------------- repeaters (landing section items) */
  function initRepeater(rep) {
    var list = $('[data-rep-list]', rep), tpl = $('template[data-rep-template]', rep), add = $('[data-rep-add]', rep);
    var max = +(rep.getAttribute('data-max') || 99), min = +(rep.getAttribute('data-min') || 0);
    var idx = $$('[data-rep-row]', list).length + 100;
    function rows() { return $$('[data-rep-row]', list); }
    function refresh() {
      var n = rows().length;
      if (add) add.disabled = n >= max;
      rows().forEach(function (r, i) {
        var num = $('[data-rep-num]', r); if (num) num.textContent = i + 1;
        var up = $('[data-rep-up]', r), dn = $('[data-rep-down]', r), rm = $('[data-rep-remove]', r);
        if (up) up.disabled = i === 0; if (dn) dn.disabled = i === n - 1; if (rm) rm.disabled = n <= min;
      });
      var c = $('[data-rep-count]', rep); if (c) c.textContent = n + ' of ' + max;
    }
    on(add, 'click', function () {
      if (rows().length >= max) return;
      var html = tpl.innerHTML.replace(/__i__/g, String(idx++));
      var w = doc.createElement('div'); w.innerHTML = html.trim();
      var row = w.firstElementChild; list.appendChild(row); refresh();
      var first = $('input[type=text],textarea', row); first && first.focus();
    });
    rep.addEventListener('click', function (e) {
      var row = e.target.closest('[data-rep-row]'); if (!row) return;
      if (e.target.closest('[data-rep-remove]')) { if (rows().length > min) { row.remove(); refresh(); } }
      else if (e.target.closest('[data-rep-up]') && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); refresh(); }
      else if (e.target.closest('[data-rep-down]') && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); refresh(); }
    });
    refresh();
  }

  /* ---------------------------------------------------------------- rich text editor */
  function initEditor(ed) {
    var area = $('[data-editor-area]', ed), src = $('[data-editor-source]', ed), bar = $('.cm-editor-bar', ed);
    var count = $('[data-editor-count]', ed), status = $('[data-editor-status]', ed), pop = $('[data-link-pop]', ed);
    var fileInp = $('[data-editor-file]', ed), uploadUrl = ed.getAttribute('data-upload');
    var saved = null, sourceMode = false;
    try { doc.execCommand('defaultParagraphSeparator', false, 'p'); } catch (x) {}
    var BLOCKS = /^(P|UL|OL|BLOCKQUOTE|H1|H2|H3|H4|HR|DIV|FIGURE|TABLE|PRE)$/;
    function isEmptyMarkup() { var h = area.innerHTML.trim(); return h === '' || h === '<br>' || h === '<p><br></p>' || h === '<div><br></div>'; }
    /* The browser leaves loose text, <div>s and <p><ul></p> behind. Tidy a copy so what is saved is clean, proper HTML. */
    function tidy() {
      var c = area.cloneNode(true), out = doc.createElement('div'), run = null;
      Array.prototype.slice.call(c.querySelectorAll('p, div')).forEach(function (el) {
        var kids = Array.prototype.slice.call(el.childNodes).filter(function (n) { return !(n.nodeType === 3 && !n.textContent.trim()); });
        if (kids.length && kids.every(function (n) { return n.nodeType === 1 && BLOCKS.test(n.tagName); })) {
          while (el.firstChild) el.parentNode.insertBefore(el.firstChild, el);
          el.parentNode.removeChild(el);
        }
      });
      Array.prototype.slice.call(c.childNodes).forEach(function (n) {
        if (n.nodeType === 1 && n.tagName === 'DIV') { var p = doc.createElement('p'); while (n.firstChild) p.appendChild(n.firstChild); n = p; }
        if (n.nodeType === 1 && BLOCKS.test(n.tagName)) { run = null; out.appendChild(n); }
        else if (n.nodeType === 3 && !n.textContent.trim() && !run) { return; }
        else { if (!run) { run = doc.createElement('p'); out.appendChild(run); } run.appendChild(n); }
      });
      Array.prototype.slice.call(out.querySelectorAll('p')).forEach(function (p) { if (!p.textContent.trim() && !p.querySelector('img')) p.parentNode.removeChild(p); });
      return out.innerHTML.trim();
    }
    function clean() { return isEmptyMarkup() ? '' : tidy(); }
    function sync() {
      if (!sourceMode) { var nv = clean(); if (src.value !== nv) { src.value = nv; src.dispatchEvent(new Event('input')); } }
      var t = (area.textContent || '').trim();
      var words = t ? t.split(/\s+/).length : 0;
      if (count) count.textContent = words + (words === 1 ? ' word' : ' words');
      if (isEmptyMarkup() && area.innerHTML) area.innerHTML = '';
    }
    /* Typing straight into an empty box puts loose text in it. Wrap that in a paragraph. */
    function wrapLoose() {
      var s = getSelection(); if (!s.rangeCount) return;
      var n = s.anchorNode;
      if (n && n.nodeType === 3 && n.parentNode === area) { try { doc.execCommand('formatBlock', false, 'p'); } catch (x) {} }
    }
    function saveSel() { var s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) saved = s.getRangeAt(0).cloneRange(); }
    function restoreSel() { area.focus(); if (saved) { var s = getSelection(); s.removeAllRanges(); s.addRange(saved); } }
    function exec(c, v) { restoreSel(); doc.execCommand(c, false, v == null ? null : v); sync(); updateState(); }
    function currentBlock() { var s = getSelection(); if (!s.rangeCount) return ''; var n = s.anchorNode; while (n && n !== area) { if (n.nodeType === 1 && /^(H2|H3|BLOCKQUOTE|P|LI)$/.test(n.tagName)) return n.tagName; n = n.parentNode; } return ''; }
    function updateState() {
      var s = getSelection(); if (!s.rangeCount || !area.contains(s.anchorNode)) return;
      var blk = currentBlock();
      $$('[data-cmd]', bar).forEach(function (b) {
        var c = b.getAttribute('data-cmd'), on_ = false;
        if (c === 'bold' || c === 'italic' || c === 'underline') { try { on_ = doc.queryCommandState(c); } catch (x) {} }
        else if (c === 'h2') on_ = blk === 'H2'; else if (c === 'h3') on_ = blk === 'H3'; else if (c === 'quote') on_ = blk === 'BLOCKQUOTE';
        else if (c === 'ul' || c === 'ol') { try { on_ = doc.queryCommandState(c === 'ul' ? 'insertUnorderedList' : 'insertOrderedList'); } catch (x) {} }
        else return;
        b.setAttribute('aria-pressed', on_ ? 'true' : 'false'); b.classList.toggle('is-on', on_);
      });
    }
    function block(tag) { restoreSel(); var cur = currentBlock(); doc.execCommand('formatBlock', false, cur === tag.toUpperCase() ? 'p' : tag); sync(); updateState(); }
    function say(msg) { if (status) { status.textContent = msg; clearTimeout(status.__t); status.__t = setTimeout(function () { status.textContent = ''; }, 3500); } }
    function openLink() {
      saveSel();
      var a = null, s = getSelection(); if (s.rangeCount) { var n = s.anchorNode; while (n && n !== area) { if (n.nodeType === 1 && n.tagName === 'A') { a = n; break; } n = n.parentNode; } }
      var inp = $('[data-link-input]', pop); inp.value = a ? a.getAttribute('href') || '' : '';
      $('[data-link-new]', pop).checked = !!(a && a.getAttribute('target') === '_blank');
      pop.hidden = false; pop.__anchor = a; inp.focus(); inp.select();
    }
    function applyLink() {
      var v = $('[data-link-input]', pop).value.trim();
      if (v && !/^(https?:\/\/|\/|#|mailto:|tel:)/i.test(v)) { if (/^[\w.-]+\.[a-z]{2,}/i.test(v)) v = 'https://' + v; else { say('Start the link with https:// or /'); return; } }
      pop.hidden = true; restoreSel();
      var newTab = $('[data-link-new]', pop).checked;
      if (!v) { doc.execCommand('unlink'); sync(); return; }
      var s = getSelection();
      if (s.isCollapsed && !pop.__anchor) { doc.execCommand('insertHTML', false, '<a href="' + v.replace(/"/g, '&quot;') + '">' + v.replace(/</g, '&lt;') + '</a>'); }
      else { doc.execCommand('createLink', false, v); }
      var a = pop.__anchor; if (!a) { var n = getSelection().anchorNode; while (n && n !== area) { if (n.nodeType === 1 && n.tagName === 'A') { a = n; break; } n = n.parentNode; } }
      if (a) { a.setAttribute('href', v); if (newTab) { a.setAttribute('target', '_blank'); a.setAttribute('rel', 'noopener noreferrer'); } else { a.removeAttribute('target'); a.removeAttribute('rel'); } }
      sync();
    }
    function insertImage(file) {
      if (!uploadUrl) return;
      if (!/^image\//.test(file.type)) { say('Choose a JPG, PNG or WebP image.'); return; }
      say('Uploading image…');
      var fd = new FormData(); fd.append('image', file);
      api(uploadUrl, { method: 'POST', data: fd }).then(function (j) {
        if (j && j.ok && j.url) {
          restoreSel();
          var alt = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').replace(/"/g, '');
          /* the picture gets its own paragraph after the block being edited, never inside a list item */
          var s = getSelection(), at = s.rangeCount ? s.anchorNode : null;
          while (at && at.parentNode !== area) at = at.parentNode;
          var pic = doc.createElement('p'), im = doc.createElement('img'), tail = doc.createElement('p');
          im.setAttribute('src', j.url); im.setAttribute('alt', alt); pic.appendChild(im); tail.innerHTML = '<br>';
          var ref = at && at.parentNode === area ? at.nextSibling : null;
          area.insertBefore(pic, ref); area.insertBefore(tail, pic.nextSibling);
          var r = doc.createRange(); r.setStart(tail, 0); r.collapse(true); s.removeAllRanges(); s.addRange(r);
          sync(); say('Image added.');
        } else say((j && j.error) || 'The image could not be uploaded.');
      });
    }
    function toggleSource(btn) {
      sourceMode = !sourceMode;
      btn.setAttribute('aria-pressed', sourceMode ? 'true' : 'false'); btn.classList.toggle('is-on', sourceMode);
      ed.classList.toggle('is-source', sourceMode);
      if (sourceMode) { src.value = clean(); src.hidden = false; area.hidden = true; src.focus(); }
      else { area.innerHTML = src.value; src.hidden = true; area.hidden = false; sync(); area.focus(); }
      $$('[data-cmd]', bar).forEach(function (b) { if (b.getAttribute('data-cmd') !== 'source') b.disabled = sourceMode; });
    }
    bar.addEventListener('mousedown', function (e) { if (e.target.closest('[data-cmd]')) { saveSel(); e.preventDefault(); } });
    bar.addEventListener('click', function (e) {
      var b = e.target.closest('[data-cmd]'); if (!b || b.disabled) return;
      var c = b.getAttribute('data-cmd');
      if (c === 'bold' || c === 'italic' || c === 'underline') exec(c);
      else if (c === 'h2' || c === 'h3') block(c);
      else if (c === 'p') { restoreSel(); doc.execCommand('formatBlock', false, 'p'); sync(); updateState(); }
      else if (c === 'quote') block('blockquote');
      else if (c === 'ul') exec('insertUnorderedList'); else if (c === 'ol') exec('insertOrderedList');
      else if (c === 'hr') exec('insertHorizontalRule');
      else if (c === 'link') openLink(); else if (c === 'unlink') exec('unlink');
      else if (c === 'image') { saveSel(); fileInp && fileInp.click(); }
      else if (c === 'clear') { restoreSel(); doc.execCommand('removeFormat'); doc.execCommand('formatBlock', false, 'p'); sync(); }
      else if (c === 'undo') exec('undo'); else if (c === 'redo') exec('redo');
      else if (c === 'source') toggleSource(b);
    });
    on(fileInp, 'change', function () { if (fileInp.files[0]) insertImage(fileInp.files[0]); fileInp.value = ''; });
    on(pop, 'click', function (e) { if (e.target.closest('[data-link-apply]')) applyLink(); else if (e.target.closest('[data-link-cancel]')) { pop.hidden = true; restoreSel(); } });
    on($('[data-link-input]', pop), 'keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); applyLink(); } else if (e.key === 'Escape') { pop.hidden = true; restoreSel(); } });
    on(area, 'input', function () { wrapLoose(); sync(); });
    on(area, 'keyup', function () { saveSel(); updateState(); });
    on(area, 'mouseup', function () { saveSel(); updateState(); });
    on(area, 'blur', saveSel);
    on(area, 'keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openLink(); } });
    on(area, 'paste', function (e) {
      e.preventDefault();
      var t = (e.clipboardData || window.clipboardData).getData('text/plain') || '';
      if (!t) return;
      var html = t.split(/\n{2,}/).map(function (p) { return '<p>' + p.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</p>'; }).join('');
      doc.execCommand('insertHTML', false, html); sync();
    });
    on(src, 'input', function () { var t = src.value.replace(/<[^>]*>/g, ' ').trim(); if (count) { var w = t ? t.split(/\s+/).length : 0; count.textContent = w + (w === 1 ? ' word' : ' words'); } });
    if (ed.closest('form')) { var fm = ed.closest('form'); on(fm, 'cm:before', function () { sync(); }); on(fm, 'submit', function () { sync(); }, true); }
    sync();
  }

  /* ---------------------------------------------------------------- SEO preview */
  function initSeo(box) {
    function pick(a) { var v = box.getAttribute(a); return v ? $(v) : null; }
    var t = pick('data-title'), d = pick('data-desc'), s = pick('data-slug');
    var fallbackT = pick('data-title-fallback'), fallbackD = pick('data-desc-fallback');
    var prefix = box.getAttribute('data-prefix') || '';
    var site = box.getAttribute('data-site') || '';
    function lim(v, n) { v = (v || '').trim(); return v.length > n ? v.slice(0, n - 1) + '…' : v; }
    function draw() {
      var title = (t && t.value.trim()) || (fallbackT && fallbackT.value.trim()) || 'Untitled';
      var desc = (d && d.value.trim()) || (fallbackD && fallbackD.value.replace(/<[^>]*>/g, ' ').trim()) || 'Add a description so search engines show a useful summary.';
      $('[data-seo-t]', box).textContent = lim(title + (site ? ' · ' + site : ''), 62);
      $('[data-seo-d]', box).textContent = lim(desc, 158);
      $('[data-seo-u]', box).textContent = prefix + ((s && s.value) || '');
      var tl = $('[data-seo-tl]', box), dl = $('[data-seo-dl]', box);
      if (tl && t) { tl.textContent = t.value.length + ' / ' + (t.maxLength > 0 ? t.maxLength : 60); tl.classList.toggle('is-warn', t.value.length > 60); }
      if (dl && d) { dl.textContent = d.value.length + ' / ' + (d.maxLength > 0 ? d.maxLength : 160); dl.classList.toggle('is-warn', d.value.length > 160); }
    }
    [t, d, s, fallbackT, fallbackD].forEach(function (el) { on(el, 'input', draw); });
    draw();
  }

  /* ---------------------------------------------------------------- hero preview + slide dialog */
  function initHeroPreview(box) {
    var imgs = $$('[data-hp-slide]', box), i = 0, timer = null;
    var speed = Math.max(2, +(box.getAttribute('data-speed') || 6)) * 1000;
    function show(n) { imgs.forEach(function (el, k) { el.classList.toggle('is-on', k === n); }); }
    if (imgs.length > 1 && !(ch().reduced && ch().reduced())) {
      show(0); timer = setInterval(function () { i = (i + 1) % imgs.length; show(i); }, speed);
      on(box, 'mouseenter', function () { clearInterval(timer); });
    } else show(0);
    delegate('click', '[data-hp-device]', function (e, el) {
      var dev = el.getAttribute('data-hp-device');
      $$('[data-hp-device]').forEach(function (b) { b.classList.toggle('is-on', b === el); b.setAttribute('aria-pressed', b === el ? 'true' : 'false'); });
      box.classList.toggle('is-mobile', dev === 'mobile');
    });
  }
  function initSlideDialog(d) {
    var form = $('form', d);
    var prevD = $('[data-sp-desktop]', d), prevM = $('[data-sp-mobile]', d), dot = $('[data-focal-dot]', d), pane = $('[data-focal-pane]', d);
    var fx = $('[name=focal_x]', d), fy = $('[name=focal_y]', d), ov = $('[name=overlay_opacity]', d), ovShade = $$('[data-sp-shade]', d);
    var h = $('[name=headline]', d), sh = $('[name=subheadline]', d), ct = $('[name=cta_text]', d);
    var base = { h: d.getAttribute('data-global-h') || '', s: d.getAttribute('data-global-s') || '', c: d.getAttribute('data-global-c') || '' };
    function pos() { var p = (fx.value || 50) + '% ' + (fy.value || 50) + '%'; $$('[data-sp-img]', d).forEach(function (im) { im.style.objectPosition = p; }); if (dot) { dot.style.left = (fx.value || 50) + '%'; dot.style.top = (fy.value || 50) + '%'; } }
    function shade() {
      var g = +(d.getAttribute('data-global-overlay') || 45), v = ov.value === '' ? 0 : Math.max(0, Math.min(90, +ov.value || 0));
      ovShade.forEach(function (s) { s.style.opacity = g / 100; });
      $$('[data-sp-extra]', d).forEach(function (s) { s.style.opacity = v / 100; });
      var l = $('[data-ov-label]', d); if (l) l.textContent = ov.value === '' ? 'None added' : v + '% extra';
    }
    function text() {
      var t = $$('[data-sp-text]', d);
      t.forEach(function (n) { var hh = $('[data-sp-h]', n), ss = $('[data-sp-s]', n), cc = $('[data-sp-c]', n); if (hh) hh.textContent = h.value.trim() || base.h; if (ss) ss.textContent = sh.value.trim() || base.s; if (cc) cc.textContent = ct.value.trim() || base.c; });
    }
    function setFocal(e) {
      var r = pane.getBoundingClientRect();
      var x = Math.max(0, Math.min(100, Math.round((e.clientX - r.left) / r.width * 100)));
      var y = Math.max(0, Math.min(100, Math.round((e.clientY - r.top) / r.height * 100)));
      fx.value = x; fy.value = y; pos();
    }
    var dragging = false;
    on(pane, 'pointerdown', function (e) { if (pane.getAttribute('data-has-image') !== '1') return; dragging = true; pane.setPointerCapture(e.pointerId); setFocal(e); });
    on(pane, 'pointermove', function (e) { if (dragging) setFocal(e); });
    on(pane, 'pointerup', function () { dragging = false; });
    on(pane, 'keydown', function (e) {
      var step = e.shiftKey ? 10 : 2, k = e.key;
      if (k === 'ArrowLeft') fx.value = Math.max(0, +fx.value - step); else if (k === 'ArrowRight') fx.value = Math.min(100, +fx.value + step);
      else if (k === 'ArrowUp') fy.value = Math.max(0, +fy.value - step); else if (k === 'ArrowDown') fy.value = Math.min(100, +fy.value + step); else return;
      e.preventDefault(); pos();
    });
    ['input', 'change'].forEach(function (ev) { on(ov, ev, shade); on(h, ev, text); on(sh, ev, text); on(ct, ev, text); on(fx, ev, pos); on(fy, ev, pos); });
    on(d, 'cm:image', function (e) {
      var box = e.target.closest('[data-cm-image]'); if (!box) return;
      var inp = $('input[type=file]', box); var src = e.detail.src;
      if (inp.name === 'image') { $$('[data-sp-img="desktop"]', d).forEach(function (im) { if (src) { im.src = src; im.hidden = false; } else { im.hidden = true; } }); pane.setAttribute('data-has-image', src ? '1' : '0'); $$('[data-sp-empty]', d).forEach(function (n) { n.hidden = !!src; }); if (!$('[data-image-input][name=mobile_image]', d).files.length && !$('[data-sp-img="mobile"]', d).__own) { $$('[data-sp-img="mobile"]', d).forEach(function (im) { if (src) { im.src = src; im.hidden = false; } }); } }
      else if (inp.name === 'mobile_image') { var m = $('[data-sp-img="mobile"]', d); m.__own = !!src; var fallback = $('[data-image-preview]', $('[data-cm-image] [name=image]', d).closest('[data-cm-image]')); m.src = src || (fallback && fallback.src) || ''; m.hidden = !m.src; }
    });
    on(d, 'cm:open', function () { setTimeout(function () { pos(); shade(); text(); }, 0); });
    on(form, 'reset', function () { setTimeout(function () { pos(); shade(); text(); }, 0); });
  }

  /* ---------------------------------------------------------------- plan preview */
  function initPlanPreview(form) {
    var card = $('[data-plan-preview]'); if (!card) return;
    if (form.getAttribute('data-symbol')) CMAdmin.symbol = form.getAttribute('data-symbol');
    var defs = {}; try { defs = JSON.parse($('#plan-feature-defs').textContent || '{}'); } catch (x) {}
    function val(name) { var el = form.elements[name]; return el ? el.value : ''; }
    function chk(name) { var el = form.elements[name]; return !!(el && el.checked); }
    function limitText(key, singular, per) {
      if (chk(key + '_unlimited')) return 'Unlimited ' + singular + per;
      var n = parseInt(val(key), 10) || 0; if (n === 0) return null;
      return n + ' ' + singular + per;
    }
    function bullets() {
      var b = [];
      var s = limitText('daily_swipes', 'likes', ' a day'); if (s) b.push(s);
      s = limitText('daily_superlikes', 'super likes', ' a day'); if (s) b.push(s);
      s = limitText('daily_messages', 'new messages', ' a day'); if (s) b.push(s);
      if (chk('rewind')) { s = limitText('daily_rewinds', 'undos', ' a day'); b.push(s ? 'Undo your last swipe (' + s + ')' : 'Undo your last swipe'); }
      var perks = { see_who_liked: 'See who liked you', advanced_filters: 'Advanced filters', incognito: 'Browse in incognito', read_receipts: 'Read receipts', message_before_match: 'Message before matching', profile_visitors: 'See who visited your profile', no_ads: 'No adverts', priority_likes: 'Your likes are shown first' };
      Object.keys(perks).forEach(function (k) { if (chk(k)) b.push(perks[k]); });
      var mp = parseInt(val('max_photos'), 10) || 0; if (mp) b.push('Up to ' + mp + ' photos');
      var bc = parseInt(val('boost_credits_monthly'), 10) || 0; if (bc) b.push(bc + ' free boost' + (bc > 1 ? 's' : '') + ' a month');
      return b;
    }
    function draw() {
      var tier = val('tier'), name = val('name') || 'Plan name';
      card.setAttribute('data-tier', tier);
      $('[data-pp-name]', card).textContent = name;
      $('[data-pp-tag]', card).textContent = val('tagline');
      var m = parseFloat(val('price_monthly')) || 0, y = parseFloat(val('price_yearly')) || 0;
      $('[data-pp-price]', card).textContent = m > 0 ? fmtMoney(m) : 'Free';
      $('[data-pp-per]', card).textContent = m > 0 ? 'a month' : '';
      var yr = $('[data-pp-year]', card);
      if (y > 0 && m > 0) { var save = Math.round((1 - y / (m * 12)) * 100); yr.textContent = fmtMoney(y) + ' a year' + (save > 0 ? ' · save ' + save + '%' : ''); yr.hidden = false; } else yr.hidden = true;
      var ul = $('[data-pp-list]', card); ul.innerHTML = '';
      bullets().forEach(function (t) { var li = doc.createElement('li'); li.innerHTML = iconSvg('check', 'w-4 h-4'); var s = doc.createElement('span'); s.textContent = t; li.appendChild(s); ul.appendChild(li); });
      var badge = $('[data-pp-badge]', card); badge.hidden = !chk('is_featured');
      var color = val('badge_color'); card.style.setProperty('--plan-color', /^#[0-9a-f]{6}$/i.test(color) ? color : '');
      var ic = val('badge_icon'); var icEl = $('[data-pp-icon]', card); icEl.innerHTML = ic ? iconSvg(ic, 'w-5 h-5') : '';
    }
    ['input', 'change'].forEach(function (ev) { on(form, ev, draw); });
    draw();
  }
  function initLimitInputs(form) {
    $$('[data-limit-unlimited]', form).forEach(function (cb) {
      var inp = form.elements[cb.getAttribute('data-limit-unlimited')];
      function sync() { if (inp) { inp.disabled = cb.checked; } }
      on(cb, 'change', sync); sync();
    });
  }

  /* ---------------------------------------------------------------- member lookup */
  function initLookup(inp) {
    var url = inp.getAttribute('data-member-lookup'), hid = $(inp.getAttribute('data-target')), list = doc.createElement('div'), t = null;
    list.className = 'cm-suggest'; list.hidden = true; inp.parentNode.style.position = 'relative'; inp.parentNode.appendChild(list);
    function close() { list.hidden = true; list.innerHTML = ''; }
    on(inp, 'input', function () {
      if (hid) hid.value = '';
      clearTimeout(t); var q = inp.value.trim(); if (q.length < 2) { close(); return; }
      t = setTimeout(function () {
        api(url + '?q=' + encodeURIComponent(q), { quiet: true }).then(function (j) {
          list.innerHTML = '';
          if (!j || !j.ok || !j.members.length) { var n = doc.createElement('div'); n.className = 'cm-suggest-empty'; n.textContent = 'No member found'; list.appendChild(n); list.hidden = false; return; }
          j.members.forEach(function (m) {
            var b = doc.createElement('button'); b.type = 'button'; b.className = 'cm-suggest-item';
            b.innerHTML = '<span class="cm-suggest-name"></span><span class="cm-suggest-sub"></span>';
            b.firstChild.textContent = m.name; b.lastChild.textContent = m.email + ' · #' + m.id;
            on(b, 'click', function () { inp.value = m.email; if (hid) hid.value = m.id; dropFieldError({ target: inp }); close(); });
            list.appendChild(b);
          });
          list.hidden = false;
        });
      }, 220);
    });
    on(inp, 'keydown', function (e) { if (e.key === 'Escape') close(); });
    doc.addEventListener('click', function (e) { if (!inp.parentNode.contains(e.target)) close(); });
  }

  /* ---------------------------------------------------------------- newsletter campaign sender */
  function initCampaign(box) {
    var url = box.getAttribute('data-url'), id = +box.getAttribute('data-id');
    var bar = $('[data-cs-bar]', box), txt = $('[data-cs-text]', box), btn = $('[data-cs-start]', box), stop = $('[data-cs-stop]', box);
    var running = false, cancelled = false;
    function render(j) {
      var pct = j.total ? Math.round((j.sent + j.failed) / j.total * 100) : 100;
      bar.style.width = pct + '%'; bar.parentNode.setAttribute('aria-valuenow', String(pct));
      txt.textContent = (j.sent + j.failed) + ' of ' + j.total + ' processed · ' + j.sent + ' sent' + (j.failed ? ' · ' + j.failed + ' failed' : '');
    }
    function step() {
      if (cancelled) { running = false; return; }
      api(url, { method: 'POST', data: { action: 'campaign_batch', id: id } }).then(function (j) {
        if (!j || !j.ok) { running = false; btn.disabled = false; btn.hidden = false; stop.hidden = true; toast((j && j.error) || 'Sending stopped. You can resume it.', 'error'); return; }
        render(j);
        if (j.done) { running = false; stop.hidden = true; txt.textContent += ' · finished'; toast('Campaign finished: ' + j.sent + ' sent.', 'success'); setTimeout(function () { location.href = j.redirect || location.href; }, 1200); }
        else setTimeout(step, 150);
      });
    }
    on(btn, 'click', function () {
      if (running) return; running = true; cancelled = false; btn.hidden = true; stop.hidden = false; box.classList.add('is-running'); step();
    });
    on(stop, 'click', function () { cancelled = true; stop.disabled = true; api(url, { method: 'POST', data: { action: 'campaign_cancel', id: id } }).then(function () { toast('Sending cancelled.', 'success'); setTimeout(function () { location.reload(); }, 600); }); });
    if (box.hasAttribute('data-autostart')) setTimeout(function () { btn.click(); }, 300);
  }

  /* ---------------------------------------------------------------- misc */
  delegate('change', '[data-show-when]', function (e, el) { /* reserved */ });
  function initConditional() {
    $$('[data-cond-source]').forEach(function (box) {
      var src = $(box.getAttribute('data-cond-source')); if (!src) return;
      var vals = (box.getAttribute('data-cond-values') || '').split(',');
      function upd() { box.hidden = vals.indexOf(src.value) === -1; }
      on(src, 'change', upd); upd();
    });
  }
  function initShowWhen(form) {
    var boxes = $$('[data-show-when]', form); if (!boxes.length) return;
    function upd() {
      boxes.forEach(function (box) {
        var rule = box.getAttribute('data-show-when').split('='), name = rule[0], vals = (rule[1] || '').split('|');
        var ctl = form.elements[name]; var v = ctl ? (ctl.value !== undefined ? ctl.value : '') : '';
        if (ctl && ctl.type === 'checkbox') v = ctl.checked ? '1' : '0';
        var show = vals.indexOf(v) !== -1;
        box.hidden = !show;
        $$('input,select,textarea', box).forEach(function (i) { if (i.hasAttribute('data-keep-enabled')) return; i.disabled = !show; });
      });
    }
    ['change', 'input'].forEach(function (ev) { on(form, ev, upd); });
    on(form, 'reset', function () { setTimeout(upd, 0); });
    on(form.closest('dialog') || form, 'cm:open', function () { setTimeout(upd, 0); });
    upd();
  }
  delegate('input', '[data-color-sync]', function (e, el) { var t = $(el.getAttribute('data-color-sync')); if (t) t.value = el.value; });
  delegate('click', '[data-reveal-target]', function (e, el) { var t = $(el.getAttribute('data-reveal-target')); if (t) { t.hidden = !t.hidden; el.setAttribute('aria-expanded', t.hidden ? 'false' : 'true'); } });
  delegate('click', '[data-fill-reason]', function (e, el) { var t = $(el.getAttribute('data-fill-reason')); if (t) { t.value = el.getAttribute('data-text'); t.focus(); } });

  /* saved replies: choosing one fills the reply box */
  delegate('change', '[data-quick-reply]', function (e, el) {
    var t = $(el.getAttribute('data-quick-reply')); if (!t || !el.value) return;
    t.value = el.value; t.dispatchEvent(new Event('input', { bubbles: true })); t.focus();
    try { t.setSelectionRange(t.value.length, t.value.length); } catch (x) {}
  });

  /* live text mirror: <div data-live-from="selector"> shows the value of that input as it is typed */
  function initLive(el) {
    var src = $(el.getAttribute('data-live-from')); if (!src) return;
    function upd() { el.textContent = src.value; el.hidden = src.value.trim() === ''; }
    on(src, 'input', upd); upd();
  }

  /* advert preview: mirrors the form into the card members see */
  function initAdPreview(box) {
    var form = box.closest('form'); if (!form) return;
    var card = $('[data-cp-card]', box), media = $('[data-ad-media]', box), img = $('[data-ad-img]', box);
    var t = $('[data-ad-title]', box), d = $('[data-ad-desc]', box), c = $('[data-ad-cta]', box);
    var title = $('[name=title]', form), desc = $('[name=description]', form), cta = $('[name=cta_text]', form);
    var file = $('input[type=file][name=image]', form), rm = $('[name=remove_image]', form);
    var original = img.getAttribute('src') || '', blob = '';
    function upd() {
      t.textContent = title.value.trim() || 'Your headline appears here';
      var dv = desc.value.trim(); d.textContent = dv; d.hidden = !dv;
      c.textContent = cta.value.trim() || 'Learn more';
      var src = blob || (rm && rm.checked ? '' : original);
      if (src) { img.src = src; media.hidden = false; } else { img.removeAttribute('src'); media.hidden = true; }
      var type = ($('[name=ad_type]:checked', form) || {}).value || 'card';
      card.className = card.className.replace(/\bcpa-(card|banner|popup|story)\b/g, '').replace(/\s+/g, ' ').trim() + ' cp-card cpa-' + type;
    }
    [title, desc, cta].forEach(function (i) { on(i, 'input', upd); });
    $$('[name=ad_type]', form).forEach(function (r) { on(r, 'change', upd); });
    if (rm) on(rm, 'change', upd);
    if (file) on(file, 'change', function () {
      if (blob) { try { URL.revokeObjectURL(blob); } catch (e) {} blob = ''; }
      if (file.files && file.files[0]) blob = URL.createObjectURL(file.files[0]);
      upd();
    });
    upd();
  }

  /* gift dialog preview: shows how the gift looks in the picker */
  function initGiftPreview(box) {
    var form = box.closest('form'), dlg = box.closest('dialog'); if (!form) return;
    var name = $('[name=name]', form), emoji = $('[name=emoji]', form), price = $('[name=price]', form), sym = box.getAttribute('data-symbol') || 'KSh';
    var f = $('[data-gift-preview-face]', box), n = $('[data-gift-preview-name]', box), p = $('[data-gift-preview-price]', box);
    function upd() {
      f.textContent = (emoji.value || '').trim() || '\uD83C\uDF81';
      n.textContent = (name.value || '').trim() || 'Gift name';
      var v = parseFloat(String(price.value).replace(/[, ]/g, ''));
      p.textContent = isFinite(v) && v > 0 ? sym + ' ' + v.toLocaleString('en-KE', { maximumFractionDigits: 2 }) : '';
    }
    [name, emoji, price].forEach(function (i) { on(i, 'input', upd); });
    if (dlg) on(dlg, 'cm:open', function () { setTimeout(upd, 0); });
    upd();
  }

  function boot() {
    initAll();
    $$('.cm-gift-preview').forEach(initGiftPreview);
    $$('[data-ad-preview]').forEach(initAdPreview);
    $$('[data-live-from]').forEach(initLive);
    $$('[data-slug-from]').forEach(initSlug);
    $$('[data-cm-repeater]').forEach(initRepeater);
    $$('[data-cm-editor]').forEach(initEditor);
    $$('[data-seo-preview]').forEach(initSeo);
    $$('[data-hero-preview]').forEach(initHeroPreview);
    $$('#slide-dialog').forEach(initSlideDialog);
    $$('form[data-plan-form]').forEach(function (f) { initLimitInputs(f); initPlanPreview(f); });
    $$('[data-member-lookup]').forEach(initLookup);
    $$('[data-campaign]').forEach(initCampaign);
    $$('[data-bulk]').forEach(bulkState);
    $$('form').forEach(function (f) { if (f.querySelector('[data-show-when]')) initShowWhen(f); });
    initConditional();
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', boot); else boot();
})();
