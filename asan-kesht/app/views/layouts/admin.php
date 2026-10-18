<?php
/** @var App $app */
/** @var string $content */
/** @var string $title */
$user = $app->auth->user();
$items = [
    ['href' => '/admin', 'label' => 'پیشخوان', 'roles' => ['admin', 'employee']],
    ['href' => '/admin/profile', 'label' => 'نمایه', 'roles' => ['admin', 'employee']],
    ['href' => '/admin/users', 'label' => 'کاربران', 'roles' => ['admin']],
    ['href' => '/admin/plugins', 'label' => 'افزونه‌ها', 'roles' => ['admin']],
];
$extra = $app->filter('admin.menu', []);
if (is_array($extra)) {
    $items = array_merge($items, $extra);
}
$role = $user['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'مدیریت') ?></title>
  <link rel="icon" href="<?= e(url('/assets/favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>">
</head>
<body>
  <div class="shell">
    <aside class="side">
      <a class="side-brand" href="<?= e(url('/admin')) ?>">آسان‌کشت</a>
      <p class="side-user"><?= e((string) ($user['name'] ?? '')) ?></p>
      <nav>
        <?php foreach ($items as $item): ?>
          <?php if (!in_array($role, $item['roles'], true)) { continue; } ?>
          <a href="<?= e(url((string) $item['href'])) ?>"><?= e((string) $item['label']) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url('/')) ?>">نمایش سایت</a>
      </nav>
      <form method="post" action="<?= e(url('/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit">خروج</button>
      </form>
    </aside>
    <section class="main">
      <h1><?= e($title ?? '') ?></h1>
      <?php foreach (take_flashes() as $flash): ?>
        <p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
      <?php endforeach; ?>
      <?= $content ?>
    </section>
  </div>
</body>
</html>
