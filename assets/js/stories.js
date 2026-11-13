/* Story strip: the round profile bubbles (landing page, Hosts directory) and the full-screen story viewer.
   Without JavaScript the bubbles are ordinary links, so nothing here is required to reach a profile.
   Settings (seconds per story, drift, viewer on or off) come from data attributes set by the server. */
(function () {
  'use strict';
  var strips = [].slice.call(document.querySelectorAll('[data-stories]'));
  if (!strips.length) return;

  var CH = window.CH || {};
  var reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) || (CH.reduced && CH.reduced());
  var SEEN_KEY = 'ch_story_seen';
  var SEEN_FOR = 12 * 3600 * 1000; // a ring comes back after 12 hours

  function seenMap() { try { return JSON.parse(localStorage.getItem(SEEN_KEY) || '{}') || {}; } catch (e) { return {}; } }
  function isSeen(key) { var t = seenMap()[key]; return !!t && Date.now() - t < SEEN_FOR; }
  function markSeen(key) {
    try {
      var m = seenMap(), keys = Object.keys(m);
      if (keys.length > 200) { keys.sort(function (a, b) { return m[a] - m[b]; }).slice(0, 50).forEach(function (k) { delete m[k]; }); }
      m[key] = Date.now();
      localStorage.setItem(SEEN_KEY, JSON.stringify(m));
    } catch (e) { /* private mode or blocked storage: the ring just stays coloured */ }
  }
  function paintSeen() {
    strips.forEach(function (s) {
      [].forEach.call(s.querySelectorAll('.sty-btn'), function (b) {
        var li = b.closest('.sty-item');
        if (!li || b.dataset.storyKey === 'join') return;
        var seen = isSeen(b.dataset.storyKey);
        if (!seen) {
          try {
            var story = JSON.parse(b.dataset.story || '{}');
            if (story.slides && story.slides.length) {
              seen = story.slides.every(function (slide) { return isSeen(b.dataset.storyKey + '-' + slide.id); });
            }
          } catch (e) { /* keep the normal profile ring */ }
        }
        li.classList.toggle('is-seen', seen);
      });
    });
  }
  paintSeen();

  /* ------------------------------------------------------------ strip drift: a slow automatic scroll that yields to the visitor */
  function drift(root) {
    var row = root.querySelector('.sty-row');
    if (!row || root.dataset.drift !== '1' || reduce) return;
    var pos = 0, dir = 1, hold = 1500, last = 0, idleUntil = 0, resync = true, inView = true, hover = false;
    function max() { return row.scrollWidth - row.clientWidth; }
    function poke() { idleUntil = performance.now() + 6000; resync = true; }
    ['pointerdown', 'wheel', 'touchstart', 'keydown'].forEach(function (ev) { row.addEventListener(ev, poke, { passive: true }); });
    row.addEventListener('mouseenter', function () { hover = true; });
    row.addEventListener('mouseleave', function () { hover = false; poke(); });
    row.addEventListener('focusin', poke); // focus moves (keyboard, or back from the viewer): hold still for a few seconds, then carry on
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { inView = en[0].isIntersecting; }, { threshold: 0.2 }).observe(root);
    }
    function tick(t) {
      var dt = last ? Math.min(64, t - last) : 16; last = t;
      if (!document.hidden && inView && !hover && !document.body.classList.contains('sty-open') && t > idleUntil && max() > 8) {
        if (resync) { pos = row.scrollLeft; resync = false; }
        if (hold > 0) {
          hold -= dt;
        } else {
          pos += dir * 0.024 * dt; // about 24 pixels a second
          if (pos >= max()) { pos = max(); dir = -1; hold = 1600; }
          if (pos <= 0) { pos = 0; dir = 1; hold = 1600; }
          row.scrollLeft = pos;
        }
      }
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  strips.forEach(drift);

  /* ------------------------------------------------------------ full-screen viewer */
  var view = null;       // the DOM, built once
  var list = [];         // stories in the strip that was tapped
  var idx = 0, startedAt = 0, elapsed = 0, held = false, raf = 0, lastFocus = null, sprite = '', viewUrl = '', secs = 5;
  var counted = {};      // adverts already counted this page view

  function ico(name, cls) { return '<svg class="icon ' + (cls || 'w-5 h-5') + '" aria-hidden="true"><use href="' + sprite + '#i-' + name + '"></use></svg>'; }
  function esc(s) { return CH.esc ? CH.esc(s) : String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function build() {
    if (view) return;
    view = document.createElement('div');
    view.className = 'sty-view';
    view.hidden = true;
    view.setAttribute('role', 'dialog');
    view.setAttribute('aria-modal', 'true');
    view.setAttribute('aria-label', 'Stories');
    view.innerHTML =
      '<div class="sty-view-bg" data-sty-close></div>' +
      '<div class="sty-card">' +
        '<div class="sty-bars" aria-hidden="true"></div>' +
        '<div class="sty-top">' +
          '<div class="sty-who"><span class="sty-who-av"><img alt="" width="40" height="40"></span>' +
            '<div class="sty-who-t"><strong></strong><small></small></div><span class="sty-pill" hidden></span></div>' +
          '<button type="button" class="sty-x" data-sty-close aria-label="Close stories">' + ico('x', 'w-6 h-6') + '</button>' +
        '</div>' +
        '<div class="sty-pics"><img class="sty-backdrop" alt="" aria-hidden="true"><img class="sty-pic" alt=""><video class="sty-video" playsinline muted preload="metadata" hidden></video><span class="sty-pic-init" hidden></span></div>' +
        '<span class="sty-shade" aria-hidden="true"></span>' +
        '<button type="button" class="sty-nav sty-prev" aria-label="Previous story"></button>' +
        '<button type="button" class="sty-nav sty-next" aria-label="Next story"></button>' +
        '<div class="sty-info">' +
          '<span class="sty-promo" hidden></span>' +
          '<p class="sty-head"></p><p class="sty-bio"></p>' +
          '<ul class="sty-chips"></ul>' +
          '<p class="sty-meta"></p>' +
          '<div class="sty-actions"><a class="btn btn-primary sty-cta"></a>' +
            '<a class="sty-circ sty-tel" hidden aria-label="Call">' + ico('phone', 'w-5 h-5') + '</a>' +
            '<a class="sty-circ sty-wa" hidden target="_blank" rel="noopener" aria-label="WhatsApp">' + ico('message-circle', 'w-5 h-5') + '</a></div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(view);

    view.addEventListener('click', function (e) {
      if (e.target.closest('[data-sty-close]')) { close(); return; }
      if (e.target.closest('.sty-prev')) { go(idx - 1); return; }
      if (e.target.closest('.sty-next')) { go(idx + 1); return; }
    });
    // hold a finger or the mouse button on the card to pause
    var card = view.querySelector('.sty-card');
    function hold(on) { if (on === held) return; held = on; card.classList.toggle('is-held', on); if (!on) { startedAt = performance.now() - elapsed; } }
    card.addEventListener('pointerdown', function (e) { if (e.target.closest('a,button.sty-x')) return; hold(true); });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) { card.addEventListener(ev, function () { hold(false); }); });
    // swipe down to close, swipe sideways to move
    var sx = 0, sy = 0;
    card.addEventListener('touchstart', function (e) { var t = e.touches[0]; sx = t.clientX; sy = t.clientY; }, { passive: true });
    card.addEventListener('touchend', function (e) {
      var t = e.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy;
      if (dy > 90 && Math.abs(dy) > Math.abs(dx) * 1.5) { close(); }
      else if (Math.abs(dx) > 70 && Math.abs(dx) > Math.abs(dy) * 1.5) { go(dx < 0 ? idx + 1 : idx - 1); }
    }, { passive: true });
    document.addEventListener('keydown', function (e) {
      if (view.hidden) return;
      if (e.key === 'Escape') { e.preventDefault(); close(); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); go(idx + 1); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); go(idx - 1); }
      else if (e.key === ' ' && !e.target.closest('a,button')) { e.preventDefault(); hold(!held); }
      else if (e.key === 'Tab') { // keep focus inside the viewer
        var f = [].slice.call(view.querySelectorAll('a[href]:not([hidden]),button:not([hidden])')).filter(function (n) { return n.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], lastEl = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); lastEl.focus(); }
        else if (!e.shiftKey && document.activeElement === lastEl) { e.preventDefault(); first.focus(); }
      }
    });
  }

  function show(i) {
    var s = list[i].v;
    var card = view.querySelector('.sty-card');
    card.dataset.kind = s.k || 'agent';
    card.dataset.tier = s.tier_k || '';
    // progress bars
    var bars = view.querySelector('.sty-bars');
    bars.innerHTML = list.map(function (_, n) { return '<i class="' + (n < i ? 'is-done' : '') + '"><b></b></i>'; }).join('');
    // who
    var av = view.querySelector('.sty-who-av img'), pic = view.querySelector('.sty-pic'), vid = view.querySelector('.sty-video'), init = view.querySelector('.sty-pic-init'), backdrop = view.querySelector('.sty-backdrop');
    var src = s.src || s.img || '';
    if (vid) { vid.pause(); vid.removeAttribute('src'); vid.load(); vid.hidden = true; }
    if (backdrop) { backdrop.hidden = true; backdrop.removeAttribute('src'); }
    if (s.type === 'video' && src && vid) {
      av.hidden = false; av.src = s.thumb || s.img || ''; pic.hidden = true; pic.removeAttribute('src');
      vid.hidden = false; vid.src = src; vid.currentTime = 0; vid.play().catch(function () {}); init.hidden = true;
    } else if (src) { av.hidden = false; av.src = s.thumb || src; pic.hidden = false; pic.src = src; if (backdrop) { backdrop.src = src; backdrop.hidden = false; } init.hidden = true; }
    else { av.hidden = true; pic.hidden = true; pic.removeAttribute('src'); if (backdrop) { backdrop.hidden = true; backdrop.removeAttribute('src'); } init.hidden = false; init.textContent = s.k === 'join' ? '' : (s.name || '?').charAt(0).toUpperCase(); }
    card.classList.toggle('is-blur', !!s.blur);
    card.style.setProperty('--sty-s', secs + 's');
    // restart the entrance animations for each story
    [pic, view.querySelector('.sty-info')].forEach(function (n) { n.classList.remove('is-anim'); void n.offsetWidth; n.classList.add('is-anim'); });
    view.querySelector('.sty-who-t strong').innerHTML = esc(s.name) + (s.verified ? ' ' + ico('badge-check', 'w-4 h-4 sty-ver') : '');
    view.querySelector('.sty-who-t small').textContent = s.place || (s.k === 'join' ? '' : (s.k === 'ad' ? '' : ''));
    var pill = view.querySelector('.sty-pill');
    pill.hidden = !s.tier; pill.textContent = s.tier || ''; pill.className = 'sty-pill' + (s.tier_k ? ' sty-pill--' + s.tier_k : '');
    // body
    view.querySelector('.sty-head').textContent = s.caption || s.headline || '';
    var promo = view.querySelector('.sty-promo'); promo.hidden = !s.promo; promo.innerHTML = s.promo ? ico('sparkles', 'w-3.5 h-3.5') + 'Promotion' : '';
    var bio = view.querySelector('.sty-bio'); bio.textContent = s.bio || ''; bio.hidden = !s.bio;
    var chips = view.querySelector('.sty-chips');
    chips.innerHTML = (s.services || []).map(function (c) { return '<li>' + esc(c) + '</li>'; }).join('');
    chips.hidden = !(s.services && s.services.length);
    var meta = [];
    if (s.rating) meta.push(ico('star', 'w-4 h-4 sty-star') + '<strong>' + esc(s.rating) + '</strong>');
    if (s.price) meta.push('<span>From <strong>' + esc(s.price) + '</strong></span>');
    var mEl = view.querySelector('.sty-meta'); mEl.innerHTML = meta.join('<span class="sty-dot" aria-hidden="true"></span>'); mEl.hidden = !meta.length;
    var cta = view.querySelector('.sty-cta');
    cta.textContent = s.cta || 'View profile'; cta.href = s.href || '#';
    if (s.k === 'ad') { cta.target = '_blank'; cta.rel = 'sponsored noopener'; } else { cta.removeAttribute('target'); cta.removeAttribute('rel'); }
    var tel = view.querySelector('.sty-tel'), wa = view.querySelector('.sty-wa');
    tel.hidden = !s.tel; if (s.tel) tel.href = s.tel;
    wa.hidden = !s.wa; if (s.wa) wa.href = s.wa;
    view.querySelector('.sty-prev').disabled = i === 0;
    // restart the timer
    elapsed = 0; startedAt = performance.now();
    markSeen(list[i].key); paintSeen();
    if (s.story_id && viewUrl && CH.api) { CH.api(viewUrl, { data: { story_id: s.story_id }, quiet: true }); }
    if (s.k === 'ad' && s.id && !counted[s.id] && viewUrl && CH.api) { counted[s.id] = 1; CH.api(viewUrl, { data: { id: s.id }, quiet: true }); }
    // keep the strip's tapped bubble in sight behind the viewer
    var opener = list[i].el; if (opener && opener.scrollIntoView) { try { opener.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'auto' }); } catch (e) { /* older browsers */ } }
  }

  function loop(t) {
    raf = requestAnimationFrame(loop);
    if (view.hidden || held || document.hidden) { startedAt = t - elapsed; return; }
    elapsed = t - startedAt;
    var vid = view.querySelector('.sty-video');
    var duration = secs;
    if (vid && !vid.hidden && Number.isFinite(vid.duration) && vid.duration > 0) duration = Math.min(30, Math.max(secs, vid.duration));
    var p = Math.min(1, elapsed / (duration * 1000));
    var cur = view.querySelectorAll('.sty-bars i')[idx];
    if (cur) cur.firstChild.style.transform = 'scaleX(' + p + ')';
    if (p >= 1) { if (idx >= list.length - 1) close(); else go(idx + 1); }
  }

  function go(i) {
    if (i < 0) { i = 0; }
    if (i >= list.length) { close(); return; }
    idx = i; show(i);
  }

  function open(strip, startAt) {
    build();
    sprite = strip.dataset.sprite || ''; viewUrl = strip.dataset.viewUrl || ''; secs = Math.max(3, parseInt(strip.dataset.seconds, 10) || 5);
    list = [];
    var startIndex = 0, bubbleIndex = 0;
    [].slice.call(strip.querySelectorAll('.sty-btn')).forEach(function (b) {
      if (bubbleIndex === startAt) startIndex = list.length;
      bubbleIndex++;
      var v = {}; try { v = JSON.parse(b.dataset.story || '{}'); } catch (e) { /* skip */ }
      if (v.slides && v.slides.length) {
        v.slides.forEach(function (slide) {
          list.push({ v: Object.assign({}, v, slide, { story_id: slide.id, caption: slide.caption || '', type: slide.type || 'image' }), key: (b.dataset.storyKey || '') + '-' + slide.id, el: b });
        });
      } else {
        list.push({ v: v, key: b.dataset.storyKey || '', el: b });
      }
    });
    if (!list.length) return;
    lastFocus = document.activeElement;
    view.hidden = false;
    document.body.classList.add('sty-open');
    requestAnimationFrame(function () { view.classList.add('is-in'); });
    held = false;
    idx = Math.max(0, Math.min(list.length - 1, startIndex)); show(idx);
    cancelAnimationFrame(raf); raf = requestAnimationFrame(loop);
    var x = view.querySelector('.sty-x'); if (x) x.focus({ preventScroll: true });
  }

  function close() {
    if (!view || view.hidden) return;
    view.classList.remove('is-in');
    cancelAnimationFrame(raf);
    var done = function () { var v = view.querySelector('.sty-video'); if (v) { v.pause(); v.removeAttribute('src'); v.load(); } view.hidden = true; document.body.classList.remove('sty-open'); };
    if (reduce) done(); else setTimeout(done, 200);
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) { /* element gone */ } }
  }

  strips.forEach(function (strip) {
    if (strip.dataset.viewer !== '1') return;
    strip.addEventListener('click', function (e) {
      var b = e.target.closest('.sty-btn');
      if (!b || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
      e.preventDefault();
      var all = [].slice.call(strip.querySelectorAll('.sty-btn'));
      open(strip, all.indexOf(b));
    });
  });
})();
