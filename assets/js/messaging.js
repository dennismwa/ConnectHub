/* ConnectHub messaging: the inbox, one conversation, and the notifications page.
   Vanilla JS on top of window.CH (assets/js/app.js). The server renders the inbox list and the thread shell;
   this file draws the messages, sends and polls. */
(function () {
  'use strict';
  var doc = document;
  var CH = window.CH;
  if (!CH) { return; }
  var $ = CH.$, $$ = CH.$$;
  var SPRITE = CH.url('/assets/icons/sprite.svg');
  var DENIAL_CODES = ['upgrade', 'match', 'limit', 'blocked', 'disabled', 'unavailable', 'login', 'self'];

  function ico(name, cls) {
    return '<svg class="icon ' + (cls || 'w-4 h-4') + '" aria-hidden="true"><use href="' + SPRITE + '#i-' + name + '"></use></svg>';
  }
  function esc(s) { return CH.esc(s); }
  function store(get, key, val) {
    try { if (get) { return sessionStorage.getItem(key); } if (val === null) { sessionStorage.removeItem(key); } else { sessionStorage.setItem(key, val); } } catch (e) { /* storage can be blocked */ }
    return null;
  }
  function timeText(ts) {
    try { return new Date(ts * 1000).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); } catch (e) { return ''; }
  }
  function dayKey(ts) { var d = new Date(ts * 1000); return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate(); }
  function dayLabel(ts) {
    var d = new Date(ts * 1000), now = new Date();
    var diff = Math.round((new Date(now.getFullYear(), now.getMonth(), now.getDate()) - new Date(d.getFullYear(), d.getMonth(), d.getDate())) / 86400000);
    if (diff === 0) { return 'Today'; }
    if (diff === 1) { return 'Yesterday'; }
    try {
      if (diff > 1 && diff < 7) { return d.toLocaleDateString([], { weekday: 'long' }); }
      return d.toLocaleDateString([], { day: 'numeric', month: 'short', year: d.getFullYear() === now.getFullYear() ? undefined : 'numeric' });
    } catch (e) { return ''; }
  }

  /* ------------------------------------------------------------------ nav badges */
  function setCount(el, n, text) {
    el.textContent = n > 99 ? '99+' : String(n);
    el.hidden = n <= 0;
    if (text !== undefined) { el.setAttribute('aria-label', text); }
  }
  function navBadge(total) {
    total = Math.max(0, total | 0);
    $$('a[href$="/messages"]').forEach(function (a) {
      if (a.closest('[data-msg-shell]') || a.classList.contains('btn')) { return; }
      var b = $('.count-badge', a);
      if (!b && total > 0) {
        b = doc.createElement('span');
        if (a.classList.contains('side-link')) { b.className = 'count-badge ml-auto'; a.appendChild(b); }
        else if (a.classList.contains('header-icon-btn')) { b.className = 'count-badge absolute -top-0.5 -right-0.5'; a.appendChild(b); }
        else if (a.classList.contains('tab-link')) { var host = $('.relative', a); if (host) { b.className = 'count-badge absolute -top-2 -right-3 !min-w-[18px] !h-[18px] !text-[10px]'; host.appendChild(b); } else { b = null; } }
        else { b = null; }
      }
      if (b) { setCount(b, total); }
    });
    $$('[data-unread-total]').forEach(function (b) { setCount(b, total); b.classList.toggle('hidden', total <= 0); });
  }
  CH.msg = { navBadge: navBadge };

  /* ================================================================== SHELL (inbox list) */
  function Shell(el) {
    var self = this;
    var list = $('[data-conv-list]', el);
    var search = $('[data-conv-search]', el);
    var pane = $('[data-thread-pane]', el);
    var empty = $('[data-list-empty]', el);
    var nomatch = $('[data-list-nomatch]', el);
    var view = el.getAttribute('data-view') || 'active';
    var filter = 'all', query = '', timer = null, fetching = false, thread = null, emptyPaneHtml = null;
    var desktop = window.matchMedia('(min-width: 1024px)');

    function rows() { return $$('.msg-row', list); }

    function applyFilter() {
      var shown = 0, all = rows();
      all.forEach(function (r) {
        var ok = (!query || (r.getAttribute('data-name') || '').indexOf(query) !== -1 || (r.getAttribute('data-preview') || '').indexOf(query) !== -1) &&
          (filter === 'all' || parseInt(r.getAttribute('data-unread') || '0', 10) > 0);
        r.hidden = !ok;
        if (ok) { shown++; }
      });
      if (empty) { empty.hidden = all.length > 0; }
      if (nomatch) {
        nomatch.hidden = all.length === 0 || shown > 0;
        nomatch.textContent = query ? 'No conversations match your search.' : 'No unread conversations. You are all caught up.';
      }
    }

    if (search) {
      search.addEventListener('input', function () { query = search.value.trim().toLowerCase(); applyFilter(); });
      search.addEventListener('keydown', function (e) { if (e.key === 'Escape') { search.value = ''; query = ''; applyFilter(); } });
    }
    $$('[data-conv-filter]', el).forEach(function (b) {
      b.addEventListener('click', function () {
        filter = b.getAttribute('data-conv-filter');
        $$('[data-conv-filter]', el).forEach(function (o) { var on = o === b; o.classList.toggle('is-active', on); o.setAttribute('aria-selected', on ? 'true' : 'false'); });
        applyFilter();
      });
    });

    /* ---- refresh the list when something changed (signature comparison keeps it cheap) ---- */
    var again = false;
    function refresh() {
      if (fetching) { again = true; return Promise.resolve(); }
      fetching = true;
      var open = parseInt(el.getAttribute('data-open') || '0', 10) || 0;
      var url = '/api/messages/list?view=' + encodeURIComponent(view) + '&open=' + open + '&active=' + (doc.hidden ? 0 : 1) + '&sig=' + encodeURIComponent(el.getAttribute('data-sig') || '');
      return CH.api(url, { quiet: true }).then(function (j) {
        fetching = false;
        if (again) { again = false; setTimeout(refresh, 250); }
        if (!j || !j.ok) { return; }
        if (typeof j.unread_total === 'number') { navBadge(j.unread_total); }
        if (!j.changed) { return; }
        el.setAttribute('data-sig', j.sig);
        rows().forEach(function (r) { r.remove(); });
        var tmp = doc.createElement('div');
        tmp.innerHTML = j.html || '';
        while (tmp.firstChild) { list.insertBefore(tmp.firstChild, empty || nomatch || null); }
        applyFilter();
      });
    }
    self.refresh = refresh;
    function schedule() {
      clearTimeout(timer);
      timer = setTimeout(function () { if (!doc.hidden) { refresh().then(schedule, schedule); } else { schedule(); } }, doc.hidden ? 20000 : 9000);
    }
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden) { refresh(); } });
    schedule();

    /* ---- open a thread without a page load on wide screens ---- */
    function mountThread() {
      var t = $('[data-thread]', pane);
      if (thread) { thread.destroy(); thread = null; }
      if (t) { thread = new Thread(t, self); }
    }
    function setActive(id) {
      el.setAttribute('data-open', String(id || 0));
      el.classList.toggle('has-thread', !!id);
      doc.body.classList.toggle('msg-thread-open', !!id);
      rows().forEach(function (r) {
        var on = parseInt(r.getAttribute('data-conv'), 10) === id;
        r.classList.toggle('is-active', on);
        if (on) {
          r.setAttribute('aria-current', 'page');
          r.setAttribute('data-unread', '0');
          r.classList.remove('is-unread');
          var cb = $('.count-badge', r); if (cb) { cb.remove(); }
        } else { r.removeAttribute('aria-current'); }
      });
      applyFilter();
    }
    function openThread(id, push, href) {
      if (!pane) { location.href = href; return; }
      if (emptyPaneHtml === null && !$('[data-thread]', pane)) { emptyPaneHtml = pane.innerHTML; }
      pane.classList.add('is-loading');
      fetch(CH.url('/messages/' + id + '?partial=1'), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) { throw new Error('status ' + r.status); } return r.text(); })
        .then(function (html) {
          if (/data-thread/.test(html) === false) { throw new Error('no thread'); }
          pane.innerHTML = html;
          pane.classList.remove('is-loading');
          mountThread();
          setActive(id);
          var t = $('[data-thread]', pane);
          if (t) { doc.title = (t.getAttribute('data-title') || 'Messages') + ' · ' + (CH.config.siteName || ''); }
          if (push) { try { history.pushState({ conv: id }, '', CH.url('/messages/' + id)); } catch (e) { /* ignore */ } }
          setTimeout(refresh, 400);
        })
        .catch(function () { location.href = href; });
    }
    function clearThread(push) {
      if (thread) { thread.destroy(); thread = null; }
      if (pane && emptyPaneHtml !== null) { pane.innerHTML = emptyPaneHtml; }
      setActive(0);
      doc.title = 'Messages · ' + (CH.config.siteName || '');
      if (push) { try { history.pushState({ conv: 0 }, '', CH.url('/messages')); } catch (e) { /* ignore */ } }
    }
    list.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a.msg-row') : null;
      if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) { return; }
      if (!desktop.matches || view === 'archived') { return; }
      e.preventDefault();
      var id = parseInt(a.getAttribute('data-conv'), 10);
      if (id === (parseInt(el.getAttribute('data-open') || '0', 10) || 0)) { return; }
      openThread(id, true, a.href);
    });
    window.addEventListener('popstate', function () {
      if (!desktop.matches) { location.reload(); return; }
      var m = location.pathname.match(/\/messages\/(\d+)\/?$/);
      if (m) { openThread(parseInt(m[1], 10), false, location.href); } else if (/\/messages\/?$/.test(location.pathname)) { clearThread(false); }
    });
    if ($('[data-thread]', pane)) { mountThread(); } else { emptyPaneHtml = pane ? pane.innerHTML : null; }
    applyFilter();
  }

  /* ================================================================== THREAD */
  function Thread(el, shell) {
    var self = this;
    var boot = {};
    try { boot = JSON.parse($('[data-boot]', el).textContent); } catch (e) { return; }
    var ac = new AbortController();
    var sig = { signal: ac.signal };
    var convId = boot.conv.id;
    var peer = boot.peer;
    var scroll = $('[data-scroll]', el), inner = $('.msg-scroll-inner', el), ol = $('[data-items]', el);
    var typingEl = $('[data-typing]', el), moreBtn = $('[data-more]', el), emptyEl = $('[data-empty]', el), starters = $('[data-starters]', el);
    var jump = $('[data-jump]', el), jumpText = jump ? $('span', jump) : null;
    var composer = $('[data-composer]', el), input = $('[data-input]', el), sendBtn = $('[data-send]', el);
    var fileInput = $('[data-file]', el), attach = $('[data-attach]', el), attachImg = $('[data-attach-img]', el);
    var emojiPanel = $('[data-emoji]', el), hint = $('[data-hint]', el);
    var denied = $('[data-denied]', el), deniedText = $('[data-denied-text]', el), deniedCta = $('[data-denied-cta]', el);
    var statusEl = $('[data-status]', el), presenceEl = $('[data-presence]', el);
    var entries = [];            // { m, li } in display order: saved messages by id, then ones still sending
    var byId = {};
    var lastId = boot.last_id || 0;
    var hasMore = !!boot.has_more;
    var stick = true, sending = 0, queue = Promise.resolve(), tmpSeq = 0, tick = 0, fails = 0, pollTimer = null, polling = false, destroyed = false;
    var file = null, lastTyping = 0, newCount = 0, typingNow = false, hintTimer = null, reportTarget = null;
    var access = boot.access;
    var draftKey = 'ch-draft-' + convId;

    /* ---------- building one message ---------- */
    function metaHtml(m) {
      var t = (m.edited ? '<span class="msg-edited">edited</span>' : '') + '<time datetime="' + new Date(m.ts * 1000).toISOString() + '">' + esc(timeText(m.ts)) + '</time>';
      if (!m.mine) { return '<span class="msg-meta">' + t + '</span>'; }
      var s;
      if (m.status === 'sending') { s = ico('clock', 'w-3.5 h-3.5') + '<span class="sr-only">Sending</span>'; }
      else if (m.status === 'failed') { s = ico('alert-circle', 'w-3.5 h-3.5') + '<span class="sr-only">Not sent</span>'; }
      else if (m.read) { s = ico('check-check', 'w-3.5 h-3.5') + '<span class="sr-only">Read</span>'; }
      else { s = ico('check', 'w-3.5 h-3.5') + '<span class="sr-only">Sent</span>'; }
      return '<span class="msg-meta">' + t + '<span class="msg-tick' + (m.read ? ' is-read' : '') + '">' + s + '</span></span>';
    }
    function canEditNow(m) {
      if (!boot.can_edit || !m.mine || m.type !== 'text' || m.deleted || !m.text) { return false; }
      if (m.masked) { return false; }
      return !boot.edit_window || (Math.floor(Date.now() / 1000) - m.ts) <= boot.edit_window;
    }
    function fill(e) {
      var m = e.m, li = e.li, h = '';
      e.editing = false;
      li.className = 'msg-item ' + (m.system ? 'is-system' : (m.mine ? 'is-mine' : 'is-theirs')) + (m.deleted ? ' is-deleted' : '') + (m.status ? ' is-' + m.status : '');
      if (m.id) { li.setAttribute('data-id', m.id); } else { li.removeAttribute('data-id'); }
      if (m.system) {
        if (m.system.kind === 'gift') {
          h = '<div class="msg-sys msg-sys-gift"><span class="msg-sys-emoji" aria-hidden="true">' + esc(m.system.emoji || '🎁') + '</span><div class="msg-sys-body"><strong>' + esc(m.text) + '</strong>' +
            (m.system.note ? '<p>' + esc(m.system.note) + '</p>' : '') + '<time>' + esc(timeText(m.ts)) + '</time></div></div>';
        } else { h = '<div class="msg-sys"><span>' + esc(m.text) + '</span></div>'; }
        li.innerHTML = h;
        return;
      }
      if (m.deleted) {
        li.innerHTML = '<div class="msg-bubble is-deleted">' + ico('ban', 'w-4 h-4') + '<span>Message deleted</span></div>';
        return;
      }
      var hasImg = !!m.media;
      h = '<div class="msg-bubble' + (hasImg ? ' has-image' : '') + (m.text ? '' : ' no-text') + '" tabindex="0">';
      if (hasImg) {
        var ratio = m.w > 0 && m.h > 0 ? ' style="aspect-ratio:' + m.w + '/' + m.h + '"' : '';
        var im = '<img src="' + esc(m.media) + '" alt="Photo in chat"' + (m.w > 0 ? ' width="' + m.w + '" height="' + m.h + '"' : '') + ' decoding="async">';
        h += m.tmp ? '<span class="msg-photo"' + ratio + '>' + im + '</span>' : '<a class="msg-photo" href="' + esc(m.media) + '" data-lightbox' + ratio + '>' + im + '</a>';
      }
      if (m.text) { h += '<p class="msg-text">' + esc(m.text) + '</p>'; }
      if (m.masked) { h += '<span class="msg-masked">' + ico('eye-off', 'w-3.5 h-3.5') + ' Contact details hidden</span>'; }
      h += metaHtml(m) + '</div>';
      if (m.status === 'failed') {
        h += '<div class="msg-failed"><span>Not sent.</span><button type="button" data-act="retry">Try again</button><button type="button" data-act="discard">Discard</button></div>';
      } else if (m.id && !m.tmp) {
        h += '<div class="msg-actions" hidden>' + (m.mine
          ? (canEditNow(m) ? '<button type="button" data-act="edit" class="is-edit">' + ico('pencil', 'w-4 h-4') + ' Edit</button>' : '') + (boot.can_delete ? '<button type="button" data-act="delete">' + ico('trash-2', 'w-4 h-4') + ' Delete</button>' : '')
          : '<button type="button" data-act="report-msg">' + ico('flag', 'w-4 h-4') + ' Report</button>') + '</div>';
      }
      li.innerHTML = h;
    }
    function makeEntry(m) {
      var li = doc.createElement('li');
      var e = { m: m, li: li };
      fill(e);
      return e;
    }

    /* ---------- grouping, day separators ---------- */
    function relayout() {
      $$('.msg-day', ol).forEach(function (d) { d.remove(); });
      var prev = null;
      entries.forEach(function (e, i) {
        var m = e.m, next = entries[i + 1] ? entries[i + 1].m : null;
        if (!prev || dayKey(prev.ts) !== dayKey(m.ts)) {
          var d = doc.createElement('li');
          d.className = 'msg-day'; d.setAttribute('role', 'separator');
          d.innerHTML = '<span>' + esc(dayLabel(m.ts)) + '</span>';
          ol.insertBefore(d, e.li);
        }
        var joinsPrev = prev && !prev.system && !m.system && prev.mine === m.mine && dayKey(prev.ts) === dayKey(m.ts) && m.ts - prev.ts < 300;
        var joinsNext = next && !next.system && !m.system && next.mine === m.mine && dayKey(next.ts) === dayKey(m.ts) && next.ts - m.ts < 300;
        e.li.classList.toggle('has-prev', !!joinsPrev);
        e.li.classList.toggle('has-next', !!joinsNext);
        prev = m;
      });
      emptyEl.hidden = entries.length > 0 || hasMore;
      if (moreBtn) { moreBtn.hidden = !hasMore; }
    }

    function atBottom() { return scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 90; }
    function toBottom(smooth) {
      stick = true;
      try { scroll.scrollTo({ top: scroll.scrollHeight, behavior: smooth && !CH.reduced() ? 'smooth' : 'auto' }); } catch (e) { scroll.scrollTop = scroll.scrollHeight; }
      hideJump();
    }
    function showJump(n) {
      if (!jump) { return; }
      newCount += n;
      jumpText.textContent = newCount > 1 ? newCount + ' new messages' : 'New message';
      jump.hidden = false;
    }
    function hideJump() { newCount = 0; if (jump) { jump.hidden = true; } }

    /* ---------- adding, replacing, removing ---------- */
    function addReal(list, incoming) {
      var added = 0, wasBottom = atBottom();
      list.forEach(function (m) {
        if (byId[m.id]) { return; }
        var e = makeEntry(m);
        byId[m.id] = e;
        // Saved messages go before anything that is still sending.
        var at = entries.length;
        while (at > 0 && entries[at - 1].m.tmp) { at--; }
        entries.splice(at, 0, e);
        ol.insertBefore(e.li, entries[at + 1] ? entries[at + 1].li : null);
        if (m.id > lastId) { lastId = m.id; }
        added++;
      });
      if (!added) { return; }
      relayout();
      if (incoming) {
        var fresh = list.filter(function (m) { return !m.mine; }).length;
        if (wasBottom) { toBottom(true); } else if (fresh) { showJump(fresh); }
      }
    }
    function addPending(m) {
      var e = makeEntry(m);
      entries.push(e);
      ol.appendChild(e.li);
      relayout();
      return e;
    }
    function removeEntry(e) {
      var i = entries.indexOf(e);
      if (i >= 0) { entries.splice(i, 1); }
      if (e.m.id) { delete byId[e.m.id]; }
      e.li.remove();
      relayout();
    }
    function entryOf(m) { for (var i = 0; i < entries.length; i++) { if (entries[i].m === m) { return entries[i]; } } return null; }
    function setDeleted(id) {
      var e = byId[id];
      if (!e || e.m.deleted) { return; }
      e.m.deleted = true; e.m.text = ''; e.m.media = null;
      fill(e);
    }
    function setEdited(nm) {
      var e = byId[nm.id];
      if (!e || e.m.deleted || (e.m.text === nm.text && e.m.edited === nm.edited) || e.editing) { return; }
      e.m.text = nm.text; e.m.edited = nm.edited; e.m.masked = nm.masked;
      fill(e);
    }
    function setReadUpto(upto) {
      if (!boot.receipts || !upto) { return; }
      entries.forEach(function (e) {
        if (e.m.mine && e.m.id && e.m.id <= upto && !e.m.read && !e.m.deleted) {
          e.m.read = true;
          var t = $('.msg-tick', e.li);
          if (t) { t.classList.add('is-read'); t.innerHTML = ico('check-check', 'w-3.5 h-3.5') + '<span class="sr-only">Read</span>'; }
        }
      });
    }

    /* ---------- presence, typing, access ---------- */
    function setPresence(p) {
      if (!p) { return; }
      peer.online = !!p.online; peer.seen = p.seen;
      if (presenceEl) { presenceEl.classList.toggle('is-online', !!p.online); }
      paintStatus();
    }
    function paintStatus() { if (statusEl) { statusEl.textContent = typingNow ? 'typing…' : (peer.online ? 'Online now' : (peer.seen || '')); statusEl.classList.toggle('is-typing', typingNow); } }
    function setTyping(on) {
      on = !!on && boot.typing_enabled;
      if (on === typingNow) { return; }
      typingNow = on;
      typingEl.hidden = !on;
      paintStatus();
      if (on && stick) { toBottom(false); }
    }
    function setAccess(a, remaining) {
      access = a || access;
      var ok = !!(access && access.allowed);
      denied.hidden = ok;
      composer.hidden = !ok;
      if (!ok) {
        deniedText.textContent = access.message || 'You cannot message this person right now.';
        var cta = access.cta || '';
        var label = { upgrade: 'See plans', limit: 'See plans', login: 'Sign in', paid: 'Get message credits' }[access.code] || 'Learn more';
        if (cta) { deniedCta.href = cta; deniedCta.textContent = label; deniedCta.hidden = false; } else { deniedCta.hidden = true; }
        if (access.code === 'limit') { deniedText.textContent = (access.message || 'You have used all of today\'s messages.'); }
      }
      if (typeof remaining === 'number') { showRemaining(remaining); }
      else if (ok) { showRemaining(boot.limits.remaining); }
    }
    function say(text, ms) {
      if (!hint) { return; }
      clearTimeout(hintTimer);
      hint.textContent = text || '';
      hintTimer = null;
      if (text && ms) { hintTimer = setTimeout(function () { hintTimer = null; hint.textContent = ''; showRemaining(boot.limits.remaining); }, ms); }
    }
    function showRemaining(n) {
      boot.limits.remaining = n;
      if (!hint || hintTimer) { return; }
      var dmNote = access && access.dm === 'new' ? 'Your first message uses 1 message credit.' : (access && access.dm === 'unlocked' && access.dm_left > 0 ? 'You can send ' + access.dm_left + (access.dm_left === 1 ? ' more message' : ' more messages') + ' until they reply.' : '');
      if (dmNote) { hint.textContent = dmNote; return; }
      hint.textContent = n >= 0 && n <= 5 ? (n === 0 ? 'No messages left today.' : n + (n === 1 ? ' message' : ' messages') + ' left today.') : '';
    }

    /* ---------- polling ---------- */
    function delay() { return doc.hidden ? 20000 : Math.min(30000, (boot.poll_ms || 3000) + fails * 4000); }
    function schedule(ms) { clearTimeout(pollTimer); if (!destroyed) { pollTimer = setTimeout(poll, ms === undefined ? delay() : ms); } }
    function poll() {
      if (destroyed) { return; }
      if (sending > 0 || polling) { schedule(700); return; }
      polling = true; tick++;
      var url = '/api/messages/poll?conversation=' + convId + '&after=' + lastId + '&active=' + (doc.hidden ? 0 : 1) + (tick % 6 === 0 ? '&access=1' : '');
      CH.api(url, { quiet: true }).then(function (j) {
        polling = false;
        if (destroyed) { return; }
        if (!j || !j.ok) {
          if (j && j._status === 404) { CH.toast('This conversation is no longer available.', 'error'); setTimeout(function () { location.href = CH.url('/messages'); }, 900); return; }
          fails = Math.min(fails + 1, 6); schedule(); return;
        }
        fails = 0;
        if (j.messages && j.messages.length) { addReal(j.messages, true); if (shell) { shell.refresh(); } }
        (j.deleted || []).forEach(setDeleted);
        (j.edited || []).forEach(setEdited);
        setReadUpto(j.read_upto);
        setTyping(j.typing);
        setPresence(j.peer);
        if (j.access) { setAccess(j.access, j.remaining); }
        if (typeof j.unread_total === 'number') { navBadge(j.unread_total); }
        if (j.last_id && j.last_id > lastId) { lastId = j.last_id; }
        schedule();
      });
    }
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden && !destroyed) { schedule(0); } }, sig);

    /* ---------- earlier messages ---------- */
    function loadEarlier() {
      if (!hasMore || moreBtn.disabled) { return; }
      var first = null;
      for (var i = 0; i < entries.length; i++) { if (entries[i].m.id) { first = entries[i].m.id; break; } }
      if (!first) { return; }
      moreBtn.disabled = true; moreBtn.textContent = 'Loading…';
      CH.api('/api/messages/poll?conversation=' + convId + '&before=' + first, { quiet: true }).then(function (j) {
        moreBtn.disabled = false; moreBtn.textContent = 'Load earlier messages';
        if (!j || !j.ok) { CH.toast((j && j.error) || 'Could not load earlier messages.', 'error'); return; }
        var prevH = scroll.scrollHeight, prevTop = scroll.scrollTop;
        var add = [];
        (j.messages || []).forEach(function (m) { if (!byId[m.id]) { var e = makeEntry(m); byId[m.id] = e; add.push(e); } });
        var anchor = entries.length ? entries[0].li : null;
        add.forEach(function (e) { ol.insertBefore(e.li, anchor ? anchor : null); });
        entries = add.concat(entries);
        hasMore = !!j.has_more;
        stick = false;
        relayout();
        scroll.scrollTop = prevTop + (scroll.scrollHeight - prevH);
      });
    }
    if (moreBtn) { moreBtn.addEventListener('click', loadEarlier, sig); }
    if (moreBtn && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting && hasMore && entries.length) { loadEarlier(); } }, { root: scroll, rootMargin: '120px 0px 0px 0px' });
      io.observe(moreBtn);
      sig.signal.addEventListener('abort', function () { io.disconnect(); });
    }

    /* ---------- composer ---------- */
    function autosize() {
      input.style.height = 'auto';
      input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }
    function refreshSend() { sendBtn.disabled = !(input.value.trim() !== '' || file); }
    function saveDraft() { store(false, draftKey, input.value ? input.value : null); }
    function setFile(f) {
      if (attachImg.src && attachImg.src.indexOf('blob:') === 0) { URL.revokeObjectURL(attachImg.src); }
      file = f || null;
      fileInput.value = '';
      attach.hidden = !file;
      if (file) { attachImg.src = URL.createObjectURL(file); } else { attachImg.removeAttribute('src'); }
      refreshSend();
    }
    function pickFile(f) {
      if (!f) { return; }
      if (!boot.limits.images) { say(boot.limits.images_why || 'Photos are not available in chats right now.', 5000); return; }
      if (!/^image\/(jpeg|png|webp|gif)$/.test(f.type)) { CH.toast('Choose a JPG, PNG, WebP or GIF photo.', 'error'); return; }
      if (f.size > boot.limits.max_mb * 1048576) { CH.toast('That photo is too big. The limit is ' + boot.limits.max_mb + ' MB.', 'error'); return; }
      setFile(f);
      input.focus();
    }
    function typingPing() {
      if (!boot.typing_enabled || !access.allowed) { return; }
      var now = Date.now();
      if (now - lastTyping < 3000) { return; }
      lastTyping = now;
      var fd = new FormData(); fd.append('conversation_id', convId);
      CH.api('/api/messages/typing', { data: fd, quiet: true });
    }
    input.addEventListener('input', function () { autosize(); refreshSend(); saveDraft(); if (input.value.trim() !== '') { typingPing(); } }, sig);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && !e.ctrlKey && !e.altKey && !e.metaKey) { e.preventDefault(); send(); }
      if (e.key === 'Escape' && emojiPanel && !emojiPanel.hidden) { toggleEmoji(false); }
    }, sig);
    input.addEventListener('paste', function (e) {
      var items = (e.clipboardData && e.clipboardData.files) || [];
      if (items.length && /^image\//.test(items[0].type)) { e.preventDefault(); pickFile(items[0]); }
    }, sig);
    input.addEventListener('focus', function () { setTimeout(function () { if (stick) { toBottom(false); } }, 250); }, sig);
    composer.addEventListener('submit', function (e) { e.preventDefault(); send(); }, sig);
    fileInput.addEventListener('change', function () { pickFile(fileInput.files && fileInput.files[0]); }, sig);

    function toggleEmoji(on) {
      if (!emojiPanel) { return; }
      emojiPanel.hidden = !on;
      var b = $('[data-act="emoji"]', el); if (b) { b.setAttribute('aria-expanded', on ? 'true' : 'false'); }
    }
    emojiPanel && emojiPanel.addEventListener('click', function (e) {
      var b = e.target.closest('[data-emoji-pick]'); if (!b) { return; }
      var s = input.selectionStart == null ? input.value.length : input.selectionStart, t = input.selectionEnd == null ? s : input.selectionEnd, em = b.getAttribute('data-emoji-pick');
      if (input.value.length + em.length > input.maxLength && input.maxLength > 0) { return; }
      input.value = input.value.slice(0, s) + em + input.value.slice(t);
      input.focus(); input.setSelectionRange(s + em.length, s + em.length);
      autosize(); refreshSend(); saveDraft();
    }, sig);

    /* ---------- sending ---------- */
    function send() {
      if (!access.allowed) { return; }
      var text = input.value.trim();
      if (!text && !file) { return; }
      if (text.length > boot.limits.max) { CH.toast('That message is too long. The limit is ' + boot.limits.max + ' characters.', 'error'); return; }
      var f = file;
      var m = { tmp: true, tid: 't' + (++tmpSeq), mine: true, type: f ? 'image' : 'text', text: text, media: f ? URL.createObjectURL(f) : null, w: 0, h: 0, ts: Math.floor(Date.now() / 1000), deleted: false, masked: false, system: null, read: false, status: 'sending' };
      input.value = ''; autosize(); setFile(null); saveDraft(); refreshSend(); toggleEmoji(false);
      addPending(m);
      toBottom(true);
      enqueue(m, text, f);
      input.focus();
    }
    function enqueue(m, text, f) {
      m.payload = { text: text, file: f };
      queue = queue.then(function () { return transmit(m); });
    }
    function transmit(m) {
      if (destroyed || !entryOf(m)) { return null; }
      sending++;
      var fd = new FormData();
      fd.append('conversation_id', convId);
      fd.append('text', m.payload.text);
      if (m.payload.file) { fd.append('image', m.payload.file, m.payload.file.name || 'photo.jpg'); }
      return CH.api('/api/messages/send', { data: fd, quiet: true }).then(function (j) {
        sending--;
        var e = entryOf(m);
        if (!e) { return; }
        if (j && j.ok) { confirmSent(e, j); } else { failSend(e, j || {}); }
      });
    }
    function confirmSent(e, j) {
      var real = j.message;
      if (e.m.media && e.m.media.indexOf('blob:') === 0) { try { URL.revokeObjectURL(e.m.media); } catch (x) { /* ignore */ } }
      if (byId[real.id]) { removeEntry(e); } else {
        e.m = real; byId[real.id] = e;
        fill(e);
        relayout();
      }
      if (real.id > lastId) { lastId = real.id; }
      if (typeof j.remaining === 'number') { showRemaining(j.remaining); }
      if (j.notice) { say(j.notice, 7000); }
      if (shell) { shell.refresh(); }
      fails = 0;
    }
    function failSend(e, j) {
      e.m.status = 'failed';
      fill(e);
      relayout();
      var msg = j.error || 'We could not send that. Check your connection and try again.';
      if (j.code && DENIAL_CODES.indexOf(j.code) !== -1) {
        setAccess({ allowed: false, code: j.code, message: msg, cta: j.cta || '' });
        removeEntry(e);
        if (e.m.payload && e.m.payload.text && !input.value) { input.value = e.m.payload.text; autosize(); saveDraft(); }
        return;
      }
      CH.toast(msg, 'error');
    }

    /* ---------- delegated clicks inside the thread ---------- */
    el.addEventListener('click', function (ev) {
      var t = ev.target;
      var lb = t.closest && t.closest('[data-lightbox]');
      if (lb) { ev.preventDefault(); lightbox(lb.href); return; }
      var btn = t.closest && t.closest('[data-act]');
      if (btn && el.contains(btn)) { act(btn.getAttribute('data-act'), btn, ev); return; }
      var gen = t.closest && t.closest('[data-emoji-pick]');
      if (gen) { return; }
      var bub = t.closest && t.closest('.msg-bubble');
      if (bub && !bub.classList.contains('is-deleted')) {
        var li = bub.closest('.msg-item');
        if (li && $('.msg-actions', li)) { var open = !li.classList.contains('is-open'); $$('.msg-item.is-open', ol).forEach(function (o) { o.classList.remove('is-open'); $('.msg-actions', o).hidden = true; }); li.classList.toggle('is-open', open); $('.msg-actions', li).hidden = !open; }
        return;
      }
      if (emojiPanel && !emojiPanel.hidden && !t.closest('[data-emoji]') && !t.closest('[data-act="emoji"]')) { toggleEmoji(false); }
    }, sig);
    el.addEventListener('keydown', function (ev) {
      if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.classList && ev.target.classList.contains('msg-bubble')) { ev.preventDefault(); ev.target.click(); }
    }, sig);
    if (jump) { jump.addEventListener('click', function () { toBottom(true); }, sig); }
    scroll.addEventListener('scroll', function () { stick = atBottom(); if (stick) { hideJump(); } }, Object.assign({ passive: true }, sig));
    if (starters) {
      (boot.icebreakers || []).forEach(function (t) {
        var b = doc.createElement('button'); b.type = 'button'; b.className = 'msg-starter'; b.textContent = t;
        b.addEventListener('click', function () { input.value = t; autosize(); refreshSend(); saveDraft(); input.focus(); });
        starters.appendChild(b);
      });
    }

    function act(name, btn) {
      var li = btn.closest('.msg-item');
      var e = li ? entries.filter(function (x) { return x.li === li; })[0] : null;
      switch (name) {
        case 'attach': if (!boot.limits.images) { say(boot.limits.images_why || 'Photos are not available in chats right now.', 5000); } else { fileInput.click(); } break;
        case 'detach': setFile(null); break;
        case 'emoji': toggleEmoji(emojiPanel.hidden); break;
        case 'retry': if (e) { e.m.status = 'sending'; fill(e); relayout(); enqueue(e.m, e.m.payload.text, e.m.payload.file); } break;
        case 'discard': if (e) { removeEntry(e); } break;
        case 'edit': if (e && e.m.id) { startEdit(e); } break;
        case 'delete': if (e && e.m.id) { confirmDelete(e); } break;
        case 'report-msg': if (e && e.m.id) { openReport({ type: 'message', id: e.m.id, title: 'Report this message' }); } break;
        case 'report-user': closeMenu(btn); openReport({ type: 'user', id: peer.id, title: 'Report ' + peer.first_name }); break;
        case 'block': closeMenu(btn); confirmBlock(); break;
        case 'pin': case 'mute': case 'archive': closeMenu(btn); convAction(name, btn); break;
      }
    }
    function closeMenu(btn) { var p = btn.closest('.menu-panel'); if (p) { p.hidden = true; } }

    function startEdit(e) {
      if (e.editing) { return; }
      if (!canEditNow(e.m)) { CH.toast(boot.edit_window ? 'Messages can only be edited for ' + Math.round(boot.edit_window / 60) + ' minutes after they are sent.' : 'This message can no longer be edited.', 'error'); fill(e); return; }
      e.editing = true;
      var bub = $('.msg-bubble', e.li), acts = $('.msg-actions', e.li);
      if (acts) { acts.hidden = true; }
      var form = doc.createElement('form'); form.className = 'msg-edit';
      form.innerHTML = '<label class="sr-only" for="edit-' + e.m.id + '">Edit message</label><textarea id="edit-' + e.m.id + '" class="msg-edit-input" rows="2" maxlength="' + boot.limits.max + '"></textarea>' +
        '<div class="msg-edit-bar"><span class="msg-edit-hint">Enter to save, Esc to cancel</span><button type="button" class="btn btn-ghost btn-sm" data-edit-cancel>Cancel</button><button type="submit" class="btn btn-primary btn-sm">Save</button></div>';
      bub.hidden = true; e.li.appendChild(form);
      var ta = $('textarea', form); ta.value = e.m.text;
      function size() { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 180) + 'px'; }
      function close() { e.editing = false; form.remove(); bub.hidden = false; }
      ta.addEventListener('input', size);
      ta.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { ev.preventDefault(); close(); }
        else if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) { ev.preventDefault(); form.requestSubmit(); }
      });
      $('[data-edit-cancel]', form).addEventListener('click', close);
      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var text = ta.value.trim();
        if (text === e.m.text) { close(); return; }
        if (!text) { CH.toast('A message cannot be empty. Delete it instead.', 'error'); return; }
        var fd = new FormData(); fd.append('id', e.m.id); fd.append('text', text);
        var sb = $('button[type=submit]', form); sb.disabled = true;
        CH.api('/api/messages/edit', { data: fd }).then(function (j) {
          sb.disabled = false;
          if (j.ok) { e.editing = false; e.m.text = j.message.text; e.m.edited = j.message.edited; e.m.masked = j.message.masked; fill(e); if (shell) { shell.refresh(); } CH.toast('Message updated', 'success'); }
          else { CH.toast(j.error || 'Could not edit that message.', 'error'); }
        });
      });
      size(); ta.focus(); ta.setSelectionRange(ta.value.length, ta.value.length);
    }
    function confirmDelete(e) {
      CH.confirm({ title: 'Delete this message?', message: 'It will be removed for both of you.', confirm: 'Delete' }).then(function (ok) {
        if (!ok) { return; }
        var fd = new FormData(); fd.append('id', e.m.id);
        CH.api('/api/messages/delete', { data: fd }).then(function (j) {
          if (j.ok) { setDeleted(e.m.id); } else { CH.toast(j.error || 'Could not delete that message.', 'error'); }
        });
      });
    }
    function convAction(name, btn) {
      var cur = { pin: boot.conv.pinned, mute: boot.conv.muted, archive: boot.conv.archived }[name];
      var verb = { pin: cur ? 'unpin' : 'pin', mute: cur ? 'unmute' : 'mute', archive: cur ? 'unarchive' : 'archive' }[name];
      var fd = new FormData(); fd.append('conversation_id', convId); fd.append('do', verb);
      CH.api('/api/messages/conversation', { data: fd }).then(function (j) {
        if (!j.ok) { CH.toast(j.error || 'That did not work.', 'error'); return; }
        CH.toast(j.message || 'Done', 'success');
        var now = !cur;
        if (name === 'pin') { boot.conv.pinned = now; $('span', btn).textContent = now ? 'Unpin conversation' : 'Pin conversation'; }
        if (name === 'mute') { boot.conv.muted = now; $('span', btn).textContent = now ? 'Unmute notifications' : 'Mute notifications'; }
        if (name === 'archive') { boot.conv.archived = now; if (now) { setTimeout(function () { location.href = CH.url('/messages'); }, 500); return; } $('span', btn).textContent = 'Archive conversation'; }
        if (shell) { shell.refresh(); }
      });
    }
    function confirmBlock() {
      CH.confirm({ title: 'Block ' + peer.first_name + '?', message: 'You will not see each other, and this chat disappears from your inbox. You can unblock from your settings.', confirm: 'Block' }).then(function (ok) {
        if (!ok) { return; }
        var fd = new FormData(); fd.append('user_id', peer.id);
        CH.api('/api/block', { data: fd }).then(function (j) {
          if (j.ok) { CH.toast(j.message || peer.first_name + ' is blocked.', 'success'); setTimeout(function () { location.href = CH.url('/messages'); }, 600); } else { CH.toast(j.error || 'Could not block this member.', 'error'); }
        });
      });
    }
    function openReport(target) {
      reportTarget = target;
      var d = $('#msg-report', el); if (!d) { return; }
      var t = $('[data-report-title]', d); if (t) { t.textContent = target.title; }
      CH.modal.open(d);
    }
    var reportForm = $('[data-report-form]', el);
    if (reportForm) {
      reportForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!reportForm.reportValidity() || !reportTarget) { return; }
        var fd = new FormData(reportForm);
        fd.append('type', reportTarget.type); fd.append('target_id', reportTarget.id);
        var b = $('button[type=submit]', reportForm); b.disabled = true;
        CH.api('/api/report', { data: fd }).then(function (j) {
          b.disabled = false;
          if (j.ok) { CH.toast(j.message || 'Thanks. Our team will take a look.', 'success'); reportForm.reset(); CH.modal.close($('#msg-report', el)); } else { CH.toast(j.error || 'Could not send the report.', 'error'); }
        });
      }, sig);
    }

    /* ---------- lightbox ---------- */
    function lightbox(src) {
      var d = doc.createElement('dialog'); d.className = 'msg-lightbox';
      d.innerHTML = '<img alt="Photo from the chat" src="' + esc(src) + '"><button type="button" class="msg-lightbox-x" aria-label="Close">' + ico('x', 'w-5 h-5') + '</button>';
      doc.body.appendChild(d);
      d.addEventListener('click', function () { d.close(); });
      d.addEventListener('close', function () { d.remove(); });
      d.showModal();
    }

    /* ---------- keep the view pinned to the bottom while content resizes (images, composer, typing dots) ---------- */
    if ('ResizeObserver' in window) {
      var ro = new ResizeObserver(function () { if (stick) { scroll.scrollTop = scroll.scrollHeight; } });
      ro.observe(inner); ro.observe(scroll);
      sig.signal.addEventListener('abort', function () { ro.disconnect(); });
    }
    /* On phones the on-screen keyboard shrinks the visual viewport; size the shell to it so the composer stays visible. */
    var shellEl = el.closest('[data-msg-shell]');
    if (window.visualViewport && shellEl) {
      var vv = window.visualViewport;
      var fit = function () {
        if (window.matchMedia('(min-width: 1024px)').matches) { shellEl.style.removeProperty('--msg-vh'); shellEl.style.removeProperty('--msg-top'); return; }
        shellEl.style.setProperty('--msg-vh', Math.round(vv.height) + 'px');
        shellEl.style.setProperty('--msg-top', Math.round(vv.offsetTop) + 'px');
        if (stick) { scroll.scrollTop = scroll.scrollHeight; }
      };
      vv.addEventListener('resize', fit, sig); vv.addEventListener('scroll', fit, sig);
      fit();
      sig.signal.addEventListener('abort', function () { shellEl.style.removeProperty('--msg-vh'); shellEl.style.removeProperty('--msg-top'); });
    }

    /* ---------- boot ---------- */
    var first = boot.messages || [];
    first.forEach(function (m) { var e = makeEntry(m); byId[m.id] = e; entries.push(e); ol.appendChild(e.li); });
    relayout();
    setAccess(access, boot.limits.remaining);
    showRemaining(boot.limits.remaining);
    setTyping(boot.typing);
    var draft = store(true, draftKey);
    if (draft) { input.value = draft; autosize(); refreshSend(); }
    if (!boot.limits.images) { var ab = $('[data-act="attach"]', el); if (ab) { ab.classList.add('is-off'); } }
    toBottom(false);
    schedule();
    if (access.allowed && !CH.reduced() && !matchMedia('(pointer: coarse)').matches) { input.focus({ preventScroll: true }); }

    self.destroy = function () {
      destroyed = true;
      clearTimeout(pollTimer);
      ac.abort();
      entries.forEach(function (e) { if (e.m.media && e.m.media.indexOf('blob:') === 0) { try { URL.revokeObjectURL(e.m.media); } catch (x) { /* ignore */ } } });
    };
  }

  /* ================================================================== NOTIFICATIONS PAGE */
  function Notifications(el) {
    var pill = $('[data-notif-new]', el), text = $('[data-notif-new-text]', el);
    var maxId = parseInt(el.getAttribute('data-max-id') || '0', 10) || 0;
    var timer = null;
    function check() {
      if (doc.hidden) { return schedule(); }
      CH.api('/api/notifications/list?after=' + maxId + '&limit=30', { quiet: true }).then(function (j) {
        if (j && j.ok) {
          var n = (j.items || []).length;
          if (n > 0 && pill) {
            text.textContent = n >= 30 ? '30+ new notifications. Show them' : (n === 1 ? '1 new notification. Show it' : n + ' new notifications. Show them');
            pill.hidden = false;
          }
          var bell = $$('[data-notif-count]');
          bell.forEach(function (b) { setCount(b, j.unread || 0); });
        }
        schedule();
      });
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(check, 30000); }
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden) { check(); } });
    schedule();
  }

  /* ================================================================== boot */
  function init() {
    var shell = $('[data-msg-shell]');
    if (shell) { new Shell(shell); }
    var n = $('[data-notifications]');
    if (n) { new Notifications(n); }
  }
  if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
