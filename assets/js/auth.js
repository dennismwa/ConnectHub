/* Sign-in / register card: AJAX submit with inline errors, turn animation hooks, brand-panel story rotation, QR codes. */
(function () {
  'use strict';
  var doc = document;
  var $ = function (s, r) { return (r || doc).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };
  var reduced = function () { return window.CH && CH.reduced && CH.reduced(); };

  /* "turning" state: drives the sheen + lift while the card rotates */
  function watchTurns() {
    $$('[data-flip-root]').forEach(function (scene) {
      if (scene.__turnWatch) return; scene.__turnWatch = true;
      var timer;
      new MutationObserver(function (m) {
        if (!m.some(function (x) { return x.attributeName === 'data-side'; })) return;
        if (reduced()) return;
        scene.classList.add('is-turning'); clearTimeout(timer);
        timer = setTimeout(function () { scene.classList.remove('is-turning'); }, 1000);
      }).observe(scene, { attributes: true, attributeFilter: ['data-side'] });
    });
  }

  function clearErrors(form) {
    $$('.error-text[data-js]', form).forEach(function (n) { n.remove(); });
    $$('.has-error', form).forEach(function (n) { n.classList.remove('has-error'); });
    var box = $('[data-form-alert]', form.closest('[data-face]') || doc); if (box) { box.hidden = true; box.textContent = ''; }
  }
  function showAlert(form, msg, kind) {
    var face = form.closest('[data-face]') || doc; var box = $('[data-form-alert]', face); if (!box) { return; }
    box.textContent = msg; box.setAttribute('data-kind', kind || 'error'); box.hidden = false;
  }
  function showFieldErrors(form, errors) {
    var first = null;
    Object.keys(errors || {}).forEach(function (k) {
      var el = form.elements[k]; if (!el) return; if (el.length && !el.tagName) el = el[0];
      var holder = el.closest('.field') || el.closest('fieldset') || el.parentNode;
      if (el.classList) el.classList.add('has-error');
      var p = doc.createElement('p'); p.className = 'error-text'; p.setAttribute('data-js', '1'); p.textContent = errors[k];
      holder.appendChild(p); if (!first) first = el;
    });
    if (first && first.focus) first.focus({ preventScroll: false });
  }
  function shake(form) {
    var face = form.closest('.auth-face'); if (!face || reduced()) return;
    face.classList.remove('is-shake'); void face.offsetWidth; face.classList.add('is-shake');
    setTimeout(function () { face.classList.remove('is-shake'); }, 500);
  }

  function clientCheck(form) {
    var errors = {};
    $$('input[required], select[required]', form).forEach(function (el) {
      if (el.disabled || el.closest('[hidden]')) return;
      if (el.type === 'checkbox') { if (!el.checked) errors[el.name] = el.name === 'terms' ? 'Please accept the terms and privacy policy to continue.' : 'Please tick this box to continue.'; }
      else if (!String(el.value).trim()) { var l = $('label[for="' + el.id + '"]', form); errors[el.name] = 'Enter ' + (l ? l.textContent.toLowerCase().replace(/\s+/g, ' ').trim() : 'a value') + '.'; }
    });
    var radios = {};
    $$('input[type=radio][data-req]', form).forEach(function (r) { if (r.closest('[hidden]')) return; radios[r.name] = radios[r.name] || r.checked; });
    Object.keys(radios).forEach(function (n) { if (!radios[n]) errors[n] = n === 'gender' ? 'Choose how you describe yourself.' : 'Choose who you would like to meet.'; });
    return errors;
  }

  function bindForm(form) {
    if (form.__bound) return; form.__bound = true;
    var isRegister = form.getAttribute('action') && /register$/.test(form.getAttribute('action'));
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearErrors(form);
      var errs = clientCheck(form);
      if (Object.keys(errs).length) { showAlert(form, 'Please fix the highlighted fields.'); showFieldErrors(form, errs); shake(form); return; }
      var btn = $('button[type=submit]', form), label = btn && $('.btn-label', btn), orig = label && label.textContent;
      if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
      CH.api(form.getAttribute('action'), { method: 'POST', data: new FormData(form), quiet: true }).then(function (j) {
        if (j && j.ok) {
          if (btn) { btn.classList.remove('is-loading'); btn.classList.add('is-done'); }
          if (label) label.textContent = isRegister ? 'Welcome aboard' : 'Signed in';
          setTimeout(function () { location.href = j.redirect || CH.url('/'); }, reduced() ? 0 : 450);
          return;
        }
        if (btn) { btn.classList.remove('is-loading'); btn.disabled = false; }
        if (label && orig) label.textContent = orig;
        showAlert(form, (j && j.error) || 'Something went wrong. Please try again.');
        if (j && j.errors) showFieldErrors(form, j.errors);
        if (j && j._status === 419) showAlert(form, 'Your page expired. Reload it and try again.');
        shake(form);
      });
    });
    $$('input, select', form).forEach(function (el) {
      el.addEventListener('input', function () {
        el.classList.remove('has-error'); var h = el.closest('.field') || el.closest('fieldset'); if (h) $$('.error-text[data-js]', h).forEach(function (n) { n.remove(); });
      });
    });
  }

  /* host tab changes the submit label */
  doc.addEventListener('ch:type', function (e) {
    var form = e.target.closest('form'); if (!form) return;
    var l = $('[data-for-label]', form); if (l) l.textContent = e.detail === 'host' ? 'Start my ' + ((CH.config && CH.config.hostTerm ? CH.config.hostTerm[0] : 'host').toLowerCase()) + ' application' : 'Create my account';
  });

  function stories() {
    var box = $('[data-stories]'); if (!box) return;
    var items = $$('.auth-story', box), dots = $$('[data-story-dots] button'), i = 0, t;
    if (items.length < 2) return;
    function go(n) { items[i].classList.remove('is-active'); dots[i] && dots[i].classList.remove('is-active'); i = (n + items.length) % items.length; items[i].classList.add('is-active'); dots[i] && dots[i].classList.add('is-active'); }
    function start() { stop(); if (!reduced()) t = setInterval(function () { go(i + 1); }, 6500); }
    function stop() { clearInterval(t); }
    dots.forEach(function (d, n) { d.addEventListener('click', function () { go(n); start(); }); });
    box.addEventListener('mouseenter', stop); box.addEventListener('mouseleave', start); start();
  }

  function qr() {
    $$('[data-qr]').forEach(function (el) {
      if (typeof qrcode !== 'function') return;
      try { var q = qrcode(0, 'M'); q.addData(el.getAttribute('data-qr')); q.make(); el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 0, scalable: true }); } catch (e) {}
    });
  }

  function init() { $$('form[data-auth-form]').forEach(bindForm); watchTurns(); stories(); qr(); }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', init); else init();
  doc.addEventListener('ch:ready', init);
})();
