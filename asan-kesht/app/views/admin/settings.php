<?php /** @var array $items */ ?>
<p class="muted">تنظیم‌های سایت همین‌جا جمع شده‌اند. فرم ثبت‌نام، قالب، پیامک و سیاست نمایندگی هر کدام صفحه خود را دارند و فقط مدیر می‌تواند عوضشان کند.</p>
<div class="cards">
  <?php foreach ($items as $item): ?>
    <?php if (!is_array($item) || empty($item['href'])) { continue; } ?>
    <a class="card" href="<?= e(url((string) $item['href'])) ?>">
      <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
      <span><?= e((string) ($item['text'] ?? '')) ?></span>
    </a>
  <?php endforeach; ?>
</div>
