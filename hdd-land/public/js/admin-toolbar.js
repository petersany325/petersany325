(function () {
  var bar = document.getElementById('hlAdminBar');
  if (!bar) return;
  document.documentElement.classList.add('has-hl-adminbar');
  document.body.classList.add('has-hl-adminbar');

  function closeAll(except) {
    bar.querySelectorAll('[data-hl-dd]').forEach(function (dd) {
      if (except && dd === except) return;
      dd.classList.remove('is-open');
      var btn = dd.querySelector('button');
      var menu = dd.querySelector('.hl-adminbar__menu');
      if (btn) btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
  }

  bar.querySelectorAll('[data-hl-dd]').forEach(function (dd) {
    var btn = dd.querySelector('button');
    var menu = dd.querySelector('.hl-adminbar__menu');
    if (!btn || !menu) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var open = dd.classList.contains('is-open');
      closeAll();
      if (!open) {
        dd.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        menu.hidden = false;
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!bar.contains(e.target)) closeAll();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAll();
  });
})();
