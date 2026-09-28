/**
 * License Request desk — all users + admin VIP add queue.
 */
(function () {
  var page = window.__VBDL_REQ_PAGE__ || {};
  var msg = document.getElementById('vbdl-req-msg');
  var listEl = document.getElementById('vbdl-req-list');
  var adminList = document.getElementById('vbdl-req-admin-list');
  var ticketLink = document.getElementById('vbdl-req-ticketlink');

  function parseJson(r) {
    return r.text().then(function (t) {
      try { return JSON.parse(t); }
      catch (e) {
        throw new Error((t && t.replace(/<[^>]+>/g, ' ').trim().slice(0, 160)) || ('HTTP ' + r.status));
      }
    });
  }

  function statusLabel(st) {
    if (st === 'approved') return 'approved';
    if (st === 'vip_added') return 'VIP added';
    if (st === 'rejected') return 'rejected';
    if (st === 'error') return 'error';
    return st || 'sent';
  }

  function loadList() {
    if (!listEl) return;
    listEl.textContent = 'Loading…';
    var url = '/vbdlmanager/pm_lic_request.php?do=list';
    if (page.canStaff) url += '&all=1';
    fetch(url, { credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        if (!data.ok) {
          listEl.textContent = data.error || 'Could not load';
          return;
        }
        var rows = data.items || [];
        if (!rows.length) {
          listEl.textContent = 'No requests yet.';
          return;
        }
        listEl.innerHTML = '';
        var prefillsent = '';
        rows.forEach(function (row) {
          if (!prefillsent && row.status === 'sent' && row.token) {
            prefillsent = row.token;
          }
          var item = document.createElement('div');
          item.className = 'vbdl-req-item';
          var left = document.createElement('div');
          var title = document.createElement('strong');
          var who = row.customer_username ? (row.customer_username + ' · ') : '';
          title.textContent = who + (row.receipt_filename || 'receipt');
          var meta = document.createElement('small');
          var bits = [row.token || '', row.sent_label || ''];
          if (row.license_filename) bits.push(row.license_filename);
          meta.textContent = bits.filter(Boolean).join(' · ');
          left.appendChild(title);
          left.appendChild(meta);
          var right = document.createElement('div');
          var badge = document.createElement('span');
          badge.className = 'vbdl-req-badge is-' + (row.status || 'sent');
          badge.textContent = statusLabel(row.status);
          right.appendChild(badge);
          if (row.message_url) {
            right.appendChild(document.createTextNode(' '));
            var a = document.createElement('a');
            a.className = 'vbdl-req-dl';
            a.href = row.message_url;
            a.textContent = 'Open ticket';
            right.appendChild(a);
          }
          if (row.license_download_url) {
            right.appendChild(document.createTextNode(' '));
            var dl = document.createElement('a');
            dl.className = 'vbdl-req-dl';
            dl.href = row.license_download_url;
            dl.textContent = 'Download license';
            right.appendChild(dl);
          }
          item.appendChild(left);
          item.appendChild(right);
          listEl.appendChild(item);
        });
        var tokInput = document.getElementById('vbdl-req-return-token');
        if (tokInput && !tokInput.value && prefillsent) {
          tokInput.value = prefillsent;
        }
      })
      .catch(function (err) {
        listEl.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function loadAdmin() {
    if (!page.canStaff || !adminList) return;
    adminList.textContent = 'Loading…';
    fetch('/vbdlmanager/pm_lic_request.php?do=admin_pending', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (data) {
        if (!data.ok) {
          adminList.textContent = data.error || 'Could not load admin queue';
          return;
        }
        var rows = data.items || [];
        if (!rows.length) {
          adminList.textContent = 'No approved requests waiting for VIP add.';
          return;
        }
        adminList.innerHTML = '';
        rows.forEach(function (row) {
          var item = document.createElement('div');
          item.className = 'vbdl-req-item';
          var left = document.createElement('div');
          var title = document.createElement('strong');
          title.textContent = (row.customer_username || 'user') + ' (#' + row.customer_userid + ')';
          var meta = document.createElement('small');
          meta.textContent = [row.token, row.approved_label, row.license_filename, row.customer_email]
            .filter(Boolean).join(' · ');
          left.appendChild(title);
          left.appendChild(meta);
          var right = document.createElement('div');
          if (row.message_url) {
            var a = document.createElement('a');
            a.className = 'vbdl-req-dl';
            a.href = row.message_url;
            a.textContent = 'Ticket';
            right.appendChild(a);
            right.appendChild(document.createTextNode(' '));
          }
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'vbdl-req-btn vbdl-req-btn-sm';
          btn.textContent = 'Add to VIP SeDiv';
          btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.textContent = 'Adding…';
            var fd = new FormData();
            fd.append('do', 'admin_add_vip');
            fd.append('token', row.token);
            fetch('/vbdlmanager/pm_lic_request.php', { method: 'POST', body: fd, credentials: 'same-origin' })
              .then(parseJson)
              .then(function (res) {
                if (!res.ok) {
                  btn.disabled = false;
                  btn.textContent = 'Add to VIP SeDiv';
                  alert(res.error || 'Failed');
                  return;
                }
                btn.textContent = 'Done';
                loadAdmin();
                loadList();
              })
              .catch(function (err) {
                btn.disabled = false;
                btn.textContent = 'Add to VIP SeDiv';
                alert((err && err.message) || 'Network error');
              });
          });
          right.appendChild(btn);
          item.appendChild(left);
          item.appendChild(right);
          adminList.appendChild(item);
        });
      })
      .catch(function (err) {
        adminList.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function submitReceipt() {
    var input = document.getElementById('vbdl-req-receipt');
    if (!input.files || !input.files[0]) {
      msg.textContent = 'Choose a payment receipt file';
      return;
    }
    var name = String(input.files[0].name || '');
    if (!/\.(jpe?g|png|gif|webp|pdf)$/i.test(name)) {
      msg.textContent = 'Only image or PDF files are accepted';
      return;
    }
    var btn = document.getElementById('vbdl-req-submit');
    btn.disabled = true;
    msg.textContent = 'Sending…';
    if (ticketLink) { ticketLink.hidden = true; ticketLink.innerHTML = ''; }
    var fd = new FormData();
    fd.append('do', 'submit');
    fd.append('receipt', input.files[0]);
    fd.append('note', (document.getElementById('vbdl-req-note').value || '').trim());
    fetch('/vbdlmanager/pm_lic_request.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        btn.disabled = false;
        if (!data.ok) {
          msg.textContent = data.error || 'Send failed';
          return;
        }
        msg.textContent = 'Sent. Ticket opened and receipt emailed to the license inbox.';
        input.value = '';
        if (ticketLink && data.message_url) {
          ticketLink.hidden = false;
          ticketLink.innerHTML = '';
          var a = document.createElement('a');
          a.className = 'vbdl-req-dl';
          a.href = data.message_url;
          a.textContent = 'Open your ticket';
          ticketLink.appendChild(a);
        }
        loadList();
        loadAdmin();
      })
      .catch(function (err) {
        btn.disabled = false;
        msg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function returnLicense() {
    var retMsg = document.getElementById('vbdl-req-return-msg');
    if (!retMsg) return;
    var token = (document.getElementById('vbdl-req-return-token').value || '').trim();
    var fileInput = document.getElementById('vbdl-req-return-file');
    var paste = (document.getElementById('vbdl-req-return-text').value || '').trim();
    if (!token) {
      retMsg.textContent = 'Enter tracking token (VBDL-REQ-…)';
      return;
    }
    if ((!fileInput.files || !fileInput.files[0]) && !paste) {
      retMsg.textContent = 'Choose a .txt file or paste the license text';
      return;
    }
    var btn = document.getElementById('vbdl-req-return-btn');
    btn.disabled = true;
    retMsg.textContent = 'Posting license into ticket…';
    var fd = new FormData();
    fd.append('do', 'return_upload');
    fd.append('token', token);
    if (fileInput.files && fileInput.files[0]) {
      fd.append('txtfile', fileInput.files[0]);
    }
    if (paste) {
      fd.append('license_text', paste);
    }
    fetch('/vbdlmanager/pm_lic_request.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(parseJson)
      .then(function (data) {
        btn.disabled = false;
        if (!data.ok) {
          retMsg.textContent = data.error || 'Return failed';
          return;
        }
        retMsg.textContent = 'Posted. Status is now approved — customer can open the ticket.';
        if (fileInput) fileInput.value = '';
        var ta = document.getElementById('vbdl-req-return-text');
        if (ta) ta.value = '';
        loadList();
        loadAdmin();
        if (data.message_url) {
          retMsg.innerHTML = 'Posted. <a class="vbdl-req-dl" href="' + data.message_url + '">Open ticket</a>';
        }
      })
      .catch(function (err) {
        btn.disabled = false;
        retMsg.textContent = (err && err.message) ? err.message : 'Network error';
      });
  }

  function pollInbox() {
    var retMsg = document.getElementById('vbdl-req-return-msg');
    if (retMsg) retMsg.textContent = 'Importing license replies from mailbox…';
    // Prefer dedicated REQ importer (posts license body into ticket even without .txt attach).
    fetch('/vbdlmanager/_import_req_replies.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(parseJson)
      .then(function (data) {
        if (!data || !data.ok) {
          // Fallback to generic inbox poll
          return fetch('/vbdlmanager/pm_lic_inbox.php?do=poll', { credentials: 'same-origin', cache: 'no-store' })
            .then(parseJson)
            .then(function (data2) {
              if (!data2 || !data2.ok) {
                if (retMsg) retMsg.textContent = (data && data.error) || (data2 && data2.error) || 'Import failed';
                return;
              }
              var n2 = (data2.processed && data2.processed.length) ? data2.processed.length : 0;
              if (retMsg) retMsg.textContent = 'Inbox poll: ' + n2 + ' imported.';
              loadList();
              loadAdmin();
            });
        }
        var n = (data.processed && data.processed.length) ? data.processed.length : 0;
        var errN = (data.errors && data.errors.length) ? data.errors.length : 0;
        if (retMsg) {
          retMsg.textContent = 'Imported ' + n + ' license reply(ies) into tickets'
            + (errN ? ('; ' + errN + ' skipped/errors') : '') + '.';
          if (n && data.processed[0] && data.processed[0].message_url) {
            retMsg.innerHTML = retMsg.textContent + ' <a class="vbdl-req-dl" href="'
              + data.processed[0].message_url + '">Open ticket</a>';
          }
          if (!n && errN && data.errors[0] && data.errors[0].error) {
            retMsg.textContent += ' First: ' + data.errors[0].error;
          }
        }
        loadList();
        loadAdmin();
      })
      .catch(function (err) {
        if (retMsg) retMsg.textContent = (err && err.message) ? err.message : 'Import network error';
      });
  }

  var submitBtn = document.getElementById('vbdl-req-submit');
  if (submitBtn) submitBtn.addEventListener('click', submitReceipt);
  var refresh = document.getElementById('vbdl-req-refresh');
  if (refresh) refresh.addEventListener('click', loadList);
  var adminRefresh = document.getElementById('vbdl-req-admin-refresh');
  if (adminRefresh) adminRefresh.addEventListener('click', loadAdmin);
  var returnBtn = document.getElementById('vbdl-req-return-btn');
  if (returnBtn) returnBtn.addEventListener('click', returnLicense);
  var pollBtn = document.getElementById('vbdl-req-poll');
  if (pollBtn) pollBtn.addEventListener('click', pollInbox);
  loadList();
  loadAdmin();
})();
