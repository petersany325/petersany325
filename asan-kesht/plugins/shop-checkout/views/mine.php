<?php /** @var array $orders */ ?>
<section class="section">
  <div class="wrap">
    <p class="kicker">حساب</p>
    <h1>سفارش‌های فروشگاه</h1>
    <?php if ($orders === []): ?>
      <p>هنوز سفارشی نیست.</p>
    <?php endif; ?>
    <?php foreach ($orders as $order): ?>
      <article class="cart-line">
        <div>
          <strong><?= e((string) $order['number']) ?></strong>
          <div class="muted"><?= e(jalali_date((string) $order['created_at'])) ?> — <?= e(shop_order_statuses()[(string) $order['status']] ?? (string) $order['status']) ?></div>
          <?php foreach ($order['items'] as $item): ?>
            <div><?= e((string) $item['name']) ?> × <?= e(fa_digits((string) $item['qty'])) ?></div>
          <?php endforeach; ?>
        </div>
        <strong><?= e(money((int) $order['total'])) ?></strong>
      </article>
    <?php endforeach; ?>
  </div>
</section>
