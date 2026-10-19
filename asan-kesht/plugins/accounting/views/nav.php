<?php
$links = [
    '/admin/accounting' => 'خلاصه',
    '/admin/accounting/proforma' => 'پیش‌فاکتور',
    '/admin/accounting/sale' => 'فاکتور فروش',
    '/admin/accounting/purchase' => 'فاکتور خرید',
    '/admin/accounting/reports' => 'گزارش سود',
    '/admin/accounting/checks' => 'چک و دسته‌چک',
    '/admin/accounting/payroll' => 'حقوق و مزایا',
    '/admin/accounting/expenses' => 'هزینه‌ها',
];
$current = rtrim(request_path(), '/') ?: '/';
?>
<nav class="acct-nav" aria-label="حسابداری">
  <?php foreach ($links as $href => $label): ?>
    <a href="<?= e(url($href)) ?>"<?= $current === $href ? ' class="is-current"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
