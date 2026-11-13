/* ConnectHub date picker.
   Upgrades <input type="date"> and <input type="datetime-local"> into a text field you can type in (DD / MM / YYYY)
   plus a calendar with month and year pickers, so a date of birth takes a few taps instead of hundreds of clicks.
   Without JavaScript the browser's own date input is used. The form still posts the same ISO value (YYYY-MM-DD).
   Opt out with data-native on an input. DOB mode (autocomplete="bday" or data-dp="dob") opens on a typical birth year. */
(function () {
  'use strict';
  var doc = document;
  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var DOW = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
  var SPRITE = (window.CH && CH.url ? CH.url('/assets/icons/sprite.svg') : '/assets/icons/sprite.svg');
  var open = null; // the picker that is currently open

  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function parseIso(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); if (!m) return null;
    var d = new Date(+m[1], +m[2] - 1, +m[3]); return d.getMonth() === +m[2] - 1 ? d : null;
  }
  function day0(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
  function same(a, b) { return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
  function icon(name, cls) { return '<svg class="icon ' + (cls || 'w-4 h-4') + '" aria-hidden="true"><use href="' + SPRITE + '#i-' + name + '"></use></svg>'; }
  function el(tag, cls, html) { var e = doc.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function fmtDate(d) { return pad(d.getDate()) + ' / ' + pad(d.getMonth() + 1) + ' / ' + d.getFullYear(); }
  function ageOn(dob, today) { var a = today.getFullYear() - dob.getFullYear(); if (today.getMonth() < dob.getMonth() || (today.getMonth() === dob.getMonth() && today.getDate() < dob.getDate())) a--; return a; }

  function Picker(input) {
    var self = this, today = day0(new Date());
    self.src = input;
    self.withTime = input.type === 'datetime-local';
    self.dob = input.getAttribute('autocomplete') === 'bday' || input.getAttribute('data-dp') === 'dob';
    self.min = parseIso(input.getAttribute('min')); self.max = parseIso(input.getAttribute('max'));
    self.minAge = parseInt(input.getAttribute('data-min-age'), 10) || 0;
    if (self.dob && self.minAge && !self.max) { self.max = new Date(today.getFullYear() - self.minAge, today.getMonth(), today.getDate()); }
    var raw = input.value || '';
    self.value = parseIso(raw); self.hour = 9; self.minute = 0;
    var tm = /T(\d{2}):(\d{2})/.exec(raw); if (tm) { self.hour = +tm[1]; self.minute = +tm[2]; }
    self.today = today;

    // ---- build the field
    var wrap = el('div', 'dp');
    var text = el('input', 'input dp-input'); text.type = 'text'; text.setAttribute('inputmode', 'numeric'); text.autocomplete = 'off'; text.spellcheck = false;
    text.placeholder = self.withTime ? 'DD / MM / YYYY  HH:MM' : 'DD / MM / YYYY';
    text.id = input.id; if (input.required) text.required = true; if (input.disabled) text.disabled = true;
    ['aria-describedby', 'aria-label', 'aria-labelledby', 'data-autosubmit'].forEach(function (a) { if (input.hasAttribute(a) && a !== 'data-autosubmit') text.setAttribute(a, input.getAttribute(a)); });
    var btn = el('button', 'dp-btn', icon('calendar-days', 'w-5 h-5')); btn.type = 'button'; btn.setAttribute('aria-label', 'Open calendar'); btn.setAttribute('aria-haspopup', 'dialog'); btn.setAttribute('aria-expanded', 'false');
    if (input.disabled) btn.disabled = true;
    var hidden = el('input'); hidden.type = 'hidden'; hidden.name = input.name; hidden.value = raw; hidden.defaultValue = raw;
    if (input.hasAttribute('data-autosubmit')) hidden.setAttribute('data-autosubmit', '');
    if (input.hasAttribute('data-dp-keep')) hidden.setAttribute('data-dp-keep', '');
    ['data-field', 'form'].forEach(function (a) { if (input.hasAttribute(a)) hidden.setAttribute(a, input.getAttribute(a)); });
    var note = el('p', 'dp-note'); note.setAttribute('aria-live', 'polite'); note.hidden = true;
    wrap.appendChild(text); wrap.appendChild(btn); wrap.appendChild(hidden); wrap.appendChild(note);
    input.parentNode.replaceChild(wrap, input);
    self.wrap = wrap; self.text = text; self.btn = btn; self.hidden = hidden; self.note = note;
    // the original input's classes (e.g. input-sm) carry over to the visible one
    input.className.split(/\s+/).forEach(function (c) { if (c && c !== 'input') text.classList.add(c); });
    if (self.value) text.value = self.render();

    // ---- typing
    text.addEventListener('input', function (e) {
      var digits = text.value.replace(/\D/g, '').slice(0, self.withTime ? 12 : 8), out = '';
      if (e.inputType && e.inputType.indexOf('delete') === 0 && /[\s\/]$/.test(text.value)) { digits = digits.slice(0, -1); }
      for (var i = 0; i < digits.length; i++) {
        if (i === 2 || i === 4) out += ' / ';
        if (i === 8) out += '  ';
        if (i === 10) out += ':';
        out += digits[i];
      }
      text.value = out;
      self.fromText(digits);
    });
    text.addEventListener('keydown', function (e) { if (e.key === 'ArrowDown' && (e.altKey || !text.value)) { e.preventDefault(); self.show(); } });
    text.addEventListener('blur', function () { if (text.value && !self.value && text.value.replace(/\D/g, '').length < 8) { self.problem('Enter the date as day, month, year.'); } });
    btn.addEventListener('click', function () { if (open === self) self.hide(true); else self.show(); });
    var form = text.form;
    if (form) form.addEventListener('reset', function () { setTimeout(function () { self.setFromIso(hidden.value, true); }, 0); });
    hidden.addEventListener('change', function () { self.setFromIso(hidden.value, true); });
    self.validate();
  }

  Picker.prototype.render = function () {
    var v = this.value; if (!v) return '';
    return fmtDate(v) + (this.withTime ? '  ' + pad(this.hour) + ':' + pad(this.minute) : '');
  };
  Picker.prototype.outOfRange = function (d) { return (this.min && d < this.min) || (this.max && d > this.max); };
  Picker.prototype.problem = function (msg) {
    this.text.setCustomValidity(msg || ''); this.text.setAttribute('aria-invalid', msg ? 'true' : 'false');
    this.wrap.classList.toggle('has-problem', !!msg);
    this.note.hidden = !msg && !this.ageText; this.note.classList.toggle('is-bad', !!msg);
    this.note.textContent = msg || this.ageText || '';
  };
  Picker.prototype.fromText = function (digits) {
    var need = this.withTime ? 12 : 8, self = this;
    if (digits.length < 8) { self.value = null; self.commit(true); self.problem(''); return; }
    var d = +digits.slice(0, 2), m = +digits.slice(2, 4), y = +digits.slice(4, 8);
    var dt = new Date(y, m - 1, d);
    if (y < 1000 || dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d) { self.value = null; self.commit(true); self.problem('That date does not exist. Check the day and month.'); return; }
    if (self.withTime && digits.length >= 12) { self.hour = Math.min(23, +digits.slice(8, 10)); self.minute = Math.min(59, +digits.slice(10, 12)); }
    self.value = dt; self.commit(true);
  };
  Picker.prototype.setFromIso = function (s, quiet) {
    var d = parseIso(s); this.value = d;
    var tm = /T(\d{2}):(\d{2})/.exec(s || ''); if (tm) { this.hour = +tm[1]; this.minute = +tm[2]; }
    this.text.value = d ? this.render() : ''; this.validate();
  };
  /** Write the chosen date to the hidden input (the one that is posted) and tell listeners. */
  Picker.prototype.commit = function (typing) {
    var v = this.value, out = v ? iso(v) + (this.withTime ? 'T' + pad(this.hour) + ':' + pad(this.minute) : '') : '';
    var changed = this.hidden.value !== out;
    this.hidden.value = out;
    if (!typing) this.text.value = this.render();
    this.validate();
    if (changed) { this.hidden.dispatchEvent(new Event('input', { bubbles: true })); this.hidden.dispatchEvent(new Event('change', { bubbles: true })); }
  };
  Picker.prototype.validate = function () {
    var v = this.value, msg = '';
    this.ageText = '';
    if (v && this.outOfRange(v)) {
      if (this.dob && this.max && v > this.max) msg = 'You must be ' + (this.minAge || 18) + ' or older to join.';
      else if (this.dob && this.min && v < this.min) msg = 'Check the year you were born.';
      else if (this.max && v > this.max) msg = 'Choose ' + fmtDate(this.max).replace(/ \/ /g, '/') + ' or earlier.';
      else if (this.min && v < this.min) msg = 'Choose ' + fmtDate(this.min).replace(/ \/ /g, '/') + ' or later.';
      v = null;
    }
    if (v && this.dob) { var a = ageOn(v, this.today); if (a >= 0 && a < 130) this.ageText = 'You are ' + a + ' years old'; }
    this.problem(msg);
  };

  // ---- the calendar
  Picker.prototype.show = function () {
    var self = this;
    if (open && open !== self) open.hide();
    if (self.text.disabled) return;
    open = self;
    var base = self.value || self.defaultView();
    self.view = new Date(base.getFullYear(), base.getMonth(), 1);
    self.focusDay = self.value ? self.value : self.clamp(base);
    var host = self.wrap.closest('dialog[open]') || doc.body;
    var pop = el('div', 'dp-pop'); pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-label', 'Choose a date'); pop.tabIndex = -1;
    var scrim = el('div', 'dp-scrim');
    self.pop = pop; self.scrim = scrim; self.host = host;
    host.appendChild(scrim); host.appendChild(pop);
    self.draw();
    self.place();
    self.btn.setAttribute('aria-expanded', 'true');
    requestAnimationFrame(function () { pop.classList.add('is-in'); scrim.classList.add('is-in'); });
    var onDoc = function (e) { if (!pop.contains(e.target) && !self.wrap.contains(e.target)) self.hide(); };
    var onKey = function (e) { if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); self.hide(true); } };
    var onMove = function () { self.place(); };
    doc.addEventListener('mousedown', onDoc, true); doc.addEventListener('touchstart', onDoc, true); doc.addEventListener('keydown', onKey, true);
    window.addEventListener('resize', onMove); window.addEventListener('scroll', onMove, true);
    self.off = function () { doc.removeEventListener('mousedown', onDoc, true); doc.removeEventListener('touchstart', onDoc, true); doc.removeEventListener('keydown', onKey, true); window.removeEventListener('resize', onMove); window.removeEventListener('scroll', onMove, true); };
    setTimeout(function () { var t = pop.querySelector('.dp-day[tabindex="0"]'); if (t) t.focus({ preventScroll: true }); }, 30);
  };
  Picker.prototype.hide = function (refocus) {
    var self = this; if (open !== self) return;
    open = null; self.off && self.off();
    var pop = self.pop, scrim = self.scrim;
    pop.classList.remove('is-in'); scrim.classList.remove('is-in');
    setTimeout(function () { pop.remove(); scrim.remove(); }, 180);
    self.btn.setAttribute('aria-expanded', 'false');
    if (refocus) self.text.focus({ preventScroll: true });
  };
  Picker.prototype.defaultView = function () {
    var t = this.today, y = t.getFullYear();
    if (this.dob) y = y - 26;
    var d = new Date(y, this.dob ? 5 : t.getMonth(), this.dob ? 1 : t.getDate());
    return this.clamp(d);
  };
  Picker.prototype.clamp = function (d) { if (this.max && d > this.max) return new Date(this.max.getTime()); if (this.min && d < this.min) return new Date(this.min.getTime()); return d; };
  Picker.prototype.yearRange = function () {
    var t = this.today.getFullYear();
    var lo = this.min ? this.min.getFullYear() : (this.dob ? t - 100 : t - 10);
    var hi = this.max ? this.max.getFullYear() : (this.dob ? t : t + 6);
    return [lo, hi];
  };
  Picker.prototype.place = function () {
    var pop = this.pop; if (!pop) return;
    if (window.innerWidth < 640) { pop.style.left = pop.style.top = pop.style.width = ''; pop.classList.add('is-sheet'); this.scrim.classList.add('is-sheet'); return; }
    pop.classList.remove('is-sheet'); this.scrim.classList.remove('is-sheet');
    var r = this.text.getBoundingClientRect(), w = 320, h = pop.offsetHeight || 380;
    var left = Math.min(Math.max(8, r.left), window.innerWidth - w - 8);
    var top = r.bottom + 8; if (top + h > window.innerHeight - 8 && r.top - h - 8 > 8) top = r.top - h - 8;
    pop.style.left = left + 'px'; pop.style.top = Math.max(8, top) + 'px'; pop.style.width = w + 'px';
  };
  Picker.prototype.draw = function () {
    var self = this, pop = self.pop, v = self.view, yr = self.yearRange();
    var minMonth = self.min ? new Date(self.min.getFullYear(), self.min.getMonth(), 1) : null, maxMonth = self.max ? new Date(self.max.getFullYear(), self.max.getMonth(), 1) : null;
    var prevOk = !minMonth || v > minMonth, nextOk = !maxMonth || v < maxMonth;
    var years = '';
    var order = self.dob ? [yr[1], yr[0], -1] : [yr[0], yr[1], 1];
    for (var y = order[0]; order[2] < 0 ? y >= order[1] : y <= order[1]; y += order[2]) years += '<option value="' + y + '"' + (y === v.getFullYear() ? ' selected' : '') + '>' + y + '</option>';
    if (v.getFullYear() < yr[0] || v.getFullYear() > yr[1]) years += '<option value="' + v.getFullYear() + '" selected>' + v.getFullYear() + '</option>';
    var months = MONTHS.map(function (m, i) {
      var dis = (minMonth && new Date(v.getFullYear(), i, 1) < minMonth) || (maxMonth && new Date(v.getFullYear(), i, 1) > maxMonth);
      return '<option value="' + i + '"' + (i === v.getMonth() ? ' selected' : '') + (dis ? ' disabled' : '') + '>' + m + '</option>';
    }).join('');
    var html = '<div class="dp-head">' +
      '<button type="button" class="dp-nav" data-dp-prev aria-label="Previous month"' + (prevOk ? '' : ' disabled') + '>' + icon('chevron-left', 'w-5 h-5') + '</button>' +
      '<div class="dp-selects"><label class="dp-sel"><span class="sr-only">Month</span><select data-dp-month>' + months + '</select></label>' +
      '<label class="dp-sel"><span class="sr-only">Year</span><select data-dp-year>' + years + '</select></label></div>' +
      '<button type="button" class="dp-nav" data-dp-next aria-label="Next month"' + (nextOk ? '' : ' disabled') + '>' + icon('chevron-right', 'w-5 h-5') + '</button></div>' +
      '<div class="dp-dow" aria-hidden="true">' + DOW.map(function (d) { return '<span>' + d + '</span>'; }).join('') + '</div>' +
      '<div class="dp-grid" role="grid" aria-label="' + MONTHS[v.getMonth()] + ' ' + v.getFullYear() + '">';
    var first = new Date(v.getFullYear(), v.getMonth(), 1), lead = (first.getDay() + 6) % 7, days = new Date(v.getFullYear(), v.getMonth() + 1, 0).getDate();
    for (var i = 0; i < lead; i++) html += '<span class="dp-pad"></span>';
    var fd = self.focusDay && self.focusDay.getMonth() === v.getMonth() && self.focusDay.getFullYear() === v.getFullYear() ? self.focusDay.getDate() : (self.value && self.value.getMonth() === v.getMonth() && self.value.getFullYear() === v.getFullYear() ? self.value.getDate() : 1);
    for (var n = 1; n <= days; n++) {
      var d = new Date(v.getFullYear(), v.getMonth(), n), dis = self.outOfRange(d), sel = same(d, self.value), isToday = same(d, self.today);
      html += '<button type="button" class="dp-day' + (sel ? ' is-sel' : '') + (isToday ? ' is-today' : '') + '" data-d="' + n + '" role="gridcell" aria-selected="' + (sel ? 'true' : 'false') + '"' + (dis ? ' disabled' : '') + ' tabindex="' + (n === fd && !dis ? '0' : '-1') + '" aria-label="' + n + ' ' + MONTHS[v.getMonth()] + ' ' + v.getFullYear() + '">' + n + '</button>';
    }
    html += '</div>';
    if (self.withTime) {
      var hrs = '', mins = '';
      for (var h = 0; h < 24; h++) hrs += '<option value="' + h + '"' + (h === self.hour ? ' selected' : '') + '>' + pad(h) + '</option>';
      for (var m2 = 0; m2 < 60; m2 += 5) mins += '<option value="' + m2 + '"' + (m2 === self.minute || (self.minute % 5 && m2 === Math.floor(self.minute / 5) * 5) ? ' selected' : '') + '>' + pad(m2) + '</option>';
      html += '<div class="dp-time">' + icon('clock', 'w-4 h-4') + '<label class="dp-sel"><span class="sr-only">Hour</span><select data-dp-hour>' + hrs + '</select></label><span>:</span><label class="dp-sel"><span class="sr-only">Minute</span><select data-dp-min>' + mins + '</select></label></div>';
    }
    var hint = self.dob && (self.minAge || self.max) ? '<span class="dp-hint">' + icon('shield-check', 'w-4 h-4') + ' You must be ' + (self.minAge || 18) + ' or older</span>' : '';
    var todayOk = !self.outOfRange(self.today);
    html += '<div class="dp-foot">' + hint + '<span class="dp-foot-btns">' +
      (todayOk && !self.dob ? '<button type="button" class="dp-link" data-dp-today>Today</button>' : '') +
      (!self.text.required && self.value ? '<button type="button" class="dp-link" data-dp-clear>Clear</button>' : '') +
      (self.withTime ? '<button type="button" class="dp-done" data-dp-done>Done</button>' : '') + '</span></div>';
    var hadFocus = pop.contains(doc.activeElement) ? doc.activeElement.getAttribute('data-d') : null;
    pop.innerHTML = html;
    pop.classList.toggle('is-dob', self.dob);
    if (hadFocus) { var t = pop.querySelector('.dp-day[data-d="' + hadFocus + '"]'); t && t.focus({ preventScroll: true }); }
    self.bind();
  };
  Picker.prototype.bind = function () {
    var self = this, pop = self.pop;
    function go(dm) { self.view = new Date(self.view.getFullYear(), self.view.getMonth() + dm, 1); self.focusDay = null; self.draw(); }
    pop.querySelector('[data-dp-prev]').onclick = function () { go(-1); };
    pop.querySelector('[data-dp-next]').onclick = function () { go(1); };
    pop.querySelector('[data-dp-month]').onchange = function (e) { self.view = new Date(self.view.getFullYear(), +e.target.value, 1); self.focusDay = null; self.draw(); };
    pop.querySelector('[data-dp-year]').onchange = function (e) {
      var y = +e.target.value, m = self.view.getMonth(), probe = new Date(y, m, 1);
      if (self.min && new Date(y, m + 1, 0) < self.min) probe = new Date(self.min.getFullYear(), self.min.getMonth(), 1);
      if (self.max && probe > self.max) probe = new Date(self.max.getFullYear(), self.max.getMonth(), 1);
      self.view = probe; self.focusDay = null; self.draw();
    };
    var t = pop.querySelector('[data-dp-today]'); if (t) t.onclick = function () { self.pick(self.today, true); };
    var c = pop.querySelector('[data-dp-clear]'); if (c) c.onclick = function () { self.value = null; self.commit(); self.hide(true); };
    var dn = pop.querySelector('[data-dp-done]'); if (dn) dn.onclick = function () { self.hide(true); };
    var hh = pop.querySelector('[data-dp-hour]'); if (hh) hh.onchange = function (e) { self.hour = +e.target.value; if (self.value) self.commit(); };
    var mm = pop.querySelector('[data-dp-min]'); if (mm) mm.onchange = function (e) { self.minute = +e.target.value; if (self.value) self.commit(); };
    pop.querySelector('.dp-grid').onclick = function (e) {
      var b = e.target.closest('.dp-day'); if (!b || b.disabled) return;
      self.pick(new Date(self.view.getFullYear(), self.view.getMonth(), +b.getAttribute('data-d')), !self.withTime);
    };
    pop.querySelector('.dp-grid').onkeydown = function (e) {
      var b = e.target.closest('.dp-day'); if (!b) return;
      var n = +b.getAttribute('data-d'), step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key];
      if (step !== undefined) {
        e.preventDefault();
        var d = new Date(self.view.getFullYear(), self.view.getMonth(), n + step);
        if (self.outOfRange(d)) return;
        if (d.getMonth() !== self.view.getMonth()) { self.view = new Date(d.getFullYear(), d.getMonth(), 1); }
        self.focusDay = d; self.draw();
        var f = pop.querySelector('.dp-day[data-d="' + d.getDate() + '"]'); f && f.focus({ preventScroll: true });
      } else if (e.key === 'PageUp' || e.key === 'PageDown') {
        e.preventDefault(); var dm = e.key === 'PageUp' ? -1 : 1; if (e.shiftKey) dm *= 12;
        var nd = new Date(self.view.getFullYear(), self.view.getMonth() + dm, Math.min(n, 28)); self.view = new Date(nd.getFullYear(), nd.getMonth(), 1); self.focusDay = nd; self.draw();
        var f2 = pop.querySelector('.dp-day[tabindex="0"]'); f2 && f2.focus({ preventScroll: true });
      } else if (e.key === 'Home' || e.key === 'End') {
        e.preventDefault(); var dow = (new Date(self.view.getFullYear(), self.view.getMonth(), n).getDay() + 6) % 7;
        var tn = e.key === 'Home' ? n - dow : n + (6 - dow); var td = new Date(self.view.getFullYear(), self.view.getMonth(), tn);
        if (td.getMonth() === self.view.getMonth() && !self.outOfRange(td)) { self.focusDay = td; self.draw(); var f3 = pop.querySelector('.dp-day[data-d="' + td.getDate() + '"]'); f3 && f3.focus({ preventScroll: true }); }
      }
    };
  };
  Picker.prototype.pick = function (d, close) {
    this.value = d; this.commit(); this.focusDay = d;
    if (close) { this.hide(true); } else { this.draw(); }
  };

  function enhance(input) {
    if (input.__dp || input.hasAttribute('data-native') || !input.name) return;
    input.__dp = new Picker(input);
  }
  function scan(root) {
    Array.prototype.forEach.call((root || doc).querySelectorAll('input[type="date"], input[type="datetime-local"]'), enhance);
  }
  window.CHDate = { scan: scan };
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', function () { scan(); }); else scan();
  // forms drawn later (modals, dialogs) are picked up too
  if (window.MutationObserver) new MutationObserver(function (muts) {
    for (var i = 0; i < muts.length; i++) for (var j = 0; j < muts[i].addedNodes.length; j++) { var n = muts[i].addedNodes[j]; if (n.nodeType === 1 && (n.matches('input[type="date"], input[type="datetime-local"]') || n.querySelector('input[type="date"], input[type="datetime-local"]'))) { scan(n.parentNode || doc); return; } }
  }).observe(doc.documentElement, { childList: true, subtree: true });
})();
