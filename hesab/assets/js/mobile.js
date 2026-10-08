(function () {
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
