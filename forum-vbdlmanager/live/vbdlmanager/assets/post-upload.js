/**
 * Specialized post Upload/Download menu widget (injected near editors).
 * Depends on /vbdlmanager/upload_api.php
 */
(function () {
  if (window.__vbdlPostUploadInit) return;
  window.__vbdlPostUploadInit = true;

  function el(tag, attrs, html) {
    var n = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (html != null) n.innerHTML = html;
    return n;
  }

  function findEditorRoot() {
    return document.querySelector('.js-editor, .b-editor, .editor-controls, #editor, .redactor-toolbar, .cke_chrome, form[action*="create-content"], form[action*="edit-content"]')
      || document.querySelector('textarea[name="text"], textarea[name="message"]');
  }

  function insertText(text) {
    var ta = document.querySelector('textarea[name="text"], textarea[name="message"], .redactor-editor, [contenteditable="true"]');
    if (!ta) return false;
    if (ta.tagName === 'TEXTAREA') {
      var start = ta.selectionStart || ta.value.length;
      ta.value = ta.value.slice(0, start) + text + ta.value.slice(start);
      ta.dispatchEvent(new Event('input', { bubbles: true }));
      return true;
    }
    if (ta.isContentEditable) {
      document.execCommand('insertText', false, text);
      return true;
    }
    return false;
  }

  function mount(panelParent) {
    if (document.getElementById('vbdl-post-upload-panel')) return;
    var box = el('div', { id: 'vbdl-post-upload-panel', class: 'vbdl-post-upload' });
    box.innerHTML = ''
      + '<div class="vbdl-pu-head">📁 Downloads Manager — Upload</div>'
      + '<div class="vbdl-pu-sub">Categories are limited to your Access Grants. VIP categories stay locked until admin grants you.</div>'
      + '<div class="vbdl-pu-row"><label>Category</label><select id="vbdl-pu-cat"></select></div>'
      + '<div class="vbdl-pu-row"><label>Title</label><input id="vbdl-pu-title" type="text" placeholder="optional title" /></div>'
      + '<div class="vbdl-pu-row"><label>File</label><input id="vbdl-pu-file" type="file" /></div>'
      + '<div class="vbdl-pu-actions"><button type="button" id="vbdl-pu-btn">Upload & insert BBCode</button>'
      + '<a class="vbdl-pu-lib" href="/vbdlmanager/" target="_blank" rel="noopener">Open library</a></div>'
      + '<div id="vbdl-pu-msg" class="vbdl-pu-msg"></div>';
    panelParent.insertBefore(box, panelParent.firstChild);

    var msg = box.querySelector('#vbdl-pu-msg');
    var cat = box.querySelector('#vbdl-pu-cat');
    fetch('/vbdlmanager/upload_api.php?do=categories', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) {
          msg.textContent = data.error || 'Upload menu unavailable';
          box.querySelector('#vbdl-pu-btn').disabled = true;
          return;
        }
        if (!data.categories || !data.categories.length) {
          msg.textContent = 'No uploadable categories for your account. Ask admin for an Access Grant with Upload.';
          box.querySelector('#vbdl-pu-btn').disabled = true;
          return;
        }
        data.categories.forEach(function (c) {
          var o = document.createElement('option');
          o.value = c.categoryid;
          o.textContent = c.title + (c.access_mode === 'grant_required' ? ' [grant]' : '');
          cat.appendChild(o);
        });
      })
      .catch(function () { msg.textContent = 'Could not load categories'; });

    box.querySelector('#vbdl-pu-btn').addEventListener('click', function () {
      var f = box.querySelector('#vbdl-pu-file').files[0];
      if (!f) { msg.textContent = 'Choose a file first'; return; }
      var fd = new FormData();
      fd.append('do', 'upload');
      fd.append('categoryid', cat.value);
      fd.append('title', box.querySelector('#vbdl-pu-title').value || f.name);
      fd.append('upload', f);
      msg.textContent = 'Uploading…';
      fetch('/vbdlmanager/upload_api.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) { msg.textContent = data.error || 'Upload failed'; return; }
          insertText('\n' + data.bbcode + '\n');
          msg.textContent = 'Inserted: ' + data.bbcode;
          box.querySelector('#vbdl-pu-file').value = '';
        })
        .catch(function () { msg.textContent = 'Upload request failed'; });
    });
  }

  function tryMount() {
    var root = findEditorRoot();
    if (!root) return;
    var host = root.closest('form') || root.parentElement || root;
    mount(host);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', tryMount);
  else tryMount();
  setTimeout(tryMount, 1200);
})();
