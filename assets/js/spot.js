/* The Advertise form: the sample card follows what is typed, the picture shows at once (with a warning when it is too small),
   and the bar at the bottom names the package that is chosen. Everything still works without this script. */
(function () {
  'use strict';
  var form = document.querySelector('[data-spot-form]');
  if (!form) return;
  var prev = document.querySelector('[data-spot-preview]');
  var MIN_W = 480, MIN_H = 270;

  function q(sel, root) { return (root || document).querySelector(sel); }
  function qa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  /* words: each input with data-spot-in writes into the matching place on the sample card */
  var fallback = { head: 'Your headline appears here', brand: 'Your business', text: 'One short line that tells people why to tap.', cta: 'Learn more' };
  function bind(el) {
    var key = el.getAttribute('data-spot-in');
    var target = prev && q('[data-spot-bind="' + key + '"]', prev);
    if (!target) return;
    var v = (el.value || '').replace(/\s+/g, ' ').trim();
    target.textContent = v || fallback[key] || '';
  }
  qa('[data-spot-in]', form).forEach(function (el) {
    el.addEventListener('input', function () { bind(el); });
    el.addEventListener('change', function () { bind(el); });
    if (el.value) bind(el);
  });

  /* character counters */
  qa('[data-spot-count]', form).forEach(function (c) {
    var input = document.getElementById(c.getAttribute('data-spot-count'));
    var max = parseInt(c.getAttribute('data-max'), 10) || 0;
    if (!input) return;
    function upd() { c.textContent = input.value.length + ' / ' + max; }
    input.addEventListener('input', upd); upd();
  });

  /* the picture */
  var file = q('input[type=file]', form);
  var warn = q('[data-spot-warn]', form);
  var nameEl = q('[data-spot-file]', form);
  var drop = q('[data-spot-drop]', form);
  var url = null;
  function showWarn(msg) { if (!warn) return; warn.textContent = msg || ''; warn.hidden = !msg; }
  function setPicture(src) {
    var pic = prev && q('.spot-pic', prev);
    if (!pic) return;
    var img = q('img', pic);
    var empty = q('.spot-pic-empty', pic);
    if (!src) { if (img) img.remove(); if (empty) empty.hidden = false; return; }
    if (!img) { img = document.createElement('img'); img.alt = ''; pic.insertBefore(img, pic.firstChild); }
    img.src = src;
    if (empty) empty.hidden = true;
  }
  if (file) {
    file.addEventListener('change', function () {
      var f = file.files && file.files[0];
      if (url) { URL.revokeObjectURL(url); url = null; }
      showWarn('');
      if (!f) { if (nameEl) nameEl.textContent = 'Choose a picture'; setPicture(''); return; }
      if (nameEl) nameEl.textContent = f.name;
      if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { showWarn('Use a JPG, PNG or WebP picture.'); setPicture(''); return; }
      if (f.size > 6 * 1048576) { showWarn('That picture is over 6 MB. Choose a smaller one.'); }
      url = URL.createObjectURL(f);
      var probe = new Image();
      probe.onload = function () {
        if (probe.naturalWidth < MIN_W || probe.naturalHeight < MIN_H) showWarn('This picture is small (' + probe.naturalWidth + ' x ' + probe.naturalHeight + ') and may look blurry. Use one at least ' + MIN_W + ' pixels wide.');
      };
      probe.src = url;
      setPicture(url);
    });
  }
  if (drop) {
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function () { drop.classList.remove('is-over'); }); });
  }

  /* the chosen package, named in the bar */
  var sum = q('[data-spot-sum]', form), sum2 = q('[data-spot-sum2]', form);
  function pkg() {
    var r = q('input[name=package]:checked', form);
    if (!r || !sum) return;
    sum.textContent = r.getAttribute('data-name') + ': ' + r.getAttribute('data-price');
    if (sum2) sum2.textContent = 'Runs for ' + r.getAttribute('data-days');
  }
  qa('input[name=package]', form).forEach(function (r) { r.addEventListener('change', pkg); });
  pkg();
})();
