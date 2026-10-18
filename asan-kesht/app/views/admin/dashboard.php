<?php /** @var array $cards */ ?>
<div class="cards">
  <?php foreach ($cards as $card): ?>
    <a class="card" href="<?= e(url((string) ($card['href'] ?? '/admin'))) ?>">
      <span><?= e((string) $card['label']) ?></span>
      <strong><?= e((string) $card['value']) ?></strong>
    </a>
  <?php endforeach; ?>
  <?php if ($cards === []): ?>
    <p>افزونه‌ها را فعال کنید تا اینجا خلاصه کار دیده شود.</p>
  <?php endif; ?>
</div>
