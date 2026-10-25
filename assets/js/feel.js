/* Servease: sounds, haptics and small moments of motion.
 * Every sound is synthesised with the Web Audio API (no audio files to download), kept short and quiet,
 * and only ever plays in response to something the person did. One switch in the profile menu
 * turns sounds and vibration off; the choice is remembered on this device. */
(function () {
  'use strict';
  const KEY = 'servease-sound';
  const root = document.documentElement;
  const fil = root.lang === 'fil';
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let on = true;
  try { on = localStorage.getItem(KEY) !== 'off'; } catch (e) {}

  /* ---- Audio engine ------------------------------------------------------ */
  const AC = window.AudioContext || window.webkitAudioContext;
  let ctx = null, master = null, noiseBuf = null;

  function audio() {
    if (!AC || !on) return null;
    if (!ctx) {
      try {
        ctx = new AC();
        master = ctx.createGain();
        master.gain.value = 0.22;
        // A gentle compressor keeps stacked sounds from clipping
        const comp = ctx.createDynamicsCompressor();
        comp.threshold.value = -18; comp.ratio.value = 4;
        master.connect(comp).connect(ctx.destination);
        noiseBuf = ctx.createBuffer(1, ctx.sampleRate * 0.4, ctx.sampleRate);
        const d = noiseBuf.getChannelData(0);
        for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
      } catch (e) { ctx = null; return null; }
    }
    if (ctx.state === 'suspended') ctx.resume();
    return ctx;
  }

  // One enveloped oscillator: frequency can glide from f to f2
  function tone(f, { at = 0, dur = 0.08, type = 'sine', vol = 0.5, f2 = null, attack = 0.004 } = {}) {
    const c = audio(); if (!c) return;
    const t = c.currentTime + at;
    const o = c.createOscillator(), g = c.createGain();
    o.type = type;
    o.frequency.setValueAtTime(f, t);
    if (f2) o.frequency.exponentialRampToValueAtTime(f2, t + dur);
    g.gain.setValueAtTime(0.0001, t);
    g.gain.exponentialRampToValueAtTime(vol, t + attack);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    o.connect(g).connect(master);
    o.start(t); o.stop(t + dur + 0.02);
  }

  // Filtered noise: clicks, paper and thuds
  function noise({ at = 0, dur = 0.03, vol = 0.3, freq = 3000, q = 1, type = 'bandpass', sweep = null } = {}) {
    const c = audio(); if (!c) return;
    const t = c.currentTime + at;
    const s = c.createBufferSource(), fl = c.createBiquadFilter(), g = c.createGain();
    s.buffer = noiseBuf;
    fl.type = type; fl.frequency.setValueAtTime(freq, t); fl.Q.value = q;
    if (sweep) fl.frequency.exponentialRampToValueAtTime(sweep, t + dur);
    g.gain.setValueAtTime(vol, t);
    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
    s.connect(fl).connect(g).connect(master);
    s.start(t); s.stop(t + dur + 0.02);
  }

  const sounds = {
    // Light tick for links, chips, menu rows
    tap() { noise({ dur: 0.018, vol: 0.18, freq: 4200, q: 2 }); tone(1900, { dur: 0.03, vol: 0.06, f2: 1300 }); },
    // Solid press for main buttons
    press() { noise({ dur: 0.02, vol: 0.2, freq: 2400, q: 1.5 }); tone(240, { dur: 0.09, vol: 0.32, f2: 150 }); },
    // Bottom tab bar
    tab() { tone(520, { dur: 0.07, vol: 0.22, f2: 680 }); noise({ dur: 0.015, vol: 0.1, freq: 5000, q: 3 }); },
    toggleOn() { tone(660, { dur: 0.06, vol: 0.22 }); tone(990, { at: 0.06, dur: 0.09, vol: 0.22 }); },
    toggleOff() { tone(880, { dur: 0.06, vol: 0.2 }); tone(587, { at: 0.06, dur: 0.09, vol: 0.2 }); },
    select() { tone(1200, { dur: 0.05, vol: 0.14, f2: 1500 }); },
    // Saved (heart) and unsaved
    pop() { tone(420, { dur: 0.12, vol: 0.3, f2: 900, type: 'triangle' }); },
    unpop() { tone(700, { dur: 0.1, vol: 0.2, f2: 380, type: 'triangle' }); },
    // Toasts
    success() { [1046.5, 1318.5, 1568].forEach((f, i) => { tone(f, { at: i * 0.07, dur: 0.32, vol: 0.18 }); tone(f * 2, { at: i * 0.07, dur: 0.12, vol: 0.04 }); }); },
    error() { tone(330, { dur: 0.13, vol: 0.2, type: 'triangle' }); tone(247, { at: 0.12, dur: 0.2, vol: 0.2, type: 'triangle' }); },
    info() { tone(880, { dur: 0.18, vol: 0.14 }); tone(1320, { at: 0.08, dur: 0.18, vol: 0.08 }); },
    // Chat
    send() { noise({ dur: 0.16, vol: 0.12, freq: 800, sweep: 5000, q: 0.8 }); tone(740, { at: 0.06, dur: 0.1, vol: 0.18, f2: 1100 }); },
    receive() { tone(988, { dur: 0.1, vol: 0.16 }); tone(1319, { at: 0.09, dur: 0.16, vol: 0.14 }); },
    // Sheets and dialogs
    open() { noise({ dur: 0.14, vol: 0.08, freq: 2400, sweep: 700, q: 0.7 }); },
    close() { noise({ dur: 0.1, vol: 0.06, freq: 700, sweep: 2400, q: 0.7 }); },
    // The rubber stamp landing on a job ticket
    stamp() { noise({ dur: 0.09, vol: 0.5, freq: 380, type: 'lowpass' }); tone(110, { dur: 0.12, vol: 0.45, f2: 60 }); noise({ at: 0.005, dur: 0.03, vol: 0.12, freq: 2200, q: 1 }); },
    // Ticket printing: a quick run of paper ticks
    print() { for (let i = 0; i < 6; i++) noise({ at: i * 0.045, dur: 0.02, vol: 0.09, freq: 3200 + (i % 2) * 600, q: 3 }); },
    swipe() { noise({ dur: 0.08, vol: 0.06, freq: 1600, sweep: 3200, q: 1 }); },
    star(n) { tone(523 * Math.pow(1.122, n), { dur: 0.12, vol: 0.18, type: 'triangle' }); },
  };

  const activated = () => !navigator.userActivation || navigator.userActivation.hasBeenActive;
  const buzz = pattern => { if (on && navigator.vibrate && activated()) try { navigator.vibrate(pattern); } catch (e) {} };
  const play = (name, arg) => { if (!on) return; try { sounds[name] && sounds[name](arg); } catch (e) {} };
  window.feel = { play, buzz };

  /* ---- What each tap sounds like ------------------------------------------ */
  let last = 0;
  document.addEventListener('click', e => {
    const el = e.target.closest('a[href], button, summary, [role="button"], [role="tab"], label, input[type="checkbox"], input[type="radio"]');
    if (!el || el.disabled || el.matches('[data-sound-toggle], [data-silent]')) return;
    // A label click also fires on its input; only count it once
    const now = performance.now(); if (now - last < 40) return; last = now;

    if (el.matches('form[data-save-toggle] button')) return; // the save handler plays its own
    if (el.closest('.star-input, .rate-input') && el.matches('label, input')) {
      const input = el.matches('input') ? el : document.getElementById(el.htmlFor) || el.querySelector('input');
      play('star', parseInt((input && input.value) || '3', 10)); buzz(6); return;
    }
    if (el.matches('input[type="checkbox"], input[type="radio"]') || el.querySelector?.(':scope > input[type="checkbox"], :scope > input[type="radio"]') || el.matches('label')) {
      const input = el.matches('input') ? el : el.querySelector('input') || (el.htmlFor && document.getElementById(el.htmlFor));
      if (input && input.type === 'checkbox') { play(input.checked ? 'toggleOff' : 'toggleOn'); buzz(8); }
      else { play('select'); buzz(5); }
      return;
    }
    if (el.closest('.tabbar')) { play('tab'); buzz(6); return; }
    if (el.matches('[data-open], summary')) { play('open'); buzz(5); return; }
    if (el.matches('[data-close]')) { play('close'); return; }
    if (el.matches('.btn-primary, .btn-dark, .btn-danger, button[type="submit"]')) { play('press'); buzz(10); return; }
    play('tap'); buzz(4);
  }, true);

  // Dialogs closing by Esc or backdrop
  document.querySelectorAll('dialog').forEach(d => d.addEventListener('close', () => play('close')));

  // Onboarding slides: a soft swipe when the page settles on a new slide
  const track = document.querySelector('[data-ob-track]');
  if (track) {
    let idx = 0, timer = 0;
    track.addEventListener('scroll', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const n = Math.round(track.scrollLeft / track.clientWidth);
        if (n !== idx) { idx = n; play('swipe'); buzz(4); }
      }, 90);
    }, { passive: true });
  }

  /* ---- Toasts and the job ticket moment ---------------------------------- */
  const toastSound = el => {
    if (el.classList.contains('alert-error')) { play('error'); buzz([30, 40, 30]); }
    else if (el.classList.contains('alert-success')) { play('success'); buzz([10, 50, 14]); }
    else play('info');
  };

  // Toasts added later (save, chat errors, ...)
  const box = () => document.querySelector('.toasts');
  const watch = new MutationObserver(list => list.forEach(m => m.addedNodes.forEach(n => {
    if (n.nodeType === 1 && n.classList.contains('toast')) toastSound(n);
    if (n.nodeType === 1 && n.classList.contains('toasts')) n.querySelectorAll('.toast').forEach(toastSound);
  })));
  watch.observe(document.body, { childList: true, subtree: true });

  // Toasts that arrived with the page (after a form was sent): the ticket prints, gets stamped, then the chime
  const flash = box() && box().querySelector('.toast');
  const ticket = document.querySelector('.ticket');
  const fresh = flash && flash.classList.contains('alert-success') && ticket;
  if (fresh && !reduceMotion) {
    ticket.classList.add('is-fresh');
    const stamp = ticket.querySelector('.stamp');
    if (stamp) stamp.classList.add('is-slam');
  }
  if (flash && on) {
    const run = () => {
      if (fresh) { play('print'); setTimeout(() => { play('stamp'); buzz(18); }, reduceMotion ? 0 : 520); setTimeout(() => toastSound(flash), reduceMotion ? 150 : 760); }
      else toastSound(flash);
    };
    // Chrome and Safari allow this once the person has used the site; if the audio is still locked,
    // skip the sound rather than play it late on some unrelated tap
    const c = audio();
    if (c && c.state === 'running') run();
    else if (c) setTimeout(() => { if (c.state === 'running') run(); }, 120);
  }

  /* ---- Saved pros: heart pops ------------------------------------------- */
  document.addEventListener('submit', e => {
    const f = e.target;
    if (f.matches('form[data-save-toggle]')) {
      const btn = f.querySelector('button');
      const willSave = btn && !btn.classList.contains('is-saved');
      play(willSave ? 'pop' : 'unpop'); buzz(willSave ? [8, 30, 8] : 6);
      if (btn && willSave && !reduceMotion) { btn.classList.remove('is-popping'); void btn.offsetWidth; btn.classList.add('is-popping'); }
    }
    if (f.matches('.chat-form')) { const t = f.querySelector('textarea'); if (t && t.value.trim()) { play('send'); buzz(8); } }
  }, true);

  // New chat messages from the other person
  const chatBody = document.querySelector('[data-chat] .chat-body');
  if (chatBody) {
    new MutationObserver(list => list.forEach(m => m.addedNodes.forEach(n => {
      if (n.nodeType === 1 && n.classList.contains('msg-them')) { play('receive'); buzz(12); }
    }))).observe(chatBody, { childList: true });
  }

  /* ---- The switch -------------------------------------------------------- */
  const label = () => on ? (fil ? 'May tunog' : 'Sound on') : (fil ? 'Walang tunog' : 'Sound off');
  const sync = () => document.querySelectorAll('[data-sound-toggle]').forEach(b => {
    b.setAttribute('aria-pressed', on ? 'true' : 'false');
    b.classList.toggle('is-on', on);
    const l = b.querySelector('[data-sound-label]'); if (l) l.textContent = label();
  });
  sync();
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-sound-toggle]');
    if (!b) return;
    e.preventDefault();
    on = !on;
    try { localStorage.setItem(KEY, on ? 'on' : 'off'); } catch (err) {}
    sync();
    if (on) { play('toggleOn'); buzz(10); }
  });
})();
