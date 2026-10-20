<?php
$links = [
    '/rep' => 'پنل',
    '/rep/prices' => 'قیمت من',
    '/rep/orders' => 'سفارش من',
    '/rep/account' => 'حساب و چک',
    '/rep/circulars' => 'بخشنامه‌ها',
    '/rep/requests' => 'درخواست‌ها',
];
$current = rtrim(request_path(), '/') ?: '/';
?>
<nav class="acct-nav" aria-label="نمایندگی">
  <?php foreach ($links as $href => $label): ?>
    <a href="<?= e(url($href)) ?>"<?= $current === $href ? ' class="is-current"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>
