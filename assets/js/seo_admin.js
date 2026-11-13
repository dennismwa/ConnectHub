/* Admin SEO: live search-result preview and character counters. */
(function () {
  'use strict';
  var doc = document;
  function $(s, r) { return (r || doc).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); }
  function trunc(s, n) { s = (s || '').replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1).replace(/\s+\S*$/, '') + '…' : s; }

  function bind(form) {
    var d = form.closest('dialog') || form, state = { path: form.getAttribute('data-serp-path') || '', defTitle: form.getAttribute('data-serp-deftitle') || '', defDesc: form.getAttribute('data-serp-defdesc') || '' };
    var title = $('[data-serp-in="title"]', form), desc = $('[data-serp-in="description"]', form), slug = $('[data-serp-in="slug"]', form);
    var elT = $('[data-serp-title]', form), elD = $('[data-serp-desc]', form), elU = $('[data-serp-url]', form);
    var site = form.getAttribute('data-site') || '';
    function paint() {
      var t = (title && title.value.trim()) || state.defTitle || '';
      var dd = (desc && desc.value.trim()) || state.defDesc || '';
      var path = slug ? '/blog/' + (slug.value.trim() || '…') : state.path;
      if (elT) { elT.textContent = trunc(t, 62) + (site ? ' · ' + site : ''); }
      if (elD) { elD.textContent = trunc(dd, 158); }
      if (elU) { elU.textContent = (location.host + path).replace(/\/$/, ''); }
      $$('[data-count-for]', form).forEach(function (c) {
        var f = $('[data-serp-in="' + c.getAttribute('data-count-for') + '"]', form); if (!f) return;
        var n = f.value.length; c.textContent = n;
        var lim = c.getAttribute('data-count-for') === 'title' ? [30, 60] : [120, 155];
        c.className = n === 0 ? '' : (n >= lim[0] && n <= lim[1] ? 'seo-ok' : 'seo-warn');
      });
    }
    $$('[data-serp-in]', form).forEach(function (i) { i.addEventListener('input', paint); });
    d.addEventListener('cm:open', function (e) {
      var r = e.detail && e.detail.row || {};
      state.path = r.path || ''; state.defTitle = r.def_title || ''; state.defDesc = r.def_desc || '';
      paint();
    });
    paint();
  }
  $$('form[data-serp]').forEach(bind);
})();
