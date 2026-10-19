<?php
$links = [
    '/admin/accounting' => 'خلاصه',
    '/admin/accounting/proforma' => 'پیش‌فاکتور',
    '/admin/accounting/sale' => 'فاکتور فروش',
    '/admin/accounting/purchase' => 'فاکتور خرید',
    '/admin/accounting/reports' => 'سود و زیان',
    '/admin/accounting/audit' => 'حسابرسی',
    '/admin/accounting/visitors' => 'ویزیتورها',
    '/admin/accounting/checks' => 'چک و دسته‌چک',
    '/admin/accounting/payroll' => 'حقوق و مزایا',
    '/admin/accounting/expenses' => 'هزینه‌ها',
    '/admin/accounting/invoice-settings' => 'تنظیم فاکتور',
];
$current = rtrim(request_path(), '/') ?: '/';
$actor = $app->auth->user();
if (!is_array($actor) || !access_can($app, $actor, 'audit', ['admin', 'accountant'])) {
    unset($links['/admin/accounting/audit']);
}
?>
<nav class="acct-nav" aria-label="حسابداری">
  <?php foreach ($links as $href => $label): ?>
    <?php $on = $current === $href || ($href === '/admin/accounting/visitors' && str_starts_with($current, '/admin/accounting/visitors/')); ?>
    <a href="<?= e(url($href)) ?>"<?= $on ? ' class="is-current"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
