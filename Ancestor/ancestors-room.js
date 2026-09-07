/* =====================================================================
   THE ANCESTORS' ROOM — SHARED JAVASCRIPT
   Door transition · Hotspots + modal · Mobile chips · Menu · Ambient sound
   ---------------------------------------------------------------------
   Load ONCE, site-wide, in the footer:
     • Elementor Pro → Custom Code → location "</body> – End", or
     • child theme:  wp_enqueue_script('tar', get_stylesheet_directory_uri().'/js/ancestors-room.js', [], '1.0', true);
   No dependencies. Safe to include on pages without the markup (it no-ops).
   ===================================================================== */
(function () {
  'use strict';

  var $  = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------------
     1. COVER GEOMETRY
     Replicates `background-size: cover; background-position: center`
     so we can pin elements to exact points of the artwork.
     ------------------------------------------------------------------ */
  function cover(cw, ch, iw, ih) {
    var s = Math.max(cw / iw, ch / ih);
    var dw = iw * s, dh = ih * s;
    return { cw: cw, ch: ch, dispW: dw, dispH: dh, offX: (cw - dw) / 2, offY: (ch - dh) / 2 };
  }
  function toPx(g, fx, fy) { return { x: g.offX + fx * g.dispW, y: g.offY + fy * g.dispH }; }
  function loadSize(src, cb) {
    var img = new Image();
    img.onload = function () { cb({ w: img.naturalWidth, h: img.naturalHeight }); };
    img.src = src;
  }
  var easeInOut = function (t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; };
  var easeOut   = function (t) { return 1 - Math.pow(1 - t, 3); };

  /* ------------------------------------------------------------------
     2. AMBIENT SOUND (Web Audio, synthesized — no files to host)
     Toggle with any element carrying [data-tar-sound].
     ------------------------------------------------------------------ */
  var Sound = (function () {
    var ctx = null, master = null, noiseBuf = null, crackleTimer = null, running = false;
    function ensure() {
      if (ctx) return ctx;
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) return null;
      ctx = new AC();
      master = ctx.createGain(); master.gain.value = 0; master.connect(ctx.destination);
      return ctx;
    }
    function noise(c) {
      if (noiseBuf) return noiseBuf;
      var len = c.sampleRate * 3, buf = c.createBuffer(1, len, c.sampleRate), d = buf.getChannelData(0), last = 0;
      for (var i = 0; i < len; i++) { var w = Math.random() * 2 - 1; last = (last + 0.02 * w) / 1.02; d[i] = last * 3.5; }
      noiseBuf = buf; return buf;
    }
    function crackle() {
      if (!ctx || !running) return;
      var src = ctx.createBufferSource(); src.buffer = noise(ctx); src.playbackRate.value = 2 + Math.random() * 3;
      var bp = ctx.createBiquadFilter(); bp.type = 'bandpass'; bp.frequency.value = 1500 + Math.random() * 2500; bp.Q.value = 1.2;
      var g = ctx.createGain(), t = ctx.currentTime, peak = 0.015 + Math.random() * 0.05;
      g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(peak, t + 0.004);
      g.gain.exponentialRampToValueAtTime(0.0005, t + 0.03 + Math.random() * 0.05);
      src.connect(bp).connect(g).connect(master); src.start(t); src.stop(t + 0.15);
      crackleTimer = setTimeout(crackle, 60 + Math.random() * 520);
    }
    function start() {
      var c = ensure(); if (!c) return;
      if (c.state === 'suspended') c.resume();
      if (running) return; running = true;
      var now = c.currentTime;
      // drone
      var drone = c.createGain(); drone.gain.value = 0.05;
      var lfo = c.createOscillator(); lfo.frequency.value = 0.08;
      var lfoG = c.createGain(); lfoG.gain.value = 0.02; lfo.connect(lfoG).connect(drone.gain); lfo.start(now);
      [55, 82.41, 110.3].forEach(function (f, i) {
        var o = c.createOscillator(); o.type = 'sine'; o.frequency.value = f; o.detune.value = i * 4;
        var g = c.createGain(); g.gain.value = i === 0 ? 1 : 0.35; o.connect(g).connect(drone); o.start(now);
      });
      drone.connect(master);
      // air
      var n = c.createBufferSource(); n.buffer = noise(c); n.loop = true;
      var lp = c.createBiquadFilter(); lp.type = 'lowpass'; lp.frequency.value = 420;
      var air = c.createGain(); air.gain.value = 0.16; n.connect(lp).connect(air).connect(master); n.start(now);
      crackle();
      master.gain.cancelScheduledValues(now); master.gain.setValueAtTime(0, now); master.gain.linearRampToValueAtTime(0.9, now + 2.5);
      document.body.classList.add('tar-sound-on');
      $$('[data-tar-sound]').forEach(function (b) { b.setAttribute('aria-pressed', 'true'); });
      try { sessionStorage.setItem('tar_sound', '1'); } catch (e) {}
    }
    function stop() {
      if (!ctx) return;
      var now = ctx.currentTime;
      master.gain.cancelScheduledValues(now); master.gain.setValueAtTime(master.gain.value, now); master.gain.linearRampToValueAtTime(0, now + 1.2);
      running = false; clearTimeout(crackleTimer);
      setTimeout(function () { if (!running && ctx) { ctx.close(); ctx = null; master = null; } }, 1400);
      document.body.classList.remove('tar-sound-on');
      $$('[data-tar-sound]').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
      try { sessionStorage.removeItem('tar_sound'); } catch (e) {}
    }
    function swell() {   // soft low swell + bell when the doors open
      if (!ctx || !running) return;
      var t = ctx.currentTime, src = ctx.createBufferSource(); src.buffer = noise(ctx);
      var lp = ctx.createBiquadFilter(); lp.type = 'lowpass';
      lp.frequency.setValueAtTime(120, t); lp.frequency.exponentialRampToValueAtTime(900, t + 1.6); lp.frequency.exponentialRampToValueAtTime(140, t + 3.2);
      var g = ctx.createGain(); g.gain.setValueAtTime(0, t); g.gain.linearRampToValueAtTime(0.5, t + 1.4); g.gain.linearRampToValueAtTime(0, t + 3.4);
      src.connect(lp).connect(g).connect(master); src.start(t); src.stop(t + 3.6);
      var o = ctx.createOscillator(); o.type = 'sine'; o.frequency.value = 164.8;
      var og = ctx.createGain(); og.gain.setValueAtTime(0, t + 0.6); og.gain.linearRampToValueAtTime(0.08, t + 1.2); og.gain.exponentialRampToValueAtTime(0.0005, t + 5);
      o.connect(og).connect(master); o.start(t + 0.6); o.stop(t + 5.2);
    }
    function bind() {
      $$('[data-tar-sound]').forEach(function (b) { b.addEventListener('click', function () { running ? stop() : start(); }); });
      // Resume on the next gesture if the visitor had sound on earlier in this session
      var wanted = false; try { wanted = sessionStorage.getItem('tar_sound') === '1'; } catch (e) {}
      if (wanted) { var once = function () { start(); document.removeEventListener('pointerdown', once); }; document.addEventListener('pointerdown', once); }
    }
    return { start: start, stop: stop, swell: swell, bind: bind };
  })();

  /* ------------------------------------------------------------------
     3. FULL-SCREEN MENU  (#tar-menu · [data-tar-menu-open] · [data-tar-menu-close])
     ------------------------------------------------------------------ */
  function initMenu() {
    var menu = $('#tar-menu'); if (!menu) return;
    var open = function () { menu.classList.add('is-open'); document.body.style.overflow = 'hidden'; };
    var close = function () { menu.classList.remove('is-open'); document.body.style.overflow = ''; };
    $$('[data-tar-menu-open]').forEach(function (b) { b.addEventListener('click', open); });
    $$('[data-tar-menu-close]', menu).forEach(function (b) { b.addEventListener('click', close); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  /* ------------------------------------------------------------------
     4. THRESHOLD — 3D door opening + camera walk-through
     ------------------------------------------------------------------ */
  function initThreshold(root) {
    var base   = $('.tar-scene__base', root),
        clip   = $('.tar-scene__clip', root),
        room   = $('.tar-scene__room', root),
        scene  = $('.tar-scene', root),
        panels = $('.tar-scene__panels', root),
        left   = $('.tar-panel--left', root),
        right  = $('.tar-panel--right', root),
        seam   = $('.tar-seam', root),
        lintel = $('.tar-lintel', root),
        glow   = $('.tar-glow', root);
    var home = $('#tar-home');
    var skipKey = root.getAttribute('data-skip-key') || '';

    // Repeat visitor in this session + homepage present → skip the doors
    var skip = false; try { skip = !!skipKey && sessionStorage.getItem(skipKey) === '1'; } catch (e) {}
    if (skip && home) { root.classList.add('is-done'); document.body.classList.add('tar-arrived'); return; }

    var baseSrc = base.getAttribute('data-src'), roomSrc = room.getAttribute('data-src');
    base.style.backgroundImage = 'url("' + baseSrc + '")';
    room.style.backgroundImage = 'url("' + roomSrc + '")';
    var pct = {
      l: parseFloat(root.getAttribute('data-door-left'))   / 100,
      r: parseFloat(root.getAttribute('data-door-right'))  / 100,
      t: parseFloat(root.getAttribute('data-door-top'))    / 100,
      b: parseFloat(root.getAttribute('data-door-bottom')) / 100,
      lintelY: parseFloat(root.getAttribute('data-lintel-y') || '11.8') / 100
    };
    var natural = null, geo = null, rect = null, center = null, busy = false;

    function layout() {
      if (!natural) return;
      var cw = root.clientWidth, ch = root.clientHeight;
      geo = cover(cw, ch, natural.w, natural.h);
      var tl = toPx(geo, pct.l, pct.t), br = toPx(geo, pct.r, pct.b);
      rect = { left: tl.x, top: tl.y, width: br.x - tl.x, height: br.y - tl.y };
      center = { x: (tl.x + br.x) / 2, y: (tl.y + br.y) / 2 };
      var size = geo.dispW + 'px ' + geo.dispH + 'px';
      base.style.backgroundSize = size;
      base.style.backgroundPosition = geo.offX + 'px ' + geo.offY + 'px';
      scene.style.transformOrigin = room.style.transformOrigin = center.x + 'px ' + center.y + 'px';
      if (!busy) clip.style.clipPath = 'inset(' + rect.top + 'px ' + (cw - rect.left - rect.width) + 'px ' + (ch - rect.top - rect.height) + 'px ' + rect.left + 'px)';
      var half = rect.width / 2 + 0.5;
      [left, right].forEach(function (p, i) {
        var x = rect.left + (i ? rect.width / 2 - 0.5 : 0);
        p.style.left = x + 'px'; p.style.top = rect.top + 'px'; p.style.width = half + 'px'; p.style.height = rect.height + 'px';
        p.style.backgroundImage = 'url("' + baseSrc + '")'; p.style.backgroundSize = size;
        p.style.backgroundPosition = (geo.offX - x) + 'px ' + (geo.offY - rect.top) + 'px';
      });
      if (seam) { seam.style.left = (center.x - rect.width * 0.22) + 'px'; seam.style.top = (rect.top - rect.height * 0.05) + 'px'; seam.style.width = (rect.width * 0.44) + 'px'; seam.style.height = (rect.height * 1.18) + 'px'; }
      if (lintel) { var lp = toPx(geo, 0.5, pct.lintelY); lintel.style.left = lp.x + 'px'; lintel.style.top = lp.y + 'px'; lintel.style.fontSize = (geo.dispW * 0.0165) + 'px'; }
      if (glow) { glow.style.setProperty('--gx', center.x + 'px'); glow.style.setProperty('--gy', center.y + 'px'); }
    }
    loadSize(baseSrc, function (s) { natural = s; layout(); });
    new Image().src = roomSrc; // preload
    window.addEventListener('resize', layout);

    function finish() {
      try { if (skipKey) sessionStorage.setItem(skipKey, '1'); } catch (e) {}
      if (home) {                       // single-page setup: reveal the homepage underneath
        document.body.classList.add('tar-arrived');
        root.classList.add('is-done');
        window.removeEventListener('resize', layout);
        setTimeout(function () { root.parentNode && root.parentNode.removeChild(root); }, 50);
      } else {                          // two-page setup: go to the homepage
        window.location.href = root.getAttribute('data-home-url') || '/';
      }
    }

    function walkThrough() {
      if (!rect) { finish(); return; }
      var cw = root.clientWidth, ch = root.clientHeight, P = center, r = rect;
      var need = Math.max(P.x / Math.max(1, P.x - r.left), (cw - P.x) / Math.max(1, r.left + r.width - P.x),
                          P.y / Math.max(1, P.y - r.top), (ch - P.y) / Math.max(1, r.top + r.height - P.y));
      var sEnd = Math.min(3.2, need * 1.05), D = reduceMotion ? 700 : 1800, start = performance.now();
      var i0 = { t: r.top, r: cw - r.left - r.width, b: ch - r.top - r.height, l: r.left };
      root.classList.add('is-entering');
      (function frame(now) {
        var t = Math.min(1, (now - start) / D), e = easeInOut(t);
        var s = 1 + (sEnd - 1) * e, k = 1.3 - 0.3 * easeOut(t), shrink = 1 - easeOut(Math.min(1, t * 1.15));
        scene.style.transform = 'scale(' + s + ')';
        room.style.transform  = 'scale(' + (k / s) + ')';
        clip.style.clipPath = 'inset(' + (i0.t * shrink) + 'px ' + (i0.r * shrink) + 'px ' + (i0.b * shrink) + 'px ' + (i0.l * shrink) + 'px)';
        var op = 1 - easeInOut(Math.min(1, t / 0.7));
        base.style.opacity = panels.style.opacity = op; if (lintel) lintel.style.opacity = op;
        if (glow) glow.style.opacity = t < 0.4 ? 0.55 + 0.45 * (t / 0.4) : Math.max(0, 1 - (t - 0.4) / 0.6);
        room.style.filter = 'brightness(' + (1.15 - 0.15 * easeOut(t)) + ') saturate(1.05)';
        if (t < 1) requestAnimationFrame(frame); else finish();
      })(start);
    }

    function enter() {
      if (busy) return; busy = true;
      Sound.swell();
      root.classList.add('is-leaving');                                    // UI fades
      setTimeout(function () { root.classList.add('is-open'); }, 250);     // doors swing
      setTimeout(walkThrough, 250 + (reduceMotion ? 500 : 1500));          // camera walks through
    }
    $$('[data-tar-enter]', root).forEach(function (b) { b.addEventListener('click', enter); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !busy && !$('#tar-menu.is-open')) enter(); });
  }

  /* ------------------------------------------------------------------
     5. HOMEPAGE — hotspots, modal, chips, lintel
     ------------------------------------------------------------------ */
  function initHome(root) {
    var bg = $('.tar-home__bg', root), spots = $$('.tar-hotspot', root), lintel = $('.tar-home__lintel', root);
    var iw = parseFloat(root.getAttribute('data-img-w')), ih = parseFloat(root.getAttribute('data-img-h'));

    function layout() {
      if (!iw || !ih) return;
      var g = cover(root.clientWidth, root.clientHeight, iw, ih);
      spots.forEach(function (h) {
        var p = toPx(g, parseFloat(h.getAttribute('data-x')) / 100, parseFloat(h.getAttribute('data-y')) / 100);
        h.style.left = p.x + 'px'; h.style.top = p.y + 'px';
      });
      if (lintel) {
        var lp = toPx(g, parseFloat(lintel.getAttribute('data-x') || '50') / 100, parseFloat(lintel.getAttribute('data-y') || '10') / 100);
        lintel.style.left = lp.x + 'px'; lintel.style.top = lp.y + 'px'; lintel.style.fontSize = (g.dispW * 0.011) + 'px';
      }
    }
    if (iw && ih) layout();
    else if (bg) { var m = /url\(["']?(.*?)["']?\)/.exec(bg.style.backgroundImage || ''); if (m) loadSize(m[1], function (s) { iw = s.w; ih = s.h; layout(); }); }
    window.addEventListener('resize', layout);

    // ---- modal ----
    var modal = $('#tar-modal'), current = -1;
    function field(name) { return modal ? $('[data-field="' + name + '"]', modal) : null; }
    function openSpot(i) {
      if (!modal) { window.location.href = spots[i].getAttribute('data-href'); return; }
      current = i; var h = spots[i];
      field('title').textContent  = h.getAttribute('data-title') || $('.tar-hotspot__label', h).textContent;
      field('kicker').textContent = h.getAttribute('data-kicker') || '';
      field('text').textContent   = h.getAttribute('data-text') || '';
      var cta = field('cta'); cta.textContent = h.getAttribute('data-cta') || 'Enter →'; cta.setAttribute('href', h.getAttribute('data-href') || '#');
      field('count').textContent  = (i + 1) + ' / ' + spots.length;
      spots.forEach(function (s, j) { s.classList.toggle('is-active', j === i); });
      modal.hidden = false; document.body.style.overflow = 'hidden';
      var card = $('.tar-modal__card', modal); card.style.animation = 'none'; void card.offsetWidth; card.style.animation = '';
      $('.tar-modal__close', modal).focus();
    }
    function closeModal() { if (!modal) return; modal.hidden = true; document.body.style.overflow = ''; spots.forEach(function (s) { s.classList.remove('is-active'); }); }
    spots.forEach(function (h, i) { h.addEventListener('click', function () { openSpot(i); }); });
    if (modal) {
      $$('[data-tar-modal-close]', modal).forEach(function (b) { b.addEventListener('click', closeModal); });
      $('[data-tar-modal-prev]', modal).addEventListener('click', function () { openSpot((current - 1 + spots.length) % spots.length); });
      $('[data-tar-modal-next]', modal).addEventListener('click', function () { openSpot((current + 1) % spots.length); });
      document.addEventListener('keydown', function (e) {
        if (modal.hidden) return;
        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowRight') openSpot((current + 1) % spots.length);
        if (e.key === 'ArrowLeft')  openSpot((current - 1 + spots.length) % spots.length);
      });
    }

    // ---- mobile chips: built from the hotspots so content stays in one place ----
    var row = $('.tar-chips__row', root);
    if (row) spots.forEach(function (h, i) {
      var chip = document.createElement('button'); chip.type = 'button'; chip.className = 'tar-chip';
      chip.textContent = $('.tar-hotspot__label', h).textContent;
      chip.addEventListener('click', function () { openSpot(i); });
      row.appendChild(chip);
    });
  }

  /* ------------------------------------------------------------------
     BOOT
     ------------------------------------------------------------------ */
  function boot() {
    Sound.bind();
    initMenu();
    var th = $('#tar-threshold'), home = $('#tar-home');
    if (home) initHome(home);
    if (th) initThreshold(th);
    // Direct landing on the homepage (no threshold on this page) → run entrance animations
    if (home && (!th || th.classList.contains('is-done'))) requestAnimationFrame(function () { document.body.classList.add('tar-arrived'); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
