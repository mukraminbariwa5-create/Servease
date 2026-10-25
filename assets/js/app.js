/* Servease: small progressive-enhancement script. Every page works without it. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const csrf = () => ($('meta[name="csrf-token"]') || {}).content || '';

  /* ---- App bar back: return to where you came from when it's this site ------ */
  $$('[data-back]').forEach(a => a.addEventListener('click', e => {
    try { sessionStorage.setItem('se-back', '1'); } catch (err) {}
    const ref = document.referrer && new URL(document.referrer, location.href);
    if (ref && ref.origin === location.origin && history.length > 1) { e.preventDefault(); history.back(); }
  }));

  /* ---- Navigation progress bar: instant feedback while the next page loads ---- */
  const bar = document.createElement('div'); bar.className = 'nav-progress'; bar.setAttribute('aria-hidden', 'true');
  document.body.appendChild(bar);
  const startNav = () => { bar.classList.remove('is-done'); void bar.offsetWidth; bar.classList.add('is-on'); };
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self' || a.hasAttribute('download')) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search && url.hash)) return;
    startNav();
  });
  document.addEventListener('submit', e => { if (!e.defaultPrevented && !e.target.hasAttribute('data-no-progress')) startNav(); });
  window.addEventListener('pageshow', () => { bar.classList.remove('is-on'); bar.classList.add('is-done'); });

  /* ---- Category rail (laptop): arrows, edge fades, current chip in view ------ */
  $$('[data-rail]').forEach(rail => {
    const track = $('[data-rail-track]', rail);
    const prev = $('.rail-prev', rail), next = $('.rail-next', rail);
    const edges = () => {
      const max = track.scrollWidth - track.clientWidth - 4;
      const p = track.scrollLeft > 4, n = track.scrollLeft < max;
      rail.classList.toggle('has-prev', p); rail.classList.toggle('has-next', n);
      if (prev) prev.hidden = !p; if (next) next.hidden = !n;
    };
    $$('[data-rail-nav]', rail).forEach(b => b.addEventListener('click', () => track.scrollBy({ left: +b.dataset.railNav * track.clientWidth * 0.7, behavior: reduceMotion ? 'auto' : 'smooth' })));
    track.addEventListener('scroll', edges, { passive: true });
    window.addEventListener('resize', edges);
    const on = $('.is-active', track);
    if (on && on.offsetLeft + on.offsetWidth > track.clientWidth) track.scrollLeft = on.offsetLeft - 60;
    edges();
  });

  /* ---- Laptop filters apply as soon as they change (phones use the sheet's button) -- */
  const filterForm = $('.layout-filters .filters-card');
  if (filterForm) {
    const wide = () => window.matchMedia('(min-width: 901px)').matches;
    filterForm.addEventListener('change', () => { if (wide()) (filterForm.requestSubmit ? filterForm.requestSubmit() : filterForm.submit()); });
  }

  /* ---- Theme ---------------------------------------------------------- */
  $$('[data-theme-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('servease-theme', next); } catch (e) {}
  }));

  /* ---- Top bar gets a hairline once the page scrolls ------------------- */
  const topbar = $('[data-topbar]');
  if (topbar) {
    const onScroll = () => topbar.classList.toggle('is-scrolled', window.scrollY > 4);
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---- Keep the current item visible in sideways-scrolling navs (admin bar, chips) */
  $$('.admin-side a.is-active, .chip-scroller .chip.is-active').forEach(a => {
    const bar = a.parentElement;
    if (bar.scrollWidth <= bar.clientWidth) return;
    const r = a.getBoundingClientRect(), b = bar.getBoundingClientRect();
    if (r.right > b.right - 24) bar.scrollLeft += r.left - b.left - b.width / 2 + r.width / 2;
  });

  /* ---- Reveal sections as they scroll into view ------------------------- */
  const reveals = $$('[data-reveal]');
  if (reveals.length && 'IntersectionObserver' in window && !reduceMotion) {
    const io = new IntersectionObserver(entries => entries.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    reveals.forEach(el => io.observe(el));
  } else reveals.forEach(el => el.classList.add('is-in'));

  /* ---- Banner carousel: swipe, dots, gentle auto-play ------------------- */
  $$('[data-carousel]').forEach(box => {
    const track = $('[data-carousel-track]', box);
    const slides = Array.from(track.children);
    const dots = $$('[data-carousel-dot]', box);
    const ms = 5500; let index = 0; let timer = null;
    box.style.setProperty('--deal-ms', ms + 'ms');
    const mark = i => {
      index = i;
      dots.forEach((d, n) => {
        d.classList.toggle('is-on', n === i);
        if (n === i) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
      });
      // Only the visible banner is reachable by keyboard and screen readers
      slides.forEach((sl, n) => {
        if (n === i) sl.removeAttribute('aria-hidden'); else sl.setAttribute('aria-hidden', 'true');
        $$('a, button, input', sl).forEach(el => { if (n === i) el.removeAttribute('tabindex'); else el.setAttribute('tabindex', '-1'); });
      });
    };
    const go = i => { track.scrollTo({ left: slides[i].offsetLeft, behavior: reduceMotion ? 'auto' : 'smooth' }); mark(i); };
    const next = () => go((index + 1) % slides.length);
    const play = () => { if (reduceMotion || slides.length < 2) { box.setAttribute('data-static', ''); return; } stop(); timer = setInterval(next, ms); box.classList.remove('is-paused'); };
    const stop = () => { clearInterval(timer); timer = null; };
    dots.forEach((d, n) => d.addEventListener('click', () => { go(n); play(); }));
    let scrollT;
    track.addEventListener('scroll', () => {
      clearTimeout(scrollT);
      scrollT = setTimeout(() => { const i = Math.round(track.scrollLeft / track.clientWidth); if (i !== index) mark(i); }, 80);
    }, { passive: true });
    ['pointerenter', 'focusin', 'touchstart'].forEach(ev => box.addEventListener(ev, () => { stop(); box.classList.add('is-paused'); }, { passive: true }));
    ['pointerleave', 'focusout', 'touchend'].forEach(ev => box.addEventListener(ev, play, { passive: true }));
    document.addEventListener('visibilitychange', () => document.hidden ? stop() : play());
    play();
  });

  /* ---- Top search: a shorter hint on phones ------------------------------- */
  const topQ = $('#top-q');
  if (topQ && matchMedia('(max-width: 900px)').matches) topQ.placeholder = 'Search services';

  /* ---- Onboarding slides: Next / Skip / dots follow the swipe ------------ */
  const ob = $('[data-onboard]');
  if (ob) {
    const track = $('[data-ob-track]', ob);
    const goTo = id => { const el = document.getElementById(id); if (el) track.scrollTo({ left: el.offsetLeft, behavior: reduceMotion ? 'auto' : 'smooth' }); };
    $$('[data-ob-next], [data-ob-skip]', ob).forEach(a => a.addEventListener('click', e => { e.preventDefault(); goTo(a.getAttribute('href').slice(1)); }));
  }


  /* ---- Installable app: service worker, install button, iPhone help --------- */
  const base = (document.querySelector('meta[name="app-base"]') || {}).content || '';
  const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
  if (standalone) document.documentElement.classList.add('is-app');
  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register(base + '/sw.js', { scope: base + '/' }).catch(() => {}));
  }
  const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const dismissedKey = 'servease-install-dismissed';
  const dismissed = () => { try { return Date.now() - (+localStorage.getItem(dismissedKey) || 0) < 14 * 864e5; } catch (e) { return false; } };
  let deferredPrompt = null;
  const showInstall = on => {
    $$('[data-install]').forEach(b => { b.hidden = !on; });
    $$('[data-install-card]').forEach(c => { c.hidden = !on || dismissed(); });
  };
  window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); deferredPrompt = e; showInstall(true); });
  window.addEventListener('appinstalled', () => { deferredPrompt = null; showInstall(false); });
  if (ios && !standalone) showInstall(true);
  document.addEventListener('click', async e => {
    if (e.target.closest('[data-install-dismiss]')) {
      try { localStorage.setItem(dismissedKey, String(Date.now())); } catch (err) {}
      const card = e.target.closest('[data-install-card]');
      if (card) { card.classList.add('is-leaving'); setTimeout(() => { card.hidden = true; }, 260); }
      return;
    }
    if (!e.target.closest('[data-install]')) return;
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const choice = await deferredPrompt.userChoice.catch(() => null);
      deferredPrompt = null;
      if (choice && choice.outcome === 'accepted') showInstall(false);
    } else {
      const help = document.getElementById('install-help');
      if (help && help.showModal) help.showModal();
    }
  });

  /* ---- Dropdowns: close on outside click / Esc; only one open ----------- */
  document.addEventListener('click', e => {
    $$('details.dropdown[open]').forEach(d => { if (!d.contains(e.target)) d.removeAttribute('open'); });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') $$('details.dropdown[open]').forEach(d => d.removeAttribute('open'));
  });
  $$('details.dropdown').forEach(d => d.addEventListener('toggle', () => {
    if (d.open) $$('details.dropdown[open]').forEach(o => { if (o !== d) o.removeAttribute('open'); });
  }));

  /* ---- Toasts ----------------------------------------------------------- */
  const dismiss = t => { t.classList.add('is-leaving'); setTimeout(() => t.remove(), 250); };
  $$('.toast').forEach(t => {
    const close = $('.alert-close', t);
    if (close) close.addEventListener('click', () => dismiss(t));
    if (!t.querySelector('a')) setTimeout(() => dismiss(t), 6000);
  });
  window.toast = (message, type = 'success') => {
    let box = $('.toasts');
    if (!box) { box = document.createElement('div'); box.className = 'toasts'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = `alert alert-${type} toast`;
    t.setAttribute('role', 'status');
    t.textContent = message;
    box.appendChild(t);
    setTimeout(() => dismiss(t), 4000);
  };

  /* ---- Modals (<dialog>) ------------------------------------------------ */
  $$('[data-open]').forEach(btn => btn.addEventListener('click', () => {
    const d = document.getElementById(btn.dataset.open);
    if (d && d.showModal) d.showModal();
  }));
  $$('dialog.modal').forEach(d => {
    d.addEventListener('click', e => { if (e.target === d) d.close(); });
    $$('[data-close]', d).forEach(b => b.addEventListener('click', () => d.close()));
  });

  /* ---- Confirm before destructive actions ------------------------------- */
  const confirmDialog = $('#confirm-dialog');
  let pendingForm = null;
  $$('form[data-confirm]').forEach(form => form.addEventListener('submit', e => {
    if (form.dataset.confirmed === '1' || !confirmDialog) return;
    e.preventDefault();
    pendingForm = form;
    $('#confirm-text', confirmDialog).textContent = form.dataset.confirm;
    confirmDialog.showModal();
  }));
  if (confirmDialog) {
    $('#confirm-ok', confirmDialog).addEventListener('click', () => {
      confirmDialog.close();
      if (pendingForm) { pendingForm.dataset.confirmed = '1'; pendingForm.requestSubmit ? pendingForm.requestSubmit() : pendingForm.submit(); }
    });
  }

  /* ---- Prevent double submits ------------------------------------------ */
  $$('form').forEach(f => f.addEventListener('submit', () => {
    if (f.dataset.confirm && f.dataset.confirmed !== '1') return;
    const b = f.querySelector('button[type="submit"]:not([data-no-lock])');
    if (b) setTimeout(() => { b.disabled = true; }, 0);
  }));

  /* ---- Password show/hide + strength ------------------------------------ */
  $$('[data-pw-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.classList.toggle('is-on', show);
  }));
  $$('[data-pw-meter]').forEach(input => {
    const bar = document.querySelector(input.dataset.pwMeter + ' span');
    const field = input.closest('.field');
    const label = field ? $('[data-pw-label]', field) : null;
    const rules = input.dataset.pwRules ? document.querySelector(input.dataset.pwRules) : null;
    const update = () => {
      const v = input.value; let s = 0;
      if (v.length >= 8) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) s++;
      const colors = ['#d92d30', '#d92d30', '#e59f00', '#12a150', '#12a150'];
      const words = ['Too weak', 'Weak', 'Fair', 'Strong', 'Very strong'];
      bar.style.width = (v ? Math.max(12, s * 25) : 0) + '%';
      bar.style.background = colors[s];
      if (label) { label.textContent = v ? words[s] : ''; label.style.color = colors[s]; }
      if (rules) {
        const ok = { len: v.length >= 8, letter: /[A-Za-z]/.test(v), number: /\d/.test(v) };
        $$('[data-rule]', rules).forEach(li => li.classList.toggle('ok', !!ok[li.dataset.rule]));
      }
    };
    input.addEventListener('input', update);
  });
  $$('[data-match]').forEach(input => {
    const other = document.querySelector(input.dataset.match);
    const msg = $('[data-match-msg]', input.closest('.field'));
    const check = () => {
      if (!msg) return;
      if (!input.value) { msg.textContent = ''; return; }
      const same = input.value === other.value;
      msg.textContent = same ? '✓ Passwords match' : 'Passwords don’t match yet';
      msg.style.color = same ? 'var(--sv-green)' : 'var(--sv-text-3)';
      msg.style.fontWeight = same ? '600' : '500';
    };
    input.addEventListener('input', check); other.addEventListener('input', check);
  });

  /* ---- Character counters: <textarea maxlength="…" data-count> ------------ */
  $$('[data-count]').forEach(el => {
    const max = parseInt(el.getAttribute('maxlength') || el.dataset.count || '0', 10);
    const min = parseInt(el.dataset.min || '0', 10);
    let out = el.closest('.field') && $('.counter', el.closest('.field'));
    if (!out) { out = document.createElement('span'); out.className = 'counter'; el.insertAdjacentElement('afterend', out); }
    const update = () => {
      const n = el.value.length;
      out.textContent = min && n < min ? `${n} / ${min} min` : (max ? `${n} / ${max}` : `${n}`);
      out.classList.toggle('is-low', !!min && n > 0 && n < min);
      out.classList.toggle('is-over', !!max && n > max);
    };
    el.addEventListener('input', update); update();
  });

  /* ---- Quick picks that fill another input: <button data-fill="#id" data-value="…"> */
  $$('[data-fill]').forEach(btn => btn.addEventListener('click', () => {
    const target = document.querySelector(btn.dataset.fill);
    if (!target) return;
    target.value = btn.dataset.value;
    target.dispatchEvent(new Event('input', { bubbles: true }));
    target.dispatchEvent(new Event('change', { bubbles: true }));
    $$(`[data-fill="${btn.dataset.fill}"]`).forEach(b => b.classList.toggle('is-active', b === btn));
  }));
  $$('[data-fill]').forEach(btn => {
    const target = document.querySelector(btn.dataset.fill);
    if (target && target.value && target.value === btn.dataset.value) btn.classList.add('is-active');
    if (target) target.addEventListener('input', () => {
      $$(`[data-fill="${btn.dataset.fill}"]`).forEach(b => b.classList.toggle('is-active', b.dataset.value === target.value));
    });
  });

  /* ---- Live preview: <el data-preview="name" data-empty="…"> mirrors form field "name" */
  $$('form[data-live-preview]').forEach(form => {
    const money = n => '₱' + Number(n).toLocaleString('en-PH');
    const fmtDate = v => { const d = new Date(v + 'T00:00:00'); return isNaN(d) ? '' : d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }); };
    const update = () => {
      const data = new FormData(form);
      $$('[data-preview]').forEach(el => {
        const key = el.dataset.preview;
        let val = '';
        if (key === 'budget') {
          const lo = data.get('budget_min'), hi = data.get('budget_max');
          val = lo && hi ? `${money(lo)}–${money(hi)}` : hi ? `Up to ${money(hi)}` : lo ? `${money(lo)}+` : '';
        } else if (key.endsWith('_label')) {
          const input = form.querySelector(`[name="${key.replace('_label', '')}"]:checked`);
          val = input ? (input.dataset.label || '') : '';
          if (el.dataset.previewIcon !== undefined) {
            const src = input && document.querySelector(`[data-icon-src="${input.dataset.icon}"]`);
            if (src) el.innerHTML = src.innerHTML;
            return;
          }
        } else {
          val = (data.get(key) || '').toString().trim();
          const input = form.querySelector(`[name="${key}"]`);
          if (val && input && input.tagName === 'SELECT' && input.selectedOptions[0]) val = input.selectedOptions[0].textContent.trim();
          if (val && el.dataset.format === 'date') val = fmtDate(val);
          if (val && el.dataset.format === 'money') val = money(val);
        }
        el.textContent = val || el.dataset.empty || '';
        el.classList.toggle('is-placeholder', !val);
      });
    };
    form.addEventListener('input', update); form.addEventListener('change', update); update();
  });

  /* ---- Unsaved-changes bar for long forms: <form data-savebar> -------------- */
  $$('form[data-savebar]').forEach(form => {
    const bar = document.createElement('div');
    bar.className = 'savebar'; bar.setAttribute('role', 'status');
    bar.innerHTML = '<i class="dot"></i><span>You have unsaved changes</span>';
    const reset = document.createElement('button'); reset.type = 'button'; reset.className = 'btn btn-ghost btn-sm'; reset.textContent = 'Discard';
    const save = document.createElement('button'); save.type = 'button'; save.className = 'btn btn-primary btn-sm'; save.textContent = 'Save changes';
    bar.append(reset, save); document.body.appendChild(bar);
    const initial = new URLSearchParams(new FormData(form)).toString();
    const dirty = () => new URLSearchParams(new FormData(form)).toString() !== initial;
    const sync = () => bar.classList.toggle('is-visible', dirty());
    form.addEventListener('input', sync); form.addEventListener('change', sync);
    reset.addEventListener('click', () => { form.reset(); form.dispatchEvent(new Event('input')); sync(); });
    save.addEventListener('click', () => form.requestSubmit ? form.requestSubmit() : form.submit());
    form.addEventListener('submit', () => bar.classList.remove('is-visible'));
  });

  /* ---- Dropzones: show chosen file + image preview ---------------------- */
  $$('.dropzone').forEach(zone => {
    const input = $('input[type=file]', zone);
    const label = $('.dropzone-file', zone);
    const preview = zone.dataset.preview ? document.querySelector(zone.dataset.preview) : null;
    const show = () => {
      const f = input.files[0];
      if (!f) return;
      if (label) label.textContent = f.name;
      if (preview && f.type.startsWith('image/')) {
        const url = URL.createObjectURL(f);
        if (preview.tagName === 'IMG') preview.src = url;
        else preview.outerHTML = `<img src="${url}" class="${preview.className}" id="${preview.id}" alt="">`;
      }
      if (zone.dataset.autosubmit !== undefined) zone.closest('form').submit();
    };
    input.addEventListener('change', show);
    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-drag'); }));
    ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-drag'); }));
    zone.addEventListener('drop', e => { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(); } });
  });

  /* ---- Save / unsave provider (AJAX with graceful fallback) ------------- */
  $$('form[data-save-toggle]').forEach(form => form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = $('button', form);
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } });
      if (res.status === 401) { window.location = form.dataset.login; return; }
      const data = await res.json();
      btn.classList.toggle('is-saved', data.saved);
      btn.setAttribute('aria-pressed', data.saved);
      btn.disabled = false;
      window.toast(data.saved ? 'Saved to your list' : 'Removed from saved');
    } catch (err) { form.submit(); }
  }));

  /* ---- Filters: a bottom sheet on phones ------------------------------- */
  const sheet = $('.filters-card');
  const backdrop = $('.sheet-backdrop');
  const toggles = $$('[data-filters-toggle]');
  const setSheet = open => {
    if (!sheet) return;
    sheet.classList.toggle('is-open', open);
    if (backdrop) backdrop.classList.toggle('is-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
    toggles.forEach(b => b.setAttribute('aria-expanded', open ? 'true' : 'false'));
    if (open) { const first = sheet.querySelector('input, select, button'); if (first) first.focus({ preventScroll: true }); }
  };
  toggles.forEach(b => b.addEventListener('click', () => setSheet(!sheet.classList.contains('is-open'))));
  $$('[data-filters-close]').forEach(b => b.addEventListener('click', () => setSheet(false)));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && sheet && sheet.classList.contains('is-open')) setSheet(false); });

  /* ---- Auto-submit selects ---------------------------------------------- */
  $$('select[data-autosubmit]').forEach(s => s.addEventListener('change', () => s.form.submit()));

  /* ---- Demo account quick-fill ----------------------------------------- */
  $$('[data-demo]').forEach(b => b.addEventListener('click', () => {
    const form = b.closest('form') || $('#login-form');
    $('input[name=email]', form).value = b.dataset.demo;
    $('input[name=password]', form).value = b.dataset.pw;
    form.requestSubmit ? form.requestSubmit() : form.submit();
  }));

  /* ---- Chat ------------------------------------------------------------- */
  const chat = $('[data-chat]');
  if (chat) {
    const body = $('.chat-body', chat);
    const form = $('.chat-form', chat);
    const box = $('textarea', form);
    const pollUrl = chat.dataset.poll;
    let lastId = parseInt(chat.dataset.last || '0', 10);
    const scroll = () => { body.scrollTop = body.scrollHeight; };
    scroll();

    const esc = s => s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const append = m => {
      if (m.id <= lastId) return;
      lastId = m.id;
      const empty = $('.chat-empty', chat); if (empty) empty.remove();
      body.insertAdjacentHTML('beforeend',
        `<div class="msg ${m.mine ? 'msg-me' : 'msg-them'}">${esc(m.body)}</div><div class="msg-time ${m.mine ? 'me' : ''}">${esc(m.time)}</div>`);
      scroll();
    };

    box.addEventListener('input', () => { box.style.height = '44px'; box.style.height = Math.min(box.scrollHeight, 160) + 'px'; });
    box.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const text = box.value.trim();
      if (!text) return;
      const btn = $('button[type=submit]', form);
      btn.disabled = true;
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.message) { append(data.message); box.value = ''; box.style.height = '44px'; }
        else window.toast(data.error || 'Message not sent', 'error');
      } catch (err) { window.toast('Network error — message not sent', 'error'); }
      btn.disabled = false;
      box.focus();
    });

    // Light polling only while this tab is visible (no busy loops in background tabs)
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await fetch(`${pollUrl}?after=${lastId}`, { headers: { 'Accept': 'application/json' } });
        if (res.ok) (await res.json()).messages.forEach(append);
      } catch (err) { /* ignore transient errors */ }
    };
    setInterval(poll, 4000);
    document.addEventListener('visibilitychange', poll);
  }
})();

/* ---- Search suggestions: trades, services and pros as you type (any search box with name="q") */
(function () {
  const forms = document.querySelectorAll('form[role="search"]');
  if (!forms.length) return;
  const base = (document.querySelector('link[rel="manifest"]')?.getAttribute('href') || '/manifest.webmanifest').replace(/manifest\.webmanifest.*$/, '');
  const endpoint = base + 'search/suggest';
  const L = document.documentElement.lang === 'fil'
    ? { Pro: 'Mga pro', Category: 'Mga kategorya', Service: 'Mga serbisyo', all: 'Tingnan lahat ng resulta para sa', none: 'Walang tugma. Pindutin ang Enter para hanapin ang' }
    : { Pro: 'Pros', Category: 'Categories', Service: 'Services', all: 'See all results for', none: 'No matches. Press Enter to search everything for' };
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  forms.forEach((form, n) => {
    const input = form.querySelector('input[name="q"]');
    if (!input) return;
    const list = document.createElement('div');
    list.className = 'suggest';
    list.id = 'suggest-' + n;
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    form.classList.add('has-suggest');
    form.appendChild(list);
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', list.id);
    input.setAttribute('aria-expanded', 'false');

    let timer = null, skelTimer = null, ctrl = null, active = -1, items = [];
    // Fixed to the viewport so banners with overflow:hidden can't clip it
    const place = () => {
      const r = form.getBoundingClientRect();
      const w = Math.max(r.width, Math.min(380, window.innerWidth - 32));
      list.style.top = (r.bottom + 8) + 'px';
      list.style.left = Math.max(16, Math.min(r.left, window.innerWidth - w - 16)) + 'px';
      list.style.width = w + 'px';
    };
    window.addEventListener('resize', () => { if (!list.hidden) place(); });
    window.addEventListener('scroll', () => { if (!list.hidden) place(); }, { passive: true });
    const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; input.removeAttribute('aria-activedescendant'); };
    const mark = i => {
      items.forEach((el, k) => el.classList.toggle('is-active', k === i));
      active = i;
      if (items[i]) { input.setAttribute('aria-activedescendant', items[i].id); items[i].scrollIntoView({ block: 'nearest' }); }
    };
    const render = (data, q) => {
      if (!data.items.length) { list.innerHTML = '<div class="suggest-empty">' + esc(L.none) + ' “' + esc(q) + '”.</div>'; }
      else {
        let html = '', last = '';
        data.items.forEach((it, i) => {
          if (it.type !== last) { html += '<div class="suggest-group">' + esc(L[it.type] || it.type) + '</div>'; last = it.type; }
          html += '<a class="suggest-item" role="option" id="' + list.id + '-' + i + '" href="' + esc(it.href) + '"><span class="suggest-ic">' + it.svg + '</span>'
                + '<span class="grow"><span class="suggest-label">' + esc(it.label) + (it.badge ? '<span class="suggest-badge" title="ID-checked">' + it.badge + '</span>' : '') + '</span><span class="suggest-sub">' + esc(it.sub || '') + '</span></span></a>';
        });
        html += '<a class="suggest-all" href="' + esc(data.all) + '">' + esc(L.all) + ' “' + esc(q) + '”</a>';
        list.innerHTML = html;
      }
      items = Array.from(list.querySelectorAll('.suggest-item, .suggest-all'));
      active = -1;
      place();
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    };
    const lookup = async () => {
      const q = input.value.trim();
      if (q.length < 2) { close(); return; }
      if (ctrl) ctrl.abort();
      ctrl = new AbortController();
      // Slow network: show placeholder rows instead of a frozen box
      clearTimeout(skelTimer);
      skelTimer = setTimeout(() => {
        list.innerHTML = '<div class="suggest-skel"><span class="skel skel-ic"></span><span class="grow"><span class="skel skel-line"></span><span class="skel skel-line"></span></span></div>'.repeat(3);
        list.setAttribute('aria-busy', 'true'); items = []; active = -1; place(); list.hidden = false;
      }, 180);
      try {
        const res = await fetch(endpoint + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: ctrl.signal });
        if (res.ok && input.value.trim() === q) { clearTimeout(skelTimer); list.removeAttribute('aria-busy'); render(await res.json(), q); }
      } catch (e) {
        /* aborted or offline: keep the plain search */
        if (e.name !== 'AbortError') { clearTimeout(skelTimer); list.removeAttribute('aria-busy'); if (list.querySelector('.suggest-skel')) close(); }
      }
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(lookup, 160); });
    input.addEventListener('focus', () => { if (input.value.trim().length >= 2 && list.innerHTML) { place(); list.hidden = false; input.setAttribute('aria-expanded', 'true'); } });
    input.addEventListener('keydown', e => {
      if (list.hidden) return;
      if (e.key === 'ArrowDown') { e.preventDefault(); mark(Math.min(active + 1, items.length - 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); mark(Math.max(active - 1, -1)); }
      else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); items[active].click(); }
      else if (e.key === 'Escape') { close(); }
    });
    document.addEventListener('click', e => { if (!form.contains(e.target)) close(); });
  });
})();
