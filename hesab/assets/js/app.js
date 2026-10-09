(function () {
  // Windows menubar: click to pin open; hover also works via CSS
  const menubar = document.getElementById('win-menubar');
  if (menubar) {
    menubar.querySelectorAll('.win-menu-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const menu = btn.closest('.win-menu');
        const wasOpen = menu.classList.contains('open');
        menubar.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
        if (!wasOpen) menu.classList.add('open');
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

    openTab(cfg.initialUrl, cfg.initialTitle, { eager: true, seedTitle: cfg.initialTitle });
  }

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
