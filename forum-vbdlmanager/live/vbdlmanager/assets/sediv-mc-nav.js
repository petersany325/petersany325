/**
 * Message Center sidebar:
 * - "License Request" for all signed-in users (above active license)
 * - "active license sediv" for SeDiv VIP / staff
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

  var ACTIVE_PAGE = '/vbdlmanager/sediv_active_license.php';
  var REQUEST_PAGE = '/vbdlmanager/sediv_license_request.php';

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

  function ensureStyle() {
    if (document.getElementById('vbdl-sediv-mc-nav-style')) return;
    var s = document.createElement('style');
    s.id = 'vbdl-sediv-mc-nav-style';
    s.textContent = ''
      + '.vbdl-sediv-active-folder .folder-link,.vbdl-sediv-req-folder .folder-link{display:flex;align-items:center;gap:8px;font-weight:700}'
      + '.vbdl-sediv-active-folder .folder-link{color:#0369a1!important}'
      + '.vbdl-sediv-req-folder .folder-link{color:#0f766e!important}'
      + '.vbdl-sediv-active-ico,.vbdl-sediv-req-ico{width:16px;height:16px;border-radius:3px;flex:0 0 16px}'
      + '.vbdl-sediv-active-ico{background:linear-gradient(135deg,#0284c7,#0f766e)}'
      + '.vbdl-sediv-req-ico{background:linear-gradient(135deg,#0f766e,#65a30d)}'
      + '.vbdl-sediv-active-label{text-transform:lowercase}';
    document.head.appendChild(s);
  }

  function insertAfter(node, after) {
    if (!after || !after.parentElement) return false;
    if (after.nextSibling) after.parentElement.insertBefore(node, after.nextSibling);
    else after.parentElement.appendChild(node);
    return true;
  }

  function findLicenseTokenNear(el) {
    var root = (el && el.closest)
      ? (el.closest('.b-post, .b-message, .l-row, article, li, .b-content-entry, .js-content-entry, .b-privatemessage, .conversation, .message') || el.parentElement)
      : null;
    var blobs = [];
    if (root && root.textContent) blobs.push(root.textContent);
    if (document.body && document.body.textContent) blobs.push(document.body.textContent.slice(0, 200000));
    for (var i = 0; i < blobs.length; i++) {
      var m = String(blobs[i]).match(/VBDL-LIC-[A-Z0-9]+/i);
      if (m) return m[0].toUpperCase();
    }
    return '';
  }

  function isSrcAttachAnchor(a) {
    if (!a || !a.getAttribute) return false;
    if (a.getAttribute('data-vbdl-srcdl') === '1') return false;
    var href = a.getAttribute('href') || '';
    var text = (a.textContent || '').trim();
    var title = a.getAttribute('title') || '';
    var download = a.getAttribute('download') || '';
    var blob = (href + ' ' + text + ' ' + title + ' ' + download).toLowerCase();
    if (blob.indexOf('.src') === -1) return false;
    if (/pm_lic_download\.php/i.test(href)) return false;
    return /filedata\/fetch|\/attachment\/|content_attach|attachment\.php|filedataid=|attachmentid=/i.test(href)
      || /\.src(\?|#|$)/i.test(href)
      || /\.src$/i.test(text)
      || /\.src$/i.test(download);
  }

  /** Rewrite MC .src attach links to dedicated staff/owner download (bypasses Invalid File Specified). */
  function rewriteSrcDownloads() {
    var anchors = document.querySelectorAll('a[href]');
    for (var i = 0; i < anchors.length; i++) {
      var a = anchors[i];
      if (!isSrcAttachAnchor(a)) continue;
      var token = findLicenseTokenNear(a);
      if (!token) continue;
      var url = '/vbdlmanager/pm_lic_download.php?token=' + encodeURIComponent(token) + '&kind=src';
      a.setAttribute('href', url);
      a.setAttribute('data-vbdl-srcdl', '1');
      a.setAttribute('title', 'Download activated .src (admin-safe)');
      if (!a.getAttribute('download')) {
        var nameMatch = ((a.textContent || '') + ' ' + (a.getAttribute('download') || '')).match(/([^\s\\/]+\.src)\b/i);
        a.setAttribute('download', nameMatch ? nameMatch[1] : 'Source.src');
      }
    }
  }

  function inject(cfg) {
    var after = findInsertPoint();
    if (!after || !after.parentElement) return;

    // 1) License request — all signed-in users
    if (cfg.show_license_request_menu || cfg.logged_in) {
      if (!document.getElementById('vbdl-sediv-lic-req')) {
        var req = document.createElement('div');
        req.className = 'folder-item folder-item-main special-folder vbdl-sediv-req-folder';
        req.id = 'vbdl-sediv-lic-req';
        req.innerHTML = ''
          + '<a class="folder-link vbdl-sediv-req-link" href="' + (cfg.license_request_url || REQUEST_PAGE) + '">'
          + '<span class="vbdl-sediv-req-ico" aria-hidden="true"></span>'
          + '<span class="vbdl-sediv-req-label">License Request</span>'
          + '</a>';
        insertAfter(req, after);
      }
    }

    // 2) Active license — VIP / staff, below request menu
    if (cfg.is_sediv_vip || cfg.can_send || cfg.show_active_license_menu) {
      if (!document.getElementById('vbdl-sediv-active-lic')) {
        var anchor = document.getElementById('vbdl-sediv-lic-req') || after;
        var li = document.createElement('div');
        li.className = 'folder-item folder-item-main special-folder vbdl-sediv-active-folder';
        li.id = 'vbdl-sediv-active-lic';
        li.innerHTML = ''
          + '<a class="folder-link vbdl-sediv-active-link" href="' + (cfg.active_license_url || ACTIVE_PAGE) + '">'
          + '<span class="vbdl-sediv-active-ico" aria-hidden="true"></span>'
          + '<span class="vbdl-sediv-active-label">active license sediv</span>'
          + '</a>';
        insertAfter(li, anchor);
      }
    }
  }

  function boot() {
    ensureStyle();
    // Always rewrite .src attach links when a VBDL-LIC token is visible (admin ACL bypass).
    rewriteSrcDownloads();
    setInterval(rewriteSrcDownloads, 1200);
    try {
      var moSrc = new MutationObserver(function () { rewriteSrcDownloads(); });
      moSrc.observe(document.documentElement, { childList: true, subtree: true });
    } catch (e0) {}

    // Merge configs from both endpoints so request menu works for non-VIP.
    Promise.all([
      fetch('/vbdlmanager/pm_lic_request.php?do=config', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return null; }),
      fetch('/vbdlmanager/pm_lic_email.php?do=config', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return null; })
    ]).then(function (arr) {
      var reqCfg = arr[0] || {};
      var licCfg = arr[1] || {};
      if ((!reqCfg || !reqCfg.ok) && (!licCfg || !licCfg.ok)) return;
      var cfg = {
        ok: true,
        logged_in: !!(reqCfg.logged_in || licCfg.ok),
        show_license_request_menu: !!(reqCfg.show_license_request_menu || reqCfg.ok),
        license_request_url: reqCfg.license_request_url || REQUEST_PAGE,
        is_sediv_vip: !!(licCfg.is_sediv_vip),
        can_send: !!(licCfg.can_send),
        show_active_license_menu: !!(licCfg.show_active_license_menu),
        active_license_url: licCfg.active_license_url || ACTIVE_PAGE
      };
      inject(cfg);
      setInterval(function () { inject(cfg); }, 1500);
      try {
        var mo = new MutationObserver(function () { inject(cfg); });
        mo.observe(document.documentElement, { childList: true, subtree: true });
      } catch (e) {}
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
