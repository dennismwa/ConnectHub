/* Admin > Payment methods: copy buttons for the addresses to give each provider. */
(function () {
  'use strict';
  if (window.CH && window.CH.copy) { return; } // app.js copies (with a toast) on every page now
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-copy]') : null;
    if (!b) { return; }
    var text = b.getAttribute('data-copy') || '';
    var done = function () {
      var old = b.textContent; b.textContent = 'Copied';
      setTimeout(function () { b.textContent = old; }, 1600);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () { fallback(text, done); });
    } else { fallback(text, done); }
  });
  function fallback(text, done) {
    var t = document.createElement('textarea'); t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
    document.body.appendChild(t); t.select();
    try { document.execCommand('copy'); done(); } catch (x) { /* ignore */ }
    document.body.removeChild(t);
  }
})();
