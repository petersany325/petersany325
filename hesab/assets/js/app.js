(function () {
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
    sumD.textContent = d.toLocaleString('en-US');
    sumC.textContent = c.toLocaleString('en-US');
    sumD.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
    sumC.style.color = d === c ? 'var(--ok)' : 'var(--danger)';
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
    tbody.appendChild(tpl.content.cloneNode(true));
    recalc();
  });

  recalc();
})();
