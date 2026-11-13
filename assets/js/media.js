/* ConnectHub media library (admin): the "Choose from media" picker used by every image slot, and the Media page.
   Vanilla, exposes AD.mediaPicker(options). Needs app.js (window.CH). */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH || {};
  var AD = window.AD = window.AD || {};

  function $(s, c) { return (c || doc).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }
  function on(el, ev, fn, o) { if (el) el.addEventListener(ev, fn, o); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }
  function toast(m, t, ms) { if (CH.toast) CH.toast(m, t || 'info', ms); }
  function sprite(name) { return (CH.url ? CH.url('/assets/icons/sprite.svg') : '/assets/icons/sprite.svg') + '#i-' + name; }
  function icon(name, cls) { return '<svg class="icon ' + (cls || 'w-4 h-4') + '" aria-hidden="true"><use href="' + sprite(name) + '"></use></svg>'; }
  function qs(o) { return Object.keys(o).filter(function (k) { return o[k] !== '' && o[k] != null && o[k] !== false; }).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(o[k] === true ? '1' : o[k]); }).join('&'); }
  function size(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }

  /* ---------------------------------------------------------------- tiles */
  function tile(it, opts) {
    var sel = opts.selected && opts.selected === it.path;
    var thumb = it.kind === 'video'
      ? '<span class="mp-video">' + icon('video', 'w-7 h-7') + '</span>'
      : '<img src="' + esc(it.thumb) + '" alt="" loading="lazy" decoding="async">';
    var dims = it.kind === 'video' ? 'Video' : (it.w && it.h ? it.w + ' × ' + it.h : (/\.svg$/i.test(it.path) ? 'SVG' : ''));
    var meta = [dims, it.size ? size(it.size) : ''].filter(Boolean).join(' · ');
    var badge = opts.manager && it.source === 'library' ? '<span class="mp-use' + (it.uses ? ' is-used' : '') + '">' + (it.uses ? 'In use' : 'Unused') + '</span>' : '';
    return '<li><button type="button" class="mp-tile' + (sel ? ' is-selected' : '') + '" data-path="' + esc(it.path) + '" aria-pressed="' + (sel ? 'true' : 'false') + '" title="' + esc(it.title) + '">' +
      '<span class="mp-thumb' + (/\.(png|svg)$/i.test(it.path) ? ' is-alpha' : '') + '">' + thumb + '<span class="mp-check">' + icon('check', 'w-4 h-4') + '</span>' + badge + '</span>' +
      '<span class="mp-name">' + esc(it.title) + '</span><span class="mp-meta">' + esc(meta) + '</span></button></li>';
  }

  /* ---------------------------------------------------------------- list loader shared by picker and manager */
  function Loader(grid, more, empty, cfg) {
    var self = this;
    self.f = { src: 'library', kind: cfg.kind || '', q: '', group: '', unused: false, page: 1 };
    self.items = []; self.busy = false; self.pages = 1; self.total = 0; self.groups = {};
    self.cfg = cfg;
    self.load = function (reset) {
      if (self.busy) return Promise.resolve();
      if (reset) { self.f.page = 1; self.items = []; grid.innerHTML = ''; grid.setAttribute('aria-busy', 'true'); }
      self.busy = true;
      return CH.api('/api/admin/media?' + qs({ src: self.f.src, kind: self.f.kind, q: self.f.q, group: self.f.group, unused: self.f.unused, page: self.f.page, per: 36, usage: cfg.manager ? 1 : 0 })).then(function (j) {
        self.busy = false; grid.removeAttribute('aria-busy');
        if (!j.ok) { toast(j.error || 'Could not load the library.', 'error'); return; }
        self.pages = j.pages; self.total = j.total; self.groups = j.groups || {};
        var start = self.items.length;
        self.items = self.items.concat(j.items);
        grid.insertAdjacentHTML('beforeend', j.items.map(function (it) { return tile(it, { selected: cfg.selected(), manager: cfg.manager }); }).join(''));
        more.hidden = j.page >= j.pages;
        empty.hidden = self.items.length > 0;
        if (cfg.onLoaded) cfg.onLoaded(self, start);
      });
    };
    self.byPath = function (p) { for (var i = 0; i < self.items.length; i++) if (self.items[i].path === p) return self.items[i]; return null; };
    on(more, 'click', function () { self.f.page++; self.load(false); });
  }

  /* ---------------------------------------------------------------- upload (shared) */
  function uploadFiles(files, hooks) {
    var list = Array.prototype.slice.call(files || []), done = 0, ok = [], i = 0;
    if (!list.length) return Promise.resolve([]);
    function next() {
      if (i >= list.length) return Promise.resolve(ok);
      var f = list[i++];
      hooks.progress && hooks.progress(i, list.length, f.name);
      if (f.size > 40 * 1048576) { toast(f.name + ' is larger than 40 MB.', 'error'); return next(); }
      var fd = new FormData(); fd.append('file', f);
      return CH.api('/api/admin/media/upload', { data: fd }).then(function (j) {
        if (j.ok) { ok.push(j.item); } else { toast(f.name + ': ' + (j.error || 'upload failed.'), 'error'); }
        return next();
      });
    }
    return next();
  }

  function dropZone(el, onFiles) {
    ['dragenter', 'dragover'].forEach(function (ev) { on(el, ev, function (e) { if (e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') >= 0) { e.preventDefault(); el.classList.add('is-over'); } }); });
    ['dragleave', 'drop'].forEach(function (ev) { on(el, ev, function (e) { el.classList.remove('is-over'); if (ev === 'drop') { e.preventDefault(); if (e.dataTransfer && e.dataTransfer.files.length) onFiles(e.dataTransfer.files); } }); });
  }

  /* ---------------------------------------------------------------- picker dialog */
  var dlg = null, st = {};
  function build() {
    dlg = doc.createElement('dialog');
    dlg.className = 'modal media-dialog';
    dlg.setAttribute('aria-labelledby', 'mp-title');
    dlg.innerHTML =
      '<div class="modal-panel mp-panel">' +
        '<button type="button" class="modal-close" data-mp-close aria-label="Close">' + icon('x', 'w-5 h-5') + '</button>' +
        '<header class="mp-head"><h2 class="modal-title" id="mp-title">Choose a picture</h2><p class="mp-sub" data-mp-sub></p></header>' +
        '<div class="mp-bar">' +
          '<div class="mp-tabs" role="tablist" aria-label="Where to choose from">' +
            '<button type="button" role="tab" class="mp-tab" data-mp-tab="library">' + icon('images', 'w-4 h-4') + ' Your library</button>' +
            '<button type="button" role="tab" class="mp-tab" data-mp-tab="art">' + icon('sparkles', 'w-4 h-4') + ' Illustrations</button>' +
            '<button type="button" role="tab" class="mp-tab" data-mp-tab="upload">' + icon('upload', 'w-4 h-4') + ' Upload</button>' +
          '</div>' +
          '<label class="mp-search"><span class="sr-only">Search</span>' + icon('search', 'w-4 h-4') + '<input type="search" placeholder="Search by name" data-mp-search autocomplete="off"></label>' +
        '</div>' +
        '<div class="mp-groups" data-mp-groups hidden></div>' +
        '<div class="mp-body" data-mp-body>' +
          '<ul class="mp-grid" data-mp-grid></ul>' +
          '<div class="mp-empty" data-mp-empty hidden>' + icon('image-off', 'w-8 h-8') + '<p class="mp-empty-t">Nothing here yet</p><p class="mp-empty-s" data-mp-empty-text></p><button type="button" class="btn btn-outline btn-sm" data-mp-tab="upload">' + icon('upload', 'w-4 h-4') + ' Upload a file</button></div>' +
          '<div class="mp-more"><button type="button" class="btn btn-outline btn-sm" data-mp-more hidden>Show more</button></div>' +
          '<div class="mp-drop" data-mp-drop hidden>' +
            '<div class="mp-drop-box"><span class="mp-drop-ic">' + icon('image-up', 'w-8 h-8') + '</span><p class="mp-drop-t">Drop pictures here</p><p class="mp-drop-s" data-mp-drop-help>JPG, PNG, WebP or SVG up to 10 MB</p>' +
            '<label class="btn btn-primary btn-sm" for="mp-file">' + icon('upload', 'w-4 h-4') + ' Choose files</label><input id="mp-file" class="sr-only" type="file" multiple data-mp-file><p class="mp-drop-status" data-mp-status role="status"></p></div>' +
          '</div>' +
        '</div>' +
        '<footer class="mp-foot"><p class="mp-picked" data-mp-picked>Nothing selected</p><div class="mp-foot-btns"><button type="button" class="btn btn-ghost" data-mp-close>Cancel</button><button type="button" class="btn btn-primary" data-mp-use disabled>Use this</button></div></footer>' +
      '</div>';
    doc.body.appendChild(dlg);

    var grid = $('[data-mp-grid]', dlg), more = $('[data-mp-more]', dlg), empty = $('[data-mp-empty]', dlg);
    st.loader = new Loader(grid, more, empty, {
      kind: '', manager: false,
      selected: function () { return st.picked ? st.picked.path : (st.current || ''); },
      onLoaded: function (l) {
        var g = $('[data-mp-groups]', dlg), keys = Object.keys(l.groups || {});
        if (l.f.src === 'art' && keys.length) {
          g.hidden = false;
          g.innerHTML = '<button type="button" class="mp-chip' + (l.f.group === '' ? ' is-on' : '') + '" data-mp-group="">All</button>' + keys.map(function (k) { return '<button type="button" class="mp-chip' + (l.f.group === k ? ' is-on' : '') + '" data-mp-group="' + esc(k) + '">' + esc(k) + '</button>'; }).join('');
        } else if (l.f.src !== 'art') { g.hidden = true; }
        var t = $('[data-mp-empty-text]', dlg);
        t.textContent = l.f.q ? 'No file matches "' + l.f.q + '".' : (l.f.src === 'art' ? 'There are no built-in illustrations.' : (st.kind === 'video' ? 'Upload a short MP4 or WebM video to use it here.' : 'Pictures you upload anywhere in the admin are kept here so you can use them again.'));
      }
    });

    on(dlg, 'click', function (e) {
      var t = e.target;
      if (t.closest('[data-mp-close]')) { dlg.close(); return; }
      var tab = t.closest('[data-mp-tab]'); if (tab) { setTab(tab.getAttribute('data-mp-tab')); return; }
      var grp = t.closest('[data-mp-group]'); if (grp) { st.loader.f.group = grp.getAttribute('data-mp-group'); st.loader.load(true); return; }
      var tl = t.closest('.mp-tile'); if (tl) { choose(tl.getAttribute('data-path')); return; }
      if (t.closest('[data-mp-use]')) { confirmPick(); }
    });
    on(dlg, 'dblclick', function (e) { var tl = e.target.closest('.mp-tile'); if (tl) { choose(tl.getAttribute('data-path')); confirmPick(); } });
    on($('[data-mp-search]', dlg), 'input', debounce(function (e) { st.loader.f.q = e.target.value.trim(); st.loader.load(true); }, 260));
    var fileInput = $('[data-mp-file]', dlg);
    on(fileInput, 'change', function () { doUpload(fileInput.files); fileInput.value = ''; });
    dropZone($('[data-mp-drop]', dlg), doUpload);
    dropZone($('[data-mp-body]', dlg), function (files) { setTab('upload'); doUpload(files); });
  }

  function setTab(tab) {
    var drop = $('[data-mp-drop]', dlg), grid = $('[data-mp-grid]', dlg), more = $('[data-mp-more]', dlg), empty = $('[data-mp-empty]', dlg), search = $('.mp-search', dlg);
    $$('.mp-tab', dlg).forEach(function (b) { var a = b.getAttribute('data-mp-tab') === tab; b.classList.toggle('is-on', a); b.setAttribute('aria-selected', a ? 'true' : 'false'); });
    if (tab === 'upload') {
      drop.hidden = false; grid.hidden = true; more.hidden = true; empty.hidden = true; search.hidden = true; $('[data-mp-groups]', dlg).hidden = true;
      $('[data-mp-drop-help]', dlg).textContent = st.kind === 'video' ? 'MP4 or WebM video up to 40 MB' : 'JPG, PNG, WebP or SVG up to 10 MB';
      $('[data-mp-file]', dlg).setAttribute('accept', st.kind === 'video' ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp,image/svg+xml');
      return;
    }
    drop.hidden = true; grid.hidden = false; search.hidden = false;
    st.loader.f.src = tab; st.loader.f.group = ''; st.loader.f.q = ''; $('[data-mp-search]', dlg).value = '';
    st.loader.load(true);
  }

  function choose(path) {
    var it = st.loader.byPath(path) || st.last;
    if (!it) return;
    st.picked = it;
    $$('.mp-tile', dlg).forEach(function (b) { var a = b.getAttribute('data-path') === path; b.classList.toggle('is-selected', a); b.setAttribute('aria-pressed', a ? 'true' : 'false'); });
    $('[data-mp-picked]', dlg).innerHTML = icon('check', 'w-4 h-4') + ' <span>' + esc(it.title) + '</span>';
    $('[data-mp-use]', dlg).disabled = false;
  }

  function confirmPick() {
    if (!st.picked) return;
    var item = st.picked, cb = st.onPick;
    dlg.close();
    if (cb) cb(item);
  }

  function doUpload(files) {
    var status = $('[data-mp-status]', dlg);
    uploadFiles(files, { progress: function (n, total, name) { status.textContent = 'Uploading ' + n + ' of ' + total + ': ' + name; } }).then(function (items) {
      status.textContent = '';
      if (!items.length) return;
      toast(items.length === 1 ? 'Added to your library.' : items.length + ' files added to your library.', 'success', 2400);
      st.last = items[0];
      setTab('library');
      var first = items[0];
      var wait = setInterval(function () { if (!st.loader.busy) { clearInterval(wait); choose(first.path); var t = $('.mp-tile[data-path="' + first.path.replace(/"/g, '\\"') + '"]', dlg); if (t && t.scrollIntoView) t.scrollIntoView({ block: 'nearest' }); } }, 60);
    });
  }

  /**
   * AD.mediaPicker({ kind: 'image'|'video', title, current: path, onPick: function (item) {} })
   * item: { path, url, thumb, title, kind, source }
   */
  AD.mediaPicker = function (o) {
    o = o || {};
    if (!dlg) build();
    st.kind = o.kind === 'video' ? 'video' : 'image';
    st.current = o.current || ''; st.picked = null; st.last = null; st.onPick = o.onPick;
    st.loader.cfg.kind = st.kind; st.loader.f.kind = st.kind;
    $('#mp-title', dlg).textContent = o.title || (st.kind === 'video' ? 'Choose a video' : 'Choose a picture');
    $('[data-mp-sub]', dlg).textContent = o.help || (st.kind === 'video' ? 'Pick a video you uploaded before, or upload a new one.' : 'Pick a picture you uploaded before, a built-in illustration, or upload a new one.');
    $('[data-mp-tab="art"]', dlg).hidden = st.kind === 'video';
    $('[data-mp-picked]', dlg).textContent = 'Nothing selected';
    $('[data-mp-use]', dlg).disabled = true;
    if (!dlg.open) dlg.showModal();
    setTab('library');
  };

  /* ---------------------------------------------------------------- image fields: "Choose from media" buttons */
  // Settings screen fields (.set-image) are handled in admin.js, which calls AD.mediaPicker itself.
  // Form fields made by the admin_cm_image_field partial:
  doc.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('[data-media-pick]') : null;
    if (!btn) return;
    e.preventDefault();
    var box = btn.closest('[data-cm-image]'); if (!box) return;
    var hidden = $('[data-image-pick]', box), img = $('[data-image-preview]', box), empty = $('[data-image-empty]', box), rm = $('[data-image-remove]', box), file = $('[data-image-input]', box);
    AD.mediaPicker({
      kind: 'image', current: hidden ? hidden.value : '',
      onPick: function (it) {
        if (hidden) hidden.value = it.path;
        if (file) file.value = '';
        if (img) { img.src = it.url; img.hidden = false; }
        if (empty) empty.hidden = true;
        if (rm) { rm.checked = false; var h = rm.closest('.cm-remove'); if (h) h.hidden = false; }
        var lbl = $('label.btn', box); if (lbl && lbl.lastChild) lbl.lastChild.textContent = ' Replace';
        box.dispatchEvent(new CustomEvent('cm:image', { bubbles: true, detail: { src: it.url, picked: it.path } }));
      }
    });
  });
  // choosing a file or ticking "remove" cancels an earlier pick
  doc.addEventListener('change', function (e) {
    var inp = e.target;
    if (inp.matches && (inp.matches('[data-image-input]') || inp.matches('[data-image-remove]'))) {
      var box = inp.closest('[data-cm-image]'), h = box && $('[data-image-pick]', box);
      if (h && (inp.matches('[data-image-remove]') ? inp.checked : (inp.files && inp.files.length))) h.value = '';
    }
  });

  /* ---------------------------------------------------------------- Media page */
  function manager(root) {
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var grid = $('[data-mm-grid]', root), more = $('[data-mm-more]', root), empty = $('[data-mm-empty]', root), status = $('[data-mm-status]', root);
    var detail = $('#media-detail');
    var loader = new Loader(grid, more, empty, {
      kind: '', manager: true, selected: function () { return ''; },
      onLoaded: function (l) {
        var t = $('[data-mm-empty-text]', root);
        if (t) t.textContent = l.f.q || l.f.unused ? 'No file matches those filters.' : (l.f.src === 'art' ? 'There are no built-in illustrations.' : 'Upload your first picture, or add one from any image field in the admin.');
        $('[data-mm-count]', root).textContent = l.total + (l.total === 1 ? ' file' : ' files');
      }
    });
    function reload() { return loader.load(true); }

    on($('[data-mm-search]', root), 'input', debounce(function (e) { loader.f.q = e.target.value.trim(); reload(); }, 260));
    on($('[data-mm-kind]', root), 'change', function (e) { loader.f.kind = e.target.value; reload(); });
    on($('[data-mm-unused]', root), 'change', function (e) { loader.f.unused = e.target.checked; reload(); });
    $$('[data-mm-tab]', root).forEach(function (b) {
      on(b, 'click', function () {
        var tab = b.getAttribute('data-mm-tab');
        $$('[data-mm-tab]', root).forEach(function (x) { var a = x === b; x.classList.toggle('is-on', a); x.setAttribute('aria-selected', a ? 'true' : 'false'); });
        loader.f.src = tab; loader.f.group = ''; loader.f.unused = false;
        $('[data-mm-unused]', root).checked = false;
        $$('[data-mm-lib-only]', root).forEach(function (n) { n.hidden = tab !== 'library'; });
        reload();
      });
    });

    function upload(files) {
      uploadFiles(files, { progress: function (n, total, name) { status.textContent = 'Uploading ' + n + ' of ' + total + ': ' + name; } }).then(function (items) {
        status.textContent = '';
        if (items.length) { toast(items.length === 1 ? 'File added.' : items.length + ' files added.', 'success', 2400); reload(); }
      });
    }
    var fi = $('[data-mm-file]', root);
    on(fi, 'change', function () { upload(fi.files); fi.value = ''; });
    dropZone(root, upload);

    on($('[data-mm-cleanup]', root), 'click', function () {
      CH.confirm({ title: 'Delete unused files?', message: 'Every picture or video that no page, setting or slide uses is deleted for good. Built-in illustrations are not touched.', confirm: 'Delete unused files' }).then(function (ok) {
        if (!ok) return;
        CH.api('/api/admin/media/cleanup', { data: {} }).then(function (j) { if (j.ok) { toast(j.message, 'success'); reload(); } else toast(j.error || 'Could not clean up.', 'error'); });
      });
    });

    /* detail dialog */
    var cur = null;
    on(grid, 'click', function (e) {
      var tl = e.target.closest('.mp-tile'); if (!tl) return;
      var it = loader.byPath(tl.getAttribute('data-path')); if (!it) return;
      open(it);
    });
    function open(it) {
      cur = it;
      var pv = $('[data-md-preview]', detail);
      pv.innerHTML = it.kind === 'video' ? '<video src="' + esc(it.url) + '" controls playsinline preload="metadata"></video>' : '<img src="' + esc(it.url) + '" alt="">';
      pv.classList.toggle('is-alpha', /\.(png|svg)$/i.test(it.path));
      var title = $('[data-md-title]', detail); title.value = it.source === 'art' ? it.title : (it.title === 'Untitled' ? '' : it.title); title.disabled = !canEdit || it.source === 'art';
      $('[data-md-meta]', detail).innerHTML = [it.kind === 'video' ? 'Video' : (it.w && it.h ? it.w + ' × ' + it.h + ' px' : 'Vector'), size(it.size), it.created ? 'Added ' + it.created.slice(0, 10) : 'Built in'].map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('');
      var link = location.origin + it.url; $('[data-md-link]', detail).value = it.source === 'art' ? it.path : link;
      var uses = $('[data-md-uses]', detail);
      if (it.source === 'art') { uses.innerHTML = '<p class="text-sm text-ink-2">Built-in illustration. Choose it from any image field. It cannot be deleted.</p>'; }
      else if (it.usage && it.usage.length) { uses.innerHTML = '<p class="md-h">Used in</p><ul class="md-uses">' + it.usage.map(function (u) { return '<li><a href="' + esc(u.url) + '">' + esc(u.label) + '</a></li>'; }).join('') + '</ul>'; }
      else { uses.innerHTML = '<p class="text-sm text-ink-2">Not used anywhere yet.</p>'; }
      var del = $('[data-md-delete]', detail); del.hidden = !canEdit || it.source === 'art'; del.disabled = !!(it.usage && it.usage.length);
      del.title = del.disabled ? 'Replace it where it is used first.' : '';
      $('[data-md-save]', detail).hidden = !canEdit || it.source === 'art';
      CH.modal.open(detail);
    }
    on($('[data-md-save]', detail), 'click', function () {
      var fd = new FormData(); fd.append('id', cur.id); fd.append('title', $('[data-md-title]', detail).value);
      CH.api('/api/admin/media/update', { data: fd }).then(function (j) {
        if (j.ok) { toast('Saved', 'success', 1800); detail.close(); reload(); } else toast(j.error || 'Could not save.', 'error');
      });
    });
    on($('[data-md-delete]', detail), 'click', function () {
      CH.confirm({ title: 'Delete this file?', message: 'It is removed from the library and from the server. This cannot be undone.', confirm: 'Delete' }).then(function (ok) {
        if (!ok) return;
        var fd = new FormData(); fd.append('id', cur.id);
        CH.api('/api/admin/media/delete', { data: fd }).then(function (j) {
          if (j.ok) { toast('Deleted', 'success', 1800); detail.close(); reload(); } else toast(j.error || 'Could not delete.', 'error');
        });
      });
    });
    on($('[data-md-copy]', detail), 'click', function () {
      var v = $('[data-md-link]', detail).value;
      (navigator.clipboard ? navigator.clipboard.writeText(v) : Promise.reject()).then(function () { toast('Link copied', 'success', 1600); }, function () { $('[data-md-link]', detail).select(); toast('Press Ctrl+C to copy.', 'info'); });
    });
    reload();
  }

  function init() { var r = $('[data-media-manager]'); if (r) manager(r); }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init); else init();
})();
