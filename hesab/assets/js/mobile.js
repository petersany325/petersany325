(function () {
  // Slide-out hamburger drawer
  const btn = document.getElementById('m-menu-btn');
  const drawer = document.getElementById('m-drawer');
  const backdrop = document.getElementById('m-drawer-backdrop');
  const closeBtn = document.getElementById('m-drawer-close');

  function openDrawer() {
    if (!drawer || !backdrop || !btn) return;
    drawer.hidden = false;
    backdrop.hidden = false;
    void drawer.offsetWidth;
    document.body.classList.add('m-drawer-open');
    btn.setAttribute('aria-expanded', 'true');
    drawer.setAttribute('aria-hidden', 'false');
  }

  function closeDrawer() {
    if (!drawer || !backdrop || !btn) return;
    document.body.classList.remove('m-drawer-open');
    btn.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    window.setTimeout(() => {
      if (!document.body.classList.contains('m-drawer-open')) {
        drawer.hidden = true;
        backdrop.hidden = true;
      }
    }, 220);
  }

  function toggleDrawer(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    if (document.body.classList.contains('m-drawer-open')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  }

  if (btn && drawer && backdrop) {
    btn.addEventListener('click', toggleDrawer);
    closeBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      closeDrawer();
    });
    backdrop.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeDrawer();
    });
  }

  // Voucher mobile line calculator
  const form = document.getElementById('m-voucher-form');
  if (form) {
    const box = document.getElementById('m-lines');
    const tpl = document.getElementById('m-line-tpl');
    const addBtn = document.getElementById('m-add-line');
    const sumD = document.getElementById('m-sum-d');
    const sumC = document.getElementById('m-sum-c');

    function recalc() {
      let d = 0, c = 0;
      box.querySelectorAll('.m-line-card').forEach((card) => {
        d += parseFloat((card.querySelector('.m-debit')?.value || '0').replace(/,/g, '')) || 0;
        c += parseFloat((card.querySelector('.m-credit')?.value || '0').replace(/,/g, '')) || 0;
      });
      if (sumD) sumD.textContent = d.toLocaleString('en-US');
      if (sumC) sumC.textContent = c.toLocaleString('en-US');
      const ok = Math.abs(d - c) < 0.0001;
      if (sumD) sumD.style.color = ok ? 'var(--m-ok)' : 'var(--m-danger)';
      if (sumC) sumC.style.color = ok ? 'var(--m-ok)' : 'var(--m-danger)';
    }

    box?.addEventListener('input', recalc);
    addBtn?.addEventListener('click', () => {
      if (tpl && box) box.appendChild(tpl.content.cloneNode(true));
      recalc();
    });
    recalc();
  }

  // PWA service worker
  if ('serviceWorker' in navigator) {
    const base = document.body.dataset.base || '';
    navigator.serviceWorker.register(base + '/sw.js').catch(() => {});
  }
})();
