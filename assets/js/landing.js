/* Landing page: hero slider, parallax, location finder. Everything degrades to plain links without JS. */
/* Group tabs (New faces): arrow keys and clicks switch the panel. */
(function () {
  'use strict';
  [].slice.call(document.querySelectorAll('[data-lp-tabs]')).forEach(function (bar) {
    var tabs = [].slice.call(bar.querySelectorAll('[role="tab"]'));
    function pick(i, focus) {
      tabs.forEach(function (t, n) {
        var on = n === i, panel = document.getElementById(t.getAttribute('aria-controls'));
        t.classList.toggle('is-on', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1;
        if (panel) { panel.hidden = !on; }
      });
      if (focus) { tabs[i].focus(); }
    }
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { pick(i, false); });
      t.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { e.preventDefault(); pick((i + 1) % tabs.length, true); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); pick((i + tabs.length - 1) % tabs.length, true); }
      });
    });
  });
})();
/* Sliders on phones (articles): dots that follow the scroll position and jump to a card when tapped. */
(function () {
  'use strict';
  var mq = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;
  [].slice.call(document.querySelectorAll('[data-slider]')).forEach(function (el) {
    var cards = [].slice.call(el.children);
    if (cards.length < 2) return;
    var wrap = document.createElement('div');
    wrap.className = 'slider-dots';
    var dots = cards.map(function (c, i) {
      var b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', 'Go to article ' + (i + 1));
      b.addEventListener('click', function () { el.scrollTo({ left: c.offsetLeft - el.offsetLeft - parseFloat(getComputedStyle(el).paddingLeft || 0), behavior: 'smooth' }); });
      wrap.appendChild(b);
      return b;
    });
    el.parentNode.insertBefore(wrap, el.nextSibling);
    var on = function () {
      if (mq && !mq.matches) return;
      var best = 0, min = Infinity, left = el.scrollLeft;
      cards.forEach(function (c, i) { var d = Math.abs(c.offsetLeft - el.offsetLeft - parseFloat(getComputedStyle(el).paddingLeft || 0) - left); if (d < min) { min = d; best = i; } });
      if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 4) best = cards.length - 1;
      dots.forEach(function (d, i) { d.classList.toggle('is-on', i === best); });
    };
    var t;
    el.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(on, 40); }, { passive: true });
    window.addEventListener('resize', on);
    on();
  });
})();

(function () {
  'use strict';
  var hero = document.querySelector('[data-hero]');
  if (!hero) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var respect = document.documentElement.getAttribute('data-respect-motion') !== '0';

  /* ---- Slider ---------------------------------------------------------- */
  var slides = [].slice.call(hero.querySelectorAll('.hero-slide'));

  /* A slide can carry its own headline, sub-headline and button (data-copy, set under Admin > Hero and landing). */
  var elH = hero.querySelector('.hero-title'), elS = hero.querySelector('.hero-sub'), elC = hero.querySelector('.hero-cta .btn-primary');
  var base = { h: elH ? elH.textContent : '', s: elS ? elS.textContent : '', c: elC ? elC.textContent : '', u: elC ? elC.getAttribute('href') : '', a: elC ? elC.getAttribute('data-auth-open') : null };
  var applyCopy = function (slide) {
    var c = {};
    try { c = JSON.parse(slide.getAttribute('data-copy') || '{}') || {}; } catch (x) { c = {}; }
    if (elH) elH.textContent = c.h || base.h;
    if (elS) elS.textContent = c.s || base.s;
    if (elC) {
      elC.textContent = c.c || base.c;
      var u = c.u || base.u;
      if (c.u && /^\//.test(c.u) && window.CH && CH.url) u = CH.url(c.u);
      elC.setAttribute('href', u);
      if (c.u && base.a) elC.removeAttribute('data-auth-open'); else if (base.a) elC.setAttribute('data-auth-open', base.a);
    }
  };
  if (slides.length && slides[0].hasAttribute('data-copy')) applyCopy(slides[0]);
  if (slides.length > 1) {
    var dots = [].slice.call(hero.querySelectorAll('[data-hero-dots] button'));
    var speed = Math.max(2, parseInt(hero.getAttribute('data-speed'), 10) || 6) * 1000;
    var pauseOnHover = hero.getAttribute('data-pause') === '1';
    var current = 0, timer = null, hovering = false;

    var show = function (n) {
      n = (n + slides.length) % slides.length;
      if (n === current) return;
      slides[current].classList.remove('is-active', 'is-prev');
      slides[current].classList.add('is-prev');
      var old = slides[current];
      setTimeout(function () { old.classList.remove('is-prev'); }, 1400);
      slides[n].classList.add('is-active');
      applyCopy(slides[n]);
      if (dots.length) {
        dots[current].classList.remove('is-active');
        dots[n].classList.add('is-active');
      }
      current = n;
    };
    var start = function () {
      stop();
      if (reduce && respect) return;
      timer = setInterval(function () { if (!hovering && !document.hidden) show(current + 1); }, speed);
    };
    var stop = function () { if (timer) { clearInterval(timer); timer = null; } };

    dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i); start(); }); });
    if (pauseOnHover) {
      hero.addEventListener('mouseenter', function () { hovering = true; });
      hero.addEventListener('mouseleave', function () { hovering = false; });
    }
    start();
  }

  /* ---- Parallax -------------------------------------------------------- */
  var px = hero.querySelector('[data-parallax]');
  if (px && !(reduce && respect)) {
    var ticking = false;
    var move = function () {
      ticking = false;
      var y = window.scrollY || 0;
      if (y > hero.offsetHeight + 80) return;
      px.style.transform = 'translate3d(0,' + Math.round(y * 0.28) + 'px,0)';
    };
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(move); } }, { passive: true });
  }

  /* ---- Location finder -------------------------------------------------- */
  var finder = hero.querySelector('[data-finder]');
  if (!finder) return;
  var form = finder.querySelector('[data-finder-form]');
  var select = finder.querySelector('[data-finder-select]');

  if (select) {
    select.addEventListener('submit', function (e) {
      var v = select.querySelector('select').value;
      e.preventDefault();
      window.location.href = v ? CH.url('/browse/' + encodeURIComponent(v)) : CH.url('/browse');
    });
  }

  var locate = finder.querySelector('[data-finder-locate]');
  if (locate) {
    locate.addEventListener('click', function () {
      if (!window.CH || !CH.locate) { return; }
      locate.disabled = true;
      locate.classList.add('is-loading');
      CH.locate().then(function (j) {
        if (j && j.ok && j.url) { window.location.href = j.url; return; }
        locate.disabled = false; locate.classList.remove('is-loading');
        if (CH.toast) CH.toast((j && (j.error || j.message)) || 'We could not find your location. Pick your town instead.', 'error');
      });
    });
  }

  if (!form) return;
  var input = form.querySelector('[data-finder-input]');
  var list = form.querySelector('[data-finder-list]');
  var items = [], active = -1, ctrl = null, t = null;

  var close = function () { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; input.removeAttribute('aria-activedescendant'); };
  var render = function (rows) {
    items = rows;
    list.innerHTML = '';
    if (!rows.length) {
      var empty = document.createElement('li');
      empty.className = 'is-empty';
      empty.textContent = 'No match. Try another town or county.';
      list.appendChild(empty);
    }
    rows.forEach(function (r, i) {
      var li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.id = 'finder-opt-' + i;
      li.className = 'finder-opt';
      var a = document.createElement('a');
      a.href = r.url;
      a.tabIndex = -1;
      var name = document.createElement('strong'); name.textContent = r.name;
      var meta = document.createElement('small'); meta.textContent = r.type === 'county' ? 'County' : (r.county || 'Town');
      a.appendChild(name); a.appendChild(meta);
      li.appendChild(a);
      list.appendChild(li);
    });
    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    active = -1;
  };
  var highlight = function (n) {
    var opts = list.querySelectorAll('.finder-opt');
    if (!opts.length) return;
    active = (n + opts.length) % opts.length;
    opts.forEach(function (o, i) { o.classList.toggle('is-active', i === active); o.setAttribute('aria-selected', i === active ? 'true' : 'false'); });
    input.setAttribute('aria-activedescendant', opts[active].id);
  };
  var search = function (q) {
    if (ctrl) ctrl.abort();
    ctrl = window.AbortController ? new AbortController() : null;
    fetch(CH.url('/api/locations') + (q ? '?q=' + encodeURIComponent(q) : ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j && j.results) render(j.results); })
      .catch(function () {});
  };

  input.addEventListener('input', function () {
    clearTimeout(t);
    var q = input.value.trim();
    if (q.length < 2) { if (q.length === 0) { close(); } return; }
    t = setTimeout(function () { search(q); }, 160);
  });
  input.addEventListener('focus', function () { if (!input.value.trim() && list.hidden) search(''); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) { search(input.value.trim()); } else { highlight(active + 1); } }
    else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(active - 1); }
    else if (e.key === 'Escape') { close(); }
    else if (e.key === 'Enter' && active > -1 && items[active]) { e.preventDefault(); window.location.href = items[active].url; }
  });
  document.addEventListener('click', function (e) { if (!form.contains(e.target)) close(); });
  form.addEventListener('submit', function (e) {
    // a typed name that matches exactly one place goes straight to it; otherwise plain search on Browse
    if (active > -1 && items[active]) { e.preventDefault(); window.location.href = items[active].url; return; }
    var q = input.value.trim();
    if (q && items.length === 1) { e.preventDefault(); window.location.href = items[0].url; }
  });
})();

/* ---------------------------------------------------------------------------
   Browse by place. On a phone the places sit in up to three rows. Each row drifts
   slowly, the opposite way to the row above it, and loops without a seam. Every
   row is an ordinary scroller, so a swipe moves it by hand; touching a row, hovering
   it with a mouse or tabbing into it holds the drift, and a button pauses all of it.
   Visitors who asked for less motion get plain swipeable rows with no drift.
   Wider screens keep the grid, so nothing here is built for them.
   --------------------------------------------------------------------------- */
(function () {
  'use strict';
  var wrap = document.querySelector('[data-loc-rail]');
  if (!wrap) return;
  var els = [].slice.call(wrap.querySelectorAll('[data-loc-row]'));
  if (!els.length || !window.matchMedia) return;

  var CH = window.CH || {};
  var mq = window.matchMedia('(max-width: 639px)');
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches || !!(CH.reduced && CH.reduced());
  var btn = document.querySelector('[data-loc-pause]');
  var KEY = 'ch_loc_paused';
  var SPEED = [24, 29, 26]; // smooth, independently paced pixels-per-second speeds

  var userPaused = false;
  try { userPaused = localStorage.getItem(KEY) === '1'; } catch (e) { /* private window: the pause lasts for this visit only */ }

  var rows = els.map(function (el, i) {
    return { el: el, dir: el.getAttribute('data-dir') === '-1' ? -1 : 1, speed: SPEED[i % SPEED.length] / 1000, set: 0, k: 1, pos: 0, idle: 0, resync: true, drifting: false, timer: 0, idx: i };
  });

  function clones(r) { return [].slice.call(r.el.querySelectorAll('[data-loc-clone]')); }

  /** Put identical copies of the row on both sides of it so that it can loop for ever. */
  function build(r) {
    teardown(r);
    // A flex item in a vertical rail can otherwise expand to its max-content
    // width. That makes scrollLeft appear to work while the row itself has no
    // usable viewport, so lock each row to the rail's visible width first.
    r.el.style.width = '100%';
    r.el.style.minWidth = '0';
    r.el.style.boxSizing = 'border-box';
    r.el.style.scrollBehavior = 'auto';
    var items = [].slice.call(r.el.children);
    if (!items.length) return;
    var gap = parseFloat(getComputedStyle(r.el).columnGap) || 10;
    // Measure each card in the row's own coordinate space. offsetLeft may be
    // relative to an ancestor and can produce an incorrect cycle width.
    var set = items.reduce(function (sum, item) { return sum + item.getBoundingClientRect().width; }, 0)
      + gap * Math.max(0, items.length - 1);
    if (set < 40 || r.el.clientWidth < 1) return;
    // Keep at least one full viewport of matching content on both sides.
    var k = Math.max(1, Math.ceil(r.el.clientWidth / set) + 1);
    function copy() {
      var f = document.createDocumentFragment();
      items.forEach(function (it) {
        var c = it.cloneNode(true);
        c.setAttribute('data-loc-clone', '');
        c.setAttribute('aria-hidden', 'true');
        c.setAttribute('tabindex', '-1');
        f.appendChild(c);
      });
      return f;
    }
    var before = document.createDocumentFragment(), after = document.createDocumentFragment();
    for (var n = 0; n < k; n++) { before.appendChild(copy()); after.appendChild(copy()); }
    var first = r.el.firstChild;
    r.el.insertBefore(before, first);
    r.el.appendChild(after);
    r.set = set; r.k = k;
    // Start on the real cards, offset each row slightly so the rows don't
    // look synchronised. Force layout after clones are inserted before setting
    // scrollLeft; this avoids browsers clamping the initial position.
    void r.el.scrollWidth;
    var offset = set > 1 ? ((r.idx * 53) % Math.max(1, Math.floor(set))) : 0;
    var start = k * set + offset;
    r.el.scrollLeft = start;
    r.pos = start; r.resync = true;
  }

  function teardown(r) {
    clones(r).forEach(function (c) { c.parentNode.removeChild(c); });
    r.set = 0; r.drifting = false;
    r.el.classList.remove('is-drift');
  }

  /** Keep the row inside the middle copy. The copies look the same, so the jump cannot be seen. */
  function normalise(r) {
    if (!r.set) return;
    var lo = r.k * r.set, hi = lo + r.set, x = r.el.scrollLeft;
    if (x >= hi) { r.el.scrollLeft = x - r.set * Math.floor((x - lo) / r.set); }
    else if (x < lo) { r.el.scrollLeft = x + r.set * Math.ceil((lo - x) / r.set); }
    r.pos = r.el.scrollLeft; r.resync = true;
  }

  function hold(r, ms) {
    r.idle = performance.now() + (ms || 6000);
    r.resync = true;
    if (r.drifting) { r.drifting = false; r.el.classList.remove('is-drift'); }
  }

  rows.forEach(function (r) {
    ['pointerdown', 'wheel', 'touchstart', 'keydown'].forEach(function (ev) {
      r.el.addEventListener(ev, function () { hold(r, 7000); }, { passive: true });
    });
    r.el.addEventListener('scroll', function () {
      if (r.drifting || !r.set) return;
      clearTimeout(r.timer);
      r.timer = setTimeout(function () { normalise(r); }, 140);
    }, { passive: true });
  });

  var inView = true, hover = false, focusIn = false;
  wrap.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') hover = true; });
  wrap.addEventListener('pointerleave', function () { hover = false; kick(); });
  wrap.addEventListener('focusin', function () { focusIn = true; });
  wrap.addEventListener('focusout', function () { focusIn = false; kick(); });
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (en) { inView = en[0].isIntersecting; kick(); }, { threshold: 0.15 }).observe(wrap);
  }
  document.addEventListener('visibilitychange', kick);

  function paintButton() {
    if (!btn) return;
    btn.classList.toggle('is-paused', userPaused);
    var t = userPaused ? 'Play moving places' : 'Pause moving places';
    btn.setAttribute('aria-label', t);
    btn.title = t;
  }
  if (btn) {
    btn.addEventListener('click', function () {
      userPaused = !userPaused;
      try { localStorage.setItem(KEY, userPaused ? '1' : '0'); } catch (e) { /* kept for this visit only */ }
      paintButton();
      rows.forEach(function (r) { r.resync = true; });
      kick();
    });
    paintButton();
  }

  var running = false, last = 0;
  function active() { return mq.matches && !reduce && inView && !document.hidden && !userPaused && !hover && !focusIn; }

  function tick(t) {
    var dt = last ? Math.min(64, t - last) : 16; last = t;
    if (!active()) { running = false; last = 0; rows.forEach(function (r) { if (r.drifting) { r.drifting = false; r.el.classList.remove('is-drift'); } }); return; }
    rows.forEach(function (r) {
      if (!r.set || t < r.idle) { if (r.drifting) { r.drifting = false; r.el.classList.remove('is-drift'); } return; }
      if (r.resync) { normalise(r); r.resync = false; }
      if (!r.drifting) { r.drifting = true; r.el.classList.add('is-drift'); }
      var lo = r.k * r.set, hi = lo + r.set;
      r.pos += r.dir * r.speed * dt;
      if (r.pos >= hi) r.pos -= r.set;
      if (r.pos < lo) r.pos += r.set;
      r.el.scrollLeft = r.pos;
    });
    requestAnimationFrame(tick);
  }
  function kick() {
    if (running || !active()) return;
    running = true; last = 0;
    requestAnimationFrame(tick);
  }

  var builtWidth = 0;
  function sync() {
    var on = mq.matches;
    if (on && !reduce) {
      // Rebuild after the browser has applied mobile layout dimensions.
      rows.forEach(build);
      builtWidth = wrap.clientWidth;
    } else {
      rows.forEach(function (r) { teardown(r); r.el.scrollLeft = 0; });
    }
    if (btn) btn.hidden = !(on && !reduce);
    kick();
  }

  if (mq.addEventListener) mq.addEventListener('change', sync); else if (mq.addListener) mq.addListener(sync);
  var rt = 0;
  window.addEventListener('resize', function () {
    clearTimeout(rt);
    rt = setTimeout(function () { if (mq.matches && !reduce && Math.abs(wrap.clientWidth - builtWidth) > 24) sync(); }, 250);
  });
  sync();
  // Some mobile browsers report zero/stale widths during the first script
  // pass. Re-measure after two paint frames and after fonts settle.
  requestAnimationFrame(function () { requestAnimationFrame(function () { if (mq.matches && !reduce) sync(); }); });
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { if (mq.matches && !reduce) sync(); });
})();
