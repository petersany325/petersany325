<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
$isAuth = in_array($name, ['login', 'm_login'], true);
$nav = $nav ?? '';
$contentFile = __DIR__ . '/' . $name . '.php';
if (!is_file($contentFile) && str_starts_with($name, 'wrap_')) {
    $contentFile = __DIR__ . '/wrap.php';
    $wrapDesktop = substr($name, 5);
}
$can = static fn(string $code): bool => class_exists('Permission') && Permission::can($user ?? null, $code);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0b3d2e">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
  <link rel="apple-touch-icon" href="<?= e(url('/assets/mobile/icon-192.png')) ?>">
  <title><?= e($title ?? $appName) ?></title>
  <link rel="stylesheet" href="<?= e(url('/assets/css/mobile.css')) ?>?v=2">
</head>
<body class="m-body <?= $isAuth ? 'm-auth' : '' ?>" data-base="<?= e(base_path()) ?>">
<?php if ($isAuth): ?>
  <?php require is_file(__DIR__ . '/' . $name . '.php') ? __DIR__ . '/' . $name . '.php' : __DIR__ . '/login.php'; ?>
<?php else: ?>
  <div class="m-app">
    <header class="m-top">
      <div class="m-brand">
        <strong><?= e($appName) ?></strong>
        <span><?= e($title ?? '') ?></span>
      </div>
      <button type="button" class="m-icon-btn" id="m-menu-btn" aria-label="منو" aria-expanded="false" aria-controls="m-drawer">☰</button>
    </header>

    <div class="m-drawer-backdrop" id="m-drawer-backdrop" hidden></div>
    <aside class="m-drawer" id="m-drawer" hidden aria-hidden="true" role="dialog" aria-label="منوی برنامه">
      <div class="m-drawer-hd">
        <div>
          <strong><?= e($appName) ?></strong>
          <span><?= e($user['name'] ?? '') ?></span>
        </div>
        <button type="button" class="m-icon-btn m-drawer-close" id="m-drawer-close" aria-label="بستن">×</button>
      </div>
      <nav class="m-drawer-nav">
        <a href="<?= e(url('/m')) ?>" class="<?= $nav === 'dashboard' ? 'active' : '' ?>">خانه / داشبورد</a>
        <a href="<?= e(url('/m/vouchers')) ?>" class="<?= $nav === 'vouchers' ? 'active' : '' ?>">اسناد حسابداری</a>
        <a href="<?= e(url('/m/vouchers/create')) ?>" class="<?= $nav === 'create' ? 'active' : '' ?>">ثبت سند جدید</a>
        <a href="<?= e(url('/m/treasury')) ?>" class="<?= $nav === 'treasury' ? 'active' : '' ?>">خزانه‌داری</a>
        <?php if ($can('reports.view')): ?>
          <a href="<?= e(url('/m/reports')) ?>" class="<?= $nav === 'reports' ? 'active' : '' ?>">گزارش‌ها</a>
          <a href="<?= e(url('/m/trial-balance')) ?>">تراز آزمایشی</a>
        <?php endif; ?>
        <?php if ($can('accounts.manage')): ?>
          <a href="<?= e(url('/m/accounts')) ?>" class="<?= $nav === 'accounts' ? 'active' : '' ?>">کدینگ حساب‌ها</a>
        <?php endif; ?>
        <?php if ($can('tafsili.manage')): ?>
          <a href="<?= e(url('/m/tafsili')) ?>">تفصیلی شناور</a>
          <a href="<?= e(url('/parties')) ?>">طرف‌حساب‌ها</a>
        <?php endif; ?>
        <?php if ($can('cheques.manage') || $can('treasury.manage')): ?>
          <a href="<?= e(url('/cheques')) ?>">مدیریت چک‌ها</a>
        <?php endif; ?>
        <?php if ($can('visitors.manage') || $can('visitors.cartable')): ?>
          <a href="<?= e(url('/visitors')) ?>">ویزیتور / پخش</a>
        <?php endif; ?>
        <a href="<?= e(url('/m/more')) ?>" class="<?= $nav === 'more' ? 'active' : '' ?>">همه امکانات</a>
        <div class="m-drawer-sep"></div>
        <a href="<?= e(url('/?desktop=1')) ?>">نسخه ویندوزی</a>
        <a href="<?= e(url('/logout')) ?>">خروج از برنامه</a>
      </nav>
    </aside>

    <main class="m-main">
      <?php if ($flash): ?>
        <div class="m-flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
      <?php require is_file($contentFile) ? $contentFile : __DIR__ . '/placeholder.php'; ?>
    </main>

    <nav class="m-tabbar">
      <a class="<?= $nav === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/m')) ?>">
        <i>⌂</i><span>خانه</span>
      </a>
      <a class="<?= $nav === 'vouchers' ? 'active' : '' ?>" href="<?= e(url('/m/vouchers')) ?>">
        <i>≡</i><span>اسناد</span>
      </a>
      <a class="m-fab <?= $nav === 'create' ? 'active' : '' ?>" href="<?= e(url('/m/vouchers/create')) ?>">
        <i>+</i><span>سند</span>
      </a>
      <a class="<?= $nav === 'treasury' ? 'active' : '' ?>" href="<?= e(url('/m/treasury')) ?>">
        <i>﷼</i><span>خزانه</span>
      </a>
      <a class="<?= in_array($nav, ['more','reports','accounts'], true) ? 'active' : '' ?>" href="<?= e(url('/m/more')) ?>" id="m-tab-more">
        <i>⋯</i><span>بیشتر</span>
      </a>
    </nav>
  </div>
<?php endif; ?>
<script src="<?= e(url('/assets/js/mobile.js')) ?>?v=2"></script>
</body>
</html>
