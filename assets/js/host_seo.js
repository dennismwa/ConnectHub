/* Admin > Host search and SEO: keyword and part chips, the starter questions, live counters and the profile title preview. */
(function () {
  'use strict';
  var doc = document;
  function $(s, r) { return (r || doc).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); }

  function phrases(text) {
    var seen = {}, out = [];
    String(text || '').split(/[\r\n,;]+/).forEach(function (p) {
      p = p.replace(/\s+/g, ' ').trim();
      var k = p.toLowerCase();
      if (p && !seen[k]) { seen[k] = 1; out.push(p); }
    });
    return out;
  }
  function fire(el) { el.dispatchEvent(new Event('input', { bubbles: true })); }

  // keyword ideas: tap to add, once
  function markUsed(field) {
    var have = phrases(field.value).map(function (p) { return p.toLowerCase(); });
    $$('[data-hs-kw][data-hs-into="' + field.id + '"]').forEach(function (c) {
      var used = have.indexOf((c.getAttribute('data-hs-kw') || '').toLowerCase()) !== -1;
      c.classList.toggle('is-used', used);
      c.setAttribute('aria-pressed', used ? 'true' : 'false');
    });
  }
  $$('[data-hs-kw]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var f = doc.getElementById(chip.getAttribute('data-hs-into')); if (!f) return;
      var list = phrases(f.value), kw = chip.getAttribute('data-hs-kw') || '';
      if (list.map(function (p) { return p.toLowerCase(); }).indexOf(kw.toLowerCase()) === -1) { list.push(kw); }
      f.value = list.join(', '); fire(f); f.focus();
    });
  });

  // phrase counters
  $$('[data-hs-count]').forEach(function (out) {
    var f = doc.getElementById(out.getAttribute('data-hs-count')); if (!f) return;
    function paint() { var n = phrases(f.value).length; out.textContent = n; out.className = n >= 6 && n <= 15 ? 'seo-ok' : (n ? 'seo-warn' : ''); markUsed(f); }
    f.addEventListener('input', paint); paint();
  });
  $$('[data-hs-kw-target]').forEach(function (f) { if (f.id) { f.addEventListener('input', function () { markUsed(f); }); markUsed(f); } });

  // starter questions
  $$('[data-hs-fill]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var f = doc.getElementById(btn.getAttribute('data-hs-fill')); if (!f) return;
      if (f.value.trim() !== '' && !window.confirm('Replace what is written with the helpful set?')) return;
      f.value = f.getAttribute('data-hs-starter') || ''; fire(f); f.focus();
    });
  });

  // profile pattern: parts at the cursor and a live example
  $$('form[data-hs-profiles]').forEach(function (form) {
    var sample = {}; try { sample = JSON.parse(form.getAttribute('data-hs-sample') || '{}'); } catch (e) {}
    var site = form.getAttribute('data-site') || '';
    var fields = $$('[data-hs-pattern]', form), last = fields[0];
    fields.forEach(function (f) { f.addEventListener('focus', function () { last = f; f.setAttribute('data-hs-touched', '1'); }); f.addEventListener('input', paint); });
    $$('[data-hs-token]', form).forEach(function (chip) {
      chip.addEventListener('click', function () {
        var f = last || fields[0]; if (!f) return;
        var t = chip.getAttribute('data-hs-token'), touched = f.hasAttribute('data-hs-touched'), a = touched && f.selectionStart != null ? f.selectionStart : f.value.length, b = touched && f.selectionEnd != null ? f.selectionEnd : a;
        f.value = f.value.slice(0, a) + t + f.value.slice(b); f.focus();
        var pos = a + t.length; try { f.setSelectionRange(pos, pos); } catch (e) {}
        fire(f);
      });
    });
    function fill(pattern) {
      var s = String(pattern || '').replace(/\{([a-z_]+)\}/gi, function (m, k) { return String(sample[k] || ''); });
      s = s.replace(/\s+(in|at|near|from)\s*(?=[,.:;|\-]|$)/gi, '');
      s = s.replace(/,\s+(in|at|near|from)\b/gi, ' $1').replace(/\s+([,.:;])/g, '$1').replace(/([,.:;])(\s*[,.:;])+/g, '$1').replace(/\s+/g, ' ').trim();
      return s.replace(/^[\s,.:;|\-]+|[\s,:;|\-]+$/g, '');
    }
    function trunc(s, n) { s = s.replace(/\s+/g, ' ').trim(); return s.length > n ? s.slice(0, n - 1).replace(/\s+\S*$/, '') + '…' : s; }
    function paint() {
      var pt = fields[0] ? fields[0].value.trim() : '', pd = fields[1] ? fields[1].value.trim() : '';
      var title = fill(pt || '{name}, {category} in {place}') || sample.name || '';
      var desc = pd ? fill(pd) : ((sample.tagline ? sample.tagline.replace(/[. ]+$/, '') + '. ' : '') + (sample.about || ''));
      var t = $('[data-hs-prev-title]', form), d = $('[data-hs-prev-desc]', form);
      if (t) t.textContent = trunc(title, 62) + (site ? ' · ' + site : '');
      if (d) d.textContent = trunc(desc, 158);
    }
    paint();
  });
})();
