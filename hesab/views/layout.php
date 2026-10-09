<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
/** @var bool $isEmbed */
/** @var bool $isShell */

// Auto-heal multitab assets when cPanel upload is unavailable.
$_hesabAssetRoot = dirname(__DIR__);
$_hesabJs = $_hesabAssetRoot . '/assets/js/app.js';
$_hesabCss = $_hesabAssetRoot . '/assets/css/app.css';
$_hesabJsBody = @file_get_contents($_hesabJs);
if (!is_string($_hesabJsBody) || !str_contains($_hesabJsBody, 'Multi-document workspace')) {
    $_payload = gzdecode(base64_decode('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'));
    if ($_payload !== false) {
        if (!is_dir(dirname($_hesabJs))) {
            mkdir(dirname($_hesabJs), 0755, true);
        }
        file_put_contents($_hesabJs, $_payload);
    }
}
$_hesabCssBody = @file_get_contents($_hesabCss);
if (!is_string($_hesabCssBody) || !str_contains($_hesabCssBody, '.win-tab-panels')) {
    $_payload = gzdecode(base64_decode('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'));
    if ($_payload !== false) {
        if (!is_dir(dirname($_hesabCss))) {
            mkdir(dirname($_hesabCss), 0755, true);
        }
        file_put_contents($_hesabCss, $_payload);
    }
}
unset($_hesabAssetRoot, $_hesabJs, $_hesabCss, $_hesabJsBody, $_hesabCssBody, $_payload);

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
