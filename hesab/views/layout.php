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
$fy = null;
try {
    if (!$isAuthPage && Installer::isInstalled()) {
        $fy = Database::query('SELECT title FROM fiscal_years WHERE is_active=1 LIMIT 1')->fetch() ?: null;
    }
} catch (Throwable $e) {
    $fy = null;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $appName) ?> — <?= e($appName) ?></title>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=6">
</head>
<body class="<?= $isAuthPage ? 'auth-body' : 'win-body' ?>">
<?php if ($isAuthPage): ?>
  <?php require __DIR__ . '/' . $name . '.php'; ?>
<?php else: ?>
<div class="win-app" id="win-app">
  <div class="win-titlebar">
    <div class="win-title">
      <span class="win-app-ico" aria-hidden="true"></span>
      <strong><?= e($appName) ?></strong>
      <span class="win-sep">—</span>
      <span><?= e($title ?? 'پنجره اصلی') ?></span>
    </div>
    <div class="win-caption">
      <a class="win-cap-btn" href="<?= e(url('/logout')) ?>" title="خروج">×</a>
    </div>
  </div>

  <nav class="win-menubar" id="win-menubar">
    <div class="win-menu">
      <button type="button" class="win-menu-btn">پرونده</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/')) ?>">پنجره اصلی</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/logout')) ?>">خروج از برنامه</a>
      </div>
    </div>

    <?php if ($can('accounts.manage') || $can('tafsili.manage') || $can('fiscal.manage')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">تعاریف</button>
      <div class="win-menu-drop">
        <?php if ($can('accounts.manage')): ?><a href="<?= e(url('/accounts')) ?>">کدینگ حساب‌ها</a><?php endif; ?>
        <?php if ($can('tafsili.manage')): ?>
          <a href="<?= e(url('/tafsili')) ?>">تفصیلی شناور</a>
          <a href="<?= e(url('/dimensions')) ?>">ابعاد تحلیلی</a>
        <?php endif; ?>
        <?php if ($can('fiscal.manage')): ?><a href="<?= e(url('/fiscal')) ?>">سال و دوره مالی</a><?php endif; ?>
        <?php if ($can('accounts.manage')): ?>
          <div class="win-menu-sep"></div>
          <a href="<?= e(url('/settings/accounting')) ?>">تنظیمات حسابداری</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('vouchers.create')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">عملیات</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/vouchers/create')) ?>">ثبت سند حسابداری</a>
        <a href="<?= e(url('/vouchers')) ?>">فهرست اسناد</a>
        <a href="<?= e(url('/invoices')) ?>">فاکتور فروش</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('treasury.manage')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">خزانه‌داری</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/treasury')) ?>">بانک / صندوق / چک</a>
        <a href="<?= e(url('/treasury')) ?>#receive">رسید دریافت</a>
        <a href="<?= e(url('/treasury')) ?>#pay">رسید پرداخت</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/reports/bank-reconcile')) ?>">مغایرت بانکی</a>
        <a href="<?= e(url('/reports/checks')) ?>">چک‌های دریافتنی و پرداختنی</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('reports.view')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">گزارش‌ها</button>
      <div class="win-menu-drop win-menu-wide">
        <a href="<?= e(url('/reports')) ?>">مرکز گزارش‌ها</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/trial-balance')) ?>">تراز آزمایشی</a>
        <a href="<?= e(url('/reports/balance-sheet')) ?>">ترازنامه</a>
        <a href="<?= e(url('/reports/pl')) ?>">سود و زیان</a>
        <a href="<?= e(url('/ledger')) ?>">دفتر معین / مرور حساب</a>
        <a href="<?= e(url('/reports/journal')) ?>">دفتر روزنامه</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/reports/nature-violations')) ?>">اسناد خلاف ماهیت</a>
        <a href="<?= e(url('/reports/share')) ?>">سهم‌بری / پروژه</a>
        <a href="<?= e(url('/reports/charts')) ?>">نمودار دوره‌ها</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('moadian.manage') || $can('users.manage') || $can('audit.view')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">سیستم</button>
      <div class="win-menu-drop">
        <?php if ($can('moadian.manage')): ?><a href="<?= e(url('/moadian')) ?>">سامانه مودیان</a><?php endif; ?>
        <?php if ($can('users.manage')): ?><a href="<?= e(url('/users')) ?>">کاربران و دسترسی</a><?php endif; ?>
        <?php if ($can('audit.view')): ?><a href="<?= e(url('/audit')) ?>">تاریخچه فعالیت</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="win-menu">
      <button type="button" class="win-menu-btn">راهنما</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/')) ?>">درباره <?= e($appName) ?></a>
      </div>
    </div>
  </nav>

  <div class="win-toolbar">
    <a class="tb-btn" href="<?= e(url('/')) ?>" title="پنجره اصلی">خانه</a>
    <?php if ($can('vouchers.create')): ?>
      <a class="tb-btn primary" href="<?= e(url('/vouchers/create')) ?>">سند جدید</a>
      <a class="tb-btn" href="<?= e(url('/vouchers')) ?>">اسناد</a>
    <?php endif; ?>
    <?php if ($can('accounts.manage')): ?>
      <a class="tb-btn" href="<?= e(url('/accounts')) ?>">کدینگ</a>
    <?php endif; ?>
    <?php if ($can('treasury.manage')): ?>
      <a class="tb-btn" href="<?= e(url('/treasury')) ?>">خزانه</a>
    <?php endif; ?>
    <?php if ($can('reports.view')): ?>
      <span class="tb-sep"></span>
      <a class="tb-btn" href="<?= e(url('/trial-balance')) ?>">تراز</a>
      <a class="tb-btn" href="<?= e(url('/reports')) ?>">گزارش‌ها</a>
    <?php endif; ?>
    <span class="tb-spacer"></span>
    <span class="tb-info"><?= e($user['name'] ?? '') ?></span>
  </div>

  <div class="win-body">
    <aside class="win-tree" id="win-tree">
      <div class="win-tree-hd">کاوشگر برنامه</div>
      <ul class="tree">
        <li class="<?= $nav === 'dashboard' ? 'on' : '' ?>"><a href="<?= e(url('/')) ?>"><span class="t-ico">▣</span> پنجره اصلی</a></li>

        <?php if ($can('accounts.manage') || $can('tafsili.manage') || $can('fiscal.manage')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">تعاریف پایه</button>
          <ul>
            <?php if ($can('accounts.manage')): ?><li class="<?= $nav === 'accounts' ? 'on' : '' ?>"><a href="<?= e(url('/accounts')) ?>">کدینگ حساب‌ها</a></li><?php endif; ?>
            <?php if ($can('tafsili.manage')): ?>
              <li class="<?= $nav === 'tafsili' ? 'on' : '' ?>"><a href="<?= e(url('/tafsili')) ?>">تفصیلی شناور</a></li>
              <li class="<?= $nav === 'dimensions' ? 'on' : '' ?>"><a href="<?= e(url('/dimensions')) ?>">ابعاد تحلیلی</a></li>
            <?php endif; ?>
            <?php if ($can('fiscal.manage')): ?><li class="<?= $nav === 'fiscal' ? 'on' : '' ?>"><a href="<?= e(url('/fiscal')) ?>">سال و دوره مالی</a></li><?php endif; ?>
            <?php if ($can('accounts.manage')): ?><li class="<?= $nav === 'settings_acc' ? 'on' : '' ?>"><a href="<?= e(url('/settings/accounting')) ?>">تنظیمات حسابداری</a></li><?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('vouchers.create')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">حسابداری</button>
          <ul>
            <li class="<?= $nav === 'vouchers' ? 'on' : '' ?>"><a href="<?= e(url('/vouchers')) ?>">اسناد حسابداری</a></li>
            <li><a href="<?= e(url('/vouchers/create')) ?>">ثبت سند جدید</a></li>
            <li class="<?= $nav === 'invoices' ? 'on' : '' ?>"><a href="<?= e(url('/invoices')) ?>">فاکتور فروش</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('treasury.manage')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">خزانه‌داری</button>
          <ul>
            <li class="<?= $nav === 'treasury' ? 'on' : '' ?>"><a href="<?= e(url('/treasury')) ?>">بانک / صندوق / چک</a></li>
            <li class="<?= $nav === 'bank_reconcile' ? 'on' : '' ?>"><a href="<?= e(url('/reports/bank-reconcile')) ?>">مغایرت بانکی</a></li>
            <li><a href="<?= e(url('/reports/checks')) ?>">اسناد چک</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('reports.view')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">گزارش‌ها</button>
          <ul>
            <li class="<?= $nav === 'reports' ? 'on' : '' ?>"><a href="<?= e(url('/reports')) ?>">مرکز گزارش‌ها</a></li>
            <li><a href="<?= e(url('/trial-balance')) ?>">تراز آزمایشی</a></li>
            <li><a href="<?= e(url('/reports/balance-sheet')) ?>">ترازنامه</a></li>
            <li><a href="<?= e(url('/reports/pl')) ?>">سود و زیان</a></li>
            <li><a href="<?= e(url('/ledger')) ?>">دفتر معین</a></li>
            <li><a href="<?= e(url('/reports/journal')) ?>">دفتر روزنامه</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('moadian.manage') || $can('users.manage') || $can('audit.view')): ?>
        <li class="branch">
          <button type="button" class="branch-toggle">سیستم</button>
          <ul>
            <?php if ($can('moadian.manage')): ?><li class="<?= $nav === 'moadian' ? 'on' : '' ?>"><a href="<?= e(url('/moadian')) ?>">سامانه مودیان</a></li><?php endif; ?>
            <?php if ($can('users.manage')): ?><li class="<?= $nav === 'users' ? 'on' : '' ?>"><a href="<?= e(url('/users')) ?>">کاربران</a></li><?php endif; ?>
            <?php if ($can('audit.view')): ?><li class="<?= $nav === 'audit' ? 'on' : '' ?>"><a href="<?= e(url('/audit')) ?>">تاریخچه فعالیت</a></li><?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </aside>

    <section class="win-workspace">
      <div class="win-tabstrip">
        <div class="win-tab active"><?= e($title ?? 'پنجره') ?></div>
      </div>
      <main class="content win-content">
        <?php if ($flash): ?>
          <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php require __DIR__ . '/' . $name . '.php'; ?>
      </main>
    </section>
  </div>

  <footer class="win-statusbar">
    <span class="sb-pane">آماده</span>
    <span class="sb-pane">کاربر: <?= e($user['name'] ?? '') ?> (<?= e($user['role'] ?? '') ?>)</span>
    <span class="sb-pane"><?= e($fy['title'] ?? 'سال مالی نامشخص') ?></span>
    <span class="sb-pane"><a href="<?= e(url('/m?mobile=1')) ?>">نسخه موبایل</a></span>
    <span class="sb-pane sb-end">build 7 · ویندوزی</span>
  </footer>
</div>
<?php endif; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=6"></script>
</body>
</html>
