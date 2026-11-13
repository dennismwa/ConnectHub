/* Mobile dock: the highlight pill glides from the tab you left to the tab you opened, even across page loads.
   Pure progressive enhancement: without this script the pill simply sits under the active tab (CSS only). */
(function () {
  'use strict';
  var doc = document;
  var nav = doc.querySelector('[data-dock]');
  if (!nav) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var KEY = 'ch_dock_i';
  var cur = parseInt(nav.getAttribute('data-i'), 10);
  var prev = null;
  try { prev = sessionStorage.getItem(KEY); } catch (e) {}
  function store(i) { try { sessionStorage.setItem(KEY, String(i)); } catch (e) {} }

  // 1. page load: start the pill where it was on the previous page, then glide to the new tab
  if (!reduce && cur >= 0 && prev !== null && +prev !== cur && +prev >= 0) {
    nav.classList.add('is-jump');
    nav.style.setProperty('--i', String(+prev));
    void nav.offsetWidth; // commit the starting position
    nav.classList.remove('is-jump');
    requestAnimationFrame(function () { requestAnimationFrame(function () { nav.style.setProperty('--i', String(cur)); }); });
  }
  if (cur >= 0) store(cur);

  // 2. tap: move the pill at once so the tap feels instant while the next page loads
  nav.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('[data-dock-i]') : null;
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') return;
    var i = parseInt(a.getAttribute('data-dock-i'), 10);
    nav.classList.remove('no-active');
    nav.style.setProperty('--i', String(i));
    var all = nav.querySelectorAll('.tab-link');
    for (var k = 0; k < all.length; k++) all[k].classList.toggle('is-active', k === i);
    store(i);
  });
  // coming back with the browser's back button: put the pill on the real active tab again
  window.addEventListener('pageshow', function (e) {
    if (e.persisted && cur >= 0) {
      nav.style.setProperty('--i', String(cur));
      var all = nav.querySelectorAll('.tab-link');
      for (var k = 0; k < all.length; k++) all[k].classList.toggle('is-active', k === cur);
      store(cur);
    }
  });
})();
