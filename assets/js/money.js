/* ConnectHub money pages: the payment page (M-Pesa waiting, manual and test forms),
   boost countdown, gift preview and receipt printing. Vanilla JS on top of window.CH. */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH;
  if (!CH) { return; }
  var $ = CH.$, $$ = CH.$$;

  /* ------------------------------------------------------------------ payment page */
  var pay = $('[data-pay]');
  if (pay) { initPay(pay); }

  function initPay(root) {
    var api = root.getAttribute('data-api');
    var state = root.getAttribute('data-state');
    var total = parseInt(root.getAttribute('data-wait') || '90', 10) || 90;
    var stk = $('[data-stk]', root), methods = $('[data-methods]', root);
    var titleEl = $('[data-pay-title]', root);
    var endAt = 0, tickTimer = null, pollTimer = null, polling = false, startedAt = 0, giveUpAt = 0, finished = false;
    var bar = stk && $('[data-stk-bar]', stk), count = stk && $('[data-stk-count]', stk), statusEl = stk && $('[data-stk-status]', stk), late = stk && $('[data-stk-late]', stk);
    var resendBtn = stk && $('[data-stk-act="resend"]', stk);

    function go(url) { location.href = /^https?:/.test(url) ? url : CH.url(url); }
    function fmt(s) { s = Math.max(0, s); var m = Math.floor(s / 60), r = s % 60; return m + ':' + (r < 10 ? '0' : '') + r; }

    /* ---- waiting for the phone prompt ---- */
    function startWaiting(left) {
      if (!stk) { return; }
      stk.hidden = false;
      if (methods) { methods.hidden = true; }
      if (titleEl) { titleEl.textContent = 'Approve on your phone'; }
      startedAt = Date.now() - (total - left) * 1000;
      endAt = Date.now() + left * 1000;
      giveUpAt = Date.now() + 15 * 60 * 1000;
      if (late) { late.hidden = left > 0; }
      stk.classList.remove('is-late');
      clearInterval(tickTimer);
      tickTimer = setInterval(tick, 1000);
      tick();
      schedulePoll(2500);
    }
    function tick() {
      var left = Math.ceil((endAt - Date.now()) / 1000);
      var elapsed = Math.floor((Date.now() - startedAt) / 1000);
      if (bar) { bar.style.width = Math.max(0, Math.min(100, (left / total) * 100)) + '%'; }
      if (left > 0) {
        if (count) { count.textContent = fmt(left); }
        if (statusEl) { statusEl.textContent = 'Waiting for M-Pesa'; }
      } else {
        if (count) { count.textContent = ''; }
        if (statusEl) { statusEl.textContent = 'Still waiting for M-Pesa'; }
        if (late) { late.hidden = false; }
        stk.classList.add('is-late');
      }
      if (resendBtn) { resendBtn.hidden = elapsed < 20; }
    }
    function schedulePoll(ms) {
      clearTimeout(pollTimer);
      if (finished) { return; }
      pollTimer = setTimeout(check, ms);
    }
    function check() {
      if (finished) { return Promise.resolve(); }
      if (doc.hidden && Date.now() < endAt) { schedulePoll(5000); return Promise.resolve(); }
      if (polling) { return Promise.resolve(); }
      polling = true;
      return CH.api(api + '/status', { quiet: true }).then(function (j) {
        polling = false;
        if (!j || !j.ok) { schedulePoll(8000); return; }
        handle(j);
      });
    }
    function handle(j) {
      if (j.state === 'paid') {
        finished = true;
        clearInterval(tickTimer);
        paidView(j);
        setTimeout(function () { go(j.redirect || '/billing'); }, 1400);
        return;
      }
      if (j.state === 'failed' || j.state === 'cancelled' || j.state === 'refunded' || (j.state === 'review' && state !== 'review')) {
        finished = true;
        location.reload();
        return;
      }
      if (j.state === 'stk' && j.left > 0) { endAt = Date.now() + j.left * 1000; }
      if (state === 'review') { schedulePoll(10000); return; }
      if (Date.now() > giveUpAt) { return; }
      schedulePoll(Date.now() < endAt ? 3000 : 8000);
    }
    function paidView(j) {
      if (titleEl) { titleEl.textContent = 'Payment received'; }
      var host = stk && !stk.hidden ? stk : (methods || $('[data-state-panel]', root));
      if (!host) { return; }
      host.hidden = false;
      host.innerHTML = '<div class="pay-state"><span class="pay-state-ico is-ok"><svg class="icon w-8 h-8" aria-hidden="true"><use href="' + CH.url('/assets/icons/sprite.svg') + '#i-circle-check"></use></svg></span>' +
        '<h2 class="pay-state-title">Thank you, it is paid</h2><p class="pay-state-text">Taking you on…</p></div>';
      host.className = 'card card-pad';
      var badge = $('[data-pay-badge]', root); if (badge) { badge.textContent = 'Paid'; badge.className = 'badge badge-success'; }
    }

    /* ---- actions from the buttons under the waiting card ---- */
    if (stk) {
      stk.addEventListener('click', function (e) {
        var b = e.target.closest('[data-stk-act]'); if (!b) { return; }
        var a = b.getAttribute('data-stk-act');
        if (a === 'check') { b.disabled = true; check().then(function () { setTimeout(function () { b.disabled = false; }, 1500); }); }
        if (a === 'other') { stk.hidden = true; if (methods) { methods.hidden = false; } if (titleEl) { titleEl.textContent = 'Complete your payment'; } clearInterval(tickTimer); finished = false; state = 'open'; root.setAttribute('data-state', 'open'); }
        if (a === 'resend') { sendAction('mpesa', { phone: stk.getAttribute('data-phone') || '' }, b); }
      });
    }

    /* ---- submitting the method forms without a page load ---- */
    function sendAction(action, data, btn) {
      var fd = new FormData();
      Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
      if (btn) { btn.disabled = true; btn.classList.add('is-loading'); }
      return CH.api(api + '/' + action, { data: fd }).then(function (j) {
        if (btn) { btn.disabled = false; btn.classList.remove('is-loading'); }
        if (!j.ok) {
          CH.toast(j.error || 'That did not work. Please try again.', 'error');
          if (j.state === 'paid') { setTimeout(function () { location.reload(); }, 800); }
          return j;
        }
        if (action === 'mpesa') {
          total = j.wait || total;
          var t = $('[data-stk-text]', stk);
          if (t && data.phone) { t.innerHTML = 'We sent an M-Pesa request to <strong></strong> for <strong></strong>. Enter your M-Pesa PIN to approve it.'; var st = $$('strong', t); st[0].textContent = data.phone; st[1].textContent = root.getAttribute('data-amount'); }
          if (data.phone) { stk.setAttribute('data-phone', data.phone); }
          finished = false; state = 'stk'; root.setAttribute('data-state', 'stk');
          startWaiting(j.wait || total);
          CH.toast(j.message || 'Check your phone and enter your M-Pesa PIN.', 'success');
          return j;
        }
        if (j.redirect) { go(j.redirect); return j; }
        location.reload();
        return j;
      });
    }
    $$('form[data-pay-form]', root).forEach(function (f) {
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        var kind = f.getAttribute('data-pay-form');
        var fd = new FormData(f), data = {};
        fd.forEach(function (v, k) { if (k !== '_csrf') { data[k] = v; } });
        if (kind === 'mpesa') {
          var digits = (data.phone || '').replace(/\D/g, '');
          if (digits.length < 9 || digits.length > 12) { CH.toast('Enter a Safaricom number such as 0712 345 678.', 'error'); var p = $('input[name=phone]', f); p && p.focus(); return; }
        }
        sendAction(data.action || kind, data, $('button[type=submit]', f));
      });
    });
    var code = $('.pay-code', root);
    if (code) { code.addEventListener('input', function () { var s = code.selectionStart; code.value = code.value.toUpperCase().replace(/[^A-Z0-9 ]/g, ''); try { code.setSelectionRange(s, s); } catch (x) { /* ignore */ } }); }

    /* ---- starting state ---- */
    if (state === 'stk' && stk) {
      var left = parseInt(root.getAttribute('data-left') || '0', 10) || 0;
      startWaiting(left);
    } else if (state === 'review') {
      schedulePoll(10000);
    }
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden && (state === 'stk' || state === 'review') && !finished) { check(); } });
  }

  /* ------------------------------------------------------------------ boost countdown */
  $$('[data-countdown]').forEach(function (el) {
    var left = parseInt(el.getAttribute('data-left') || '0', 10) || 0;
    var end = Date.now() + left * 1000;
    function paint() {
      var s = Math.max(0, Math.round((end - Date.now()) / 1000));
      var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), r = s % 60;
      el.textContent = (h > 0 ? h + ':' + (m < 10 ? '0' : '') : '') + m + ':' + (r < 10 ? '0' : '') + r;
      if (s <= 0) { clearInterval(t); setTimeout(function () { location.reload(); }, 1200); }
    }
    var t = setInterval(paint, 1000);
    paint();
  });

  /* ------------------------------------------------------------------ gift preview */
  var gf = $('[data-gift-form]');
  if (gf) {
    var emoji = $('[data-preview-emoji]', gf), name = $('[data-preview-name]', gf), price = $('[data-preview-price]', gf), noteOut = $('[data-preview-note]', gf);
    var note = $('textarea[name=note]', gf), submit = $('[data-gift-submit]', gf);
    var update = function () {
      var r = $('input[name=gift]:checked', gf);
      if (r) { emoji.textContent = r.getAttribute('data-emoji'); name.textContent = r.getAttribute('data-name'); price.textContent = r.getAttribute('data-price'); }
      submit.disabled = !r;
      var n = note.value.trim();
      noteOut.hidden = n === '';
      noteOut.textContent = n === '' ? '' : '“' + n + '”';
    };
    gf.addEventListener('change', update);
    note.addEventListener('input', update);
    update();
  }

  /* ------------------------------------------------------------------ receipts, confetti */
  var pr = $('[data-print]');
  if (pr) { pr.addEventListener('click', function () { window.print(); }); }
  var cf = $('[data-confetti]');
  if (cf && CH.confetti) { setTimeout(function () { CH.confetti(); }, 350); }
})();

/* Manual M-Pesa: the "Open the M-PESA menu" button (dials *334#) only works on Android phones; iPhones get a hint. */
(function () {
  var box = document.querySelector('[data-mpx]');
  if (!box) return;
  var ua = navigator.userAgent || '';
  var android = /Android/i.test(ua);
  var ios = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var btn = box.querySelector('[data-mpx-ussd]');
  var hint = box.querySelector('[data-mpx-ios]');
  if (btn && android) btn.hidden = false;
  if (hint && ios) hint.hidden = false;
  /* The confirmation code: capitals, no spaces, as M-PESA prints it */
  var code = box.querySelector('.mpx-code');
  if (code) code.addEventListener('input', function () { var v = code.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); if (v !== code.value) code.value = v; });
})();
