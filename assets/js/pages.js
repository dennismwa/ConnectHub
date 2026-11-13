/* Small behaviours for public pages: FAQ search, filter auto-submit, copy link. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

  /* FAQ search */
  var box = $('[data-faq-search]');
  if (box) {
    var items = $$('[data-faq-item]');
    var groups = $$('[data-faq-group]');
    var none = $('[data-faq-none]');
    box.addEventListener('input', function () {
      var q = box.value.trim().toLowerCase();
      var words = q.split(/\s+/).filter(Boolean);
      var shown = 0;
      items.forEach(function (it) {
        var t = it.getAttribute('data-text') || '';
        var ok = words.every(function (w) { return t.indexOf(w) > -1; });
        it.hidden = !ok;
        if (ok) shown++;
        if (q && ok) it.open = true; else if (!q) it.open = false;
      });
      groups.forEach(function (g) { g.hidden = !$$('[data-faq-item]:not([hidden])', g).length; });
      if (none) none.hidden = shown > 0;
    });
  }

  /* filters that submit on change */
  $$('form[data-autosubmit]').forEach(function (f) {
    $$('select, input[type=checkbox]', f).forEach(function (el) { el.addEventListener('change', function () { f.submit(); }); });
  });

  /* copy link */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var text = b.getAttribute('data-copy');
    var done = function () { if (window.CH && CH.toast) CH.toast('Link copied', 'success'); };
    if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(done, function () { window.prompt('Copy this link', text); }); }
    else { window.prompt('Copy this link', text); }
  });
})();
