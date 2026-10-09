<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
/** @var bool $isEmbed */
/** @var bool $isShell */

$isAuthPage = in_array($name, ['login', 'install'], true);
$isEmbed = !empty($isEmbed);
$isShell = !empty($isShell);
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

// Initial URL for workspace first tab (preserve query except embed)
$initialPath = request_path();
$qs = $_GET;
unset($qs['embed']);
$initialUrl = url($initialPath);
if ($qs) {
    $initialUrl .= '?' . http_build_query($qs);
}
$initialTitle = $title ?? 'پنجره';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $appName) ?> — <?= e($appName) ?></title>
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=8">
</head>
<body class="<?= $isAuthPage ? 'auth-body' : ($isEmbed ? 'embed-body' : 'win-body') ?>">
<?php if ($isAuthPage): ?>
  <?php require __DIR__ . '/' . $name . '.php'; ?>

<?php elseif ($isEmbed): ?>
  <main class="content win-content embed-content">
    <?php if ($flash): ?>
      <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php require __DIR__ . '/' . $name . '.php'; ?>
  </main>

<?php else: ?>
<div class="win-app" id="win-app">
  <div class="win-titlebar">
    <div class="win-title">
      <span class="win-app-ico" aria-hidden="true"></span>
      <strong><?= e($appName) ?></strong>
      <span class="win-sep">—</span>
      <span id="win-title-label"><?= e($initialTitle) ?></span>
    </div>
    <div class="win-caption">
      <a class="win-cap-btn" href="<?= e(url('/logout')) ?>" title="خروج" data-ws-bypass="1">×</a>
    </div>
  </div>

  <nav class="win-menubar" id="win-menubar">
    <div class="win-menu">
      <button type="button" class="win-menu-btn">پرونده</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/')) ?>" data-ws-title="پنجره اصلی">پنجره اصلی</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/logout')) ?>" data-ws-bypass="1">خروج از برنامه</a>
      </div>
    </div>

    <?php if ($can('accounts.manage') || $can('tafsili.manage') || $can('fiscal.manage')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">تعاریف</button>
      <div class="win-menu-drop">
        <?php if ($can('accounts.manage')): ?><a href="<?= e(url('/accounts')) ?>" data-ws-title="کدینگ حساب‌ها">کدینگ حساب‌ها</a><?php endif; ?>
        <?php if ($can('tafsili.manage')): ?>
          <a href="<?= e(url('/tafsili')) ?>" data-ws-title="تفصیلی شناور">تفصیلی شناور</a>
          <a href="<?= e(url('/dimensions')) ?>" data-ws-title="ابعاد تحلیلی">ابعاد تحلیلی</a>
        <?php endif; ?>
        <?php if ($can('fiscal.manage')): ?><a href="<?= e(url('/fiscal')) ?>" data-ws-title="سال و دوره مالی">سال و دوره مالی</a><?php endif; ?>
        <?php if ($can('accounts.manage')): ?>
          <div class="win-menu-sep"></div>
          <a href="<?= e(url('/settings/accounting')) ?>" data-ws-title="تنظیمات حسابداری">تنظیمات حسابداری</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('vouchers.create')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">عملیات</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/vouchers/create')) ?>" data-ws-title="ثبت سند حسابداری">ثبت سند حسابداری</a>
        <a href="<?= e(url('/vouchers')) ?>" data-ws-title="فهرست اسناد">فهرست اسناد</a>
        <a href="<?= e(url('/invoices')) ?>" data-ws-title="فاکتور فروش">فاکتور فروش</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('treasury.manage')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">خزانه‌داری</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/treasury')) ?>" data-ws-title="خزانه‌داری">بانک / صندوق / چک</a>
        <a href="<?= e(url('/treasury')) ?>#receive" data-ws-title="رسید دریافت">رسید دریافت</a>
        <a href="<?= e(url('/treasury')) ?>#pay" data-ws-title="رسید پرداخت">رسید پرداخت</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/reports/bank-reconcile')) ?>" data-ws-title="مغایرت بانکی">مغایرت بانکی</a>
        <a href="<?= e(url('/reports/checks')) ?>" data-ws-title="چک‌ها">چک‌های دریافتنی و پرداختنی</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('reports.view')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">گزارش‌ها</button>
      <div class="win-menu-drop win-menu-wide">
        <a href="<?= e(url('/reports')) ?>" data-ws-title="مرکز گزارش‌ها">مرکز گزارش‌ها</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/trial-balance')) ?>" data-ws-title="تراز آزمایشی">تراز آزمایشی</a>
        <a href="<?= e(url('/reports/balance-sheet')) ?>" data-ws-title="ترازنامه">ترازنامه</a>
        <a href="<?= e(url('/reports/pl')) ?>" data-ws-title="سود و زیان">سود و زیان</a>
        <a href="<?= e(url('/ledger')) ?>" data-ws-title="دفتر معین">دفتر معین / مرور حساب</a>
        <a href="<?= e(url('/reports/journal')) ?>" data-ws-title="دفتر روزنامه">دفتر روزنامه</a>
        <div class="win-menu-sep"></div>
        <a href="<?= e(url('/reports/nature-violations')) ?>" data-ws-title="خلاف ماهیت">اسناد خلاف ماهیت</a>
        <a href="<?= e(url('/reports/share')) ?>" data-ws-title="سهم‌بری">سهم‌بری / پروژه</a>
        <a href="<?= e(url('/reports/charts')) ?>" data-ws-title="نمودارها">نمودار دوره‌ها</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($can('moadian.manage') || $can('users.manage') || $can('audit.view')): ?>
    <div class="win-menu">
      <button type="button" class="win-menu-btn">سیستم</button>
      <div class="win-menu-drop">
        <?php if ($can('moadian.manage')): ?><a href="<?= e(url('/moadian')) ?>" data-ws-title="سامانه مودیان">سامانه مودیان</a><?php endif; ?>
        <?php if ($can('users.manage')): ?><a href="<?= e(url('/users')) ?>" data-ws-title="کاربران">کاربران و دسترسی</a><?php endif; ?>
        <?php if ($can('audit.view')): ?><a href="<?= e(url('/audit')) ?>" data-ws-title="تاریخچه">تاریخچه فعالیت</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="win-menu">
      <button type="button" class="win-menu-btn">راهنما</button>
      <div class="win-menu-drop">
        <a href="<?= e(url('/')) ?>" data-ws-title="درباره برنامه">درباره <?= e($appName) ?></a>
      </div>
    </div>
  </nav>

  <div class="win-toolbar">
    <a class="tb-btn" href="<?= e(url('/')) ?>" data-ws-title="پنجره اصلی" title="پنجره اصلی">خانه</a>
    <?php if ($can('vouchers.create')): ?>
      <a class="tb-btn primary" href="<?= e(url('/vouchers/create')) ?>" data-ws-title="ثبت سند حسابداری">سند جدید</a>
      <a class="tb-btn" href="<?= e(url('/vouchers')) ?>" data-ws-title="فهرست اسناد">اسناد</a>
    <?php endif; ?>
    <?php if ($can('accounts.manage')): ?>
      <a class="tb-btn" href="<?= e(url('/accounts')) ?>" data-ws-title="کدینگ حساب‌ها">کدینگ</a>
    <?php endif; ?>
    <?php if ($can('treasury.manage')): ?>
      <a class="tb-btn" href="<?= e(url('/treasury')) ?>" data-ws-title="خزانه‌داری">خزانه</a>
    <?php endif; ?>
    <?php if ($can('reports.view')): ?>
      <span class="tb-sep"></span>
      <a class="tb-btn" href="<?= e(url('/trial-balance')) ?>" data-ws-title="تراز آزمایشی">تراز</a>
      <a class="tb-btn" href="<?= e(url('/reports')) ?>" data-ws-title="مرکز گزارش‌ها">گزارش‌ها</a>
    <?php endif; ?>
    <span class="tb-spacer"></span>
    <button type="button" class="tb-btn" id="ws-close-tab" title="بستن زبانه فعلی">بستن زبانه</button>
    <span class="tb-info"><?= e($user['name'] ?? '') ?></span>
  </div>

  <div class="win-body">
    <aside class="win-tree" id="win-tree">
      <div class="win-tree-hd">کاوشگر برنامه</div>
      <ul class="tree">
        <li data-nav="dashboard"><a href="<?= e(url('/')) ?>" data-ws-title="پنجره اصلی"><span class="t-ico">▣</span> پنجره اصلی</a></li>

        <?php if ($can('accounts.manage') || $can('tafsili.manage') || $can('fiscal.manage')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">تعاریف پایه</button>
          <ul>
            <?php if ($can('accounts.manage')): ?><li data-nav="accounts"><a href="<?= e(url('/accounts')) ?>" data-ws-title="کدینگ حساب‌ها">کدینگ حساب‌ها</a></li><?php endif; ?>
            <?php if ($can('tafsili.manage')): ?>
              <li data-nav="tafsili"><a href="<?= e(url('/tafsili')) ?>" data-ws-title="تفصیلی شناور">تفصیلی شناور</a></li>
              <li data-nav="dimensions"><a href="<?= e(url('/dimensions')) ?>" data-ws-title="ابعاد تحلیلی">ابعاد تحلیلی</a></li>
            <?php endif; ?>
            <?php if ($can('fiscal.manage')): ?><li data-nav="fiscal"><a href="<?= e(url('/fiscal')) ?>" data-ws-title="سال و دوره مالی">سال و دوره مالی</a></li><?php endif; ?>
            <?php if ($can('accounts.manage')): ?><li data-nav="settings_acc"><a href="<?= e(url('/settings/accounting')) ?>" data-ws-title="تنظیمات حسابداری">تنظیمات حسابداری</a></li><?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('vouchers.create')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">حسابداری</button>
          <ul>
            <li data-nav="vouchers"><a href="<?= e(url('/vouchers')) ?>" data-ws-title="اسناد حسابداری">اسناد حسابداری</a></li>
            <li><a href="<?= e(url('/vouchers/create')) ?>" data-ws-title="ثبت سند جدید">ثبت سند جدید</a></li>
            <li data-nav="invoices"><a href="<?= e(url('/invoices')) ?>" data-ws-title="فاکتور فروش">فاکتور فروش</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('treasury.manage')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">خزانه‌داری</button>
          <ul>
            <li data-nav="treasury"><a href="<?= e(url('/treasury')) ?>" data-ws-title="خزانه‌داری">بانک / صندوق / چک</a></li>
            <li data-nav="bank_reconcile"><a href="<?= e(url('/reports/bank-reconcile')) ?>" data-ws-title="مغایرت بانکی">مغایرت بانکی</a></li>
            <li><a href="<?= e(url('/reports/checks')) ?>" data-ws-title="اسناد چک">اسناد چک</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('reports.view')): ?>
        <li class="branch open">
          <button type="button" class="branch-toggle">گزارش‌ها</button>
          <ul>
            <li data-nav="reports"><a href="<?= e(url('/reports')) ?>" data-ws-title="مرکز گزارش‌ها">مرکز گزارش‌ها</a></li>
            <li><a href="<?= e(url('/trial-balance')) ?>" data-ws-title="تراز آزمایشی">تراز آزمایشی</a></li>
            <li><a href="<?= e(url('/reports/balance-sheet')) ?>" data-ws-title="ترازنامه">ترازنامه</a></li>
            <li><a href="<?= e(url('/reports/pl')) ?>" data-ws-title="سود و زیان">سود و زیان</a></li>
            <li><a href="<?= e(url('/ledger')) ?>" data-ws-title="دفتر معین">دفتر معین</a></li>
            <li><a href="<?= e(url('/reports/journal')) ?>" data-ws-title="دفتر روزنامه">دفتر روزنامه</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($can('moadian.manage') || $can('users.manage') || $can('audit.view')): ?>
        <li class="branch">
          <button type="button" class="branch-toggle">سیستم</button>
          <ul>
            <?php if ($can('moadian.manage')): ?><li data-nav="moadian"><a href="<?= e(url('/moadian')) ?>" data-ws-title="سامانه مودیان">سامانه مودیان</a></li><?php endif; ?>
            <?php if ($can('users.manage')): ?><li data-nav="users"><a href="<?= e(url('/users')) ?>" data-ws-title="کاربران">کاربران</a></li><?php endif; ?>
            <?php if ($can('audit.view')): ?><li data-nav="audit"><a href="<?= e(url('/audit')) ?>" data-ws-title="تاریخچه">تاریخچه فعالیت</a></li><?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </aside>

    <section class="win-workspace">
      <div class="win-tabstrip" id="win-tabstrip" role="tablist" aria-label="زبانه‌های باز"></div>
      <div class="win-tab-panels" id="win-tab-panels"></div>
    </section>
  </div>

  <footer class="win-statusbar">
    <span class="sb-pane" id="ws-status">آماده</span>
    <span class="sb-pane">کاربر: <?= e($user['name'] ?? '') ?> (<?= e($user['role'] ?? '') ?>)</span>
    <span class="sb-pane"><?= e($fy['title'] ?? 'سال مالی نامشخص') ?></span>
    <span class="sb-pane"><a href="<?= e(url('/m?mobile=1')) ?>" data-ws-bypass="1">نسخه موبایل</a></span>
    <span class="sb-pane sb-end">build 8 · چندزبانه</span>
  </footer>
</div>
<script>
window.HESAB_WS = {
  initialUrl: <?= json_encode($initialUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  initialTitle: <?= json_encode($initialTitle, JSON_UNESCAPED_UNICODE) ?>,
  basePath: <?= json_encode(base_path(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
  appName: <?= json_encode($appName, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<?php endif; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=8"></script>
</body>
</html>
