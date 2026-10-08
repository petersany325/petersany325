(function () {
  // Windows menubar: click to pin open; hover also works via CSS
  const menubar = document.getElementById('win-menubar');
  if (menubar) {
    menubar.querySelectorAll('.win-menu-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const menu = btn.closest('.win-menu');
        const wasOpen = menu.classList.contains('open');
        menubar.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
        if (!wasOpen) menu.classList.add('open');
      });
    });
    document.addEventListener('click', (e) => {
      if (!menubar.contains(e.target)) {
        menubar.querySelectorAll('.win-menu.open').forEach((m) => m.classList.remove('open'));
      }
    });
  }

  // Explorer tree branches
  document.querySelectorAll('.branch-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('.branch')?.classList.toggle('open');
    });
  });

  // Voucher lines calculator
  const table = document.getElementById('lines');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  const tpl = document.getElementById('line-tpl');
  const addBtn = document.getElementById('add-line');
  const sumD = document.getElementById('sum-d');
  const sumC = document.getElementById('sum-c');

  function recalc() {
    let d = 0, c = 0;
    tbody.querySelectorAll('tr').forEach((tr) => {
      const dv = parseFloat((tr.querySelector('.debit')?.value || '0').replace(/,/g, '')) || 0;
      const cv = parseFloat((tr.querySelector('.credit')?.value || '0').replace(/,/g, '')) || 0;
      d += dv; c += cv;
    });
    if (sumD) {
      sumD.textContent = d.toLocaleString('en-US');
      sumD.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
    }
    if (sumC) {
      sumC.textContent = c.toLocaleString('en-US');
      sumC.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
    }
  }

  tbody.addEventListener('input', recalc);
  tbody.addEventListener('click', (e) => {
    if (e.target.classList.contains('rm')) {
      const rows = tbody.querySelectorAll('tr');
      if (rows.length > 2) e.target.closest('tr').remove();
      recalc();
    }
  });

  addBtn?.addEventListener('click', () => {
    if (tpl) tbody.appendChild(tpl.content.cloneNode(true));
    recalc();
  });

  recalc();
})();
