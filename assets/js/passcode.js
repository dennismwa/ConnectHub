/* The passcode screen: a round keypad, four dots, and a ring that turns green when the code is right.
   Works with taps and with a physical keyboard. Without JavaScript the password form below still opens the app. */
(function () {
  'use strict';
  var root = document.querySelector('[data-passcode]');
  if (!root) return;
  var CH = window.CH || {};
  var dots = [].slice.call(root.querySelectorAll('.pc-dot'));
  var pad = root.querySelector('[data-pc-pad]');
  var msg = root.querySelector('[data-pc-msg]');
  var passForm = root.querySelector('[data-pc-pass-form]');
  var len = parseInt(root.getAttribute('data-len'), 10) || 4;
  var url = root.getAttribute('data-url');
  var next = root.getAttribute('data-next');
  var code = '';
  var busy = false;

  function paint() {
    dots.forEach(function (d, i) { d.classList.toggle('is-on', i < code.length); });
  }
  function say(text, kind) {
    if (!msg) return;
    msg.textContent = text;
    msg.className = 'pc-sub' + (kind ? ' is-' + kind : '');
  }
  function reset(delay) {
    setTimeout(function () {
      code = '';
      paint();
      root.classList.remove('is-wrong');
      busy = false;
    }, delay || 0);
  }

  function submit() {
    busy = true;
    root.classList.add('is-busy');
    CH.api(url, { data: { code: code } }).then(function (j) {
      root.classList.remove('is-busy');
      if (j && j.ok) {
        root.classList.add('is-right');
        say('Welcome back', 'ok');
        setTimeout(function () { location.href = (j.redirect || next); }, CH.reduced && CH.reduced() ? 0 : 420);
        return;
      }
      root.classList.add('is-wrong');
      var left = j && j.left;
      say((j && j.error) || 'That passcode is not right.' , 'bad');
      if (j && j.redirect && left === undefined) { setTimeout(function () { location.href = j.redirect; }, 1200); return; }
      if (typeof left === 'number' && left <= 2) {
        say(((j && j.error) || 'That passcode is not right.') + ' ' + left + (left === 1 ? ' try left.' : ' tries left.'), 'bad');
      }
      if (navigator.vibrate) { try { navigator.vibrate(60); } catch (e) {} }
      reset(520);
    }).catch(function () {
      root.classList.remove('is-busy');
      say('Could not reach the site. Try again.', 'bad');
      reset(400);
    });
  }

  function push(d) {
    if (busy || code.length >= len) return;
    code += d;
    paint();
    if (code.length === len) submit();
  }
  function back() {
    if (busy || !code.length) return;
    code = code.slice(0, -1);
    paint();
  }

  if (pad) {
    pad.addEventListener('click', function (e) {
      var key = e.target.closest('[data-pc-key]');
      if (key) { push(key.getAttribute('data-pc-key')); return; }
      if (e.target.closest('[data-pc-back]')) back();
    });
  }
  document.addEventListener('keydown', function (e) {
    if (passForm && !passForm.hidden) return;
    if (e.key >= '0' && e.key <= '9') { e.preventDefault(); push(e.key); }
    else if (e.key === 'Backspace') { e.preventDefault(); back(); }
  });

  [].forEach.call(root.querySelectorAll('[data-pc-pass-toggle]'), function (b) {
    b.addEventListener('click', function () {
      if (!passForm) return;
      passForm.hidden = !passForm.hidden;
      if (!passForm.hidden) {
        var inp = passForm.querySelector('input[type=password]');
        if (inp) inp.focus();
        say('Enter your account password');
      } else {
        say('Enter your passcode to carry on');
      }
    });
  });

  paint();
})();
