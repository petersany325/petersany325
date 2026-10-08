<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
$isAuthPage = in_array($name, ['login', 'install'], true);
$can = static fn(string $code): bool => Permission::can($user, $code);
$nav = $nav ?? '';

$menuOpen = static function (array $keys) use ($nav): bool {
    return in_array($nav, $keys, true);
};
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $appName) ?> | <?= e($appName) ?></title>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=5">
</head>
<body>
<?php if ($isAuthPage): ?>
  <?php require __DIR__ . '/' . $name . '.php'; ?>
<?php else: ?>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <strong><?= e($appName) ?></strong>
        <span>حسابداری مالی · منوی نرم‌افزاری</span>
      </div>
      <nav class="nav" id="app-nav">
        <a class="nav-item <?= $nav === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/')) ?>">داشبورد</a>

        <?php if ($can('accounts.manage') || $can('tafsili.manage') || $can('fiscal.manage')): ?>
        <details class="nav-group" open>
          <summary>اطلاعات پایه</summary>
          <div class="nav-sub">
            <?php if ($can('accounts.manage')): ?>
              <a class="<?= $nav === 'accounts' ? 'active' : '' ?>" href="<?= e(url('/accounts')) ?>">کدینگ حساب‌ها</a>
            <?php endif; ?>
            <?php if ($can('tafsili.manage')): ?>
              <a class="<?= $nav === 'tafsili' ? 'active' : '' ?>" href="<?= e(url('/tafsili')) ?>">تفصیلی شناور</a>
              <a class="<?= $nav === 'dimensions' ? 'active' : '' ?>" href="<?= e(url('/dimensions')) ?>">ابعاد تحلیلی</a>
            <?php endif; ?>
            <?php if ($can('fiscal.manage')): ?>
              <a class="<?= $nav === 'fiscal' ? 'active' : '' ?>" href="<?= e(url('/fiscal')) ?>">سال و دوره مالی</a>
            <?php endif; ?>
            <?php if ($can('accounts.manage')): ?>
              <a class="<?= $nav === 'settings_acc' ? 'active' : '' ?>" href="<?= e(url('/settings/accounting')) ?>">تنظیمات حسابداری</a>
            <?php endif; ?>
          </div>
        </details>
        <?php endif; ?>

        <?php if ($can('vouchers.create')): ?>
        <details class="nav-group" open>
          <summary>حسابداری</summary>
          <div class="nav-sub">
            <a class="<?= $nav === 'vouchers' ? 'active' : '' ?>" href="<?= e(url('/vouchers')) ?>">اسناد حسابداری</a>
            <a href="<?= e(url('/vouchers/create')) ?>">ثبت سند جدید</a>
            <a class="<?= $nav === 'invoices' ? 'active' : '' ?>" href="<?= e(url('/invoices')) ?>">فاکتور فروش</a>
          </div>
        </details>
        <?php endif; ?>

        <?php if ($can('treasury.manage')): ?>
        <details class="nav-group" open>
          <summary>خزانه‌داری</summary>
          <div class="nav-sub">
            <a class="<?= $nav === 'treasury' ? 'active' : '' ?>" href="<?= e(url('/treasury')) ?>">بانک / صندوق / چک</a>
            <a href="<?= e(url('/treasury')) ?>#receive">رسید دریافت</a>
            <a href="<?= e(url('/treasury')) ?>#pay">رسید پرداخت</a>
            <a class="<?= $nav === 'bank_reconcile' ? 'active' : '' ?>" href="<?= e(url('/reports/bank-reconcile')) ?>">مغایرت بانکی</a>
            <a href="<?= e(url('/reports/checks')) ?>">چک‌های دریافتنی/پرداختنی</a>
          </div>
        </details>
        <?php endif; ?>

        <?php if ($can('reports.view')): ?>
        <details class="nav-group" open>
          <summary>گزارش‌ها</summary>
          <div class="nav-sub">
            <a class="<?= $nav === 'reports' ? 'active' : '' ?>" href="<?= e(url('/reports')) ?>">مرکز گزارش‌ها</a>
            <a href="<?= e(url('/trial-balance')) ?>">تراز آزمایشی</a>
            <a href="<?= e(url('/reports/balance-sheet')) ?>">ترازنامه</a>
            <a href="<?= e(url('/reports/pl')) ?>">سود و زیان</a>
            <a href="<?= e(url('/ledger')) ?>">دفتر معین / مرور</a>
            <a href="<?= e(url('/reports/journal')) ?>">دفتر روزنامه</a>
            <a href="<?= e(url('/reports/nature-violations')) ?>">خلاف ماهیت</a>
            <a href="<?= e(url('/reports/share')) ?>">سهم‌بری / پروژه</a>
            <a href="<?= e(url('/reports/charts')) ?>">نمودار دوره‌ها</a>
          </div>
        </details>
        <?php endif; ?>

        <?php if ($can('moadian.manage') || $can('users.manage') || $can('audit.view')): ?>
        <details class="nav-group" open>
          <summary>سیستم</summary>
          <div class="nav-sub">
            <?php if ($can('moadian.manage')): ?>
              <a class="<?= $nav === 'moadian' ? 'active' : '' ?>" href="<?= e(url('/moadian')) ?>">سامانه مودیان</a>
            <?php endif; ?>
            <?php if ($can('users.manage')): ?>
              <a class="<?= $nav === 'users' ? 'active' : '' ?>" href="<?= e(url('/users')) ?>">کاربران و دسترسی</a>
            <?php endif; ?>
            <?php if ($can('audit.view')): ?>
              <a class="<?= $nav === 'audit' ? 'active' : '' ?>" href="<?= e(url('/audit')) ?>">تاریخچه فعالیت</a>
            <?php endif; ?>
          </div>
        </details>
        <?php endif; ?>
      </nav>
      <div class="sidebar-foot">
        <span><?= e($user['name'] ?? '') ?> · build 5</span>
        <a href="<?= e(url('/logout')) ?>">خروج</a>
      </div>
    </aside>
    <div class="main">
      <header class="topbar">
        <h1><?= e($title ?? '') ?></h1>
        <div class="meta">
          <span class="role-chip"><?= e($user['role'] ?? '') ?></span>
        </div>
      </header>
      <main class="content">
        <?php if ($flash): ?>
          <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php require __DIR__ . '/' . $name . '.php'; ?>
      </main>
    </div>
  </div>
<?php endif; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=5"></script>
</body>
</html>
