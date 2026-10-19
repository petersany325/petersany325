<?php
/** @var App $app */
/** @var string $content */
/** @var string $title */
$user = $app->auth->user();
$siteName = 'آسان‌کشت';
$tagline = 'ماشین‌آلات صنعتی کشاورزی';
$phone = '';
$address = '';
$oxide = '#e2b000';
$ink = '#172033';
$paper = '#f4f6f8';
$dark = '#0e1c33';
if ($app->pluginEnabled('theme-settings')) {
    $siteName = setting($app, 'theme-settings', 'site_name', $siteName);
    $tagline = setting($app, 'theme-settings', 'tagline', $tagline);
    $phone = setting($app, 'theme-settings', 'phone', '');
    $address = setting($app, 'theme-settings', 'address', '');
    $oxide = hex_color(setting($app, 'theme-settings', 'color_oxide', $oxide), $oxide);
    $ink = hex_color(setting($app, 'theme-settings', 'color_ink', $ink), $ink);
    $paper = hex_color(setting($app, 'theme-settings', 'color_paper', $paper), $paper);
    $dark = hex_color(setting($app, 'theme-settings', 'color_dark', $dark), $dark);
}
$nav = $app->filter('site.nav', [['href' => '/', 'label' => 'خانه']]);
if (!is_array($nav)) {
    $nav = [];
}
$ready = $app->filter('site.nav.ready', $nav);
if (is_array($ready)) {
    $nav = $ready;
}
$menuEffect = 'fade';
$menuOpen = 'hover';
if ($app->pluginEnabled('menus')) {
    $effect = setting($app, 'menus', 'effect', 'fade');
    $open = setting($app, 'menus', 'open', 'hover');
    $menuEffect = in_array($effect, ['none', 'fade', 'slide'], true) ? $effect : 'fade';
    $menuOpen = in_array($open, ['hover', 'click'], true) ? $open : 'hover';
}
$description = $tagline . ' — ' . $siteName;
$editTools = [];
if ($user && (string) $user['role'] === 'admin') {
    $filteredTools = $app->filter('site.toolbar', []);
    $editTools = is_array($filteredTools) ? $filteredTools : [];
}
$aside = $app->filter('site.aside', []);
if (!is_array($aside)) {
    $aside = [];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? $siteName) ?> | <?= e($siteName) ?></title>
  <meta name="description" content="<?= e($description) ?>">
  <link rel="icon" href="<?= e(url('/assets/favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('/assets/css/site.css')) ?>?v=<?= e((string) (@filemtime($app->root . '/assets/css/site.css') ?: 1)) ?>">
  <style>:root{--oxide:<?= e($oxide) ?>;--ink:<?= e($ink) ?>;--paper:<?= e($paper) ?>;--dark:<?= e($dark) ?>;--on-oxide:<?= e(contrast_on($oxide)) ?>;--oxide-deep:<?= e(shade_hex($oxide, 0.78)) ?>;--paper-2:<?= e(shade_hex($paper, 0.94)) ?>;--dark-2:<?= e(shade_hex($dark, 1.55)) ?>;--field:<?= e(mix_hex($paper, '#e7eef6', 0.55)) ?>;--white:#ffffff;--brass:<?= e($oxide) ?>;--ink-soft:<?= e(mix_hex($ink, '#ffffff', 0.28)) ?>;--muted:<?= e(mix_hex($ink, '#ffffff', 0.46)) ?>}</style>
</head>
<body>
  <a class="skip" href="#main">رفتن به محتوا</a>
  <header class="top">
    <?php if ($editTools !== [] || ($user && (string) $user['role'] === 'admin')): ?>
      <nav class="editbar" aria-label="ویرایش">
        <div class="wrap editbar-row">
          <strong>ویرایش</strong>
          <?php foreach ($editTools as $tool): ?>
            <?php if (!is_array($tool)) { continue; } ?>
            <a href="<?= e(url((string) ($tool['href'] ?? '/'))) ?>"<?= !empty($tool['primary']) ? ' class="is-primary"' : '' ?>><?= e((string) ($tool['label'] ?? '')) ?></a>
          <?php endforeach; ?>
          <a href="<?= e(url('/admin')) ?>">پیشخوان</a>
          <a href="<?= e(url('/admin/settings')) ?>">تنظیمات</a>
        </div>
      </nav>
    <?php endif; ?>
    <div class="stripe" aria-hidden="true"></div>
    <div class="wrap bar">
      <a class="brand" href="<?= e(url('/')) ?>">
        <svg class="mark" viewBox="0 0 32 32" aria-hidden="true">
          <rect width="32" height="32" rx="4" fill="currentColor"/>
          <path d="M7 22h18M9 22c1.2-6 3-9 7-12 2.2 2.4 3.2 5.2 3.6 8" fill="none" stroke="#172033" stroke-width="1.7" stroke-linecap="round"/>
          <path d="M16 10l5 2.2-1.2 4.2" fill="none" stroke="#172033" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        <span>
          <strong><?= e($siteName) ?></strong>
          <small><?= e($tagline) ?></small>
        </span>
      </a>
      <button class="menu-btn" type="button" aria-expanded="false" aria-controls="site-nav">فهرست</button>
      <nav class="nav" id="site-nav" data-effect="<?= e($menuEffect) ?>" data-open="<?= e($menuOpen) ?>">
        <?php foreach ($nav as $item): ?>
          <?php if (!is_array($item)) { continue; } ?>
          <?php
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            $mega = !empty($item['mega']) && $children !== [];
            $href = nav_href((string) ($item['href'] ?? '/'));
            $label = (string) ($item['label'] ?? '');
          ?>
          <?php if ($children === []): ?>
            <a href="<?= e($href) ?>"<?= !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
          <?php else: ?>
            <div class="nav-item<?= $mega ? ' is-mega' : '' ?>">
              <a href="<?= e($href) ?>"<?= !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
              <button class="nav-more" type="button" aria-expanded="false">زیرمنو</button>
              <div class="nav-panel">
                <?php if ($mega): ?>
                  <?php
                    $columns = [];
                    $loose = [];
                    foreach ($children as $child) {
                        if (!is_array($child)) {
                            continue;
                        }
                        $grand = is_array($child['children'] ?? null) ? $child['children'] : [];
                        if ($grand !== []) {
                            $columns[] = $child;
                        } else {
                            $loose[] = $child;
                        }
                    }
                    if ($loose !== []) {
                        array_unshift($columns, ['label' => 'پیوندها', 'href' => '', 'description' => '', 'children' => $loose]);
                    }
                  ?>
                  <div class="wrap mega-grid">
                    <div class="mega-intro">
                      <strong><?= e($label) ?></strong>
                      <?php if ((string) ($item['description'] ?? '') !== ''): ?><p><?= e((string) $item['description']) ?></p><?php endif; ?>
                      <a href="<?= e($href) ?>">مشاهده</a>
                    </div>
                    <div class="mega-cols">
                      <?php foreach ($columns as $column): ?>
                        <div class="mega-col">
                          <?php if ((string) ($column['href'] ?? '') !== ''): ?>
                            <a class="mega-title" href="<?= e(nav_href((string) $column['href'])) ?>"><?= e((string) ($column['label'] ?? '')) ?></a>
                          <?php else: ?>
                            <strong class="mega-title"><?= e((string) ($column['label'] ?? '')) ?></strong>
                          <?php endif; ?>
                          <?php if ((string) ($column['description'] ?? '') !== ''): ?><p><?= e((string) $column['description']) ?></p><?php endif; ?>
                          <?php foreach ($column['children'] as $link): ?>
                            <?php if (!is_array($link) || (string) ($link['href'] ?? '') === '') { continue; } ?>
                            <a href="<?= e(nav_href((string) $link['href'])) ?>"<?= !empty($link['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>>
                              <span><?= e((string) ($link['label'] ?? '')) ?></span>
                              <?php if ((string) ($link['description'] ?? '') !== ''): ?><small><?= e((string) $link['description']) ?></small><?php endif; ?>
                            </a>
                          <?php endforeach; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="nav-sub">
                    <?php foreach ($children as $child): ?>
                      <?php if (!is_array($child)) { continue; } ?>
                      <?php $grand = is_array($child['children'] ?? null) ? $child['children'] : []; ?>
                      <div class="nav-sub-item">
                        <?php if ((string) ($child['href'] ?? '') !== ''): ?>
                          <a href="<?= e(nav_href((string) $child['href'])) ?>"<?= !empty($child['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e((string) ($child['label'] ?? '')) ?></a>
                        <?php else: ?>
                          <strong class="nav-sub-label"><?= e((string) ($child['label'] ?? '')) ?></strong>
                        <?php endif; ?>
                        <?php if ($grand !== []): ?>
                          <div class="nav-nested">
                            <?php foreach ($grand as $link): ?>
                              <?php if (!is_array($link) || (string) ($link['href'] ?? '') === '') { continue; } ?>
                              <a href="<?= e(nav_href((string) $link['href'])) ?>"<?= !empty($link['new_tab']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e((string) ($link['label'] ?? '')) ?></a>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach ($aside as $item): ?>
          <?php if (!is_array($item)) { continue; } ?>
          <a href="<?= e(url((string) ($item['href'] ?? '/'))) ?>"><?= e((string) ($item['label'] ?? '')) ?></a>
        <?php endforeach; ?>
        <?php if ($user): ?>
          <a href="<?= e(url(home_for_role((string) $user['role']))) ?>">حساب</a>
          <form method="post" action="<?= e(url('/logout')) ?>">
            <?= csrf_field() ?>
            <button class="nav-quote" type="submit">خروج</button>
          </form>
        <?php else: ?>
          <?php
            $loginMenu = [];
            if ($app->pluginEnabled('sms-login')) {
                $loginMenu[] = ['href' => '/login#sms', 'icon' => 'phone', 'label' => 'پیامک', 'text' => 'دریافت کد'];
                $loginMenu[] = ['href' => '/login#code', 'icon' => 'code', 'label' => 'کد', 'text' => 'تأیید پیامک'];
            }
            $loginMenu[] = ['href' => '/login#pass', 'icon' => 'key', 'label' => 'مدیر', 'text' => 'نام کاربری و رمز'];
            if ($app->pluginEnabled('register') && setting($app, 'register', 'open', '1') === '1') {
                $loginMenu[] = ['href' => '/register', 'icon' => 'plus', 'label' => 'ثبت‌نام', 'text' => 'ساخت حساب'];
            }
          ?>
          <div class="nav-item auth-nav">
            <a class="nav-quote auth-chip" href="<?= e(url('/login')) ?>"><?= ui_icon('user') ?><span>ورود نمایندگان</span></a>
            <button class="nav-more" type="button" aria-expanded="false" aria-label="روش ورود"><?= ui_icon('chevron') ?></button>
            <div class="nav-panel">
              <div class="nav-sub auth-menu">
                <?php foreach ($loginMenu as $item): ?>
                  <a href="<?= e(url($item['href'])) ?>">
                    <span class="auth-ico"><?= ui_icon($item['icon']) ?></span>
                    <span class="auth-copy"><b><?= e($item['label']) ?></b><small><?= e($item['text']) ?></small></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </nav>
    </div>
  </header>
  <main id="main">
    <?php foreach (take_flashes() as $flash): ?>
      <div class="wrap"><p class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
  <footer class="foot">
    <div class="wrap foot-grid">
      <div>
        <strong><?= e($siteName) ?></strong>
        <p><?= e($tagline) ?></p>
      </div>
      <div>
        <ul>
          <?php
            $footerLinks = [];
            $collectFooter = static function (array $nodes) use (&$collectFooter, &$footerLinks): void {
                foreach ($nodes as $node) {
                    if (!is_array($node)) {
                        continue;
                    }
                    if ((string) ($node['href'] ?? '') !== '') {
                        $footerLinks[] = $node;
                    }
                    $kids = $node['children'] ?? [];
                    if (is_array($kids)) {
                        $collectFooter($kids);
                    }
                }
            };
            $collectFooter($nav);
          ?>
          <?php foreach ($footerLinks as $item): ?>
            <li><a href="<?= e(nav_href((string) $item['href'])) ?>"><?= e((string) $item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <?php if ($phone !== ''): ?><p>تلفن: <?= e(fa_digits($phone)) ?></p><?php endif; ?>
        <?php if ($address !== ''): ?><p><?= e($address) ?></p><?php endif; ?>
        <p>ورود کارمندان و مشتریان با پیامک، بعد از اتصال پنل نیازپرداز.</p>
      </div>
    </div>
    <div class="wrap legal">© <?= e(fa_digits((string) gregorian_to_jalali((int) date('Y'), (int) date('n'), (int) date('j'))[0])) ?> <?= e($siteName) ?></div>
  </footer>
  <script src="<?= e(url('/assets/js/site.js')) ?>?v=<?= e((string) (@filemtime($app->root . '/assets/js/site.js') ?: 1)) ?>"></script>
</body>
</html>
