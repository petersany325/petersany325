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
$role = (string) (($app->auth->user()['role'] ?? ''));
if (!in_array($role, ['admin', 'accountant'], true)) {
    unset($links['/admin/accounting/audit']);
}
?>
<nav class="acct-nav" aria-label="حسابداری">
  <?php foreach ($links as $href => $label): ?>
    <a href="<?= e(url($href)) ?>"<?= $current === $href ? ' class="is-current"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
