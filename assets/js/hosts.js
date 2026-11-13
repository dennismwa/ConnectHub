/* ConnectHub Hosts: directory filters, profile gallery, booking picker, Host area helpers.
   Vanilla JS on top of window.CH (assets/js/app.js). Everything is optional: pages work for reading without it. */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH;
  if (!CH) { return; }
  var $ = CH.$, $$ = CH.$$, on = CH.on, delegate = CH.delegate;

  function readJson(id) {
    var el = doc.getElementById(id);
    if (!el) { return null; }
    try { return JSON.parse(el.textContent || 'null'); } catch (e) { return null; }
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  /* Dates are handled as plain "YYYY-MM-DD" strings and UTC arithmetic, so the visitor's own time zone never shifts a day. */
  function parseYmd(s) { var p = String(s).split('-'); return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); }
  function ymd(d) { return d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()); }
  function longDate(s) { var d = parseYmd(s); return DAYS[d.getUTCDay()].slice(0, 3) + ' ' + d.getUTCDate() + ' ' + MONTHS[d.getUTCMonth()].slice(0, 3) + ' ' + d.getUTCFullYear(); }

  /* What the admin calls Hosts (Admin > Hosts > Names and Discover). */
  var TERM = (window.CH && CH.config && CH.config.hostTerm) || ['Host', 'Hosts'];
  function T(s) { return String(s).replace(/\bHosts\b/g, TERM[1]).replace(/\bHost\b/g, TERM[0]).replace(/\bhosts\b/g, TERM[1].toLowerCase()).replace(/\bhost\b/g, TERM[0].toLowerCase()); }

  /* ------------------------------------------------------------------ directory filters */
  var dir = $('[data-host-directory]');
  if (dir) {
    var panel = $('[data-filters-panel]', dir), toggle = $('[data-filters-toggle]', dir);
    on(toggle, 'click', function () {
      var open = panel.hidden;
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var f = $('select, input:not([type=hidden])', panel); f && f.focus({ preventScroll: true }); }
    });
    delegate('change', '[data-host-directory] [data-autosubmit]', function (e, el) {
      var f = el.form || el.closest('form');
      if (f) { f.requestSubmit ? f.requestSubmit() : f.submit(); }
    });
  }

  /* ------------------------------------------------------------------ county -> town selects (directory, setup, profile) */
  var townMap = readJson('host-towns') || {};
  function fillTowns(county) {
    var scope = county.closest('form') || doc;
    var town = $('[data-town]', scope);
    if (!town) { return; }
    var keep = town.getAttribute('data-selected') || town.value || '';
    var first = town.options[0] ? town.options[0].cloneNode(true) : null;
    town.innerHTML = '';
    if (first) { town.appendChild(first); }
    (townMap[county.value] || []).forEach(function (t) {
      var o = doc.createElement('option'); o.value = t[0]; o.textContent = t[1];
      if (String(t[0]) === String(keep)) { o.selected = true; }
      town.appendChild(o);
    });
    town.setAttribute('data-selected', '');
  }
  delegate('change', '[data-county]', function (e, el) {
    var scope = el.closest('form') || doc;
    var town = $('[data-town]', scope);
    if (town) { town.setAttribute('data-selected', ''); }
    fillTowns(el);
  });

  /* ------------------------------------------------------------------ area chips filter */
  delegate('input', '[data-area-filter]', function (e, el) {
    var q = el.value.trim().toLowerCase();
    $$('[data-area-item]', el.closest('fieldset') || doc).forEach(function (l) {
      var show = !q || l.getAttribute('data-area-item').indexOf(q) !== -1 || $('input', l).checked;
      l.hidden = !show;
    });
  });

  /* ------------------------------------------------------------------ profile: gallery, share, favourite, mobile CTA */
  var gallery = $('[data-gallery]');
  if (gallery) {
    var main = $('[data-gallery-main]', gallery);
    delegate('click', '[data-gallery-thumb]', function (e, btn) {
      if (!main) { return; }
      main.src = btn.getAttribute('data-src');
      var blurTokens = ($('img', btn).className.match(/ch-blur\S*/g) || []).join(' ');
      main.className = main.className.replace(/\bch-blur\S*/g, '').trim() + (blurTokens ? ' ' + blurTokens : '');
      $$('[data-gallery-thumb]', gallery).forEach(function (b) { b.classList.toggle('is-on', b === btn); });
    });
  }
  delegate('click', '[data-share]', function (e, el) {
    var data = { title: el.getAttribute('data-title') || doc.title, url: el.getAttribute('data-url') || location.href };
    if (navigator.share) {
      navigator.share(data).catch(function () { /* cancelled */ });
    } else {
      (navigator.clipboard ? navigator.clipboard.writeText(data.url) : Promise.reject()).then(
        function () { CH.toast('Link copied', 'success', 2200); },
        function () { CH.toast('Copy this link: ' + data.url, 'info', 6000); });
    }
  });
  delegate('click', '[data-host-fav]', function (e, btn) {
    var id = parseInt(btn.getAttribute('data-host-fav'), 10);
    btn.disabled = true;
    CH.api('/api/favorite', { data: { user_id: id } }).then(function (j) {
      btn.disabled = false;
      if (!j.ok) { CH.toast(j.error || 'Could not save. Please try again.', 'error'); return; }
      btn.classList.toggle('is-fav', !!j.favorited);
      btn.setAttribute('aria-pressed', j.favorited ? 'true' : 'false');
      var l = $('[data-fav-label]', btn); if (l) { l.textContent = j.favorited ? 'Saved' : 'Save'; }
      CH.toast(j.message || (j.favorited ? 'Saved' : 'Removed'), 'success', 2200);
    });
  });
  var cta = $('[data-hprof-cta]');
  if (cta) {
    var side = $('.hprof-side .hprof-card');
    if (side && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { cta.classList.toggle('is-on', !en[0].isIntersecting); }, { rootMargin: '0px 0px -40px 0px' }).observe(side);
    } else { cta.classList.add('is-on'); }
  }

  /* ------------------------------------------------------------------ report dialog */
  delegate('ch:success', '[data-host-report]', function (e, form) {
    var d = form.closest('dialog'); d && d.close();
    form.reset();
  });

  /* ------------------------------------------------------------------ host dashboard switches */
  delegate('change', '[data-host-toggle]', function (e, box) {
    var field = box.getAttribute('data-host-toggle');
    box.disabled = true;
    CH.api('/api/hosts/toggle', { data: { field: field, value: box.checked ? 1 : 0 } }).then(function (j) {
      box.disabled = false;
      if (!j.ok) { box.checked = !box.checked; CH.toast(j.error || 'Could not save that change.', 'error'); return; }
      CH.toast(j.message || 'Saved', 'success', 2400);
      if (field === 'bookings_paused') {
        var live = $('.hdash-live');
        if (live) {
          var visible = live.getAttribute('data-visible') === '1', paused = !!j.value;
          $('.hdash-dot', live).classList.toggle('is-on', visible && !paused);
          var t = $('strong', live), p = $('p', live);
          if (visible) {
            t.textContent = paused ? 'Visible, but not taking bookings' : 'Live in the directory';
            p.textContent = paused ? 'Clients can see your profile but cannot book.' : 'Clients can find and book you.';
          }
        }
      }
    });
  });

  /* ------------------------------------------------------------------ services: repeatable package rows, travel switch */
  $$('[data-repeat]').forEach(function (box) {
    var max = parseInt(box.getAttribute('data-max') || '12', 10);
    var addBtn = $('[data-repeat-add]', box);
    function rows() { return $$('[data-repeat-row]', box); }
    function filled(r) { return !!($('input[name$="[name]"]', r).value.trim() || $('input[name$="[price]"]', r).value.trim()); }
    function sync() {
      var all = rows(), shown = all.filter(function (r) { return !r.hidden; });
      if (!shown.length && all.length) { all[0].hidden = false; shown = [all[0]]; }
      addBtn.hidden = false;
      addBtn.disabled = shown.length >= max;
    }
    // Start with the filled rows plus one empty row.
    var all0 = rows(), firstEmpty = true;
    all0.forEach(function (r) { if (filled(r) || $('.error-text', r)) { return; } if (firstEmpty) { firstEmpty = false; return; } r.hidden = true; });
    sync();
    on(addBtn, 'click', function () {
      var all = rows(), hidden = all.filter(function (r) { return r.hidden; })[0];
      if (!hidden && all.length < max) {
        var n = all.length, src = all[all.length - 1];
        hidden = src.cloneNode(true);
        $$('input, select, textarea, label', hidden).forEach(function (el) {
          ['name', 'id', 'for', 'aria-label'].forEach(function (a) {
            var v = el.getAttribute(a); if (v) { el.setAttribute(a, v.replace(/offer\[\d+\]/, 'offer[' + n + ']').replace(/-\d+$/, '-' + n).replace(/package \d+/, 'package ' + (n + 1))); }
          });
          if (el.tagName === 'INPUT' && el.type !== 'hidden') { el.value = ''; }
          if (el.name && /\[id\]$/.test(el.name)) { el.value = '0'; }
        });
        var err = $('.error-text', hidden); err && err.remove();
        hidden.classList.remove('has-error');
        src.parentNode.insertBefore(hidden, addBtn);
      }
      if (hidden) { hidden.hidden = false; var f = $('input[type=text]', hidden); f && f.focus(); }
      sync();
    });
    delegate('click', '[data-repeat-remove]', function (e, btn) {
      var r = btn.closest('[data-repeat-row]'); if (!r || !box.contains(r)) { return; }
      $$('input[type=text], input[type=number]', r).forEach(function (i) { i.value = ''; });
      r.classList.remove('has-error');
      var visible = rows().filter(function (x) { return !x.hidden; });
      if (visible.length > 1) { r.hidden = true; }
      sync();
    });
  });
  delegate('change', '[data-travel-switch]', function (e, sw) {
    var f = $('[data-travel-fee]', sw.closest('form') || doc);
    if (f) { f.hidden = !sw.checked; }
  });

  /* ------------------------------------------------------------------ availability editor */
  var avail = $('[data-avail]');
  if (avail) { initAvail(avail); }
  function initAvail(form) {
    function days() { return $$('.hday', form); }
    function winRows(day) { return $$('[data-window]', day); }
    function isBlank(w) { return !$('input[type=time]', w).value && !$$('input[type=time]', w)[1].value; }
    function setState(day) {
      var open = $('[data-day-toggle]', day).checked;
      day.classList.toggle('is-open', open);
      $('[data-day-body]', day).hidden = !open;
      var st = $('.hday-state', day);
      if (st) {
        if (!open) { st.textContent = 'Day off'; return; }
        var parts = winRows(day).filter(function (w) { return !w.hidden && !isBlank(w); }).map(function (w) {
          var t = $$('input[type=time]', w); return label12(t[0].value) + ' to ' + label12(t[1].value);
        });
        st.textContent = parts.join(', ');
      }
    }
    function label12(v) {
      if (!v) { return ''; }
      var p = v.split(':'), h = +p[0], m = p[1], s = h >= 12 ? 'pm' : 'am', h12 = h % 12 === 0 ? 12 : h % 12;
      return h12 + ':' + m + ' ' + s;
    }
    function tidy(day) {
      var rows = winRows(day), blanks = rows.filter(isBlank);
      // Show filled rows; keep at most one blank row visible when the day is open and nothing is filled yet.
      var filledRows = rows.filter(function (w) { return !isBlank(w); });
      rows.forEach(function (w) { w.hidden = isBlank(w); });
      if (!filledRows.length && blanks.length) { blanks[0].hidden = false; }
      var add = $('[data-window-add]', day);
      add.hidden = false;
      add.disabled = rows.filter(function (w) { return !w.hidden; }).length >= 4;
      setState(day);
    }
    function cloneRow(day) {
      var rows = winRows(day), n = rows.length, src = rows[rows.length - 1], c = src.cloneNode(true);
      $$('input, label', c).forEach(function (el) {
        ['name', 'id', 'for'].forEach(function (a) {
          var v = el.getAttribute(a); if (v) { el.setAttribute(a, v.replace(/\[\d+\]\[(s|e)\]$/, '[' + n + '][$1]').replace(/-\d+-(s|e)$/, '-' + n + '-$1')); }
        });
        if (el.tagName === 'INPUT') { el.value = ''; }
      });
      src.parentNode.insertBefore(c, src.nextSibling);
      return c;
    }
    days().forEach(function (d) { tidy(d); });
    on(form, 'change', function (e) {
      var t = e.target, day = t.closest('.hday'); if (!day) { return; }
      if (t.matches('[data-day-toggle]')) {
        if (t.checked && !winRows(day).some(function (w) { return !isBlank(w); })) {
          var first = winRows(day)[0], ts = $$('input[type=time]', first); ts[0].value = '10:00'; ts[1].value = '20:00';
        }
        tidy(day);
      } else { tidy(day); }
    });
    on(form, 'click', function (e) {
      var b = e.target.closest('button'); if (!b) { return; }
      var day = b.closest('.hday'); if (!day) { return; }
      if (b.matches('[data-window-add]')) {
        var rows = winRows(day), hiddenBlank = rows.filter(function (w) { return w.hidden; })[0];
        if (!hiddenBlank && rows.length < 4) { hiddenBlank = cloneRow(day); }
        if (hiddenBlank) { hiddenBlank.hidden = false; var i = $('input', hiddenBlank); i && i.focus(); }
        $('[data-window-add]', day).disabled = winRows(day).filter(function (w) { return !w.hidden; }).length >= 4;
      } else if (b.matches('[data-window-remove]')) {
        var w = b.closest('[data-window]');
        $$('input', w).forEach(function (i) { i.value = ''; });
        tidy(day);
      } else if (b.matches('[data-copy-all]')) {
        var src = winRows(day).filter(function (w2) { return !isBlank(w2); }).map(function (w2) { var t = $$('input[type=time]', w2); return [t[0].value, t[1].value]; });
        if (!src.length) { CH.toast('Add a time for this day first.', 'error'); return; }
        days().forEach(function (d) {
          if (d === day) { return; }
          while (winRows(d).length < src.length + 1 && winRows(d).length < 4) { cloneRow(d); }
          winRows(d).forEach(function (w3, idx) { var t = $$('input[type=time]', w3); t[0].value = src[idx] ? src[idx][0] : ''; t[1].value = src[idx] ? src[idx][1] : ''; });
          $('[data-day-toggle]', d).checked = true;
          tidy(d);
        });
        CH.toast('Copied to every day. Remember to save.', 'success', 3000);
      }
    });
  }

  /* ------------------------------------------------------------------ booking page */
  var book = $('[data-host-book]');
  if (book && $('[data-book-form]', book)) { initBook(book); }

  function initBook(root) {
    var cfg = readJson('book-config') || {};
    var form = $('[data-book-form]', root);
    var inDate = $('[data-in-date]', form), inStart = $('[data-in-start]', form), inHours = $('[data-in-hours]', form);
    var inArea = $('[data-in-area]', form), inVenue = $('[data-in-venue]', form), inAccept = $('[data-in-accept]', form);
    var calBox = $('[data-cal]', form), slotsBox = $('[data-slots]', form), slotsHint = $('[data-slots-hint]', form);
    var priceBox = $('[data-price]', form), submit = $('[data-submit]', form), submitLabel = $('[data-submit-label]', form);
    var hoursWrap = $('[data-hours-wrap]', form), hoursOut = $('[data-hours-out]', form);
    var sum = {
      service: $('[data-sum="service"]', form), date: $('[data-sum="date"]', form), time: $('[data-sum="time"]', form)
    };
    var offerRadios = $$('[data-offer]', form);
    var st = {
      offering: +(cfg.start && cfg.start.offering) || 0,
      hours: +(cfg.start && cfg.start.hours) || cfg.minHours || 2,
      date: (cfg.start && cfg.start.date) || '', time: (cfg.start && cfg.start.time) || '',
      days: null, today: '', slots: null, quote: null, month: 0, slotEnd: {}
    };
    var seq = { days: 0, slots: 0, quote: 0 };

    function offer() { for (var i = 0; i < (cfg.offerings || []).length; i++) { if (cfg.offerings[i].id === st.offering) { return cfg.offerings[i]; } } return null; }
    function hours() { var o = offer(); return o ? o.hours : st.hours; }
    function hoursLabel(h) { if (h < 1) { return Math.round(h * 60) + ' minutes'; } return (h % 1 === 0 ? h : h.toFixed(1)) + (h === 1 ? ' hour' : ' hours'); }
    function qs(obj) { return Object.keys(obj).map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]); }).join('&'); }
    function travel() { return cfg.travelOk && inArea && inArea.value === 'other' ? 1 : 0; }

    /* ---- service ---- */
    function syncService() {
      var o = offer(), custom = !o;
      if (hoursWrap) { hoursWrap.hidden = !custom; }
      if (inHours) { inHours.value = o ? 0 : st.hours; }
      if (hoursOut) { hoursOut.textContent = hoursLabel(st.hours); }
      sum.service.textContent = o ? o.name + ', ' + hoursLabel(o.hours) : (cfg.customOk ? 'Custom, ' + hoursLabel(st.hours) : 'Choose a service');
      var minus = $('[data-hours-step="-0.5"]', form), plus = $('[data-hours-step="0.5"]', form);
      if (minus) { minus.disabled = st.hours <= cfg.minHours; }
      if (plus) { plus.disabled = st.hours >= cfg.maxHours; }
    }
    function onServiceChange() {
      syncService();
      loadDays(); loadQuote();
    }
    offerRadios.forEach(function (r) {
      if (r.checked) { st.offering = r.hasAttribute('data-custom') ? 0 : +r.value; }
      on(r, 'change', function () { if (r.checked) { st.offering = r.hasAttribute('data-custom') ? 0 : +r.value; onServiceChange(); } });
    });
    delegate('click', '[data-hours-step]', function (e, b) {
      if (!root.contains(b)) { return; }
      var next = Math.round((st.hours + parseFloat(b.getAttribute('data-hours-step'))) * 2) / 2;
      st.hours = Math.max(cfg.minHours, Math.min(cfg.maxHours, next));
      onServiceChange();
    });

    /* ---- calendar ---- */
    function loadDays() {
      var h = hours(); if (!(h > 0)) { return; }
      var my = ++seq.days;
      calBox.setAttribute('aria-busy', 'true');
      CH.api('/api/hosts/' + cfg.hostId + '/days?' + qs({ hours: h, offering: st.offering })).then(function (j) {
        if (my !== seq.days) { return; }
        calBox.removeAttribute('aria-busy');
        if (!j.ok) { calBox.innerHTML = '<p class="error-text">' + CH.esc(j.error || 'Could not load the calendar.') + '</p>'; return; }
        st.days = j.days; st.today = j.today;
        if (st.date && !(st.days[st.date] > 0)) { st.date = ''; st.time = ''; inDate.value = ''; inStart.value = ''; }
        var keys = Object.keys(st.days).sort();
        var focus = st.date || firstFree(keys) || st.today;
        st.month = monthIndex(focus, keys);
        renderCal();
        if (st.date) { loadSlots(); } else { renderSlots(null); }
        sync();
      });
    }
    function firstFree(keys) { for (var i = 0; i < keys.length; i++) { if (st.days[keys[i]] > 0) { return keys[i]; } } return ''; }
    function monthKeys(keys) { var m = []; keys.forEach(function (k) { var ym = k.slice(0, 7); if (m.indexOf(ym) === -1) { m.push(ym); } }); return m; }
    function monthIndex(date, keys) { var idx = monthKeys(keys).indexOf(String(date).slice(0, 7)); return idx < 0 ? 0 : idx; }
    function renderCal() {
      var keys = Object.keys(st.days).sort(), months = monthKeys(keys);
      if (!months.length) { calBox.innerHTML = '<p class="text-sm text-ink-3">No dates are open right now.</p>'; return; }
      st.month = Math.max(0, Math.min(months.length - 1, st.month));
      var ym = months[st.month].split('-'), y = +ym[0], m = +ym[1] - 1;
      var first = new Date(Date.UTC(y, m, 1)), lead = (first.getUTCDay() + 6) % 7, dim = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
      var html = '<div class="hcal-nav"><button type="button" class="btn btn-ghost btn-icon btn-sm" data-cal-nav="-1" aria-label="Previous month"' + (st.month === 0 ? ' disabled' : '') + '><svg class="icon w-4 h-4" aria-hidden="true"><use href="' + CH.url('/assets/icons/sprite.svg') + '#i-chevron-left"></use></svg></button>' +
        '<strong class="hcal-title">' + MONTHS[m] + ' ' + y + '</strong>' +
        '<button type="button" class="btn btn-ghost btn-icon btn-sm" data-cal-nav="1" aria-label="Next month"' + (st.month === months.length - 1 ? ' disabled' : '') + '><svg class="icon w-4 h-4" aria-hidden="true"><use href="' + CH.url('/assets/icons/sprite.svg') + '#i-chevron-right"></use></svg></button></div>';
      html += '<div class="hcal-grid">';
      ['M', 'T', 'W', 'T', 'F', 'S', 'S'].forEach(function (d) { html += '<span class="hcal-dow" aria-hidden="true">' + d + '</span>'; });
      for (var i = 0; i < lead; i++) { html += '<span class="hcal-day is-empty" aria-hidden="true"></span>'; }
      for (var d = 1; d <= dim; d++) {
        var key = y + '-' + pad(m + 1) + '-' + pad(d), n = st.days[key];
        var cls = 'hcal-day' + (key === st.today ? ' is-today' : '');
        if (n === undefined) { html += '<span class="' + cls + ' is-out">' + d + '</span>'; }
        else if (n > 0) {
          html += '<button type="button" class="' + cls + ' is-free' + (key === st.date ? ' is-sel' : '') + '" data-cal-day="' + key + '" aria-pressed="' + (key === st.date ? 'true' : 'false') + '" aria-label="' + CH.esc(longDate(key)) + ', ' + n + (n === 1 ? ' start time' : ' start times') + '">' + d + '</button>';
        } else { html += '<span class="' + cls + ' is-busy" title="Booked or closed">' + d + '</span>'; }
      }
      html += '</div>';
      calBox.innerHTML = html;
    }
    delegate('click', '[data-cal-nav]', function (e, b) {
      if (!calBox.contains(b)) { return; }
      st.month += parseInt(b.getAttribute('data-cal-nav'), 10); renderCal();
    });
    delegate('click', '[data-cal-day]', function (e, b) {
      if (!calBox.contains(b)) { return; }
      st.date = b.getAttribute('data-cal-day'); st.time = ''; inDate.value = st.date; inStart.value = '';
      renderCal(); loadSlots(); sync();
      slotsBox.scrollIntoView && window.matchMedia('(max-width: 1023px)').matches && slotsBox.closest('.hbook-step').scrollIntoView({ behavior: CH.reduced() ? 'auto' : 'smooth', block: 'start' });
    });

    /* ---- slots ---- */
    function loadSlots() {
      var h = hours(); if (!st.date || !(h > 0)) { renderSlots(null); return; }
      var my = ++seq.slots;
      slotsHint.textContent = 'Loading start times...'; slotsHint.hidden = false; slotsBox.innerHTML = '';
      CH.api('/api/hosts/' + cfg.hostId + '/slots?' + qs({ date: st.date, hours: h, offering: st.offering })).then(function (j) {
        if (my !== seq.slots) { return; }
        if (!j.ok) { slotsHint.textContent = j.error || 'Could not load start times.'; return; }
        st.slots = j.slots || [];
        var found = st.slots.some(function (s) { return s.start === st.time; });
        if (!found) { st.time = ''; inStart.value = ''; }
        renderSlots(st.slots); sync();
      });
    }
    function renderSlots(slots) {
      if (slots === null) { slotsHint.hidden = false; slotsHint.textContent = 'Choose a date first.'; slotsBox.innerHTML = ''; return; }
      if (!slots.length) { slotsHint.hidden = false; slotsHint.textContent = 'No start times are free on ' + longDate(st.date) + '. Please pick another date.'; slotsBox.innerHTML = ''; return; }
      slotsHint.hidden = false;
      slotsHint.textContent = 'Free start times on ' + longDate(st.date) + '. Your booking lasts ' + hoursLabel(hours()) + '.';
      slotsBox.innerHTML = slots.map(function (s) {
        var on_ = s.start === st.time;
        return '<button type="button" class="hslot' + (on_ ? ' is-sel' : '') + '" role="radio" aria-checked="' + (on_ ? 'true' : 'false') + '" data-slot="' + CH.esc(s.start) + '"><strong>' + CH.esc(s.label) + '</strong><small>to ' + CH.esc(s.end_label || s.end) + (s.next_day ? ' (+1 day)' : '') + '</small></button>';
      }).join('');
    }
    delegate('click', '[data-slot]', function (e, b) {
      if (!slotsBox.contains(b)) { return; }
      st.time = b.getAttribute('data-slot'); inStart.value = st.time;
      renderSlots(st.slots); sync();
    });

    /* ---- quote ---- */
    function loadQuote() {
      var h = hours(); if (!(h > 0)) { return; }
      var my = ++seq.quote;
      CH.api('/api/hosts/' + cfg.hostId + '/quote?' + qs({ hours: h, offering: st.offering, travel: travel() })).then(function (j) {
        if (my !== seq.quote) { return; }
        if (!j.ok) { st.quote = null; priceBox.innerHTML = '<p class="text-sm text-ink-3">' + CH.esc(j.error || 'No price available.') + '</p>'; sync(); return; }
        st.quote = j; renderPrice(); sync();
      });
    }
    function renderPrice() {
      var q = st.quote, l = q.label, d = q.quote;
      var rows = '<div><dt>' + CH.esc(offer() ? offer().name : T('Time with the Host')) + ' · ' + CH.esc(l.hours) + '</dt><dd>' + CH.esc(l.base) + '</dd></div>';
      if (d.travel_fee > 0) { rows += '<div><dt>Travel fee</dt><dd>' + CH.esc(l.travel) + '</dd></div>'; }
      rows += '<div class="is-total"><dt>Total</dt><dd>' + CH.esc(l.total) + '</dd></div>' +
        '<div class="is-due"><dt>Deposit due now (' + CH.esc(l.deposit_pct) + '%)</dt><dd>' + CH.esc(l.deposit) + '</dd></div>' +
        '<div><dt>' + T('Balance, paid to the Host at the meeting') + '</dt><dd>' + CH.esc(l.balance) + '</dd></div>';
      priceBox.innerHTML = '<dl class="hprice-list">' + rows + '</dl>';
      var pct = $('[data-fee-pct]', form), amt = $('[data-fee-amount]', form), host = $('[data-fee-host]', form);
      if (pct) { pct.textContent = l.commission_pct; }
      if (amt) { amt.textContent = 'which is ' + l.commission + ' on this booking'; }
      if (host) { host.textContent = T(' (the Host earns ') + l.host_earning + ')'; }
    }
    on(inArea, 'change', function () { loadQuote(); sync(); });
    on(inVenue, 'input', sync);
    on(inAccept, 'change', sync);

    /* ---- summary and submit button ---- */
    function sync() {
      sum.date.textContent = st.date ? longDate(st.date) : 'Choose a date';
      var slot = null; (st.slots || []).forEach(function (s) { if (s.start === st.time) { slot = s; } });
      sum.time.textContent = slot ? slot.label + ' to ' + (slot.end_label || slot.end) + (slot.next_day ? ' (+1 day)' : '') : 'Choose a time';
      var venue = inVenue ? inVenue.value.trim() : '';
      var need = '';
      if (!offer() && !cfg.customOk) { need = 'Choose a service'; }
      else if (!st.date) { need = 'Choose a date'; }
      else if (!st.time) { need = 'Pick a start time'; }
      else if (!inArea.value) { need = 'Choose an area'; }
      else if (venue.length < 3) { need = 'Add the venue'; }
      else if (!st.quote) { need = 'Working out the price'; }
      else if (!inAccept.checked) { need = T('Agree to the Host rules'); }
      submit.disabled = !!need;
      submitLabel.textContent = need || ('Continue to pay ' + st.quote.label.deposit + ' deposit');
    }

    syncService();
    inDate.value = st.date; inStart.value = st.time;
    sync();
    loadDays(); loadQuote();
  }

  /* ------------------------------------------------------------------ service picker: search, count and cap */
  $$('[data-svc-picker]').forEach(function (box) {
    var max = parseInt(box.getAttribute('data-max'), 10) || 99;
    var counter = $('[data-svc-count]', box), search = $('[data-svc-search]', box), empty = $('[data-svc-empty]', box);
    function boxes() { return $$('input[type=checkbox]', box); }
    function update() {
      var on = boxes().filter(function (b) { return b.checked; }).length;
      if (counter) counter.textContent = on;
      boxes().forEach(function (b) { b.disabled = !b.checked && on >= max; var card = b.closest('.svc-chip, .svcp-card'); if (card) card.classList.toggle('is-off', b.disabled); });
    }
    function filter() {
      var q = (search.value || '').trim().toLowerCase(), any = false;
      $$('[data-svc-group]', box).forEach(function (g) {
        var shown = 0;
        $$('.svc-chip, .svcp-card', g).forEach(function (c) { var hit = !q || (c.getAttribute('data-svc-name') || '').indexOf(q) !== -1; c.hidden = !hit; if (hit) shown++; });
        g.hidden = shown === 0; if (shown) any = true;
      });
      if (empty) empty.hidden = any;
    }
    box.addEventListener('change', update);
    if (search) search.addEventListener('input', filter);
    update();
  });

  /* ------------------------------------------------------------------ textarea counters on load */
  $$('[data-counter]').forEach(function (el) {
    var t = $(el.getAttribute('data-counter')); if (t) { t.textContent = el.value.length + ' / ' + (el.maxLength > 0 ? el.maxLength : ''); }
  });
})();
