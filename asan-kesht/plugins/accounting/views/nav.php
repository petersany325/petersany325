<?php
$links = [
    '/admin/accounting' => 'خلاصه',
    '/admin/accounting/proforma' => 'پیش‌فاکتور',
    '/admin/accounting/sale' => 'فاکتور فروش',
    '/admin/accounting/purchase' => 'فاکتور خرید',
    '/admin/accounting/reports' => 'سود و زیان',
    '/admin/accounting/audit' => 'حسابرسی',
    '/admin/accounting/visitors' => 'ویزیتورها',
    '/admin/accounting/warehouses' => 'انبارها',
    '/admin/accounting/checks' => 'چک و دسته‌چک',
    '/admin/accounting/payroll' => 'حقوق و مزایا',
    '/admin/accounting/expenses' => 'هزینه‌ها',
    '/admin/accounting/invoice-settings' => 'تنظیم فاکتور',
];
if ($app->pluginEnabled('received-checks')) {
    $rebuilt = [];
    foreach ($links as $href => $label) {
        $rebuilt[$href] = $label;
        if ($href === '/admin/accounting/checks') {
            $rebuilt['/admin/received-checks'] = 'چک دریافتی';
        }
    }
    $links = $rebuilt;
}
$current = rtrim(request_path(), '/') ?: '/';
$actor = $app->auth->user();
if (!is_array($actor) || !access_can($app, $actor, 'audit', ['admin', 'accountant'])) {
    unset($links['/admin/accounting/audit']);
}
if (!$app->pluginEnabled('warehouses')) {
    unset($links['/admin/accounting/warehouses']);
}
?>
<nav class="acct-nav" aria-label="حسابداری">
  <?php foreach ($links as $href => $label): ?>
    <?php $on = $current === $href || ($href === '/admin/accounting/visitors' && str_starts_with($current, '/admin/accounting/visitors/')) || ($href === '/admin/accounting/warehouses' && str_starts_with($current, '/admin/accounting/warehouses')) || ($href === '/admin/received-checks' && str_starts_with($current, '/admin/received-checks')); ?>
    <a href="<?= e(url($href)) ?>"<?= $on ? ' class="is-current"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
