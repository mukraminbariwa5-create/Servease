/* Fixoria — small progressive-enhancement script. Pages work without it. */
(function () {
  'use strict';
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const csrf = () => ($('meta[name="csrf-token"]') || {}).content || '';

  /* ---- Theme ---------------------------------------------------------- */
  $$('[data-theme-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem('fixoria-theme', next); } catch (e) {}
  }));

  /* ---- Mobile nav ------------------------------------------------------- */
  $$('[data-nav-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const nav = btn.closest('.navbar');
    nav.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', nav.classList.contains('is-open'));
  }));

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
      msg.style.color = same ? 'var(--green)' : 'var(--text-3)';
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

  /* ---- Mobile filter toggle --------------------------------------------- */
  $$('[data-filters-toggle]').forEach(b => b.addEventListener('click', () => {
    $('.filters-card').classList.toggle('is-open');
  }));

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
