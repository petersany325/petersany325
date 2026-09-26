/**
 * Active License SeDiv — VIP desk page.
 * VIP: pick type → upload .lic → ticket (VIP+support) → email. Staff: review all tickets.
 */
(function () {
  var page = window.__VBDL_SEDIV_PAGE__ || {};
  var msg = document.getElementById('vbdl-sediv-msg');
  var returnMsg = document.getElementById('vbdl-sediv-return-msg');
  var listEl = document.getElementById('vbdl-sediv-list');
  var ticketLink = document.getElementById('vbdl-sediv-ticketlink');
  var subjectPreview = document.getElementById('vbdl-sediv-subject-preview');

  function parseJson(r) {
    return r.text().then(function (t) {
      try { return JSON.parse(t); }
      catch (e) {
        throw new Error((t && t.replace(/<[^>]+>/g, ' ').trim().slice(0, 160)) || ('HTTP ' + r.status));
      }
    });
  }

  function selectedType() {
    var el = document.querySelector('input[name="vbdl_sediv_type"]:checked');
    if (!el) return null;
    return {
      id: el.value,
      subject: el.getAttribute('data-subject') || '',
      label: el.getAttribute('data-label') || ''
    };
  }

  function syncSubjectPreview() {
    if (!subjectPreview) return;
    var t = selectedType();
    subjectPreview.textContent = t && t.subject ? t.subject : '—';
  }

  function loadList() {
    if (!listEl) return;
    listEl.textContent = 'Loading…';
    var url = '/vbdlmanager/pm_lic_email.php?do=vip_list';
    if (page.canStaff) url += '&all=1';
    fetch(url, { credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        if (!data.ok) {
          listEl.textContent = data.error || 'Could not load requests';
          return;
        }
        var rows = data.items || [];
        if (!rows.length) {
          listEl.textContent = page.canStaff && !page.isVip
            ? 'No VIP license tickets yet.'
            : 'No license tickets yet. Send a .lic above to open one.';
          return;
        }
        listEl.innerHTML = '';
        rows.forEach(function (row) {
          var item = document.createElement('div');
          item.className = 'vbdl-sediv-item';
          var left = document.createElement('div');
          var title = document.createElement('strong');
          var who = row.customer_username ? (row.customer_username + ' · ') : '';
          var typeBit = row.license_label ? (row.license_label + ' · ') : '';
          title.textContent = who + typeBit + (row.lic_filename || 'license.lic');
          var meta = document.createElement('small');
          var bits = [row.token || '', row.sent_label || ''];
          if (row.status === 'returned' && row.return_filename) {
            bits.push(row.return_filename);
            if (row.src_purged) bits.push('src purged after retention');
            else if (typeof row.download_count === 'number') bits.push(row.download_count + ' downloads');
          }
          if (row.status === 'rejected' && row.reply_text) {
            bits.push(String(row.reply_text).replace(/\s+/g, ' ').slice(0, 80));
          }
          meta.textContent = bits.filter(Boolean).join(' · ');
          left.appendChild(title);
          left.appendChild(meta);
          var right = document.createElement('div');
          var badge = document.createElement('span');
          badge.className = 'vbdl-sediv-badge is-' + (row.status || 'sent');
          badge.textContent = row.status || 'sent';
          right.appendChild(badge);
          if (row.src_download_url && row.src_available) {
            right.appendChild(document.createTextNode(' '));
            var dl = document.createElement('a');
            dl.className = 'vbdl-sediv-dl';
            dl.href = row.src_download_url;
            dl.textContent = 'Download .src';
            right.appendChild(dl);
          } else if (row.src_purged) {
            right.appendChild(document.createTextNode(' '));
            var gone = document.createElement('span');
            gone.className = 'vbdl-sediv-muted';
            gone.textContent = 'src expired';
            right.appendChild(gone);
          }
          if (row.message_url) {
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
    if (!page.isVip && !page.canStaff) {
      msg.textContent = 'Only SeDiv VIP members or administrators can submit a license here.';
      return;
    }
    var type = selectedType();
    if (!type || !type.id) {
      msg.textContent = 'Choose a license type';
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
    msg.textContent = 'Sending… opening ticket…';
    if (ticketLink) {
      ticketLink.hidden = true;
      ticketLink.innerHTML = '';
    }
    var fd = new FormData();
    fd.append('do', 'vip_submit');
    fd.append('license_type', type.id);
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
        msg.textContent = 'Sent to license inbox (' + (data.subject || type.subject) + '). Ticket opened for you and support.';
        input.value = '';
        if (data.token) {
          var tokEl = document.getElementById('vbdl-sediv-token');
          if (tokEl) tokEl.value = data.token;
        }
        if (ticketLink && data.message_url) {
          ticketLink.hidden = false;
          ticketLink.innerHTML = '';
          var a = document.createElement('a');
          a.className = 'vbdl-sediv-dl';
          a.href = data.message_url;
          a.textContent = 'Open your license ticket';
          ticketLink.appendChild(a);
        }
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
    var statusMsg = document.getElementById('vbdl-sediv-imap-msg');
    var pollBtn = document.getElementById('vbdl-sediv-poll');
    if (!card || !statusMsg) return;
    card.hidden = false;
    fetch('/vbdlmanager/pm_lic_email.php?do=config', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (cfg) {
        if (!cfg.ok) return;
        var days = cfg.src_retention_days || 7;
        if (cfg.maildir_ready) {
          statusMsg.textContent = 'Auto-import is active via local info@ maildir. Returned .src or text replies post into the VIP ticket. .src files are purged after ' + days + ' days (filename + download count kept).';
          if (pollBtn) pollBtn.hidden = false;
        } else if (cfg.imap_configured) {
          statusMsg.textContent = 'IMAP ready for ' + (cfg.imap_user || 'inbox') + '. Poll inbox now or set cron on pm_lic_inbox.php. Retention: ' + days + ' days.';
          if (pollBtn) pollBtn.hidden = false;
        } else {
          statusMsg.textContent = 'Auto-import not ready. Configure maildir/IMAP, or use manual .src upload below.';
          if (pollBtn) pollBtn.hidden = true;
        }
      })
      .catch(function () {
        statusMsg.textContent = 'Could not load auto-return status.';
      });
  }

  function pollInbox() {
    var pollMsg = document.getElementById('vbdl-sediv-poll-msg');
    pollMsg.textContent = 'Polling…';
    fetch('/vbdlmanager/pm_lic_inbox.php?do=poll', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (data) {
        if (data.skipped) {
          pollMsg.textContent = data.message || 'Inbox not configured';
          return;
        }
        if (!data.ok) {
          pollMsg.textContent = data.error || 'Poll failed';
          return;
        }
        var n = (data.processed && data.processed.length) || 0;
        var p = data.purged_count || 0;
        pollMsg.textContent = 'Checked ' + (data.checked || 0) + ' messages, imported ' + n
          + (data.mode ? (' (' + data.mode + ')') : '')
          + (p ? (', purged ' + p + ' expired .src') : '') + '.';
        loadList();
      })
      .catch(function (err) {
        pollMsg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  var typeRadios = document.querySelectorAll('input[name="vbdl_sediv_type"]');
  for (var i = 0; i < typeRadios.length; i++) {
    typeRadios[i].addEventListener('change', syncSubjectPreview);
  }
  syncSubjectPreview();

  var submitBtn = document.getElementById('vbdl-sediv-submit');
  if (submitBtn) submitBtn.addEventListener('click', submitLic);
  var retBtn = document.getElementById('vbdl-sediv-return');
  if (retBtn) retBtn.addEventListener('click', returnSrc);
  var refresh = document.getElementById('vbdl-sediv-refresh');
  if (refresh) refresh.addEventListener('click', loadList);
  var pollBtn = document.getElementById('vbdl-sediv-poll');
  if (pollBtn) pollBtn.addEventListener('click', pollInbox);
  try {
    var q = new URLSearchParams(location.search || '');
    var tok = q.get('token');
    if (tok) {
      var tokEl = document.getElementById('vbdl-sediv-token');
      if (tokEl) tokEl.value = tok;
    }
  } catch (e) {}
  showImapStatus();
  loadList();
})();
