<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
$isAuthPage = in_array($name, ['login', 'install'], true);
$can = static fn(string $code): bool => Permission::can($user, $code);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $appName) ?> | <?= e($appName) ?></title>
  <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=3">
</head>
<body>
<?php if ($isAuthPage): ?>
  <?php require __DIR__ . '/' . $name . '.php'; ?>
<?php else: ?>
  <div class="app">
    <aside class="sidebar">
      <div class="brand">
        <strong><?= e($appName) ?></strong>
        <span>نرم‌افزار حسابداری تحت وب</span>
      </div>
      <nav class="nav">
        <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/')) ?>"><span>داشبورد</span></a>
        <?php if ($can('accounts.manage')): ?>
          <a class="<?= ($nav ?? '') === 'accounts' ? 'active' : '' ?>" href="<?= e(url('/accounts')) ?>"><span>کدینگ حساب‌ها</span></a>
        <?php endif; ?>
        <?php if ($can('tafsili.manage')): ?>
          <a class="<?= ($nav ?? '') === 'tafsili' ? 'active' : '' ?>" href="<?= e(url('/tafsili')) ?>"><span>تفصیلی شناور</span></a>
        <?php endif; ?>
        <?php if ($can('vouchers.create')): ?>
          <a class="<?= ($nav ?? '') === 'vouchers' ? 'active' : '' ?>" href="<?= e(url('/vouchers')) ?>"><span>اسناد حسابداری</span></a>
          <a class="<?= ($nav ?? '') === 'invoices' ? 'active' : '' ?>" href="<?= e(url('/invoices')) ?>"><span>فاکتور فروش</span></a>
        <?php endif; ?>
        <?php if ($can('treasury.manage')): ?>
          <a class="<?= ($nav ?? '') === 'treasury' ? 'active' : '' ?>" href="<?= e(url('/treasury')) ?>"><span>خزانه‌داری</span></a>
        <?php endif; ?>
        <?php if ($can('fiscal.manage')): ?>
          <a class="<?= ($nav ?? '') === 'fiscal' ? 'active' : '' ?>" href="<?= e(url('/fiscal')) ?>"><span>دوره مالی</span></a>
        <?php endif; ?>
        <?php if ($can('reports.view')): ?>
          <a class="<?= ($nav ?? '') === 'reports' ? 'active' : '' ?>" href="<?= e(url('/reports')) ?>"><span>گزارش‌ها</span></a>
        <?php endif; ?>
        <?php if ($can('moadian.manage')): ?>
          <a class="<?= ($nav ?? '') === 'moadian' ? 'active' : '' ?>" href="<?= e(url('/moadian')) ?>"><span>سامانه مودیان</span></a>
        <?php endif; ?>
        <?php if ($can('users.manage')): ?>
          <a class="<?= ($nav ?? '') === 'users' ? 'active' : '' ?>" href="<?= e(url('/users')) ?>"><span>کاربران</span></a>
        <?php endif; ?>
        <?php if ($can('audit.view')): ?>
          <a class="<?= ($nav ?? '') === 'audit' ? 'active' : '' ?>" href="<?= e(url('/audit')) ?>"><span>تاریخچه فعالیت</span></a>
        <?php endif; ?>
      </nav>
    </aside>
    <div class="main">
      <header class="topbar">
        <h1><?= e($title ?? '') ?></h1>
        <div class="meta">
          <span><?= e($user['name'] ?? '') ?></span>
          <a class="btn ghost" href="<?= e(url('/logout')) ?>">خروج</a>
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
<script src="<?= e(url('/assets/js/app.js')) ?>?v=3"></script>
</body>
</html>
