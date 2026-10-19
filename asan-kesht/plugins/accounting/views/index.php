<?php /** @var array<string, int> $figures */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<div class="cards">
  <a class="card" href="<?= e(url('/admin/accounting/sale')) ?>"><span>فروش</span><strong><?= e(money($figures['sales'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>بهای تمام‌شده فروش</span><strong><?= e(money($figures['cogs'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>درصد سود ناخالص</span><strong><?= e(accounting_rate($figures['gross'], $figures['sales'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>سود ناخالص</span><strong><?= e(money($figures['gross'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/purchase')) ?>"><span>خرید</span><strong><?= e(money($figures['purchases'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/visitors')) ?>"><span>سهم ویزیتور</span><strong><?= e(money($figures['visitor_commission'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/expenses')) ?>"><span>هزینه</span><strong><?= e(money($figures['expenses'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/payroll')) ?>"><span>حقوق و مزایا</span><strong><?= e(money($figures['payroll'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/reports')) ?>"><span>سود (زیان) عملیاتی</span><strong><?= e(money($figures['operating'])) ?></strong></a>
  <a class="card" href="<?= e(url('/admin/accounting/proforma')) ?>"><span>پیش‌فاکتور باز</span><strong><?= e(money($figures['proforma'])) ?></strong></a>
</div>
<p class="muted">سود ناخالص برابر فروش فاکتورشده منهای بهای تمام‌شده است. سود عملیاتی، پورسانت قطعی نماینده، سهم ویزیتور، حقوق با سهم کارفرما و سایر هزینه‌ها را هم کم می‌کند. پورسانت در جریان و مدل خرید و فروش دوباره در این هزینه نیست. جزئیات و درصدها در سود و زیان است.</p>
