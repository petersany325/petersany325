(function () {
  var cfg = window.WEBAPP || {};

  function $(id) { return document.getElementById(id); }

  function isIos() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function isAndroid() {
    return /Android/i.test(navigator.userAgent || '');
  }

  function isMobile() {
    return isIos() || isAndroid() || /Mobile|webOS|BlackBerry/i.test(navigator.userAgent || '') ||
      (window.matchMedia && window.matchMedia('(max-width: 860px)').matches);
  }

  function isStandalone() {
    if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return true;
    if (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches) return true;
    if (window.navigator.standalone === true) return true;
    if (document.referrer && document.referrer.indexOf('android-app://') === 0) return true;
    return false;
  }

  if (cfg.enabled !== false && 'serviceWorker' in navigator && cfg.swUrl) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(cfg.swUrl, { scope: '/' }).catch(function () {});
    });
  }

  var deferredPrompt = null;
  var bannerShown = false;
  var banner = $('waInstall') || document.querySelector('.site-wa-install#waInstall') || document.querySelector('.site-wa-install');
  var installedBox = $('waInstalled');
  var titleEl = $('waInstallTitle');
  var hintEl = $('waInstallHint');
  var btn = $('waInstallBtn') || document.querySelector('[data-wa-install]');
  var dismiss = $('waInstallDismiss') || document.querySelector('[data-wa-dismiss]');

  function show(el) {
    if (!el) return;
    el.hidden = false;
    el.removeAttribute('hidden');
    el.style.display = 'flex';
  }
  function hide(el) {
    if (!el) return;
    el.hidden = true;
    el.setAttribute('hidden', '');
    el.style.display = 'none';
  }

  function markInstalled() {
    try { localStorage.setItem('wa_installed', '1'); } catch (e) {}
    hide(banner);
    if (cfg.hideWhenInstalled !== false) show(installedBox);
  }

  function prepareBanner(mode) {
    if (titleEl) titleEl.textContent = cfg.bannerText || 'نصب اپ سرزمین هارد';
    if (mode === 'ios') {
      if (hintEl) hintEl.textContent = cfg.installHelpIos || 'Safari ← Share ← Add to Home Screen';
      if (btn) btn.textContent = 'راهنمای نصب';
    } else if (mode === 'ready') {
      if (hintEl) hintEl.textContent = cfg.readyText || 'آماده نصب روی گوشی';
      if (btn) btn.textContent = 'نصب';
    } else {
      if (hintEl) hintEl.textContent = cfg.installHelpAndroid || 'از منوی مرورگر گزینه Install / افزودن به صفحه اصلی';
      if (btn) btn.textContent = deferredPrompt ? 'نصب' : 'راهنمای نصب';
    }
  }

  function showInstallBanner(mode) {
    if (!banner || bannerShown || isStandalone()) return;
    if (cfg.installOnlyMobile && !isMobile()) return;
    prepareBanner(mode || (isIos() ? 'ios' : (deferredPrompt ? 'ready' : 'manual')));
    show(banner);
    bannerShown = true;
  }

  function setupSmartInstall() {
    if (!banner) return;

    // Only hide when actually running as installed PWA
    if (isStandalone()) {
      hide(banner);
      if (cfg.hideWhenInstalled !== false) show(installedBox);
      return;
    }

    // Clear stale flag from older builds that blocked the banner in browser
    try {
      if (localStorage.getItem('wa_installed') === '1' && !isStandalone()) {
        localStorage.removeItem('wa_installed');
      }
    } catch (e) {}

    if (cfg.installOnlyMobile && !isMobile()) {
      hide(banner);
      return;
    }

    if (!cfg.smartInstall) {
      showInstallBanner(isIos() ? 'ios' : 'manual');
      return;
    }

    hide(banner);

    if (isIos()) {
      setTimeout(function () { showInstallBanner('ios'); }, 500);
      return;
    }

    // Android / desktop Chromium: wait briefly for beforeinstallprompt, then fallback
    setTimeout(function () {
      if (bannerShown || isStandalone()) return;
      showInstallBanner(deferredPrompt ? 'ready' : 'manual');
    }, 800);
  }

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    if (isStandalone()) return;
    if (cfg.installOnlyMobile && !isMobile()) return;
    showInstallBanner('ready');
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    markInstalled();
  });

  try {
    var mq = window.matchMedia('(display-mode: standalone)');
    if (mq && mq.addEventListener) {
      mq.addEventListener('change', function (ev) {
        if (ev.matches) markInstalled();
      });
    }
  } catch (e) {}

  if (btn) {
    btn.addEventListener('click', function () {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (res) {
          deferredPrompt = null;
          if (res && res.outcome === 'accepted') markInstalled();
        }).catch(function () { deferredPrompt = null; });
        return;
      }
      if (isIos()) {
        alert(cfg.installHelpIos || 'در Safari روی Share بزنید و Add to Home Screen را انتخاب کنید.');
        return;
      }
      alert(cfg.installHelpAndroid || 'از منوی مرورگر گزینه Install app / افزودن به صفحه اصلی را بزنید.');
    });
  }

  if (dismiss && banner) {
    dismiss.addEventListener('click', function () {
      hide(banner);
      bannerShown = true;
      if (cfg.dismissUrl) {
        fetch(cfg.dismissUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': cfg.csrf || '', Accept: 'application/json' },
          credentials: 'same-origin'
        }).catch(function () {});
      }
    });
  }

  setupSmartInstall();

  if (navigator.getInstalledRelatedApps) {
    navigator.getInstalledRelatedApps().then(function (apps) {
      if (apps && apps.length && isStandalone()) markInstalled();
    }).catch(function () {});
  }

  window.addEventListener('offline', function () {
    if (!cfg.offlineMessage) return;
    var t = document.createElement('div');
    t.textContent = cfg.offlineMessage;
    t.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1a1d23;color:#fff;padding:.55rem 1rem;border-radius:999px;font:700 .78rem Vazirmatn,Tahoma,sans-serif;z-index:99;max-width:90%;text-align:center';
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2800);
  });

  /* Dedicated WebApp drawer menu */
  (function setupDrawer() {
    var drawer = $('waDrawer');
    var backdrop = $('waDrawerBackdrop');
    var openBtn = $('waMenuOpen');
    var closeBtn = $('waMenuClose');
    if (!drawer || !openBtn) return;

    function openDrawer() {
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      openBtn.setAttribute('aria-expanded', 'true');
      if (backdrop) {
        backdrop.hidden = false;
        backdrop.classList.add('is-open');
      }
      document.body.classList.add('wa-drawer-open');
    }

    function closeDrawer() {
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      openBtn.setAttribute('aria-expanded', 'false');
      if (backdrop) {
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
      }
      document.body.classList.remove('wa-drawer-open');
    }

    openBtn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();
    });
    drawer.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { closeDrawer(); });
    });

    drawer.querySelectorAll('[data-wa-sub-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var group = btn.closest('[data-wa-sub]');
        if (!group) return;
        var panel = group.querySelector('.wa-drawer-sub');
        var open = group.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (panel) panel.hidden = !open;
      });
    });
  })();
})();
