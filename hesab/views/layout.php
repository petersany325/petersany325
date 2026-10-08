<?php
/** @var string $name */
/** @var string $title */
/** @var string|null $nav */
/** @var array|null $flash */
/** @var array|null $user */
/** @var string $appName */
$isAuthPage = in_array($name, ['login', 'install'], true);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $appName) ?> | <?= e($appName) ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
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
        <a class="<?= ($nav ?? '') === 'dashboard' ? 'active' : '' ?>" href="/"><span>داشبورد</span></a>
        <a class="<?= ($nav ?? '') === 'accounts' ? 'active' : '' ?>" href="/accounts"><span>کدینگ حساب‌ها</span></a>
        <a class="<?= ($nav ?? '') === 'vouchers' ? 'active' : '' ?>" href="/vouchers"><span>اسناد حسابداری</span></a>
        <a class="<?= ($nav ?? '') === 'ledger' ? 'active' : '' ?>" href="/ledger"><span>دفتر حساب</span></a>
        <a class="<?= ($nav ?? '') === 'trial' ? 'active' : '' ?>" href="/trial-balance"><span>تراز آزمایشی</span></a>
        <a class="<?= ($nav ?? '') === 'parties' ? 'active' : '' ?>" href="/parties"><span>طرف‌حساب‌ها</span></a>
        <a class="<?= ($nav ?? '') === 'invoices' ? 'active' : '' ?>" href="/invoices"><span>فاکتور فروش</span></a>
      </nav>
    </aside>
    <div class="main">
      <header class="topbar">
        <h1><?= e($title ?? '') ?></h1>
        <div class="meta">
          <span><?= e($user['name'] ?? '') ?></span>
          <a class="btn ghost" href="/logout">خروج</a>
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
<script src="/assets/js/app.js"></script>
</body>
</html>
