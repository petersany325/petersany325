<?php /** @var array $user */ /** @var array $cards */ ?>
<section class="section">
  <div class="wrap">
    <p class="kicker">حساب کاربری</p>
    <h1><?= e((string) $user['name']) ?></h1>
    <p class="lead">نقش شما: <?= e($user['role'] === 'customer' ? 'مشتری' : ($user['role'] === 'employee' ? 'کارمند' : 'مدیر')) ?></p>
    <div class="facts">
      <?php foreach ($cards as $card): ?>
        <a class="line" href="<?= e(url((string) $card['href'])) ?>">
          <em><?= e((string) $card['value']) ?></em>
          <h3><?= e((string) $card['label']) ?></h3>
        </a>
      <?php endforeach; ?>
      <?php if ($cards === []): ?>
        <p>هنوز موردی برای نمایش نیست.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
