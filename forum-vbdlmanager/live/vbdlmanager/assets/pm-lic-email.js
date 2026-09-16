/**
 * Message Center: "Send to email" next to .lic attachments (staff only).
 * Username in the outgoing email is resolved server-side from the attachment author.
 */
(function () {
  if (window.__vbdlPmLicEmailInit) return;
  window.__vbdlPmLicEmailInit = true;

  function isMessageCenterPage() {
    var path = String(location.pathname || '').toLowerCase();
    var href = String(location.href || '').toLowerCase();
    var needles = [
      'messagecenter', 'message-center', 'privatemessage', 'private-message',
      'private_message', 'pmchat', 'vbmessenger', '/messenger', '/pm/',
      'contenttype=privatemessage', 'contenttypeid=22'
    ];
    for (var i = 0; i < needles.length; i++) {
      if (path.indexOf(needles[i]) !== -1 || href.indexOf(needles[i]) !== -1) return true;
    }
    return !!document.querySelector('.b-messagecenter, #messagecenter, [data-ui="messagecenter"], .b-pmchat, #pmchat, .privatemessage-compose');
  }

  if (!isMessageCenterPage()) return;

  var cfg = { can_send: 0, default_to: '', default_subject_prefix: '[License]' };
  var modalEl = null;

  function el(tag, attrs, html) {
    var n = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (html != null) n.innerHTML = html;
    return n;
  }

  function parseIdsFromHref(href) {
    var out = { filedataid: 0, attachmentid: 0, nodeid: 0 };
    if (!href) return out;
    var h = String(href);
    var m;
    m = h.match(/filedataid[=/](\d+)/i);
    if (m) out.filedataid = parseInt(m[1], 10);
    m = h.match(/attachmentid[=/](\d+)/i);
    if (m) out.attachmentid = parseInt(m[1], 10);
    m = h.match(/\/attachment\/(\d+)/i);
    if (m) out.attachmentid = parseInt(m[1], 10);
    m = h.match(/\/filedata\/fetch\/(\d+)/i);
    if (m) out.filedataid = parseInt(m[1], 10);
    // vB5 Message Center: /filedata/fetch?id=NODE_OR_FILEDATA
    m = h.match(/filedata\/fetch\?[^#]*\bid=(\d+)/i) || h.match(/[?&]id=(\d+)/i);
    if (m) {
      var id = parseInt(m[1], 10);
      // Pass the same id in all slots — server tries filedataid/nodeid/attach.nodeid.
      if (!out.filedataid) out.filedataid = id;
      if (!out.attachmentid) out.attachmentid = id;
      if (!out.nodeid) out.nodeid = id;
    }
    m = h.match(/nodeid[=/](\d+)/i);
    if (m) out.nodeid = parseInt(m[1], 10);
    return out;
  }

  function cleanFilename(text, href) {
    var raw = String(text || '').replace(/\s+/g, ' ').trim();
    var m = raw.match(/([^\s\\/]+\.lic)\b/i);
    if (m) return m[1];
    m = String(href || '').match(/([^\/?]+\.lic)/i);
    if (m) return m[1];
    return raw || 'license.lic';
  }

  function isLicAnchor(a) {
    if (!a || !a.getAttribute) return false;
    var href = a.getAttribute('href') || '';
    var text = (a.textContent || '').trim();
    var title = a.getAttribute('title') || '';
    var download = a.getAttribute('download') || '';
    var blob = (href + ' ' + text + ' ' + title + ' ' + download).toLowerCase();
    if (blob.indexOf('.lic') === -1) return false;
    if (/filedata\/fetch|\/attachment\/|content_attach|attachment\.php|filedataid=|attachmentid=/i.test(href)) return true;
    if (/\.lic(\?|#|$)/i.test(href) || /\.lic$/i.test(text) || /\.lic$/i.test(download)) return true;
    return false;
  }

  function findLicTargets() {
    var found = [];
    var seen = {};
    var anchors = document.querySelectorAll('a[href]');
    for (var i = 0; i < anchors.length; i++) {
      var a = anchors[i];
      if (!isLicAnchor(a)) continue;
      var key = (a.getAttribute('href') || '') + '|' + (a.textContent || '');
      if (seen[key]) continue;
      seen[key] = 1;
      found.push(a);
    }
    // Also match attachment filename text that is not a pure .lic href
    var nodes = document.querySelectorAll('.attachments a, .b-media a, .attached-files a, .b-post-attachments a, .js-attachments a, a.filename, .filename a');
    for (var j = 0; j < nodes.length; j++) {
      var n = nodes[j];
      var t = (n.textContent || '').trim().toLowerCase();
      if (t.indexOf('.lic') === -1) continue;
      var k2 = (n.getAttribute('href') || '') + '|' + t;
      if (seen[k2]) continue;
      seen[k2] = 1;
      found.push(n);
    }
    return found;
  }

  function guessCustomerNear(a) {
    // UI hint only — server re-resolves and overwrites
    var root = a.closest('.b-post, .b-message, .l-row, article, li, .b-content-entry, .js-content-entry, .b-privatemessage') || a.parentElement;
    if (!root) return '';
    var nameEl = root.querySelector('.author, .b-post__author, .username, a.username, .b-userinfo__name, .js-userinfo__name, .b-meta__username');
    if (nameEl) return (nameEl.textContent || '').trim();
    return '';
  }

  function ensureModal() {
    if (modalEl) return modalEl;
    modalEl = el('div', { id: 'vbdl-pmlic-modal', class: 'vbdl-pmlic-modal', hidden: 'hidden' });
    modalEl.innerHTML = ''
      + '<div class="vbdl-pmlic-dialog" role="dialog" aria-modal="true">'
      + '  <div class="vbdl-pmlic-head">Send license (.lic) to email</div>'
      + '  <div class="vbdl-pmlic-sub">Customer username is filled automatically from the ticket and verified on the server.</div>'
      + '  <label>Customer username<input type="text" id="vbdl-pmlic-user" readonly /></label>'
      + '  <label>To email<input type="email" id="vbdl-pmlic-to" required /></label>'
      + '  <label>Subject<input type="text" id="vbdl-pmlic-subject" required /></label>'
      + '  <label>Note (optional)<textarea id="vbdl-pmlic-note" rows="2" placeholder="optional note for license manager"></textarea></label>'
      + '  <div class="vbdl-pmlic-file" id="vbdl-pmlic-file"></div>'
      + '  <div class="vbdl-pmlic-msg" id="vbdl-pmlic-msg"></div>'
      + '  <div class="vbdl-pmlic-actions">'
      + '    <button type="button" class="vbdl-pmlic-send" id="vbdl-pmlic-send">Send email</button>'
      + '    <button type="button" class="vbdl-pmlic-cancel" id="vbdl-pmlic-cancel">Cancel</button>'
      + '  </div>'
      + '</div>';
    document.body.appendChild(modalEl);
    modalEl.querySelector('#vbdl-pmlic-cancel').addEventListener('click', closeModal);
    modalEl.addEventListener('click', function (e) {
      if (e.target === modalEl) closeModal();
    });
    return modalEl;
  }

  function closeModal() {
    if (!modalEl) return;
    modalEl.setAttribute('hidden', 'hidden');
    modalEl._payload = null;
  }

  function applyResolvedUser(m, username) {
    var userInput = m.querySelector('#vbdl-pmlic-user');
    var subjInput = m.querySelector('#vbdl-pmlic-subject');
    userInput.value = username;
    var prefix = cfg.default_subject_prefix || '[License]';
    var filename = (m._payload && m._payload.filename) ? m._payload.filename : 'license.lic';
    subjInput.value = prefix + ' ' + username + ' — ' + filename;
    if (m._payload) m._payload.customerHint = username;
  }

  function resolveUsername(payload, m) {
    var q = new URLSearchParams();
    q.set('do', 'resolve');
    q.set('filedataid', String(payload.filedataid || 0));
    q.set('attachmentid', String(payload.attachmentid || 0));
    q.set('nodeid', String(payload.nodeid || 0));
    fetch('/vbdlmanager/pm_lic_email.php?' + q.toString(), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok || !data.customer_username) return;
        applyResolvedUser(m, data.customer_username);
      })
      .catch(function () {});
  }

  function openModal(payload) {
    var m = ensureModal();
    m._payload = payload;
    m.querySelector('#vbdl-pmlic-user').value = payload.customerHint || 'Resolving from database…';
    m.querySelector('#vbdl-pmlic-to').value = cfg.default_to || '';
    var subj = (cfg.default_subject_prefix || '[License]');
    if (payload.customerHint) subj += ' ' + payload.customerHint;
    if (payload.filename) subj += ' — ' + payload.filename;
    m.querySelector('#vbdl-pmlic-subject').value = subj;
    m.querySelector('#vbdl-pmlic-note').value = '';
    m.querySelector('#vbdl-pmlic-file').textContent = 'File: ' + (payload.filename || 'license.lic');
    m.querySelector('#vbdl-pmlic-msg').textContent = '';
    m.removeAttribute('hidden');
    resolveUsername(payload, m);
  }

  function sendNow() {
    var m = ensureModal();
    var payload = m._payload || {};
    var msg = m.querySelector('#vbdl-pmlic-msg');
    var btn = m.querySelector('#vbdl-pmlic-send');
    var to = m.querySelector('#vbdl-pmlic-to').value.trim();
    var subject = m.querySelector('#vbdl-pmlic-subject').value.trim();
    var note = m.querySelector('#vbdl-pmlic-note').value.trim();
    if (!to) { msg.textContent = 'Enter destination email'; return; }
    if (!subject) { msg.textContent = 'Enter subject'; return; }
    btn.disabled = true;
    msg.textContent = 'Sending…';
    var fd = new FormData();
    fd.append('do', 'send');
    fd.append('to', to);
    fd.append('subject', subject);
    fd.append('note', note);
    fd.append('filedataid', String(payload.filedataid || 0));
    fd.append('attachmentid', String(payload.attachmentid || 0));
    fd.append('nodeid', String(payload.nodeid || 0));
    fetch('/vbdlmanager/pm_lic_email.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        btn.disabled = false;
        if (!data.ok) {
          msg.textContent = data.error || 'Send failed';
          return;
        }
        msg.textContent = 'Sent to ' + data.sent_to + ' for user ' + data.customer_username;
        setTimeout(closeModal, 1200);
      })
      .catch(function () {
        btn.disabled = false;
        msg.textContent = 'Network error';
      });
  }

  function decorate() {
    if (!cfg.can_send) return;
    var targets = findLicTargets();
    for (var i = 0; i < targets.length; i++) {
      var a = targets[i];
      if (a.getAttribute('data-vbdl-pmlic') === '1') continue;
      var parent = a.parentElement;
      if (parent && parent.querySelector('.vbdl-pmlic-btn')) {
        a.setAttribute('data-vbdl-pmlic', '1');
        continue;
      }
      if (!parent) continue;

      var ids = parseIdsFromHref(a.getAttribute('href') || '');
      // Also pass surrounding message node from URL when present (/messagecenter/view/12345)
      if (!ids.nodeid) {
        var pathNode = String(location.pathname || '').match(/messagecenter\/view\/(\d+)/i);
        if (pathNode) ids.nodeid = parseInt(pathNode[1], 10);
      }
      var filename = cleanFilename(a.textContent || '', a.getAttribute('href') || '');
      var btn = el('button', {
        type: 'button',
        class: 'vbdl-pmlic-btn',
        title: 'Email this .lic to the license activator'
      }, 'Send to email');
      (function (idsRef, filenameRef, anchorRef) {
        btn.addEventListener('click', function (ev) {
          ev.preventDefault();
          ev.stopPropagation();
          openModal({
            filedataid: idsRef.filedataid,
            attachmentid: idsRef.attachmentid,
            nodeid: idsRef.nodeid,
            filename: filenameRef,
            customerHint: guessCustomerNear(anchorRef)
          });
        });
      })(ids, filename, a);
      a.setAttribute('data-vbdl-pmlic', '1');
      if (a.nextSibling) parent.insertBefore(btn, a.nextSibling);
      else parent.appendChild(btn);
    }
  }

  function boot() {
    ensureModal();
    document.getElementById('vbdl-pmlic-send').addEventListener('click', sendNow);
    fetch('/vbdlmanager/pm_lic_email.php?do=config', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        cfg = data;
        if (!cfg.can_send) return;
        decorate();
        setInterval(decorate, 1200);
        try {
          var mo = new MutationObserver(function () { decorate(); });
          mo.observe(document.documentElement, { childList: true, subtree: true });
        } catch (e) {}
      })
      .catch(function () {});
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
