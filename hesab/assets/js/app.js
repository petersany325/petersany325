(function () {
  // Windows menubar: click to pin open; hover also works via CSS
  const menubar = document.getElementById('win-menubar');
  if (menubar) {
    const placeDrop = (menu) => {
      const drop = menu.querySelector('.win-menu-drop');
      const btn = menu.querySelector('.win-menu-btn');
      if (!drop || !btn) return;
      // Reset absolute anchoring under the trigger (fixes RTL mis-stack)
      drop.style.left = 'auto';
      drop.style.right = 'auto';
      drop.style.insetInlineStart = '0';
      drop.style.insetInlineEnd = 'auto';
      const btnRect = btn.getBoundingClientRect();
      const dropRect = drop.getBoundingClientRect();
      const vw = window.innerWidth || document.documentElement.clientWidth;
      // Keep dropdown inside viewport horizontally
      if (btnRect.left + dropRect.width > vw - 4) {
        drop.style.insetInlineStart = 'auto';
        drop.style.insetInlineEnd = '0';
      }
      if (btnRect.right - dropRect.width < 4) {
        drop.style.insetInlineStart = '0';
        drop.style.insetInlineEnd = 'auto';
      }
    };
    menubar.querySelectorAll('.win-menu-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const menu = btn.closest('.win-menu');
        const wasOpen = menu.classList.contains('open');
        menubar.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
        if (!wasOpen) {
          menu.classList.add('open');
          placeDrop(menu);
        }
      });
      btn.addEventListener('mouseenter', () => {
        requestAnimationFrame(() => placeDrop(btn.closest('.win-menu')));
      });
    });
    document.addEventListener('click', (e) => {
      if (!menubar.contains(e.target)) {
        menubar.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
      }
    });
  }

  // Explorer tree branches
  document.querySelectorAll('.branch-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('.branch')?.classList.toggle('open');
    });
  });

  // ===== Multi-document workspace (desktop shell) =====
  const cfg = window.HESAB_WS;
  const strip = document.getElementById('win-tabstrip');
  const panels = document.getElementById('win-tab-panels');
  if (cfg && strip && panels) {
    const titleLabel = document.getElementById('win-title-label');
    const statusPane = document.getElementById('ws-status');
    const MAX_TABS = 12;
    let seq = 0;
    /** @type {{id:string,key:string,title:string,url:string,iframe:HTMLIFrameElement,btn:HTMLButtonElement,panel:HTMLElement}[]} */
    const tabs = [];
    let activeId = null;

    function closeMenus() {
      menubar?.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
    }

    function tabKey(href) {
      const u = new URL(href, window.location.origin);
      u.searchParams.delete('embed');
      // Same document with different hash shares one tab (e.g. treasury#receive)
      return u.pathname + u.search;
    }

    function embedUrl(href) {
      const u = new URL(href, window.location.origin);
      u.searchParams.set('embed', '1');
      return u.pathname + u.search + u.hash;
    }

    function isAppLink(href) {
      if (!href || href.startsWith('javascript:') || href.startsWith('mailto:')) return false;
      if (href.startsWith('#')) return false;
      try {
        const u = new URL(href, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        const base = cfg.basePath || '';
        if (base && !u.pathname.startsWith(base)) return false;
        if (u.pathname.includes('/logout')) return false;
        if (u.pathname.includes('/m') && (u.pathname === base + '/m' || u.pathname.startsWith(base + '/m/'))) {
          return false;
        }
        return true;
      } catch (_) {
        return false;
      }
    }

    function setStatus(text) {
      if (statusPane) statusPane.textContent = text;
    }

    function syncChrome(tab) {
      if (titleLabel) titleLabel.textContent = tab.title;
      document.title = tab.title + ' — ' + (cfg.appName || 'حساب');
      setStatus(tab.title);
      // Highlight tree item whose link matches this tab
      const tree = document.getElementById('win-tree');
      if (tree) {
        tree.querySelectorAll('li.on').forEach((li) => li.classList.remove('on'));
        tree.querySelectorAll('a[href]').forEach((a) => {
          if (tabKey(a.href) === tab.key) {
            a.closest('li')?.classList.add('on');
          }
        });
      }
    }

    function activate(id) {
      const tab = tabs.find((t) => t.id === id);
      if (!tab) return;
      activeId = id;
      tabs.forEach((t) => {
        const on = t.id === id;
        t.btn.classList.toggle('active', on);
        t.btn.setAttribute('aria-selected', on ? 'true' : 'false');
        t.panel.classList.toggle('active', on);
      });
      syncChrome(tab);
      tab.btn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    function renderTabButton(tab) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'win-tab';
      btn.setAttribute('role', 'tab');
      btn.dataset.tabId = tab.id;
      btn.title = tab.title;

      const label = document.createElement('span');
      label.className = 'win-tab-label';
      label.textContent = tab.title;

      const close = document.createElement('span');
      close.className = 'win-tab-close';
      close.title = 'بستن';
      close.setAttribute('aria-label', 'بستن زبانه');
      close.textContent = '×';

      btn.appendChild(label);
      btn.appendChild(close);

      btn.addEventListener('click', (e) => {
        if (e.target === close || close.contains(e.target)) {
          e.preventDefault();
          e.stopPropagation();
          closeTab(tab.id);
          return;
        }
        activate(tab.id);
      });

      btn.addEventListener('auxclick', (e) => {
        if (e.button === 1) {
          e.preventDefault();
          closeTab(tab.id);
        }
      });

      return btn;
    }

    function openTab(href, title, opts) {
      opts = opts || {};
      if (!isAppLink(href)) {
        window.location.href = href;
        return;
      }
      const key = tabKey(href);
      const existing = tabs.find((t) => t.key === key);
      if (existing) {
        // Update hash inside existing iframe if needed
        const want = embedUrl(href);
        try {
          const cur = existing.iframe.contentWindow?.location;
          if (cur && cur.hash !== new URL(want, location.origin).hash) {
            existing.iframe.contentWindow.location.hash = new URL(want, location.origin).hash;
          }
        } catch (_) { /* cross-origin guard */ }
        if (title && title !== existing.title) {
          existing.title = title;
          existing.btn.querySelector('.win-tab-label').textContent = title;
        }
        activate(existing.id);
        return;
      }

      if (tabs.length >= MAX_TABS) {
        setStatus('حداکثر ' + MAX_TABS + ' زبانه — یکی را ببندید');
        return;
      }

      const id = 't' + (++seq);
      const panel = document.createElement('div');
      panel.className = 'win-tab-panel';
      panel.dataset.tabId = id;
      panel.setAttribute('role', 'tabpanel');

      const iframe = document.createElement('iframe');
      iframe.className = 'win-tab-frame';
      iframe.src = embedUrl(href);
      iframe.title = title || 'پنجره';
      iframe.setAttribute('loading', opts.eager ? 'eager' : 'lazy');

      panel.appendChild(iframe);
      panels.appendChild(panel);

      const tab = {
        id,
        key,
        title: title || 'پنجره',
        url: href,
        iframe,
        btn: null,
        panel,
      };
      tab.btn = renderTabButton(tab);
      strip.appendChild(tab.btn);
      tabs.push(tab);

      iframe.addEventListener('load', () => {
        try {
          const doc = iframe.contentDocument;
          if (!doc) return;
          const pageTitle = doc.title || '';
          // Prefer short title before em dash
          let t = pageTitle.split('—')[0].trim();
          if (t && t !== tab.title && tab.title === (opts.seedTitle || tab.title)) {
            // keep explicit menu title; only fill if generic
          }
          if ((!tab.title || tab.title === 'پنجره') && t) {
            tab.title = t;
            tab.btn.querySelector('.win-tab-label').textContent = t;
            if (activeId === tab.id) syncChrome(tab);
          }
          // Keep tab key in sync if iframe navigated (drill-down)
          const loc = iframe.contentWindow.location;
          const newKey = tabKey(loc.pathname + loc.search + loc.hash);
          if (newKey !== tab.key) {
            tab.key = newKey;
            tab.url = loc.pathname + loc.search + loc.hash;
            if (activeId === tab.id) syncChrome(tab);
          }
        } catch (_) { /* ignore */ }
      });

      activate(id);
      return tab;
    }

    function closeTab(id) {
      const idx = tabs.findIndex((t) => t.id === id);
      if (idx < 0) return;
      if (tabs.length === 1) {
        // Reload home in the sole tab instead of closing the app
        const only = tabs[0];
        const home = (cfg.basePath || '') + '/';
        only.key = tabKey(home);
        only.title = 'پنجره اصلی';
        only.url = home;
        only.btn.querySelector('.win-tab-label').textContent = only.title;
        only.iframe.src = embedUrl(home);
        activate(only.id);
        return;
      }
      const [removed] = tabs.splice(idx, 1);
      removed.btn.remove();
      removed.panel.remove();
      if (activeId === id) {
        const next = tabs[Math.max(0, idx - 1)];
        activate(next.id);
      }
    }

    function navClick(e) {
      const a = e.target.closest('a[href]');
      if (!a) return;
      if (a.dataset.wsBypass === '1') return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      if (e.button !== 0 && e.type === 'click') return;

      const inChrome = a.closest('#win-menubar, #win-tree, .win-toolbar');
      if (!inChrome) return;

      const href = a.getAttribute('href');
      if (!isAppLink(href)) return;

      e.preventDefault();
      closeMenus();
      const title = a.dataset.wsTitle || a.textContent.trim().replace(/\s+/g, ' ');
      openTab(href, title);
    }

    document.addEventListener('click', navClick);

    document.getElementById('ws-close-tab')?.addEventListener('click', () => {
      if (activeId) closeTab(activeId);
    });

    // Keyboard: Ctrl+W close, Ctrl+Tab next (approximate)
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'w') {
        e.preventDefault();
        if (activeId) closeTab(activeId);
      }
      if ((e.ctrlKey || e.metaKey) && e.key === 'Tab') {
        e.preventDefault();
        if (!tabs.length) return;
        const i = tabs.findIndex((t) => t.id === activeId);
        const next = e.shiftKey
          ? tabs[(i - 1 + tabs.length) % tabs.length]
          : tabs[(i + 1) % tabs.length];
        activate(next.id);
      }
    });

    window.HESAB_WS_API = {
      openTab: (href, title) => openTab(href, title),
      closeActive: () => { if (activeId) closeTab(activeId); },
      refreshActive: () => {
        const tab = tabs.find((t) => t.id === activeId);
        if (tab) tab.iframe.src = tab.iframe.src;
      },
    };

    openTab(cfg.initialUrl, cfg.initialTitle, { eager: true, seedTitle: cfg.initialTitle });
  }

  // ===== Keyboard shortcuts engine =====
  (function initShortcuts() {
    const sc = window.HESAB_SHORTCUTS;
    if (!sc || !sc.items) return;

    let lastSaveAt = 0;
    const chordIndex = {};
    Object.keys(sc.items).forEach((id) => {
      const item = sc.items[id];
      if (!item || !item.available || !item.key) return;
      chordIndex[item.key] = item;
      chordIndex[item.key].id = id;
    });

    function eventChord(e) {
      const parts = [];
      if (e.ctrlKey || e.metaKey) parts.push('Ctrl');
      if (e.altKey) parts.push('Alt');
      if (e.shiftKey) parts.push('Shift');
      let k = e.key;
      if (k === 'Escape') k = 'Esc';
      else if (k === ' ') k = 'Space';
      else if (k === 'Del') k = 'Delete';
      else if (k.length === 1) k = k.toUpperCase();
      if (k === 'Control' || k === 'Shift' || k === 'Alt' || k === 'Meta') return '';
      parts.push(k);
      return parts.join('+');
    }

    function api() {
      if (window.HESAB_WS_API) return window.HESAB_WS_API;
      try {
        if (window.parent && window.parent !== window && window.parent.HESAB_WS_API) {
          return window.parent.HESAB_WS_API;
        }
      } catch (_) { /* ignore */ }
      return null;
    }

    function absPath(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      const base = sc.basePath || '';
      if (path.startsWith('#')) {
        return (base || '') + '/' + path;
      }
      const hashIdx = path.indexOf('#');
      const pure = hashIdx >= 0 ? path.slice(0, hashIdx) : path;
      const hash = hashIdx >= 0 ? path.slice(hashIdx) : '';
      const p = pure.startsWith('/') ? pure : '/' + pure;
      return base + p + hash;
    }

    function openPath(path) {
      if (!path) return;
      const href = absPath(path);
      const title = (sc.titles && (sc.titles[path] || sc.titles[path.split('#')[0]])) || path;
      const ws = api();
      if (ws) ws.openTab(href, title);
      else window.location.href = href;
    }

    function activeDoc() {
      // Prefer current document; if shell, look into active iframe
      let doc = document;
      const wsRoot = document.getElementById('win-tab-panels');
      if (wsRoot) {
        const panel = wsRoot.querySelector('.win-tab-panel.active iframe');
        try {
          if (panel && panel.contentDocument) doc = panel.contentDocument;
        } catch (_) { /* ignore */ }
      }
      return doc;
    }

    function submitVoucher(kind) {
      const now = Date.now();
      if (now - lastSaveAt < 1200) return; // anti double-submit
      const doc = activeDoc();
      const form = doc.getElementById('voucher-form') || doc.querySelector('form.panel');
      if (!form) return;
      lastSaveAt = now;
      let btn = null;
      if (kind === 'draft') btn = form.querySelector('button[name="save_as"][value="draft"]');
      else if (kind === 'operational') btn = form.querySelector('button[name="save_as"][value="operational"]');
      else if (kind === 'locked') btn = form.querySelector('button[name="save_as"][value="locked"]');
      if (btn) btn.click();
      else form.requestSubmit ? form.requestSubmit() : form.submit();
    }

    function ensureCalculator() {
      let overlay = document.getElementById('hesab-calc-overlay');
      if (overlay) return overlay;
      overlay = document.createElement('div');
      overlay.id = 'hesab-calc-overlay';
      overlay.className = 'hesab-calc-overlay';
      overlay.innerHTML = ''
        + '<div class="hesab-calc panel" role="dialog" aria-label="ماشین‌حساب">'
        + '<div class="hd"><strong>ماشین‌حساب</strong><button type="button" class="btn ghost" data-calc-close>×</button></div>'
        + '<input class="hesab-calc-display" id="hesab-calc-display" value="0" readonly>'
        + '<div class="hesab-calc-pad">'
        + '<button type="button" data-k="C">C</button><button type="button" data-k="/">÷</button><button type="button" data-k="*">×</button><button type="button" data-k="-">−</button>'
        + '<button type="button" data-k="7">7</button><button type="button" data-k="8">8</button><button type="button" data-k="9">9</button><button type="button" data-k="+">+</button>'
        + '<button type="button" data-k="4">4</button><button type="button" data-k="5">5</button><button type="button" data-k="6">6</button><button type="button" data-k="=">=</button>'
        + '<button type="button" data-k="1">1</button><button type="button" data-k="2">2</button><button type="button" data-k="3">3</button><button type="button" data-k=".">.</button>'
        + '<button type="button" data-k="0" style="grid-column:span 2">0</button><button type="button" data-k="Backspace">⌫</button><button type="button" data-k="=">OK</button>'
        + '</div></div>';
      document.body.appendChild(overlay);
      const display = overlay.querySelector('#hesab-calc-display');
      let expr = '';
      function render() { display.value = expr || '0'; }
      function apply(k) {
        if (k === 'C') { expr = ''; render(); return; }
        if (k === 'Backspace') { expr = expr.slice(0, -1); render(); return; }
        if (k === '=') {
          try {
            // eslint-disable-next-line no-new-func
            const v = Function('"use strict"; return (' + expr.replace(/[^0-9+\-*/().]/g, '') + ')')();
            expr = String(v);
          } catch (_) { expr = ''; }
          render();
          return;
        }
        expr += k;
        render();
      }
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target.hasAttribute('data-calc-close')) {
          overlay.classList.remove('open');
          return;
        }
        const b = e.target.closest('button[data-k]');
        if (b) apply(b.getAttribute('data-k'));
      });
      return overlay;
    }

    function runAction(item) {
      if (!item || !item.available) return;
      const action = item.action;
      const doc = activeDoc();
      const ws = api();

      if (action === 'nav' && item.path) {
        openPath(item.path);
        return;
      }
      if (action === 'help') {
        openPath('/settings/shortcuts');
        return;
      }
      if (action === 'print') {
        const frame = document.querySelector('#win-tab-panels .win-tab-panel.active iframe');
        if (frame && frame.contentWindow) frame.contentWindow.print();
        else window.print();
        return;
      }
      if (action === 'refresh') {
        if (ws) ws.refreshActive();
        else location.reload();
        return;
      }
      if (action === 'cancel') {
        const overlay = document.getElementById('hesab-calc-overlay');
        if (overlay && overlay.classList.contains('open')) {
          overlay.classList.remove('open');
          return;
        }
        if (ws) ws.closeActive();
        return;
      }
      if (action === 'calculator') {
        ensureCalculator().classList.add('open');
        return;
      }
      if (action === 'save' || action === 'save_draft') {
        submitVoucher('draft');
        return;
      }
      if (action === 'save_operational') {
        submitVoucher('operational');
        return;
      }
      if (action === 'save_locked') {
        submitVoucher('locked');
        return;
      }
      if (action === 'recalc') {
        doc.getElementById('lines')?.dispatchEvent(new Event('input', { bubbles: true }));
        return;
      }
      if (action === 'row_insert') {
        doc.getElementById('add-line')?.click();
        return;
      }
      if (action === 'row_delete') {
        const tr = doc.activeElement && doc.activeElement.closest
          ? doc.activeElement.closest('#lines tbody tr')
          : null;
        if (tr) {
          const rows = doc.querySelectorAll('#lines tbody tr');
          if (rows.length > 2) tr.querySelector('.rm')?.click();
        }
        return;
      }
      if (action === 'search' || action === 'list') {
        const sel = doc.querySelector('.moein-sel, select[name="moein_id[]"], input[type="search"], input[name="q"]');
        if (sel) { sel.focus(); if (sel.showPicker) try { sel.showPicker(); } catch (_) {} }
        else openPath('/vouchers');
        return;
      }
      if (action === 'edit') {
        openPath('/vouchers');
        return;
      }
      if (action === 'undo_form') {
        const form = doc.getElementById('voucher-form');
        if (form && confirm('فرم به مقادیر اولیه برگردد؟')) form.reset();
        return;
      }
    }

    document.addEventListener('keydown', (e) => {
      // Allow typing in shortcut settings capture fields
      if (e.target && e.target.classList && e.target.classList.contains('sc-key')) return;

      const chord = eventChord(e);
      if (!chord) return;
      const item = chordIndex[chord];
      if (!item) return;

      // Don't steal plain typing except function/special keys and modified chords
      const isSpecial = /^(F\d+|Esc|Delete|Insert|Enter|Tab)/.test(chord)
        || chord.includes('Ctrl') || chord.includes('Alt');
      if (!isSpecial) return;

      // Insert/Delete only meaningful on voucher grid
      if ((item.action === 'row_insert' || item.action === 'row_delete')) {
        const doc = activeDoc();
        const inGrid = doc.activeElement && doc.activeElement.closest && doc.activeElement.closest('#lines');
        if (!inGrid && item.action === 'row_delete') return;
      }

      e.preventDefault();
      runAction(item);
    }, true);
  })();

  // Money thousand-separator (display only; strip before submit)
  document.querySelectorAll('input.debit, input.credit, input.num').forEach((input) => {
    if (input.type === 'hidden') return;
    input.addEventListener('blur', () => {
      const raw = (input.value || '').replace(/,/g, '');
      if (raw === '' || Number.isNaN(Number(raw))) return;
      input.value = Number(raw).toLocaleString('en-US');
    });
    input.addEventListener('focus', () => {
      input.value = (input.value || '').replace(/,/g, '');
    });
  });
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('input.debit, input.credit, input.num').forEach((input) => {
        input.value = (input.value || '').replace(/,/g, '');
      });
    });
  });

  // Voucher lines calculator (runs in embed pages too)
  const table = document.getElementById('lines');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  const tpl = document.getElementById('line-tpl');
  const addBtn = document.getElementById('add-line');
  const sumD = document.getElementById('sum-d');
  const sumC = document.getElementById('sum-c');

  function recalc() {
    let d = 0, c = 0;
    tbody.querySelectorAll('tr').forEach((tr) => {
      const dv = parseFloat((tr.querySelector('.debit')?.value || '0').replace(/,/g, '')) || 0;
      const cv = parseFloat((tr.querySelector('.credit')?.value || '0').replace(/,/g, '')) || 0;
      d += dv; c += cv;
    });
    if (sumD) {
      sumD.textContent = d.toLocaleString('en-US');
      sumD.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
    }
    if (sumC) {
      sumC.textContent = c.toLocaleString('en-US');
      sumC.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
    }
  }

  tbody.addEventListener('input', recalc);
  tbody.addEventListener('click', (e) => {
    if (e.target.classList.contains('rm')) {
      const rows = tbody.querySelectorAll('tr');
      if (rows.length > 2) e.target.closest('tr').remove();
      recalc();
    }
  });

  addBtn?.addEventListener('click', () => {
    if (tpl) tbody.appendChild(tpl.content.cloneNode(true));
    recalc();
  });

  recalc();
})();
