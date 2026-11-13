/* International connections: fills the phone country code from the chosen country, and dims the number fields when no number is wanted. */
(function () {
  'use strict';
  var country = document.querySelector('[data-ix-country]');
  var dial = document.querySelector('[data-ix-dial]');
  if (country && dial) {
    var touched = dial.value !== '';
    dial.addEventListener('input', function () { touched = true; });
    country.addEventListener('change', function () {
      var opt = country.options[country.selectedIndex];
      if (opt && opt.getAttribute('data-dial') && !touched) { dial.value = opt.getAttribute('data-dial'); }
    });
    if (!dial.value && country.value) {
      var o = country.options[country.selectedIndex];
      if (o && o.getAttribute('data-dial')) { dial.value = o.getAttribute('data-dial'); }
    }
  }
  var number = document.querySelector('[data-ix-number]');
  var radios = document.querySelectorAll('input[name="contact_type"]');
  function sync() {
    if (!number || !radios.length) { return; }
    var none = true;
    radios.forEach(function (r) { if (r.checked && r.value !== '') { none = false; } });
    number.classList.toggle('is-off', none);
    number.querySelectorAll('input').forEach(function (i) { i.disabled = none; });
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();

/* Profile form: filter the country list, and keep the preview and checklist in step with what is typed. */
(function () {
  'use strict';
  var find = document.querySelector('[data-ix-cfilter]');
  var list = document.querySelector('[data-ix-clist]');
  if (find && list) {
    find.addEventListener('input', function () {
      var q = find.value.trim().toLowerCase();
      list.querySelectorAll('label').forEach(function (l) { l.hidden = q !== '' && l.textContent.toLowerCase().indexOf(q) === -1; });
    });
    var clear = document.querySelector('[data-ix-cclear]');
    if (clear) { clear.addEventListener('click', function () { list.querySelectorAll('input').forEach(function (i) { i.checked = false; }); }); }
  }
  var form = document.querySelector('.ix-form');
  if (!form) { return; }
  var $ = function (s) { return document.querySelector(s); };
  function ok(key, on) { var li = document.querySelector('[data-ix-check="' + key + '"]'); if (li) { li.classList.toggle('is-ok', !!on); } }
  function sync() {
    var c = form.querySelector('[name="country"]'), head = form.querySelector('[name="headline"]'), city = form.querySelector('[name="city"]');
    var opt = c && c.options[c.selectedIndex];
    var langs = Array.prototype.filter.call(form.querySelectorAll('[name="languages[]"]'), function (i) { return i.checked; });
    var purp = form.querySelectorAll('[name="purposes[]"]:checked').length;
    ok('country', c && c.value); ok('languages', langs.length); ok('purposes', purp); ok('headline', head && head.value.trim().length >= 8);
    var f = $('[data-ix-pflag]'), w = $('[data-ix-pwhere]'), ln = $('[data-ix-pline]'), lg = $('[data-ix-plangs]');
    if (opt && c.value) {
      var t = opt.textContent.trim(), sp = t.indexOf(' ');
      if (f) { f.textContent = t.slice(0, sp); }
      if (w) { w.textContent = (city && city.value.trim() ? city.value.trim() + ', ' : '') + t.slice(sp + 1); }
    } else { if (f) { f.textContent = '🌍'; } if (w) { w.textContent = 'Pick your country'; } }
    if (ln) { ln.textContent = head && head.value.trim() ? head.value.trim() : 'Your one-line summary shows here.'; }
    if (lg) {
      lg.textContent = '';
      langs.slice(0, 4).forEach(function (i) { var s = document.createElement('span'); s.textContent = i.parentNode.querySelector('.chip').textContent; lg.appendChild(s); });
    }
  }
  form.addEventListener('input', sync); form.addEventListener('change', sync); sync();
})();

/* Admin > International > Setup: show only the fields that fit the chosen mode, and describe the result in one sentence. */
(function () {
  'use strict';
  var form = document.querySelector('[data-ix-setup]');
  if (!form) { return; }
  var cur = form.getAttribute('data-currency') || '';
  function val(n) { var e = form.elements[n]; return e ? e.value : ''; }
  function on(n) { var e = form.elements[n]; return !!(e && e.checked); }
  function mode() { var r = form.querySelector('[name="intl_mode"]:checked'); return r ? r.value : 'free'; }
  function price(fee, days) {
    var f = parseFloat(String(fee).replace(/,/g, '')) || 0, d = parseInt(days, 10) || 0;
    return cur + ' ' + f.toLocaleString() + (d > 0 ? ' for ' + d + (d === 1 ? ' day' : ' days') : ', one time');
  }
  function sync() {
    var m = mode();
    form.querySelectorAll('[data-ix-when]').forEach(function (el) {
      el.hidden = el.getAttribute('data-ix-when').split(',').indexOf(m) === -1;
    });
    var out = form.querySelector('[data-ix-summary] span');
    if (!out) { return; }
    if (!on('intl_enabled')) { out.textContent = 'International connections are switched off.'; return; }
    var join = m === 'join' || m === 'both', acc = m === 'access' || m === 'both', p = [];
    p.push(join ? 'Members pay ' + price(val('intl_join_fee'), val('intl_join_days')) + ' to be listed' : 'Listing is free');
    p.push(acc ? 'people pay ' + price(val('intl_access_fee'), val('intl_access_days')) + ' to browse' + (on('intl_msg_included') ? ' and message' : '') : 'browsing and messaging are free');
    var f = val('intl_premium_free');
    if (m !== 'free' && f !== 'none') { p.push((f === 'vip' ? 'VIP' : 'Premium and VIP') + ' members pay nothing'); }
    var t = p.join(', ');
    out.textContent = t.charAt(0).toUpperCase() + t.slice(1) + '.';
  }
  form.addEventListener('input', sync); form.addEventListener('change', sync); sync();
})();
