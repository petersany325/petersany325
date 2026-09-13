(function () {
  function closest(el, sel) {
    while (el && el.nodeType === 1) {
      if (el.matches(sel)) return el;
      el = el.parentElement;
    }
    return null;
  }

  function closeAll(root, except) {
    root.querySelectorAll('[data-mega-item].is-open').forEach(function (item) {
      if (except && item === except) return;
      clearTimeout(item._megaCloseTimer);
      item.classList.remove('is-open');
      var t = item.querySelector('[data-mega-trigger]');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  }

  function openItem(root, item, trigger) {
    clearTimeout(item._megaCloseTimer);
    closeAll(root, item);
    item.classList.add('is-open');
    if (trigger) trigger.setAttribute('aria-expanded', 'true');
  }

  function scheduleClose(item, trigger, delay) {
    clearTimeout(item._megaCloseTimer);
    item._megaCloseTimer = setTimeout(function () {
      item.classList.remove('is-open');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }, delay || 220);
  }

  function initNav(root) {
    if (!root || root._megaInit) return;
    root._megaInit = true;
    var isTouch = matchMedia('(hover: none), (max-width: 960px)').matches;

    root.querySelectorAll('[data-mega-item]').forEach(function (item) {
      var trigger = item.querySelector('[data-mega-trigger]');
      var panel = item.querySelector('[data-mega-panel]');
      if (!trigger || !panel) return;

      var mode = (item.getAttribute('data-open-mode') || 'hover').toLowerCase();

      if (!isTouch && mode !== 'click') {
        item.addEventListener('mouseenter', function () {
          openItem(root, item, trigger);
        });
        item.addEventListener('mouseleave', function () {
          scheduleClose(item, trigger, 260);
        });
        panel.addEventListener('mouseenter', function () {
          clearTimeout(item._megaCloseTimer);
          openItem(root, item, trigger);
        });
        panel.addEventListener('mouseleave', function () {
          scheduleClose(item, trigger, 260);
        });
      }

      trigger.addEventListener('click', function (e) {
        if (!isTouch && mode !== 'click' && !item.classList.contains('has-mega')) return;
        if (!panel) return;
        if (isTouch || window.innerWidth <= 960 || mode === 'click') {
          e.preventDefault();
          var open = item.classList.contains('is-open');
          closeAll(root);
          if (!open) openItem(root, item, trigger);
        }
      });
    });

    root.querySelectorAll('[data-mega-tabs]').forEach(function (tabs) {
      tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-tab');
          tabs.querySelectorAll('[data-tab]').forEach(function (b) {
            b.classList.toggle('active', b === btn);
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
          });
          tabs.querySelectorAll('[data-tab-panel]').forEach(function (p) {
            p.classList.toggle('active', p.getAttribute('data-tab-panel') === id);
          });
        });
      });
    });

    document.addEventListener('click', function (e) {
      if (!closest(e.target, '[data-mega-nav]')) closeAll(root);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAll(root);
    });
  }

  /**
   * Glass/sticky headers with backdrop-filter create a containing block.
   * Fixed mobile drawers inside the header become clipped/invisible.
   * Mount the drawer on <body> for mobile; restore into the header slot on desktop.
   */
  function syncNavMount() {
    var wrap = document.getElementById('headerNavWrap');
    var slot = document.getElementById('headerNavSlot');
    var backdrop = document.getElementById('navBackdrop');
    if (!wrap) return;
    var mobile = window.innerWidth <= 960;
    if (mobile) {
      if (wrap.parentElement !== document.body) {
        document.body.appendChild(wrap);
      }
      if (backdrop && backdrop.parentElement !== document.body) {
        document.body.appendChild(backdrop);
      }
    } else if (slot && wrap.parentElement !== slot) {
      slot.appendChild(wrap);
      if (backdrop && document.body.contains(backdrop) && backdrop.previousElementSibling !== wrap) {
        // keep backdrop near wrap for consistency; desktop hides it via CSS
        document.body.appendChild(backdrop);
      }
      document.body.classList.remove('nav-open');
    }
  }

  function boot() {
    document.querySelectorAll('[data-mega-nav]').forEach(initNav);

    var toggle = document.getElementById('navToggle');
    var closeBtn = document.getElementById('navCloseBtn');
    var backdrop = document.getElementById('navBackdrop');

    function closeNav() {
      document.body.classList.remove('nav-open');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
      if (backdrop) {
        backdrop.hidden = true;
        backdrop.setAttribute('hidden', '');
      }
    }
    function openNav() {
      syncNavMount();
      document.body.classList.add('nav-open');
      if (toggle) toggle.setAttribute('aria-expanded', 'true');
      if (backdrop) {
        backdrop.hidden = false;
        backdrop.removeAttribute('hidden');
      }
    }
    if (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        document.body.classList.contains('nav-open') ? closeNav() : openNav();
      });
    }
    if (closeBtn) closeBtn.addEventListener('click', closeNav);
    if (backdrop) backdrop.addEventListener('click', closeNav);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNav();
    });

    syncNavMount();
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        syncNavMount();
        if (window.innerWidth > 960) closeNav();
      }, 120);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
