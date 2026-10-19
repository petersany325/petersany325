<?php /** @var array<string, int> $figures */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<div class="cards">
  <a class="card" href="<?= e(url('/admin/accounting/sale')) ?>"><span>فروش</span><strong><?= e(money($figures['sales'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>بهای تمام‌شده فروش</span><strong><?= e(money($figures['cogs'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>سود ناخالص</span><strong><?= e(money($figures['gross'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/purchase')) ?>"><span>خرید</span><strong><?= e(money($figures['purchases'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/expenses')) ?>"><span>هزینه</span><strong><?= e(money($figures['expenses'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/payroll')) ?>"><span>حقوق</span><strong><?= e(money($figures['payroll'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>سود عملیاتی</span><strong><?= e(money($figures['operating'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/proforma')) ?>"><span>پیش‌فاکتور باز</span><strong><?= e(money($figures['proforma'])) ?></strong></a>
</div>
<p class="muted">سود ناخالص برابر فروش منهای بهای تمام‌شده ردیف‌های فاکتور فروش است. سود عملیاتی، هزینه‌ها و حقوق را هم کم می‌کند. خرید در سود دوباره حساب نمی‌شود.</p>
