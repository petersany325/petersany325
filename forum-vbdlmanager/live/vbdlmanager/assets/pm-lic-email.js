/**
 * Message Center: SeDiv VIP license → sedivlic@list.ru, return .src into same ticket.
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

  var cfg = {
    can_send: 0,
    vip_only: 1,
    locked_to: 'sedivlic@list.ru',
    locked_subject: 'Active SeDiv 2026',
    return_ext: 'src'
  };
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
    m = h.match(/filedata\/fetch\?[^#]*\bid=(\d+)/i) || h.match(/[?&]id=(\d+)/i);
    if (m) {
      var id = parseInt(m[1], 10);
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
      + '  <div class="vbdl-pmlic-head">Send SeDiv VIP license</div>'
      + '  <div class="vbdl-pmlic-sub">For SeDiv VIP customers only. Email is locked to the license inbox. Return file must be <code>.src</code> and posts back into this ticket.</div>'
      + '  <label>Customer username<input type="text" id="vbdl-pmlic-user" readonly /></label>'
      + '  <label>Customer email<input type="text" id="vbdl-pmlic-email" readonly /></label>'
      + '  <label>To email<input type="email" id="vbdl-pmlic-to" readonly /></label>'
      + '  <label>Subject<input type="text" id="vbdl-pmlic-subject" readonly /></label>'
      + '  <label>Note (optional)<textarea id="vbdl-pmlic-note" rows="2" placeholder="optional note"></textarea></label>'
      + '  <div class="vbdl-pmlic-file" id="vbdl-pmlic-file"></div>'
      + '  <div class="vbdl-pmlic-msg" id="vbdl-pmlic-msg"></div>'
      + '  <div class="vbdl-pmlic-actions">'
      + '    <button type="button" class="vbdl-pmlic-send" id="vbdl-pmlic-send">Send to license email</button>'
      + '    <button type="button" class="vbdl-pmlic-cancel" id="vbdl-pmlic-cancel">Cancel</button>'
      + '  </div>'
      + '  <hr class="vbdl-pmlic-hr" />'
      + '  <div class="vbdl-pmlic-return">'
      + '    <div class="vbdl-pmlic-return-h">Return activated .src into this ticket</div>'
      + '    <label>Tracking token<input type="text" id="vbdl-pmlic-token" placeholder="VBDL-LIC-..." /></label>'
      + '    <label>Activated .src file<input type="file" id="vbdl-pmlic-src" accept=".src,application/octet-stream" /></label>'
      + '    <button type="button" class="vbdl-pmlic-return-btn" id="vbdl-pmlic-return">Upload .src to ticket</button>'
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

  function applyResolved(m, data) {
    m.querySelector('#vbdl-pmlic-user').value = data.customer_username || '';
    m.querySelector('#vbdl-pmlic-email').value = data.customer_email || '(not set)';
    m.querySelector('#vbdl-pmlic-to').value = data.locked_to || cfg.locked_to;
    m.querySelector('#vbdl-pmlic-subject').value = data.locked_subject || cfg.locked_subject;
    if (m._payload) {
      m._payload.customerHint = data.customer_username || '';
      m._payload.customerEmail = data.customer_email || '';
      m._payload.isVip = !!data.is_vip;
      m._payload.canUse = !!data.can_use_sediv_flow;
      if (data.message_nodeid) m._payload.nodeid = data.message_nodeid;
      if (data.filedataid) m._payload.filedataid = data.filedataid;
    }
    var sendBtn = m.querySelector('#vbdl-pmlic-send');
    var msg = m.querySelector('#vbdl-pmlic-msg');
    if (!data.can_use_sediv_flow) {
      sendBtn.disabled = true;
      msg.textContent = 'This flow is only for SeDiv VIP customers.';
    } else {
      sendBtn.disabled = false;
      msg.textContent = '';
    }
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
        if (!data || !data.ok) {
          m.querySelector('#vbdl-pmlic-msg').textContent = (data && data.error) || 'Resolve failed';
          return;
        }
        applyResolved(m, data);
      })
      .catch(function () {});
  }

  function openModal(payload) {
    var m = ensureModal();
    m._payload = payload;
    m.querySelector('#vbdl-pmlic-user').value = payload.customerHint || 'Resolving…';
    m.querySelector('#vbdl-pmlic-email').value = 'Resolving…';
    m.querySelector('#vbdl-pmlic-to').value = cfg.locked_to || '';
    m.querySelector('#vbdl-pmlic-subject').value = cfg.locked_subject || 'Active SeDiv 2026';
    m.querySelector('#vbdl-pmlic-note').value = '';
    m.querySelector('#vbdl-pmlic-token').value = '';
    m.querySelector('#vbdl-pmlic-src').value = '';
    m.querySelector('#vbdl-pmlic-file').textContent = 'File: ' + (payload.filename || 'license.lic');
    m.querySelector('#vbdl-pmlic-msg').textContent = '';
    m.querySelector('#vbdl-pmlic-send').disabled = false;
    m.removeAttribute('hidden');
    resolveUsername(payload, m);
  }

  function sendNow() {
    var m = ensureModal();
    var payload = m._payload || {};
    var msg = m.querySelector('#vbdl-pmlic-msg');
    var btn = m.querySelector('#vbdl-pmlic-send');
    if (payload.canUse === false) {
      msg.textContent = 'Only SeDiv VIP customers can use this flow';
      return;
    }
    btn.disabled = true;
    msg.textContent = 'Sending…';
    var fd = new FormData();
    fd.append('do', 'send');
    fd.append('note', m.querySelector('#vbdl-pmlic-note').value.trim());
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
        msg.textContent = 'Sent to ' + data.sent_to + ' — token ' + data.token;
        if (data.token) m.querySelector('#vbdl-pmlic-token').value = data.token;
        setTimeout(closeModal, 1800);
      })
      .catch(function () {
        btn.disabled = false;
        msg.textContent = 'Network error';
      });
  }

  function returnSrc() {
    var m = ensureModal();
    var msg = m.querySelector('#vbdl-pmlic-msg');
    var token = m.querySelector('#vbdl-pmlic-token').value.trim();
    var fileInput = m.querySelector('#vbdl-pmlic-src');
    if (!token) { msg.textContent = 'Enter tracking token from the sent email'; return; }
    if (!fileInput.files || !fileInput.files[0]) { msg.textContent = 'Choose the returned .src file'; return; }
    var fd = new FormData();
    fd.append('do', 'return_upload');
    fd.append('token', token);
    fd.append('srcfile', fileInput.files[0]);
    msg.textContent = 'Uploading .src into ticket…';
    fetch('/vbdlmanager/pm_lic_email.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          msg.textContent = data.error || 'Return upload failed';
          return;
        }
        msg.textContent = 'Posted .src into ticket. Refresh to see it.';
        setTimeout(function () { location.reload(); }, 1200);
      })
      .catch(function () { msg.textContent = 'Network error'; });
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
      if (!ids.nodeid) {
        var pathNode = String(location.pathname || '').match(/messagecenter\/view\/(\d+)/i);
        if (pathNode) ids.nodeid = parseInt(pathNode[1], 10);
      }
      var filename = cleanFilename(a.textContent || '', a.getAttribute('href') || '');
      var btn = el('button', {
        type: 'button',
        class: 'vbdl-pmlic-btn',
        title: 'Email this .lic to SeDiv license inbox'
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
    document.getElementById('vbdl-pmlic-return').addEventListener('click', returnSrc);
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
