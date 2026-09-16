/**
 * Message Center sidebar: "active license sediv" for SeDiv VIP (and staff).
 * License send UI no longer decorates normal tickets — only this menu entry.
 */
(function () {
  if (window.__vbdlSedivMcNavInit) return;
  window.__vbdlSedivMcNavInit = true;

  function isMc() {
    var path = String(location.pathname || '').toLowerCase();
    var href = String(location.href || '').toLowerCase();
    return path.indexOf('messagecenter') !== -1
      || href.indexOf('messagecenter') !== -1
      || !!document.querySelector('.folder-item, .folder-link, .compose-btn-container');
  }
  if (!isMc()) return;

  var PAGE = '/vbdlmanager/sediv_active_license.php';

  function findInsertPoint() {
    var sent = document.querySelector('.folder-item.sent-items')
      || document.querySelector('a.folder-link[href*="sentitems"]');
    if (sent) {
      return sent.classList && sent.classList.contains('folder-item') ? sent : sent.closest('.folder-item');
    }
    var trash = document.querySelector('.folder-item.trash');
    if (trash) return trash;
    var compose = document.querySelector('.compose-btn-container');
    return compose || null;
  }

  function inject(cfg) {
    if (!(cfg.is_sediv_vip || cfg.can_send)) return;
    if (document.getElementById('vbdl-sediv-active-lic')) return;
    var after = findInsertPoint();
    if (!after || !after.parentElement) return;

    var li = document.createElement('div');
    li.className = 'folder-item folder-item-main special-folder vbdl-sediv-active-folder';
    li.id = 'vbdl-sediv-active-lic';
    li.innerHTML = ''
      + '<a class="folder-link vbdl-sediv-active-link" href="' + PAGE + '">'
      + '<span class="vbdl-sediv-active-ico" aria-hidden="true"></span>'
      + '<span class="vbdl-sediv-active-label">active license sediv</span>'
      + '</a>';
    if (after.nextSibling) after.parentElement.insertBefore(li, after.nextSibling);
    else after.parentElement.appendChild(li);
  }

  function ensureStyle() {
    if (document.getElementById('vbdl-sediv-mc-nav-style')) return;
    var s = document.createElement('style');
    s.id = 'vbdl-sediv-mc-nav-style';
    s.textContent = ''
      + '.vbdl-sediv-active-folder .folder-link{display:flex;align-items:center;gap:8px;font-weight:700;color:#0369a1!important}'
      + '.vbdl-sediv-active-ico{width:16px;height:16px;border-radius:3px;background:linear-gradient(135deg,#0284c7,#0f766e);flex:0 0 16px}'
      + '.vbdl-sediv-active-label{text-transform:lowercase}';
    document.head.appendChild(s);
  }

  function boot() {
    ensureStyle();
    fetch('/vbdlmanager/pm_lic_email.php?do=config', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (cfg) {
        if (!cfg || !cfg.ok) return;
        inject(cfg);
        setInterval(function () { inject(cfg); }, 1500);
        try {
          var mo = new MutationObserver(function () { inject(cfg); });
          mo.observe(document.documentElement, { childList: true, subtree: true });
        } catch (e) {}
      })
      .catch(function () {});
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
