/**
 * Active License SeDiv — VIP desk page.
 */
(function () {
  var page = window.__VBDL_SEDIV_PAGE__ || {};
  var msg = document.getElementById('vbdl-sediv-msg');
  var returnMsg = document.getElementById('vbdl-sediv-return-msg');
  var listEl = document.getElementById('vbdl-sediv-list');

  function parseJson(r) {
    return r.text().then(function (t) {
      try { return JSON.parse(t); }
      catch (e) {
        throw new Error((t && t.replace(/<[^>]+>/g, ' ').trim().slice(0, 160)) || ('HTTP ' + r.status));
      }
    });
  }

  function loadList() {
    if (!listEl) return;
    listEl.textContent = 'Loading…';
    fetch('/vbdlmanager/pm_lic_email.php?do=vip_list', { credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        if (!data.ok) {
          listEl.textContent = data.error || 'Could not load requests';
          return;
        }
        var rows = data.items || [];
        if (!rows.length) {
          listEl.textContent = 'No license requests yet.';
          return;
        }
        listEl.innerHTML = '';
        rows.forEach(function (row) {
          var item = document.createElement('div');
          item.className = 'vbdl-sediv-item';
          var left = document.createElement('div');
          var title = document.createElement('strong');
          title.textContent = row.lic_filename || 'license.lic';
          var meta = document.createElement('small');
          meta.textContent = (row.token || '') + ' · ' + (row.sent_label || '') + (row.status === 'returned' && row.return_filename ? (' · ' + row.return_filename) : '');
          left.appendChild(title);
          left.appendChild(meta);
          var right = document.createElement('div');
          var badge = document.createElement('span');
          badge.className = 'vbdl-sediv-badge is-' + (row.status || 'sent');
          badge.textContent = row.status || 'sent';
          right.appendChild(badge);
          if (row.status === 'returned' && row.message_url) {
            right.appendChild(document.createTextNode(' '));
            var a = document.createElement('a');
            a.className = 'vbdl-sediv-dl';
            a.href = row.message_url;
            a.textContent = 'Open ticket';
            right.appendChild(a);
          }
          item.appendChild(left);
          item.appendChild(right);
          listEl.appendChild(item);
        });
      })
      .catch(function (err) {
        listEl.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function submitLic() {
    if (!page.isVip) {
      msg.textContent = 'Only SeDiv VIP members can submit a license here.';
      return;
    }
    var input = document.getElementById('vbdl-sediv-lic');
    if (!input.files || !input.files[0]) {
      msg.textContent = 'Choose a .lic file';
      return;
    }
    var name = String(input.files[0].name || '');
    if (!/\.lic$/i.test(name)) {
      msg.textContent = 'Only .lic files are accepted';
      return;
    }
    var btn = document.getElementById('vbdl-sediv-submit');
    btn.disabled = true;
    msg.textContent = 'Sending…';
    var fd = new FormData();
    fd.append('do', 'vip_submit');
    fd.append('licfile', input.files[0]);
    fd.append('note', (document.getElementById('vbdl-sediv-note').value || '').trim());
    fetch('/vbdlmanager/pm_lic_email.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        btn.disabled = false;
        if (!data.ok) {
          msg.textContent = data.error || 'Send failed';
          return;
        }
        msg.textContent = 'Sent. Token ' + data.token + (data.message_url ? ' — ticket created.' : '');
        input.value = '';
        if (data.token) document.getElementById('vbdl-sediv-token').value = data.token;
        loadList();
      })
      .catch(function (err) {
        btn.disabled = false;
        msg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function returnSrc() {
    var token = (document.getElementById('vbdl-sediv-token').value || '').trim();
    var fileInput = document.getElementById('vbdl-sediv-src');
    if (!token) { returnMsg.textContent = 'Enter tracking token'; return; }
    if (!fileInput.files || !fileInput.files[0]) { returnMsg.textContent = 'Choose the returned .src file'; return; }
    var fd = new FormData();
    fd.append('do', 'return_upload');
    fd.append('token', token);
    fd.append('srcfile', fileInput.files[0]);
    returnMsg.textContent = 'Uploading .src into ticket…';
    fetch('/vbdlmanager/pm_lic_email.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        if (!data.ok) {
          returnMsg.textContent = data.error || 'Return upload failed';
          return;
        }
        returnMsg.textContent = 'Posted .src into Message Center ticket.';
        loadList();
      })
      .catch(function (err) {
        returnMsg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function showImapStatus() {
    if (!page.canStaff) return;
    var card = document.getElementById('vbdl-sediv-imap-card');
    var msg = document.getElementById('vbdl-sediv-imap-msg');
    var pollBtn = document.getElementById('vbdl-sediv-poll');
    if (!card) return;
    card.hidden = false;
    fetch('/vbdlmanager/pm_lic_email.php?do=config', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (cfg) {
        if (!cfg.ok) return;
        if (cfg.imap_configured) {
          msg.textContent = 'IMAP ready for ' + (cfg.imap_user || 'inbox') + ' @ ' + (cfg.imap_host || '') + '. Use Poll inbox now, or set a cron on pm_lic_inbox.php.';
          if (pollBtn) pollBtn.hidden = false;
        } else {
          msg.textContent = 'IMAP password is not set in AdminCP → Download Manager → Settings. Replies land in info@hdd-land.com but are not auto-imported until license_imap_pass (and cron) are configured. Use manual .src upload below for this reply.';
          if (pollBtn) pollBtn.hidden = true;
        }
      })
      .catch(function () {
        msg.textContent = 'Could not load IMAP status.';
      });
  }

  function pollInbox() {
    var pollMsg = document.getElementById('vbdl-sediv-poll-msg');
    pollMsg.textContent = 'Polling…';
    fetch('/vbdlmanager/pm_lic_inbox.php?do=poll', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (data) {
        if (data.skipped) {
          pollMsg.textContent = data.message || 'IMAP not configured';
          return;
        }
        if (!data.ok) {
          pollMsg.textContent = data.error || 'Poll failed';
          return;
        }
        var n = (data.processed && data.processed.length) || 0;
        pollMsg.textContent = 'Checked ' + (data.checked || 0) + ' messages, imported ' + n + '.';
        loadList();
      })
      .catch(function (err) {
        pollMsg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  var submitBtn = document.getElementById('vbdl-sediv-submit');
  if (submitBtn) submitBtn.addEventListener('click', submitLic);
  var retBtn = document.getElementById('vbdl-sediv-return');
  if (retBtn) retBtn.addEventListener('click', returnSrc);
  var refresh = document.getElementById('vbdl-sediv-refresh');
  if (refresh) refresh.addEventListener('click', loadList);
  var pollBtn = document.getElementById('vbdl-sediv-poll');
  if (pollBtn) pollBtn.addEventListener('click', pollInbox);
  // Prefill token from query ?token=
  try {
    var q = new URLSearchParams(location.search || '');
    var tok = q.get('token');
    if (tok) document.getElementById('vbdl-sediv-token').value = tok;
  } catch (e) {}
  showImapStatus();
  loadList();
})();
