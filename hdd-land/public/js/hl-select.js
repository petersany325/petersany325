/**
 * Custom graphical dropdown for سرزمین هارد product/serial UI.
 */
(function () {
  function closeAll(except) {
    document.querySelectorAll('[data-hl-dd].is-open').forEach(function (dd) {
      if (except && dd === except) return;
      dd.classList.remove('is-open');
      var trigger = dd.querySelector('[data-hl-dd-trigger]');
      var panel = dd.querySelector('[data-hl-dd-panel]');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      if (panel) panel.hidden = true;
    });
  }

  function setOpen(dd, open) {
    var trigger = dd.querySelector('[data-hl-dd-trigger]');
    var panel = dd.querySelector('[data-hl-dd-panel]');
    if (!trigger || !panel) return;
    dd.classList.toggle('is-open', open);
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.hidden = !open;
  }

  function boot(root) {
    (root || document).querySelectorAll('[data-hl-dd]').forEach(function (dd) {
      if (dd.dataset.hlReady === '1') return;
      dd.dataset.hlReady = '1';

      var trigger = dd.querySelector('[data-hl-dd-trigger]');
      var panel = dd.querySelector('[data-hl-dd-panel]');
      var input = dd.querySelector('input[data-hl-dd-input]');
      var label = dd.querySelector('[data-hl-dd-label]');
      if (!trigger || !panel || !input) return;

      function toggle(e) {
        if (e) {
          e.preventDefault();
          e.stopPropagation();
        }
        var willOpen = !dd.classList.contains('is-open');
        closeAll(dd);
        setOpen(dd, willOpen);
        if (willOpen) dd.classList.remove('is-invalid');
      }

      trigger.addEventListener('click', toggle);
      trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') toggle(e);
      });

      panel.querySelectorAll('[data-hl-dd-option]').forEach(function (opt) {
        opt.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var value = opt.getAttribute('data-value') || '';
          var text = opt.getAttribute('data-label') || opt.textContent.trim();
          input.value = value;
          input.dispatchEvent(new Event('change', { bubbles: true }));
          if (label) label.textContent = text;
          dd.classList.add('has-value');
          panel.querySelectorAll('[data-hl-dd-option]').forEach(function (o) {
            o.classList.toggle('is-selected', o === opt);
          });
          closeAll();
        });
      });
    });
  }

  document.addEventListener('click', function (e) {
    if (e.target && e.target.closest && e.target.closest('[data-hl-dd]')) return;
    closeAll();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });

  function start() { boot(); }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
  window.HLSelect = { boot: boot, closeAll: closeAll };
})();