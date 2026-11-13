/* Elite pages: filters that apply on change, the request dialog (counter, prompts), the unlock option cards, criteria rows. */
(function () {
  'use strict';
  var CH = window.CH;
  if (!CH) return;
  var $ = CH.$, $$ = CH.$$;

  /* Filters: apply as soon as a choice changes */
  $$('[data-autosubmit]').forEach(function (el) { el.addEventListener('change', function () { if (el.form) el.form.submit(); }); });

  /* Request dialog: character counter and tap-to-add prompts */
  $$('[data-elx-msg]').forEach(function (ta) {
    var out = $('[data-elx-left]', ta.closest('form'));
    var max = parseInt(ta.getAttribute('maxlength') || '500', 10);
    function upd() { if (out) out.textContent = (max - ta.value.length) + ' left'; }
    ta.addEventListener('input', upd); upd();
  });
  CH.delegate('click', '[data-elx-prompt]', function (e, el) {
    e.preventDefault();
    var ta = $('[data-elx-msg]', el.closest('form'));
    if (!ta) return;
    var t = el.getAttribute('data-elx-prompt');
    ta.value = (ta.value.trim() ? ta.value.trim() + ' ' : '') + t;
    ta.dispatchEvent(new Event('input'));
    ta.focus();
  });

  /* Unlock options: the chosen card shows as selected */
  $$('[data-elx-options]').forEach(function (box) {
    function sync() { $$('.elx-opt', box).forEach(function (o) { var i = $('input', o); o.classList.toggle('is-on', !!(i && i.checked)); }); }
    box.addEventListener('change', sync); sync();
  });

  /* Criteria rows: the importance control greys out the row when Off */
  $$('[data-elx-crit]').forEach(function (row) {
    function sync() { var v = ($('input[type=radio]:checked', row) || {}).value || 'off'; row.classList.toggle('is-off', v === 'off'); row.classList.toggle('is-must', v === 'must'); }
    row.addEventListener('change', sync); sync();
  });

  /* Minimum match slider shows its value */
  $$('[data-elx-range]').forEach(function (r) {
    var out = $('[data-elx-range-out]', r.closest('.field') || document);
    function upd() { if (out) out.textContent = r.value + '%'; }
    r.addEventListener('input', upd); upd();
  });
})();

/* Page extras: the filter panel, the heart, the sticky bar on a profile */
(function () {
  'use strict';
  var CH = window.CH;
  if (!CH) return;
  var $ = CH.$, $$ = CH.$$;

  /* "More filters" opens and closes the panel */
  CH.delegate('click', '[data-elx-toggle]', function (e, btn) {
    e.preventDefault();
    var panel = document.getElementById(btn.getAttribute('data-elx-toggle'));
    if (!panel) return;
    var open = panel.hasAttribute('hidden');
    if (open) panel.removeAttribute('hidden'); else panel.setAttribute('hidden', '');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  /* The heart: save or remove without leaving the page. Without scripts the form posts and comes back. */
  CH.delegate('submit', 'form[data-elx-save]', function (e, form) {
    e.preventDefault();
    var btn = $('button', form), to = $('input[name=to]', form);
    if (!btn || form.classList.contains('is-busy')) return;
    form.classList.add('is-busy');
    CH.api(form.getAttribute('action').replace(CH.base, ''), { method: 'POST', data: new FormData(form) }).then(function (j) {
      form.classList.remove('is-busy');
      if (!j || !j.ok) { CH.toast((j && (j.error || j.message)) || 'That did not work. Please try again.', 'error'); return; }
      var on = !!j.saved;
      btn.classList.toggle('is-on', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (to) to.value = on ? 'remove' : 'save';
      var lab = $('span', btn); if (lab) lab.textContent = on ? 'Saved' : 'Save';
      btn.classList.remove('is-pop'); void btn.offsetWidth; btn.classList.add('is-pop');
      CH.toast(j.message || (on ? 'Saved.' : 'Removed.'), 'success', 2200);
      if (navigator.vibrate) { try { navigator.vibrate(10); } catch (x) {} }
      var page = $('[data-elx-saved]');
      var card = form.closest('.elx-card');
      if (page && card && !on) {
        card.style.transition = 'opacity .25s, transform .25s'; card.style.opacity = '0'; card.style.transform = 'scale(.96)';
        setTimeout(function () { card.remove(); if (!$('.elx-grid .elx-card', page)) location.reload(); }, 260);
      }
    });
  });

  /* Profile: the sticky "Send a request" bar shows while the actions are out of view */
  $$('[data-elx-sticky]').forEach(function (bar) {
    var target = document.getElementById(bar.getAttribute('data-elx-sticky'));
    if (!target || !('IntersectionObserver' in window)) return;
    var io = new IntersectionObserver(function (en) {
      var seen = en[0].isIntersecting;
      if (seen) bar.setAttribute('hidden', ''); else bar.removeAttribute('hidden');
    }, { rootMargin: '0px 0px -12% 0px' });
    io.observe(target);
  });
})();
