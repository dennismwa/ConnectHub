/* Admin > Names and Discover: live preview of the chosen name and web addresses. */
(function () {
  var one = document.querySelector('[data-hn-one]'), many = document.querySelector('[data-hn-many]');
  if (!one || !many) return;
  var sp = document.querySelector('[data-hn-sp]'), ss = document.querySelector('[data-hn-ss]'), auto = document.querySelector('[data-hn-auto]');
  function plural(s) { return /(s|x|z|ch|sh)$/i.test(s) ? s + 'es' : (/[^aeiou]y$/i.test(s) ? s.slice(0, -1) + 'ies' : s + 's'); }
  function slug(s) { return s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); }
  function set(sel, t) { document.querySelectorAll(sel).forEach(function (n) { n.textContent = t; }); }
  function show() {
    var o = one.value.trim() || 'Host', m = many.value.trim() || plural(o);
    set('[data-hn-p]', m); set('[data-hn-p2]', m); set('[data-hn-s]', o); set('[data-hn-sl]', o.toLowerCase());
    set('[data-hn-a]', /^[aeiou]/i.test(o) ? 'an' : 'a');
    if (auto && auto.checked) { sp.value = slug(m); ss.value = slug(o); }
    set('[data-hn-spv]', sp.value || 'hosts'); set('[data-hn-ssv]', ss.value || 'host');
  }
  [one, many, sp, ss].forEach(function (i) { i.addEventListener('input', show); });
  if (auto) auto.addEventListener('change', show);
  document.querySelectorAll('[data-hn-preset]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.getAttribute('data-hn-preset').replace(/\u200D/g, '');
      one.value = v; many.value = b.hasAttribute('data-hn-reset') ? 'Hosts' : plural(v);
      if (b.hasAttribute('data-hn-reset')) { sp.value = 'hosts'; ss.value = 'host'; if (auto) auto.checked = false; } else if (auto) { auto.checked = true; }
      show();
    });
  });
  show();
})();
