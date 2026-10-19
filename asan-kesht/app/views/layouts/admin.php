<?php
/** @var App $app */
/** @var string $content */
/** @var string $title */
$user = $app->auth->user();
$groups = admin_nav_groups($app);
$current = request_path();
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
  <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>?v=<?= e((string) (@filemtime($app->root . '/assets/css/admin.css') ?: 1)) ?>">
</head>
<body>
  <div class="shell">
    <aside class="side">
      <a class="side-brand" href="<?= e(url('/admin')) ?>"><?= ui_icon('home') ?><span>آسان‌کشت</span></a>
      <p class="side-user"><?= e((string) ($user['name'] ?? '')) ?></p>
      <nav class="side-nav">
        <?php foreach ($groups as $group): ?>
          <details class="side-group" open>
            <summary><?= e($group['name']) ?></summary>
            <?php foreach ($group['items'] as $item): ?>
              <?php $href = (string) $item['href']; ?>
              <a href="<?= e(url($href)) ?>"<?= $current === rtrim($href, '/') ? ' class="is-current"' : '' ?>><?= ui_icon(admin_glyph($href)) ?><span><?= e((string) $item['label']) ?></span></a>
            <?php endforeach; ?>
          </details>
        <?php endforeach; ?>
        <a class="side-site" href="<?= e(url('/')) ?>"><?= ui_icon('chevron') ?><span>نمایش سایت</span></a>
      </nav>
      <form method="post" action="<?= e(url('/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit">خروج</button>
      </form>
    </aside>
    <section class="main">
      <button class="nav-toggle" type="button">منوی کارتابل</button>
      <h1><?= e($title ?? '') ?></h1>
      <?php foreach (take_flashes() as $flash): ?>
        <p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
      <?php endforeach; ?>
      <?= $content ?>
    </section>
  </div>
  <script>
    (function () {
      var shell = document.querySelector('.shell');
      var narrow = window.matchMedia('(max-width: 800px)');
      function fold() {
        document.querySelectorAll('.side-group').forEach(function (group) {
          group.open = !narrow.matches || !!group.querySelector('.is-current');
        });
      }
      fold();
      narrow.addEventListener('change', function () {
        if (shell) shell.classList.remove('is-nav');
        fold();
      });
      var toggle = document.querySelector('.nav-toggle');
      if (toggle) {
        toggle.addEventListener('click', function () {
          if (shell) shell.classList.toggle('is-nav');
        });
      }
    })();
  </script>
</body>
</html>
