/* ConnectHub Explorer: swipe deck, Discover feeds, quick actions, profile gallery, photo manager,
   onboarding and settings helpers. Vanilla JS on top of window.CH (assets/js/app.js). The server renders
   every page; this file adds the behaviour. Everything is optional: pages still work as plain forms and links. */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH;
  if (!CH) { return; }
  var $ = CH.$, $$ = CH.$$;
  var SPRITE = CH.url('/assets/icons/sprite.svg');

  /* ------------------------------------------------------------------ helpers */
  function ico(name, cls) { return '<svg class="icon ' + (cls || 'w-4 h-4') + '" aria-hidden="true"><use href="' + SPRITE + '#i-' + name + '"></use></svg>'; }
  function esc(s) { return CH.esc(s); }
  function catSlug(c) { return String(c || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'general'; }
  // interest picture: its icon in a tile coloured by category (same markup as interest_icon_html() in PHP)
  function intIcon(n) {
    var ic = n.icon && /^[a-z0-9-]+$/.test(n.icon) ? n.icon : '';
    if (ic) return '<span class="int-ic int-xs" data-cat="' + catSlug(n.cat) + '" aria-hidden="true">' + ico(ic, 'int-svg') + '</span>';
    if (n.emoji) return '<span class="int-ic int-xs is-emoji" data-cat="' + catSlug(n.cat) + '" aria-hidden="true">' + esc(n.emoji) + '</span>';
    return '<span class="int-ic int-xs" data-cat="general" aria-hidden="true">' + ico('sparkles', 'int-svg') + '</span>';
  }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function ready(fn) { if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', fn); } else { fn(); } }
  function post(url, data) { return CH.api(url, { data: data || {} }); }
  function readJson(sel, root) {
    var el = $(sel, root || doc);
    if (!el) { return null; }
    try { return JSON.parse(el.textContent || 'null'); } catch (e) { return null; }
  }
  function reduced() { return CH.reduced(); }
  function store(key, val) { try { if (val === undefined) { return localStorage.getItem(key); } localStorage.setItem(key, val); } catch (e) { /* storage can be blocked */ } return null; }
  function qs(obj) {
    var out = [];
    Object.keys(obj).forEach(function (k) { if (obj[k] !== '' && obj[k] != null) { out.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k])); } });
    return out.join('&');
  }
  function plural(n, one, many) { return n + ' ' + (n === 1 ? one : (many || one + 's')); }
  function isTyping(t) { return !!(t && t.closest && t.closest('input, textarea, select, [contenteditable="true"]')); }
  function dialogOpen() { return !!$('dialog[open]') || !!$('.drawer.is-open'); }

  /* ------------------------------------------------------------------ shared dialogs */
  function showUpgrade(title, text, cta) {
    var d = $('#xupgrade');
    if (!d) { CH.toast(text || title || 'This is part of Premium.', 'info'); return; }
    var t = $('[data-u-title]', d), p = $('[data-u-text]', d), c = $('[data-u-cta]', d);
    if (t) { t.textContent = title || 'Go further with Premium'; }
    if (p) { p.textContent = text || ''; }
    if (c && cta) { c.textContent = cta; }
    CH.modal.open(d);
  }

  function showMatch(m) {
    var d = $('#xmatch');
    if (!d || !m) { return; }
    var mine = $('[data-m-mine]', d), theirs = $('[data-m-theirs]', d);
    if (mine && m.my_photo) { mine.src = m.my_photo; }
    if (theirs && m.photo) { theirs.src = m.photo; }
    var t = $('[data-m-title]', d); if (t) { t.textContent = m.title || 'It is a match!'; }
    var x = $('[data-m-text]', d); if (x) { x.textContent = 'You and ' + (m.name || 'they') + ' liked each other. Say hello while it is fresh.'; }
    var msg = $('[data-m-message]', d); if (msg && m.message_url) { msg.href = m.message_url; }
    var pr = $('[data-m-profile]', d); if (pr && m.profile_url) { pr.href = m.profile_url; }
    CH.modal.open(d);
    if (CH.confetti) { setTimeout(function () { CH.confetti(); }, 250); }
  }

  /* Plan, limit and match answers from the swipe endpoint, shared by the deck, cards and profile page. */
  function handleDenied(j) {
    if (j && (j.code === 'limit' || j.code === 'upgrade')) {
      showUpgrade(j.code === 'limit' ? 'You have reached today’s limit' : 'Part of Premium', j.error);
      return true;
    }
    return false;
  }

  function updateRemaining(r) {
    if (!r) { return; }
    Deck.rem = r;
    if (r.likes >= 0) {
      $$('[data-likes-left]').forEach(function (n) { n.textContent = r.likes; });
      $$('[data-likes-left-b]').forEach(function (n) { n.textContent = r.likes; });
      limitBanner(r.likes);
    }
  }
  var Deck = { rem: null };

  function limitBanner(left) {
    var root = $('[data-xdiscover]');
    if (!root) { return; }
    var b = $('[data-limit-banner]', root);
    if (left > 0 && left <= 5 && !b) { return; }
    if (left === 0) {
      var html = '<div><strong>You have used all your likes for today.</strong><span class="block text-sm opacity-90">You can still pass and look at profiles. Likes come back at midnight.</span></div>' +
        '<a class="btn btn-light btn-sm" href="' + esc(CH.url('/premium')) + '">' + ico('crown', 'w-4 h-4') + 'Get unlimited likes</a>';
      if (!b) {
        b = doc.createElement('div'); b.setAttribute('data-limit-banner', '');
        var anchor = $('[data-deck-root], [data-feed]', root) || root.lastElementChild;
        anchor.parentNode.insertBefore(b, anchor);
      }
      b.className = 'xd-banner xd-banner-limit'; b.innerHTML = html;
    }
  }

  /* ------------------------------------------------------------------ removing a card from a list */
  function removeCard(card, then) {
    if (!card) { return; }
    var parent = card.parentNode;
    card.classList.add('is-leaving');
    setTimeout(function () {
      card.remove();
      if (then) { then(parent); }
    }, reduced() ? 0 : 260);
  }
  function reloadIfEmpty(parent, selector) {
    if (parent && !$(selector || '[data-xcard]', parent)) { location.reload(); }
  }

  /* ------------------------------------------------------------------ quick actions (data-xa) */
  function markLiked(btn, dir) {
    $$('[data-xa="like"][data-id="' + btn.getAttribute('data-id') + '"], [data-xa="superlike"][data-id="' + btn.getAttribute('data-id') + '"]').forEach(function (b) {
      if (b.getAttribute('data-xa') === 'like') {
        b.classList.add('is-on'); b.setAttribute('aria-pressed', 'true'); b.disabled = true;
        var s = $('span', b); if (s) { s.textContent = dir === 'superlike' ? 'Super liked' : 'Liked'; }
      } else if (dir === 'superlike') { b.disabled = true; }
    });
  }
  function unmarkLiked(btn, wasDisabled) {
    $$('[data-xa="like"][data-id="' + btn.getAttribute('data-id') + '"]').forEach(function (b) {
      b.classList.remove('is-on'); b.setAttribute('aria-pressed', 'false'); b.disabled = !!wasDisabled;
      var s = $('span', b); if (s) { s.textContent = 'Like'; }
    });
  }

  var ACTIONS = {
    like: function (el) { sendLike(el, 'like'); },
    superlike: function (el) {
      if (el.classList.contains('is-locked')) { showUpgrade('Super Likes are part of Premium', 'Stand out from the crowd. Premium and VIP members can send Super Likes every day.'); return; }
      sendLike(el, 'superlike');
    },
    pass: function (el) {
      var id = +el.getAttribute('data-id'), card = el.closest('[data-xcard]');
      el.disabled = true;
      post('/api/swipe', { user_id: id, direction: 'nope' }).then(function (j) {
        if (!j.ok) { el.disabled = false; CH.toast(j.error || 'Could not do that.', 'error'); return; }
        CH.toast('Passed.', 'info', 1800);
        removeCard(card, function (p) { reloadIfEmpty(p); });
      });
    },
    fav: function (el) {
      var id = +el.getAttribute('data-id'), on = el.classList.contains('is-on'), want = !on;
      el.classList.toggle('is-on', want); el.setAttribute('aria-pressed', want ? 'true' : 'false');
      el.classList.add('is-pop'); setTimeout(function () { el.classList.remove('is-pop'); }, 400);
      post('/api/favorite', { user_id: id, on: want }).then(function (j) {
        if (!j.ok) {
          el.classList.toggle('is-on', on); el.setAttribute('aria-pressed', on ? 'true' : 'false');
          CH.toast(j.error || 'Could not save that.', 'error'); return;
        }
        CH.toast(j.message || (want ? 'Saved to your favourites.' : 'Removed from your favourites.'), 'success', 2200);
        var list = el.closest('[data-fav-list]');
        if (list && !j.favorited) { removeCard(el.closest('[data-xcard]'), function (p) { reloadIfEmpty(p); }); }
      });
    },
    unlike: function (el) {
      var card = el.closest('[data-xcard]');
      CH.confirm({ title: 'Take back your like?', message: el.getAttribute('data-confirm-text') || 'Take back your like?', confirm: 'Take it back' }).then(function (ok) {
        if (!ok) { return; }
        el.disabled = true;
        post('/api/explorer/unlike', { user_id: +el.getAttribute('data-id') }).then(function (j) {
          if (!j.ok) { el.disabled = false; CH.toast(j.error || 'Could not do that.', 'error'); return; }
          CH.toast(j.message || 'Like removed.', 'success', 2200);
          removeCard(card, function (p) { reloadIfEmpty(p); });
        });
      });
    },
    unmatch: function (el) {
      var name = el.getAttribute('data-name') || 'this member', card = el.closest('[data-xcard]');
      CH.confirm({ title: 'Unmatch ' + name + '?', message: 'You will no longer be matched and your chat will close. This cannot be undone.', confirm: 'Unmatch' }).then(function (ok) {
        if (!ok) { return; }
        el.disabled = true;
        post('/api/explorer/unmatch', { user_id: +el.getAttribute('data-id') }).then(function (j) {
          if (!j.ok) { el.disabled = false; CH.toast(j.error || 'Could not unmatch.', 'error'); return; }
          CH.toast(j.message || 'You are no longer matched.', 'success');
          removeCard(card, function (p) { reloadIfEmpty(p); });
        });
      });
    },
    block: function (el) {
      var name = el.getAttribute('data-name') || 'this member';
      CH.confirm({ title: 'Block ' + name + '?', message: 'You will not see each other any more, and any match or chat between you closes. They are not told. You can unblock them in Settings.', confirm: 'Block' }).then(function (ok) {
        if (!ok) { return; }
        post('/api/block', { user_id: +el.getAttribute('data-id') }).then(function (j) {
          if (!j.ok) { CH.toast(j.error || 'Could not block.', 'error'); return; }
          CH.toast(j.message || 'Blocked.', 'success');
          setTimeout(function () { location.href = CH.url('/discover'); }, 700);
        });
      });
    },
    report: function (el) {
      var d = $('#xreport');
      if (!d) { return; }
      var f = $('form', d) || d;
      var set = function (n, v) { var i = $('[name="' + n + '"]', f); if (i) { i.value = v; } };
      set('type', el.getAttribute('data-type') || 'user'); set('target_id', el.getAttribute('data-target') || el.getAttribute('data-id') || '');
      var nm = $('[data-r-name]', d); if (nm) { nm.textContent = el.getAttribute('data-name') || 'this member'; }
      var er = $('[data-r-error]', d); if (er) { er.hidden = true; er.textContent = ''; }
      var det = $('[name="details"]', f); if (det) { det.value = ''; }
      var first = $('input[name="reason"]', f); if (first) { first.checked = true; }
      $$('.menu-panel', doc).forEach(function (p) { p.hidden = true; });
      CH.modal.open(d);
    },
    share: function (el) {
      var url = el.getAttribute('data-url') || location.href, title = el.getAttribute('data-title') || doc.title;
      $$('.menu-panel', doc).forEach(function (p) { p.hidden = true; });
      if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () { /* cancelled */ }); return; }
      (navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.reject()).then(function () { CH.toast('Link copied.', 'success', 2000); }, function () { CH.toast(url, 'info', 8000); });
    }
  };

  function sendLike(el, dir) {
    var id = +el.getAttribute('data-id');
    var card = el.closest('[data-xcard]');
    var answering = !!(card && $('[data-xa="pass"]', card));
    var wasDisabled = el.disabled;
    if (el.disabled) { return; }
    var rem = Deck.rem;
    if (rem && dir === 'like' && rem.likes === 0) { showUpgrade('You have used today’s likes', 'You can still pass and look at profiles. Upgrade for unlimited likes, or come back after midnight.'); return; }
    if (rem && dir === 'superlike') {
      if (rem.superlikes_in_plan === false) { showUpgrade('Super Likes are part of Premium', 'Stand out from the crowd. Premium and VIP members can send Super Likes every day.'); return; }
      if (rem.superlikes === 0) { showUpgrade('You have used today’s Super Likes', 'They come back at midnight.'); return; }
    }
    markLiked(el, dir);
    post('/api/swipe', { user_id: id, direction: dir }).then(function (j) {
      if (!j.ok) {
        unmarkLiked(el, wasDisabled);
        if (!handleDenied(j)) { CH.toast(j.error || 'Could not send that.', 'error'); }
        return;
      }
      updateRemaining(j.remaining);
      if (j.match) { showMatch(j.match); } else if (!j.noop) { CH.toast(dir === 'superlike' ? 'Super like sent.' : 'Liked.', 'success', 1800); }
      if (answering) { removeCard(card, function (p) { reloadIfEmpty(p); }); }
    });
  }

  CH.delegate('click', '[data-xa]', function (e, el) {
    var fn = ACTIONS[el.getAttribute('data-xa')];
    if (!fn) { return; }
    e.preventDefault();
    fn(el);
  });
  CH.delegate('click', '[data-upgrade-msg]', function (e, el) {
    e.preventDefault();
    showUpgrade(el.getAttribute('data-upgrade-title') || 'Part of Premium', el.getAttribute('data-upgrade-msg'));
  });
  CH.delegate('click', '[data-hint]', function (e, el) { e.preventDefault(); CH.toast(el.getAttribute('data-hint'), 'info', 5000); });

  /* Report dialog */
  doc.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.matches || !f.matches('[data-xreport]')) { return; }
    e.preventDefault();
    var d = f.closest('dialog'), er = $('[data-r-error]', f), btn = $('button[type="submit"]', f);
    var reason = ($('input[name="reason"]:checked', f) || {}).value || '';
    if (!reason) { if (er) { er.textContent = 'Choose a reason.'; er.hidden = false; } return; }
    btn.disabled = true;
    post('/api/report', { type: f.elements.type.value, target_id: +f.elements.target_id.value, reason: reason, details: f.elements.details.value }).then(function (j) {
      btn.disabled = false;
      if (j.ok) { d && d.close(); CH.toast(j.message || 'Thank you. Our team will look into it.', 'success', 5000); }
      else if (er) { er.textContent = j.error || 'Could not send your report.'; er.hidden = false; }
    });
  }, true);

  /* ------------------------------------------------------------------ swipe deck */
  function initDeck(data) {
    var root = $('[data-deck-root]');
    if (!root) { return; }
    var stage = $('[data-deck]', root), skel = $('[data-deck-skel]', root), empty = $('[data-deck-empty]', root);
    var actions = $('[data-deck-actions]', root), hint = $('.xdeck-hint', root);
    var cards = (data.cards || []).slice(), i = 0, hasMore = !!data.hasMore, loading = false, shownAt = Date.now();
    var history = [], seen = {}, busy = false;
    var live = doc.createElement('p'); live.className = 'sr-only'; live.setAttribute('aria-live', 'polite'); root.appendChild(live);
    Deck.rem = data.remaining;
    cards.forEach(function (c) { seen[c.id] = true; });

    function photoEl(c, k) {
      var p = c.photos[k], cls = 'sw-img' + (k === 0 ? ' is-on' : '') + (p.blurred ? ' ch-blurred ch-blur-' + (data.blur || 'medium') : '');
      return '<img class="' + cls + '" ' + (k < 2 ? 'src="' + esc(p.url) + '" ' : 'data-src="' + esc(p.url) + '" ') + 'alt="' + (k === 0 ? esc(c.name + (c.age ? ', ' + c.age : '')) : '') + '" draggable="false" decoding="async">';
    }
    function build(c) {
      var el = doc.createElement('article');
      el.className = 'sw-card'; el.dataset.id = c.id; el.dataset.pos = '9'; el._p = 0; el._card = c;
      el.setAttribute('aria-label', c.name + (c.age ? ', ' + c.age : ''));
      var bars = '';
      if (c.photos.length > 1) { bars = '<div class="sw-bars" aria-hidden="true">'; c.photos.forEach(function (_, k) { bars += '<i' + (k === 0 ? ' class="is-on"' : '') + '></i>'; }); bars += '</div>'; }
      var tags = '';
      if (c.superliked_you) { tags += '<span class="xtag xtag-super">' + ico('star', 'w-3 h-3') + 'Super liked you</span>'; }
      else if (c.likes_you) { tags += '<span class="xtag xtag-like">' + ico('heart', 'w-3 h-3') + 'Likes you</span>'; }
      if (c.new) { tags += '<span class="xtag xtag-new-in">New here</span>'; }
      if (c.boosted || c.featured) { tags += '<span class="xtag xtag-top">' + ico('zap', 'w-3 h-3') + 'Featured</span>'; }
      var compat = (c.compat !== null && c.compat !== undefined)
        ? '<span class="sw-compat" style="--p:' + (+c.compat) + '" role="img" aria-label="' + (+c.compat) + ' percent match"><b aria-hidden="true">' + (+c.compat) + '</b><small aria-hidden="true">match</small></span>' : '';
      var where = [c.town, c.distance].filter(Boolean).join(' · ');
      var chips = '';
      (c.interests || []).slice(0, 4).forEach(function (n) { chips += '<li class="xchip' + (n.shared ? ' is-shared' : '') + '">' + intIcon(n) + esc(n.name) + '</li>'; });
      var live = c.online ? '<span class="sw-live"><i aria-hidden="true"></i>Online now</span>' : (c.seen ? '<span class="sw-seen">Active ' + esc(c.seen) + '</span>' : '');
      el.innerHTML =
        '<div class="sw-photos">' + c.photos.map(function (_, k) { return photoEl(c, k); }).join('') + '</div>' + bars +
        '<span class="sw-shade" aria-hidden="true"></span>' +
        '<span class="sw-stamp sw-stamp-like" aria-hidden="true">LIKE</span><span class="sw-stamp sw-stamp-nope" aria-hidden="true">NOPE</span><span class="sw-stamp sw-stamp-super" aria-hidden="true">SUPER</span>' +
        (tags ? '<div class="sw-tags">' + tags + '</div>' : '') + compat +
        '<div class="sw-info">' +
          '<h3 class="sw-name"><span class="sw-first">' + esc(c.name) + '</span>' + (c.age ? '<span class="sw-age">' + (+c.age) + '</span>' : '') +
            (c.verified ? '<span class="xcard-verified" title="Verified">' + ico('badge-check', 'w-5 h-5') + '<span class="sr-only">Verified</span></span>' : '') + '</h3>' +
          (live ? '<p class="sw-presence">' + live + '</p>' : '') +
          (c.occupation ? '<p class="sw-meta">' + ico('briefcase', 'w-3.5 h-3.5') + esc(c.occupation) + '</p>' : '') +
          (where ? '<p class="sw-meta">' + ico('map-pin', 'w-3.5 h-3.5') + esc(where) + '</p>' : '') +
          (c.bio ? '<p class="sw-bio">' + esc(c.bio) + '</p>' : '') +
          (chips ? '<ul class="sw-chips" aria-label="Interests">' + chips + '</ul>' : '') +
        '</div>' +
        '<button type="button" class="sw-more" aria-haspopup="dialog" aria-expanded="false" aria-label="' + esc('More about ' + c.name) + '">' + ico('chevron-up', 'w-5 h-5') + '</button>' +
        sheetHtml(c);
      bind(el);
      return el;
    }

    function sheetHtml(c) {
      var facts = '';
      function fact(icon, label, val) { if (val) { facts += '<div><dt>' + ico(icon, 'w-4 h-4') + esc(label) + '</dt><dd>' + esc(val) + '</dd></div>'; } }
      fact('heart', 'Looking for', c.intent);
      fact('briefcase', 'Works as', c.occupation);
      fact('map-pin', 'Lives in', c.town);
      fact('route', 'Distance', c.distance);
      if (c.compat !== null && c.compat !== undefined) { fact('sparkles', 'Match', (+c.compat) + '%'); }
      var all = '', shared = 0;
      (c.interests || []).forEach(function (n) { if (n.shared) { shared++; } all += '<li class="xchip' + (n.shared ? ' is-shared' : '') + '">' + intIcon(n) + esc(n.name) + '</li>'; });
      return '<div class="sw-sheet" role="dialog" aria-label="' + esc('About ' + c.name) + '" hidden>' +
        '<div class="sw-sheet-grab" aria-hidden="true"></div>' +
        '<button type="button" class="sw-sheet-x" aria-label="Close details">' + ico('x', 'w-5 h-5') + '</button>' +
        '<div class="sw-sheet-body" tabindex="0">' +
          '<h3 class="sw-sheet-name">' + esc(c.name) + (c.age ? ', ' + (+c.age) : '') + (c.verified ? '<span class="xcard-verified" title="Verified">' + ico('badge-check', 'w-5 h-5') + '</span>' : '') + '</h3>' +
          (c.online ? '<p class="sw-sheet-live"><i aria-hidden="true"></i>Online now</p>' : (c.seen ? '<p class="sw-sheet-seen">Active ' + esc(c.seen) + '</p>' : '')) +
          (c.bio_full ? '<p class="sw-sheet-bio">' + esc(c.bio_full) + '</p>' : '') +
          (c.looking_for ? '<p class="sw-sheet-want"><strong>Hoping to find</strong> ' + esc(c.looking_for) + '</p>' : '') +
          (facts ? '<dl class="sw-facts">' + facts + '</dl>' : '') +
          (all ? '<h4 class="sw-sheet-h">Interests' + (shared ? '<span>' + shared + ' in common</span>' : '') + '</h4><ul class="sw-chips sw-chips-all">' + all + '</ul>' : '') +
        '</div>' +
        '<div class="sw-sheet-foot">' +
          '<button type="button" class="btn btn-outline btn-sm" data-sheet-act="nope">' + ico('x', 'w-4 h-4') + 'Pass</button>' +
          '<a class="btn btn-ghost btn-sm" href="' + esc(c.href) + '">Full profile</a>' +
          '<button type="button" class="btn btn-primary btn-sm" data-sheet-act="like">' + ico('heart', 'w-4 h-4') + 'Like</button>' +
        '</div>' +
      '</div>';
    }

    function openSheet(el) {
      if (!el || el.dataset.pos !== '0') { return; }
      var sh = $('.sw-sheet', el); if (!sh) { return; }
      sh.hidden = false; el.classList.add('is-sheet');
      var more = $('.sw-more', el); if (more) { more.setAttribute('aria-expanded', 'true'); }
      var x = $('.sw-sheet-x', sh); if (x) { x.focus({ preventScroll: true }); }
    }
    function closeSheet(el, refocus) {
      el = el || stage.querySelector('.sw-card.is-sheet');
      if (!el) { return; }
      var sh = $('.sw-sheet', el); if (sh) { sh.hidden = true; sh.scrollTop = 0; }
      el.classList.remove('is-sheet');
      var more = $('.sw-more', el); if (more) { more.setAttribute('aria-expanded', 'false'); if (refocus) { more.focus({ preventScroll: true }); } }
    }
    function sheetOpen() { return !!stage.querySelector('.sw-card.is-sheet'); }

    function setPhoto(el, k) {
      var c = el._card, n = c.photos.length;
      if (n < 2) { return; }
      k = (k + n) % n; el._p = k;
      $$('.sw-img', el).forEach(function (im, idx) {
        if (idx === k || idx === (k + 1) % n) { if (!im.src && im.dataset.src) { im.src = im.dataset.src; } }
        im.classList.toggle('is-on', idx === k);
      });
      $$('.sw-bars i', el).forEach(function (b, idx) { b.classList.toggle('is-on', idx === k); });
    }

    function render() {
      var want = cards.slice(i, i + 3), keep = {};
      want.forEach(function (c) { keep[c.id] = true; });
      $$('.sw-card', stage).forEach(function (el) { if (!keep[el.dataset.id] && !el.classList.contains('is-flying')) { el.remove(); } });
      var have = {};
      $$('.sw-card:not(.is-flying)', stage).forEach(function (el) { have[el.dataset.id] = el; });
      want.forEach(function (c, pos) {
        var el = have[c.id];
        if (!el) { el = build(c); stage.appendChild(el); }
        el.dataset.pos = String(pos);
        el.tabIndex = pos === 0 ? 0 : -1;
        el.setAttribute('aria-hidden', pos === 0 ? 'false' : 'true');
        if (pos === 0) { el.setAttribute('role', 'group'); } else { el.removeAttribute('role'); }
      });
      var top = cards[i];
      if (skel) { skel.hidden = !!top || !loading; }
      var done = !top && !loading && !hasMore;
      if (empty) { empty.hidden = !done; }
      if (actions) { actions.hidden = !top && !loading; $$('.xaction', actions).forEach(function (b) { b.disabled = !top; }); }
      if (hint) { hint.hidden = !top; }
      if (top) { live.textContent = 'Now showing ' + top.name + (top.age ? ', ' + top.age : '') + '.'; }
      if (cards.length - i <= 4 && hasMore && !loading) { more(); }
    }

    function more() {
      loading = true;
      var ex = cards.slice(Math.max(0, i - 30)).map(function (c) { return c.id; }).slice(-150);
      CH.api('/api/explorer/deck?' + (data.query ? data.query + '&' : '') + 'limit=' + (data.batch || 10) + '&exclude=' + ex.join(','), { quiet: true }).then(function (j) {
        loading = false;
        if (!j.ok) { hasMore = false; if (!cards[i]) { CH.toast(j.error || 'Could not load more people.', 'error'); } render(); return; }
        var hadTop = !!cards[i];
        var fresh = (j.cards || []).filter(function (c) { return !seen[c.id]; });
        fresh.forEach(function (c) { seen[c.id] = true; cards.push(c); });
        hasMore = !!j.has_more && fresh.length > 0;
        if (j.remaining) { updateRemaining(j.remaining); }
        if (!hadTop) { shownAt = Date.now(); }
        render();
      });
    }

    function paint(el, dx, dy, w, h) {
      el.style.transform = 'translate3d(' + dx + 'px,' + dy + 'px,0) rotate(' + (dx / w * 14) + 'deg)';
      var like = clamp(dx / (w * 0.3), 0, 1), nope = clamp(-dx / (w * 0.3), 0, 1);
      var sup = dy < 0 && Math.abs(dy) > Math.abs(dx) ? clamp(-dy / (h * 0.22), 0, 1) : 0;
      el.style.setProperty('--like', like); el.style.setProperty('--nope', nope); el.style.setProperty('--super', sup);
    }
    function snap(el) {
      el.classList.add('is-snap');
      el.style.transform = ''; el.style.setProperty('--like', 0); el.style.setProperty('--nope', 0); el.style.setProperty('--super', 0);
      setTimeout(function () { el.classList.remove('is-snap'); }, 460);
    }

    function bind(el) {
      var sx = 0, sy = 0, st = 0, dx = 0, dy = 0, drag = false, pid = null, w = 1, h = 1;
      el.addEventListener('pointerdown', function (e) {
        if (el.dataset.pos !== '0' || busy || el.classList.contains('is-sheet') || (e.pointerType === 'mouse' && e.button !== 0)) { return; }
        if (e.target.closest('a, button')) { return; }
        drag = true; pid = e.pointerId; sx = e.clientX; sy = e.clientY; st = performance.now(); dx = dy = 0;
        w = el.offsetWidth || 1; h = el.offsetHeight || 1;
        try { el.setPointerCapture(pid); } catch (x) { /* not supported */ }
        el.classList.add('is-drag'); el.classList.remove('is-snap');
      });
      el.addEventListener('pointermove', function (e) {
        if (!drag || e.pointerId !== pid) { return; }
        dx = e.clientX - sx; dy = e.clientY - sy;
        paint(el, dx, dy, w, h);
      });
      function end(e) {
        if (!drag || e.pointerId !== pid) { return; }
        drag = false; el.classList.remove('is-drag');
        try { el.releasePointerCapture(pid); } catch (x) { /* ignore */ }
        if (e.type === 'pointercancel') { snap(el); return; }
        var dt = Math.max(1, performance.now() - st), dist = Math.hypot(dx, dy), vx = dx / dt, vy = dy / dt;
        if (dist < 8 && dt < 600) { snap(el); tap(el, e); return; }
        if (dx > w * 0.28 || (vx > 0.55 && dx > 40)) { act('like'); }
        else if (dx < -w * 0.28 || (vx < -0.55 && dx < -40)) { act('nope'); }
        else if ((dy < -h * 0.2 || (vy < -0.6 && dy < -40)) && Math.abs(dy) > Math.abs(dx) * 1.2) { act('superlike'); }
        else { snap(el); }
      }
      el.addEventListener('pointerup', end);
      el.addEventListener('pointercancel', end);
      el.addEventListener('dragstart', function (e) { e.preventDefault(); });
      el.addEventListener('keydown', function (e) {
        if (e.key === ' ' && el.dataset.pos === '0' && e.target === el) { e.preventDefault(); setPhoto(el, el._p + 1); }
      });
    }

    function tap(el, e) {
      var c = el._card, r = el.getBoundingClientRect(), x = (e.clientX - r.left) / Math.max(1, r.width);
      if (c.photos.length > 1 && x < 0.33) { setPhoto(el, el._p - 1); return; }
      if (c.photos.length > 1 && x > 0.67) { setPhoto(el, el._p + 1); return; }
      openSheet(el);
    }

    function fly(el, dir) {
      var w = window.innerWidth, h = window.innerHeight;
      var x = dir === 'like' ? w * 1.4 : (dir === 'nope' ? -w * 1.4 : 0), y = dir === 'superlike' ? -h * 1.1 : 60;
      var rot = dir === 'like' ? 22 : (dir === 'nope' ? -22 : 0);
      el.dataset.pos = 'out'; el.classList.add('is-flying'); el.classList.remove('is-drag', 'is-snap');
      el.style.setProperty(dir === 'like' ? '--like' : (dir === 'nope' ? '--nope' : '--super'), 1);
      if (reduced()) { el.style.opacity = '0'; }
      else { el.style.transform = 'translate3d(' + x + 'px,' + y + 'px,0) rotate(' + rot + 'deg)'; }
      setTimeout(function () { el.remove(); }, reduced() ? 120 : 380);
    }

    function act(dir) {
      var c = cards[i], el = stage.querySelector('.sw-card[data-pos="0"]');
      if (!c || !el) { return; }
      var rem = Deck.rem || {};
      if (dir === 'like' && rem.likes === 0) { snap(el); showUpgrade('You have used today’s likes', 'You can still pass and look at profiles. Upgrade for unlimited likes, or come back after midnight.'); return; }
      if (dir === 'superlike') {
        if (rem.superlike_enabled === false) { snap(el); CH.toast('Super likes are switched off right now.', 'info'); return; }
        if (rem.superlikes_in_plan === false) { snap(el); showUpgrade('Super Likes are part of Premium', 'Stand out from the crowd. Premium and VIP members can send Super Likes every day.'); return; }
        if (rem.superlikes === 0) { snap(el); showUpgrade('You have used today’s Super Likes', 'They come back at midnight.'); return; }
      }
      var dwell = Math.min(600000, Date.now() - shownAt);
      fly(el, dir);
      history.push({ card: c, dir: dir });
      i++; shownAt = Date.now(); render();
      post('/api/swipe', { user_id: c.id, direction: dir, dwell_ms: dwell }).then(function (j) {
        if (j.ok) {
          updateRemaining(j.remaining);
          if (j.match) { setTimeout(function () { showMatch(j.match); }, reduced() ? 0 : 320); }
          return;
        }
        // Put the card back when the server said no.
        history = history.filter(function (hh) { return hh.card.id !== c.id; });
        if (j.code === 'unavailable') { CH.toast('That member is not available any more.', 'info'); return; }
        cards.splice(i, 0, c); shownAt = Date.now(); render();
        var back = stage.querySelector('.sw-card[data-pos="0"]');
        if (back) { back.dataset.from = dir; back.classList.add('is-entering'); setTimeout(function () { back.classList.remove('is-entering'); }, 520); }
        if (!handleDenied(j)) { CH.toast(j.error || 'That did not work. Please try again.', 'error'); }
      });
    }

    function rewind() {
      if (busy) { return; }
      var locked = $('[data-act="rewind"]', actions);
      var rem = Deck.rem || {};
      if (rem.rewind_enabled === false) { CH.toast('Undo is switched off right now.', 'info'); return; }
      if (rem.rewind_in_plan === false) { showUpgrade('Undo is part of Premium', 'Changed your mind? Premium and VIP members can take back their last swipe.'); return; }
      if (rem.rewinds === 0) { showUpgrade('You have used today’s undos', 'They come back at midnight.'); return; }
      busy = true;
      post('/api/swipe', { direction: 'rewind' }).then(function (j) {
        busy = false;
        if (!j.ok) {
          if (!handleDenied(j)) { CH.toast(j.error || 'Nothing to undo.', 'info'); }
          return;
        }
        var last = history.pop();
        var card = j.card || (last && last.card);
        updateRemaining(j.remaining);
        if (!card) { CH.toast('Undone.', 'success', 1500); return; }
        cards = cards.filter(function (c, idx) { return idx < i || c.id !== card.id; });
        cards.splice(i, 0, card); seen[card.id] = true;
        shownAt = Date.now(); render();
        var back = stage.querySelector('.sw-card[data-pos="0"]');
        if (back) { back.dataset.from = (j.direction === 'nope') ? 'nope' : (j.direction === 'superlike' ? 'superlike' : 'like'); back.classList.add('is-entering'); setTimeout(function () { back.classList.remove('is-entering'); }, 520); }
        if (locked) { locked.classList.remove('is-locked'); }
      });
    }

    stage.addEventListener('click', function (e) {
      var card = e.target.closest ? e.target.closest('.sw-card') : null;
      if (!card) { return; }
      if (e.target.closest('.sw-more')) { e.preventDefault(); if (card.classList.contains('is-sheet')) { closeSheet(card, true); } else { openSheet(card); } return; }
      if (e.target.closest('.sw-sheet-x')) { e.preventDefault(); closeSheet(card, true); return; }
      var sa = e.target.closest('[data-sheet-act]');
      if (sa) { e.preventDefault(); closeSheet(card); act(sa.getAttribute('data-sheet-act')); }
    });
    $$('[data-act]', root).forEach(function (b) {
      b.addEventListener('click', function () {
        var a = b.getAttribute('data-act');
        if (a === 'rewind') { rewind(); } else { act(a); }
      });
    });
    doc.addEventListener('keydown', function (e) {
      if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || isTyping(e.target) || dialogOpen()) { return; }
      var top = stage.querySelector('.sw-card[data-pos="0"]'), open = sheetOpen();
      if (e.key === 'Escape' && open) { e.preventDefault(); closeSheet(null, true); }
      else if (e.key === 'ArrowRight') { e.preventDefault(); if (open) { closeSheet(); } act('like'); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); if (open) { closeSheet(); } act('nope'); }
      else if (e.key === 'ArrowUp' && !open) { e.preventDefault(); act('superlike'); }
      else if ((e.key === 'i' || e.key === 'I') && top) { e.preventDefault(); if (open) { closeSheet(top, true); } else { openSheet(top); } }
      else if ((e.key === 'z' || e.key === 'Z') && !open) { e.preventDefault(); rewind(); }
      else if ((e.key === 'ArrowDown' || e.key === 'Enter') && top && !open && (e.target === doc.body || e.target === top)) { e.preventDefault(); openSheet(top); }
    });
    // Escape inside the sheet (focus is on its close button) also closes it
    stage.addEventListener('keydown', function (e) { if (e.key === 'Escape' && sheetOpen()) { e.preventDefault(); e.stopPropagation(); closeSheet(null, true); } });

    /* Fit the deck to the screen so the buttons are always in view above the bottom bar, on any phone or window. */
    function fit() {
      var w = stage.parentElement ? stage.parentElement.clientWidth : 360;
      var tab = $('.app-tabbar'), tabH = tab && getComputedStyle(tab).display !== 'none' ? tab.offsetHeight + 10 : 0;
      var topDoc = stage.getBoundingClientRect().top + (window.pageYOffset || 0);
      var hintOn = hint && !hint.hidden && getComputedStyle(hint).display !== 'none';
      var below = (actions && !actions.hidden ? actions.offsetHeight + 16 : 0) + (hintOn ? hint.offsetHeight + 16 : 0) + 22 + (tabH ? tabH + 14 : 28);
      var cardW = Math.min(w, 540), max = Math.min(780, Math.round(cardW * 1.36));
      var h = Math.max(300, Math.min(window.innerHeight - topDoc - below, max));
      if (Math.abs((stage.offsetHeight || 0) - h) > 1) { stage.style.height = h + 'px'; }
      var nw = Math.min(cardW, Math.round(h * 0.9));
      stage.style.width = nw + 'px';
    }
    var fitT = 0;
    function refit() { cancelAnimationFrame(fitT); fitT = requestAnimationFrame(fit); }
    window.addEventListener('resize', refit); window.addEventListener('orientationchange', refit); window.addEventListener('load', refit);
    if ('ResizeObserver' in window) { try { new ResizeObserver(refit).observe(doc.body); } catch (x) { /* optional */ } }
    fit();

    /* One-time hint for people who have never swiped here. */
    function coach() {
      try { if (localStorage.getItem('chx_swipe_tip')) { return; } localStorage.setItem('chx_swipe_tip', '1'); } catch (x) { return; }
      var top = stage.querySelector('.sw-card[data-pos="0"]'); if (!top) { return; }
      var tip = doc.createElement('div'); tip.className = 'sw-coach'; tip.setAttribute('aria-hidden', 'true');
      tip.innerHTML = '<span>' + ico('arrow-left', 'w-4 h-4') + 'Pass</span><span>Like' + ico('arrow-right', 'w-4 h-4') + '</span>';
      stage.appendChild(tip);
      if (!reduced()) { top.classList.add('is-wiggle'); setTimeout(function () { top.classList.remove('is-wiggle'); }, 1400); }
      setTimeout(function () { tip.classList.add('is-out'); setTimeout(function () { tip.remove(); }, 400); }, 3600);
    }

    render();
    refit();
    setTimeout(coach, 700);
  }

  /* ------------------------------------------------------------------ grid / list feeds ("show more") */
  function initFeed(root) {
    var items = $('[data-feed-items]', root), moreBox = $('[data-feed-more]', root), btn = $('[data-feed-load]', root), end = $('[data-feed-end]', root);
    if (!items || !btn) { return; }
    var view = root.getAttribute('data-view') || 'grid', loading = false, hasMore = root.getAttribute('data-more') === '1';
    function raw() {
      var p = new URLSearchParams(location.search), parts = [];
      p.forEach(function (v, k) { if (['view', 'exclude', 'start', 'limit', 'page'].indexOf(k) === -1) { parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v)); } });
      return parts.join('&');
    }
    function load() {
      if (loading || !hasMore) { return; }
      loading = true; btn.classList.add('is-loading'); btn.disabled = true;
      var ids = $$('[data-xcard]', items).map(function (c) { return c.getAttribute('data-id'); });
      var url = '/api/explorer/feed?' + raw() + (raw() ? '&' : '') + 'view=' + view + '&start=' + ids.length + '&exclude=' + ids.slice(-150).join(',');
      CH.api(url, { quiet: true }).then(function (j) {
        loading = false; btn.classList.remove('is-loading'); btn.disabled = false;
        if (!j.ok) { CH.toast(j.error || 'Could not load more people.', 'error'); return; }
        if (j.html && j.ids && j.ids.length) {
          var tmp = doc.createElement('div'); tmp.innerHTML = j.html;
          var first = null;
          while (tmp.firstChild) { if (!first && tmp.firstChild.nodeType === 1) { first = tmp.firstChild; } items.appendChild(tmp.firstChild); }
          if (first && first.querySelector) { var a = first.querySelector('a, button'); a && a.focus({ preventScroll: true }); }
        }
        updateRemaining(j.remaining);
        hasMore = !!j.has_more && !!(j.ids && j.ids.length);
        if (moreBox) { moreBox.hidden = !hasMore; }
        if (end) { end.hidden = hasMore; }
      });
    }
    btn.addEventListener('click', load);
    if ('IntersectionObserver' in window && moreBox) {
      var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { load(); } }, { rootMargin: '600px 0px' });
      io.observe(moreBox);
    }
  }

  /* ------------------------------------------------------------------ filters, ranges, places, interest counts */
  var locTree = null;
  function tree() { if (!locTree) { locTree = readJson('#xloc-tree') || { counties: [], towns: {} }; } return locTree; }

  function fillTowns(countySel, townSel, keep) {
    var t = tree(), list = t.towns[countySel.value] || [];
    var first = townSel.options[0] ? townSel.options[0].cloneNode(true) : null;
    townSel.innerHTML = '';
    if (first) { townSel.appendChild(first); }
    list.forEach(function (x) { var o = doc.createElement('option'); o.value = x.id; o.textContent = x.name; townSel.appendChild(o); });
    townSel.value = keep && list.some(function (x) { return String(x.id) === String(keep); }) ? keep : '0';
    townSel.disabled = !list.length;
  }
  doc.addEventListener('change', function (e) {
    var sel = e.target;
    if (sel && sel.matches && sel.matches('[data-loc-county]')) {
      var box = sel.closest('[data-loc-pick]'), town = box && $('[data-loc-town]', box);
      if (town) { fillTowns(sel, town); }
    }
  });
  doc.addEventListener('ch:located', function (e) {
    var j = e.detail || {}, box = e.target.closest ? e.target.closest('[data-loc-pick]') : null;
    if (!box) { return; }
    var cs = $('[data-loc-county]', box), ts = $('[data-loc-town]', box), name = function (s) { return String(s || '').trim().toLowerCase(); };
    if (cs && j.county) {
      var opt = Array.prototype.filter.call(cs.options, function (o) { return name(o.textContent) === name(j.county); })[0];
      if (opt) { cs.value = opt.value; if (ts) { fillTowns(cs, ts); } }
    }
    if (ts && j.town) {
      var to = Array.prototype.filter.call(ts.options, function (o) { return name(o.textContent) === name(j.town); })[0];
      if (to) { ts.value = to.value; }
    }
    var lab = $('[data-loc-label]', box), now = $('[data-loc-now]', box);
    if (lab && j.label) { lab.textContent = j.label; }
    if (now) { now.hidden = false; }
  });

  function syncPair(pair) {
    var a = $('input[name="age_min"]', pair), b = $('input[name="age_max"]', pair), box = $('.xrange-pair', pair);
    if (!a || !b || !box) { return; }
    var lo = +a.min, span = (+a.max - lo) || 1;
    box.style.setProperty('--lo', ((a.value - lo) / span * 100) + '%');
    box.style.setProperty('--hi', ((b.value - lo) / span * 100) + '%');
  }
  doc.addEventListener('input', function (e) {
    var el = e.target;
    if (!el || !el.matches || !el.matches('input[type="range"]')) { return; }
    if (el.name === 'age_min' || el.name === 'age_max') {
      var pair = el.closest('[data-range-pair]'); if (!pair) { return; }
      var a = $('input[name="age_min"]', pair), b = $('input[name="age_max"]', pair);
      if (+a.value > +b.value) { if (el === a) { b.value = a.value; } else { a.value = b.value; } }
      var out = $('[data-age-out]', pair); if (out) { out.textContent = a.value + ' to ' + b.value; }
      syncPair(pair);
    } else if (el.name === 'distance' || el.name === 'distance_km') {
      var g = el.closest('.xf-group, .xprefs, .xset-field'), o = g && $('[data-dist-out]', g);
      if (o) { o.textContent = el.value + ' km'; }
    }
  });
  doc.addEventListener('change', function (e) {
    var el = e.target;
    if (el && el.matches && el.matches('.xf-details input[type="checkbox"]')) {
      var d = el.closest('.xf-details'), n = $$('input:checked', d).length, c = $('[data-interest-count]', d);
      if (c) { c.textContent = n ? n + ' selected' : 'Any'; }
    }
    var f = el && el.form;
    if (f && f.hasAttribute && f.hasAttribute('data-autosubmit') && el.tagName === 'SELECT') { f.submit(); }
  });

  /* ------------------------------------------------------------------ interest picker and bio helper */
  function initInterestPicker(box) {
    var min = +box.getAttribute('data-min') || 0, max = +box.getAttribute('data-max') || 99;
    var out = $('[data-interest-n]', box);
    function update() {
      var boxes = $$('input[type="checkbox"]', box), n = boxes.filter(function (b) { return b.checked; }).length;
      if (out) { out.textContent = n; }
      boxes.forEach(function (b) { b.disabled = !b.checked && n >= max; var c = b.nextElementSibling; if (c) { c.classList.toggle('is-disabled', b.disabled); } });
      var cnt = $('.xint-count', box); if (cnt) { cnt.classList.toggle('is-ok', n >= min); }
    }
    box.addEventListener('change', function (e) {
      if (e.target.matches('input[type="checkbox"]')) {
        var n = $$('input:checked', box).length;
        if (n > max) { e.target.checked = false; CH.toast('You can pick up to ' + max + ' interests.', 'info'); }
        update();
      }
    });
    update();
  }
  CH.delegate('click', '[data-bio-add]', function (e, el) {
    var form = el.closest('form'), ta = form && $('textarea[data-bio]', form);
    if (!ta) { return; }
    var add = el.getAttribute('data-bio-add'), cur = ta.value.trim(), sep = cur === '' ? '' : (/[.!?]$/.test(cur) ? ' ' : '. ');
    var next = cur + sep + add;
    if (ta.maxLength > 0 && next.length > ta.maxLength) { CH.toast('That would make your bio too long.', 'info'); return; }
    ta.value = next; ta.dispatchEvent(new Event('input', { bubbles: true })); ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);
  });

  /* ------------------------------------------------------------------ profile page: gallery, lightbox, tracking */
  function initProfile(root) {
    var pics = readJson('[data-xgallery]') || [];
    var remData = readJson('[data-xrem]'); if (remData) { Deck.rem = remData; }
    var slides = $$('.xp-slide', root), thumbs = $$('[data-thumb]', root), bars = $$('.xp-bars i', root);
    var idx = 0, n = slides.length, viewed = {}; viewed[0] = true;
    function show(k) {
      if (!n) { return; }
      idx = (k + n) % n; viewed[idx] = true;
      slides.forEach(function (s, j) { var on = j === idx; s.classList.toggle('is-active', on); s.tabIndex = on ? 0 : -1; s.setAttribute('aria-hidden', on ? 'false' : 'true'); });
      thumbs.forEach(function (t, j) { t.classList.toggle('is-active', j === idx); });
      bars.forEach(function (b, j) { b.classList.toggle('is-on', j === idx); });
      var t = thumbs[idx]; if (t && t.parentNode && t.parentNode.parentNode) { t.parentNode.parentNode.scrollLeft = t.parentNode.offsetLeft - 16; }
    }
    var prev = $('[data-gallery-prev]', root), next = $('[data-gallery-next]', root);
    prev && prev.addEventListener('click', function (e) { e.stopPropagation(); show(idx - 1); });
    next && next.addEventListener('click', function (e) { e.stopPropagation(); show(idx + 1); });
    thumbs.forEach(function (t, j) { t.addEventListener('click', function () { show(j); }); });

    // Tap zones and swipe on the big photo.
    var main = $('.xp-main', root), sx = 0, moved = false;
    if (main) {
      main.addEventListener('pointerdown', function (e) { sx = e.clientX; moved = false; });
      main.addEventListener('pointerup', function (e) {
        var dx = e.clientX - sx;
        if (Math.abs(dx) > 45 && n > 1) { moved = true; show(dx < 0 ? idx + 1 : idx - 1); }
      });
      main.addEventListener('click', function (e) {
        var slide = e.target.closest('.xp-slide'); if (!slide) { return; }
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; return; }
        if (e.detail === 0 || n < 2) { openLightbox(idx); return; }
        var r = main.getBoundingClientRect(), x = (e.clientX - r.left) / Math.max(1, r.width);
        if (x < 0.3) { show(idx - 1); } else if (x > 0.7) { show(idx + 1); } else { openLightbox(idx); }
      }, true);
      main.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { show(idx + 1); } else if (e.key === 'ArrowLeft') { show(idx - 1); }
      });
    }

    // Lightbox
    var lb = $('#xlightbox'), lbImg = lb && $('[data-lb-img]', lb), lbCount = lb && $('[data-lb-count]', lb), cur = 0;
    function lbShow(k) {
      if (!lb || !pics.length) { return; }
      cur = (k + pics.length) % pics.length;
      var p = pics[cur], img = slides[cur] && $('img', slides[cur]);
      lbImg.src = p.url; lbImg.className = 'xlb-img' + (p.blurred && img ? ' ' + (img.className.match(/ch-blurred|ch-blur-\w+/g) || []).join(' ') : '');
      lbImg.alt = (root.getAttribute('data-name') || '') + ', photo ' + (cur + 1) + ' of ' + pics.length;
      if (lbCount) { lbCount.textContent = (cur + 1) + ' / ' + pics.length; }
      viewed[cur] = true;
    }
    function openLightbox(k) { if (!lb) { return; } lbShow(k); CH.modal.open(lb); }
    if (lb) {
      var lp = $('[data-lb-prev]', lb), ln = $('[data-lb-next]', lb), lsx = 0;
      lp && lp.addEventListener('click', function () { lbShow(cur - 1); });
      ln && ln.addEventListener('click', function () { lbShow(cur + 1); });
      lb.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') { lbShow(cur + 1); } else if (e.key === 'ArrowLeft') { lbShow(cur - 1); } });
      lb.addEventListener('pointerdown', function (e) { lsx = e.clientX; });
      lb.addEventListener('pointerup', function (e) { var dx = e.clientX - lsx; if (Math.abs(dx) > 50 && pics.length > 1) { lbShow(dx < 0 ? cur + 1 : cur - 1); } });
      lb.addEventListener('close', function () { show(cur); });
      lb.addEventListener('click', function (e) { if (e.target === lb || e.target.classList.contains('xlb-stage')) { lb.close(); } });
    }
    show(0);

    // How long the member looked at this profile (feeds match scoring). One beacon each time the page is hidden.
    var tid = root.getAttribute('data-track-profile');
    if (tid) {
      var start = Date.now(), acc = 0, maxScroll = 0, visible = !doc.hidden;
      window.addEventListener('scroll', function () {
        var h = doc.documentElement.scrollHeight - window.innerHeight;
        if (h > 0) { maxScroll = Math.max(maxScroll, Math.round(window.scrollY / h * 100)); }
      }, { passive: true });
      var flush = function () {
        if (visible) { acc += Date.now() - start; visible = false; }
        if (acc < 800) { return; }
        var fd = new FormData();
        fd.append('_csrf', CH.config.csrf || ''); fd.append('user_id', tid); fd.append('dwell_ms', String(Math.min(acc, 600000)));
        fd.append('photos', String(Object.keys(viewed).length)); fd.append('scroll', String(maxScroll));
        try { navigator.sendBeacon(CH.url('/api/explorer/track'), fd); } catch (x) { /* ignore */ }
        acc = 0;
      };
      doc.addEventListener('visibilitychange', function () { if (doc.hidden) { flush(); } else { start = Date.now(); visible = true; } });
      window.addEventListener('pagehide', flush);
    }
  }

  /* ------------------------------------------------------------------ photo manager */
  function initPhotos(root) {
    var cfg = readJson('#xphotos-data') || { photos: [], limit: 6 };
    var photos = cfg.photos.slice(), limit = cfg.limit, grid = $('[data-ph-grid]', root), queue = $('[data-ph-queue]', root);
    var countEl = $('[data-ph-count]', root), emptyEl = $('[data-ph-empty]', root), input = $('[data-ph-input]', root), drop = $('[data-ph-drop]', root);
    var min = +root.getAttribute('data-min') || 0, busy = false, dragId = null;

    function live() { return photos.filter(function (p) { return p.status !== 'rejected'; }); }
    function toneBadge(p) {
      if (p.status === 'pending') { return '<span class="badge badge-warning">' + ico('clock', 'w-3 h-3') + 'In review</span>'; }
      if (p.status === 'rejected') { return '<span class="badge badge-danger">Not accepted</span>'; }
      return '';
    }
    function tool(act, icon, label, extra) { return '<button type="button" class="xph-tool" data-ph-act="' + act + '" aria-label="' + esc(label) + '" data-tip="' + esc(label) + '"' + (extra || '') + '>' + ico(icon, 'w-4 h-4') + '</button>'; }
    function draw() {
      var n = photos.length;
      grid.innerHTML = '';
      photos.forEach(function (p, k) {
        var li = doc.createElement('li');
        li.className = 'xph-tile' + (p.primary ? ' is-primary' : '') + (p.status !== 'approved' ? ' is-' + p.status : '');
        li.dataset.id = p.id; li.draggable = n > 1;
        var tools = '';
        if (n > 1) {
          tools += k > 0 ? tool('left', 'chevron-left', 'Move earlier') : '';
          tools += k < n - 1 ? tool('right', 'chevron-right', 'Move later') : '';
        }
        if (!p.primary && p.status === 'approved') { tools += tool('primary', 'star', 'Make main photo'); }
        if (cfg.canVip && !p.primary && p.status !== 'rejected') { tools += tool('vip', 'crown', p.vip ? 'Visible to everyone' : 'VIP members only', ' aria-pressed="' + (p.vip ? 'true' : 'false') + '"'); }
        tools += tool('delete', 'trash-2', 'Delete photo');
        li.innerHTML =
          '<div class="xph-img"><img src="' + esc(p.thumb || p.url) + '" alt="' + esc('Photo ' + (k + 1) + ' of ' + n + (p.primary ? ', main photo' : '')) + '" draggable="false" loading="lazy"></div>' +
          '<span class="xph-badges">' + (p.primary ? '<span class="badge badge-primary">Main</span>' : '') + (p.vip ? '<span class="badge badge-vip">' + ico('crown', 'w-3 h-3') + 'VIP</span>' : '') + toneBadge(p) + '</span>' +
          '<div class="xph-tools">' + tools + '</div>' +
          (p.status !== 'approved' && p.status_text ? '<p class="xph-note xph-note-' + esc(p.tone || 'warning') + '">' + esc(p.status_text) + '</p>' : '');
        grid.appendChild(li);
      });
      var shown = live().length;
      if (countEl) { countEl.textContent = shown; }
      if (emptyEl) { emptyEl.hidden = n > 0; }
      grid.hidden = n === 0;
      root.classList.toggle('is-short', min > 0 && shown < min);
      var full = shown >= limit;
      if (drop) { drop.classList.toggle('is-full', full); drop.setAttribute('aria-disabled', full ? 'true' : 'false'); }
      if (input) { input.disabled = full; }
    }
    function apply(j) {
      if (j.photos) { photos = j.photos; }
      if (j.limit) { limit = j.limit; }
      draw();
    }
    function call(action, data, okMsg) {
      if (busy) { return Promise.resolve(); }
      busy = true; root.classList.add('is-busy');
      return post('/api/photos/' + action, data).then(function (j) {
        busy = false; root.classList.remove('is-busy');
        if (j.ok) { apply(j); if (okMsg !== false) { CH.toast(j.message || okMsg || 'Saved.', 'success', 2200); } }
        else if (!handleDenied(j)) { CH.toast(j.error || 'That did not work. Please try again.', 'error'); if (j.photos) { apply(j); } }
        return j;
      });
    }
    function saveOrder(ids) {
      var before = photos.slice();
      photos = ids.map(function (id) { return photos.filter(function (p) { return p.id === id; })[0]; }).filter(Boolean);
      draw();
      busy = false;
      call('reorder', { order: ids }, 'Order saved.').then(function (j) { if (!j || !j.ok) { photos = before; draw(); } });
    }

    grid.addEventListener('click', function (e) {
      var b = e.target.closest('[data-ph-act]'); if (!b) { return; }
      var tile = b.closest('.xph-tile'), id = +tile.dataset.id, act = b.getAttribute('data-ph-act');
      var ids = photos.map(function (p) { return p.id; }), at = ids.indexOf(id);
      if (act === 'delete') {
        CH.confirm({ title: 'Delete this photo?', message: 'It will be removed from your profile.', confirm: 'Delete' }).then(function (ok) { if (ok) { call('delete', { photo_id: id }, 'Photo deleted.'); } });
      } else if (act === 'primary') { call('primary', { photo_id: id }, 'Main photo updated.'); }
      else if (act === 'vip') { call('vip', { photo_id: id, on: b.getAttribute('aria-pressed') === 'true' ? 0 : 1 }); }
      else if (act === 'left' && at > 0) { ids.splice(at - 1, 0, ids.splice(at, 1)[0]); saveOrder(ids); }
      else if (act === 'right' && at < ids.length - 1) { ids.splice(at + 1, 0, ids.splice(at, 1)[0]); saveOrder(ids); }
    });
    // Drag to reorder (mouse). Touch uses the arrow buttons.
    grid.addEventListener('dragstart', function (e) {
      var t = e.target.closest && e.target.closest('.xph-tile'); if (!t) { return; }
      dragId = +t.dataset.id; t.classList.add('is-dragging'); try { e.dataTransfer.setData('text/plain', String(dragId)); e.dataTransfer.effectAllowed = 'move'; } catch (x) { /* ignore */ }
    });
    grid.addEventListener('dragover', function (e) {
      var t = e.target.closest && e.target.closest('.xph-tile'); if (!t || dragId === null) { return; }
      e.preventDefault(); $$('.xph-tile.is-over', grid).forEach(function (x) { x.classList.remove('is-over'); }); if (+t.dataset.id !== dragId) { t.classList.add('is-over'); }
    });
    grid.addEventListener('dragend', function () { dragId = null; $$('.xph-tile', grid).forEach(function (x) { x.classList.remove('is-over', 'is-dragging'); }); });
    grid.addEventListener('drop', function (e) {
      var t = e.target.closest && e.target.closest('.xph-tile'); if (!t || dragId === null) { return; }
      e.preventDefault();
      var to = +t.dataset.id, ids = photos.map(function (p) { return p.id; }), from = ids.indexOf(dragId), dest = ids.indexOf(to);
      dragId = null;
      if (from < 0 || dest < 0 || from === dest) { draw(); return; }
      ids.splice(dest, 0, ids.splice(from, 1)[0]); saveOrder(ids);
    });

    // Uploading
    function upload(file, row) {
      return new Promise(function (resolve) {
        var xhr = new XMLHttpRequest(), fd = new FormData(), bar = $('i', row), msg = $('.xph-q-msg', row);
        fd.append('photo', file);
        xhr.open('POST', CH.url('/api/photos/upload'));
        xhr.setRequestHeader('X-CSRF-Token', CH.config.csrf || ''); xhr.setRequestHeader('Accept', 'application/json'); xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.upload.onprogress = function (e) { if (e.lengthComputable && bar) { bar.style.width = Math.round(e.loaded / e.total * 100) + '%'; } };
        xhr.onload = function () {
          var j; try { j = JSON.parse(xhr.responseText); } catch (x) { j = { ok: false, error: 'Unexpected response. Please try again.' }; }
          if (bar) { bar.style.width = '100%'; }
          if (j.ok) { row.classList.add('is-done'); if (msg) { msg.textContent = 'Added'; } apply(j); setTimeout(function () { row.remove(); }, 1400); }
          else { row.classList.add('is-error'); if (msg) { msg.textContent = j.error || 'Upload failed.'; } addDismiss(row); }
          resolve(j);
        };
        xhr.onerror = function () { row.classList.add('is-error'); if (msg) { msg.textContent = 'Upload failed. Check your connection.'; } addDismiss(row); resolve({ ok: false }); };
        xhr.send(fd);
      });
    }
    function addDismiss(row) {
      var b = doc.createElement('button'); b.type = 'button'; b.className = 'btn btn-ghost btn-xs'; b.textContent = 'Dismiss'; b.addEventListener('click', function () { row.remove(); }); row.appendChild(b);
    }
    function pick(files) {
      var okTypes = ['image/jpeg', 'image/png', 'image/webp'], room = limit - live().length, list = Array.prototype.slice.call(files), run = [];
      if (!list.length) { return; }
      if (room <= 0) { CH.toast('You have reached the limit of ' + limit + ' photos.' + (cfg.upgradeUrl ? ' Delete one or upgrade for more.' : ''), 'info'); return; }
      list.forEach(function (f) {
        if (okTypes.indexOf(f.type) === -1) { CH.toast(f.name + ' is not a JPG, PNG or WebP picture.', 'error'); return; }
        if (f.size > cfg.maxMb * 1048576) { CH.toast(f.name + ' is larger than ' + cfg.maxMb + ' MB.', 'error'); return; }
        if (run.length >= room) { return; }
        run.push(f);
      });
      if (list.length > room) { CH.toast('Only ' + plural(room, 'more photo') + ' fit in your gallery, so the rest were skipped.', 'info'); }
      var chain = Promise.resolve(), added = 0, pending = false;
      run.forEach(function (f) {
        var row = doc.createElement('li'); row.className = 'xph-q';
        var url = URL.createObjectURL(f);
        row.innerHTML = '<img src="' + url + '" alt=""><div class="xph-q-body"><span class="xph-q-name">' + esc(f.name) + '</span><span class="xph-prog"><i></i></span><span class="xph-q-msg">Uploading…</span></div>';
        queue.appendChild(row);
        chain = chain.then(function () { return upload(f, row); }).then(function (j) { URL.revokeObjectURL(url); if (j && j.ok) { added++; pending = pending || /checked/.test(j.message || ''); } });
      });
      chain.then(function () {
        if (added) { CH.toast(added === 1 ? 'Photo added.' + (pending ? ' It will appear once our team has checked it.' : '') : added + ' photos added.', 'success', 3200); }
      });
    }
    if (input) {
      input.addEventListener('change', function () { pick(input.files); input.value = ''; });
    }
    if (drop) {
      drop.addEventListener('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && e.target === drop) { e.preventDefault(); input && input.click(); } });
      drop.addEventListener('click', function (e) {
        if (drop.classList.contains('is-full')) { e.preventDefault(); CH.toast('You have reached the limit of ' + limit + ' photos.', 'info'); }
      });
    }
    // Onboarding: don't leave the photo step without enough photos.
    var form = root.closest('form');
    if (form && min > 0) {
      form.addEventListener('submit', function (e) {
        var sub = e.submitter;
        if (sub && sub.value === 'skip') { return; }
        if (live().length < min) {
          e.preventDefault(); e.stopImmediatePropagation();
          CH.toast('Add at least ' + plural(min, 'photo') + ' to continue.', 'error');
          drop && drop.focus();
        }
      }, true);
    }
    draw();
  }

  /* ------------------------------------------------------------------ onboarding */
  function initWizard(root) {
    var form = $('[data-wizard-form]', root), step = root.getAttribute('data-step');
    var firstErr = $('.has-error, .error-text', root);
    if (firstErr) { setTimeout(function () { firstErr.scrollIntoView({ block: 'center', behavior: reduced() ? 'auto' : 'smooth' }); }, 120); }
    if (!form || step !== 'finish') { return; }
    form.addEventListener('submit', function (e) {
      if (e.defaultPrevented) { return; }
      e.preventDefault(); e.stopImmediatePropagation();
      var btn = $('[data-wizard-next]', form);
      btn.classList.add('is-loading'); btn.disabled = true;
      CH.api('/onboarding', { data: new FormData(form) }).then(function (j) {
        if (!j.ok) {
          btn.classList.remove('is-loading'); btn.disabled = false;
          CH.toast(j.error || 'Something went wrong. Please try again.', 'error');
          if (j.goto) { setTimeout(function () { location.href = CH.url('/onboarding?step=' + j.goto); }, 900); }
          return;
        }
        var go = function () { location.href = j.redirect || CH.url('/discover'); };
        var mode = j.celebrate || 'confetti';
        if (mode === 'none') { go(); return; }
        var card = $('.xob-card', root);
        if (card) { card.classList.add('is-celebrating'); }
        var title = $('.xob-title h1', root), sub = $('.xob-title p', root);
        if (title) { title.textContent = 'Welcome aboard'; }
        if (sub) { sub.textContent = 'Your profile is live. Taking you to Discover…'; }
        if (mode === 'confetti') { CH.config.confetti = true; CH.confetti(); }
        setTimeout(go, mode === 'confetti' && !reduced() ? 2200 : 1100);
      });
    }, true);
  }

  /* ------------------------------------------------------------------ edit profile */
  function initEdit(form) {
    var dirty = false, sending = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { sending = true; });
    window.addEventListener('beforeunload', function (e) { if (dirty && !sending) { e.preventDefault(); e.returnValue = ''; } });
    var links = $$('.xedit-nav a[href^="#"]'), map = {};
    links.forEach(function (a) { var s = $(a.getAttribute('href')); if (s) { map[s.id] = a; } });
    if ('IntersectionObserver' in window && links.length) {
      var io = new IntersectionObserver(function (en) {
        en.forEach(function (x) { if (x.isIntersecting) { links.forEach(function (a) { a.classList.toggle('is-active', a === map[x.target.id]); }); } });
      }, { rootMargin: '-30% 0px -60% 0px' });
      Object.keys(map).forEach(function (id) { io.observe($('#' + id)); });
    }
  }

  /* ------------------------------------------------------------------ settings */
  function initTheme(form) {
    function apply(pref) {
      var dark = pref === 'dark' || (pref === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
      doc.documentElement.dataset.theme = dark ? 'dark' : 'light';
    }
    var saved = store('ch-theme');
    if (saved && ['light', 'dark', 'system'].indexOf(saved) !== -1) { var r = $('input[value="' + saved + '"]', form); if (r) { r.checked = true; } }
    form.addEventListener('change', function (e) {
      var pref = e.target.value;
      if (['light', 'dark', 'system'].indexOf(pref) === -1) { return; }
      store('ch-theme', pref); apply(pref);
      post('/api/theme', { theme: pref }).then(function (j) { if (j.ok) { CH.toast('Appearance saved.', 'success', 1800); } else { CH.toast(j.error || 'Could not save that.', 'error'); } });
    });
  }
  doc.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.matches || !f.matches('[data-unblock-form]') || e.defaultPrevented) { return; }
    e.preventDefault();
    var row = f.closest('li'), btn = $('button', f);
    btn.disabled = true;
    post('/api/unblock', { user_id: +f.elements.user_id.value }).then(function (j) {
      if (!j.ok) { btn.disabled = false; CH.toast(j.error || 'Could not unblock.', 'error'); return; }
      CH.toast(j.message || 'Unblocked.', 'success');
      removeCard(row, function (p) { if (p && !$('li', p)) { location.reload(); } });
    });
  }, true);

  /* ------------------------------------------------------------------ verification */
  function initVerify(form) {
    $$('[data-xv-drop]', form).forEach(function (box) {
      var input = $('[data-xv-input]', box), img = $('[data-xv-preview]', box), empty = $('[data-xv-empty]', box), change = $('[data-xv-change]', box);
      input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f) { return; }
        if (!/^image\//.test(f.type)) { CH.toast('Choose a picture (JPG, PNG or WebP).', 'error'); input.value = ''; return; }
        img.src = URL.createObjectURL(f); img.hidden = false;
        if (empty) { empty.hidden = true; }
        if (change) { change.hidden = false; }
        box.classList.add('has-file'); box.classList.remove('has-error');
      });
    });
    form.addEventListener('submit', function (e) {
      var missing = $$('[data-xv-input]', form).filter(function (i) { return !i.files || !i.files.length; })[0];
      if (missing) {
        e.preventDefault(); e.stopImmediatePropagation();
        CH.toast('Add the picture' + ($$('[data-xv-input]', form).length > 1 ? 's' : '') + ' first.', 'error');
        missing.closest('[data-xv-drop]').classList.add('has-error'); missing.focus();
      }
    }, true);
  }

  /* The Search filters are a drawer on small screens and a fixed sidebar on large ones (the core script makes drawers inert). */
  function initSideFilters() {
    var mq = window.matchMedia ? window.matchMedia('(min-width: 1024px)') : null;
    function sync() {
      $$('.xdrawer-lg').forEach(function (d) {
        var wide = mq && mq.matches, open = d.classList.contains('is-open');
        d.inert = wide ? false : !open;
        d.setAttribute('aria-hidden', wide || open ? 'false' : 'true');
      });
    }
    if (mq && mq.addEventListener) { mq.addEventListener('change', sync); }
    sync();
  }

  /* Filter forms: leave empty and default-looking fields out of the address so shared links stay short. */
  doc.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.matches || !f.matches('form[data-xfilters]')) { return; }
    $$('input, select', f).forEach(function (el) {
      if (el.disabled || !el.name || el.type === 'hidden' && el.name === 'view') { return; }
      var v = el.value;
      if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) { return; }
      if (v === '' || ((el.name === 'county' || el.name === 'town') && v === '0')) { el.disabled = true; setTimeout(function () { el.disabled = false; }, 0); }
    });
  });

  /* ------------------------------------------------------------------ boot */
  ready(function () {
    $$('[data-range-pair]').forEach(syncPair);
    initSideFilters();
    var dd = $('[data-xdiscover]');
    if (dd) {
      var data = readJson('#xdiscover-data') || {};
      Deck.rem = data.remaining || null;
      if (data.view === 'swipe') { initDeck(data); }
      var feed = $('[data-feed]', dd); if (feed) { initFeed(feed); }
    }
    var profile = $('[data-xprofile]'); if (profile) { initProfile(profile); }
    $$('[data-photos]').forEach(initPhotos);
    var wiz = $('[data-wizard]'); if (wiz) { initWizard(wiz); }
    $$('[data-interest-picker]').forEach(initInterestPicker);
    var edit = $('[data-xedit]'); if (edit) { initEdit(edit); }
    var th = $('[data-theme-form]'); if (th) { initTheme(th); }
    var xv = $('[data-xverify]'); if (xv) { initVerify(xv); }
  });
})();
