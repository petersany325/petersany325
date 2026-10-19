<?php /** @var array $orders */ ?>
<section class="section">
  <div class="wrap">
    <p class="kicker">حساب</p>
    <h1>خریدهای من</h1>
    <?php if ($orders === []): ?>
      <p>هنوز سفارشی نیست.</p>
    <?php endif; ?>
    <?php foreach ($orders as $order): ?>
      <?php
        $pay = (string) ($order['payment_status'] ?? 'offline');
        $canPay = in_array($pay, ['pending', 'failed'], true)
            && shop_online_is((string) $order['payment_method'])
            && (string) $order['status'] !== 'cancelled';
      ?>
      <article class="cart-line">
        <div>
          <strong><?= e((string) $order['number']) ?></strong>
          <div class="muted"><?= e(jalali_date((string) $order['created_at'])) ?> — <?= e(shop_order_statuses()[(string) $order['status']] ?? (string) $order['status']) ?></div>
          <div class="muted">پرداخت: <?= e(shop_payment_label($pay)) ?></div>
          <?php foreach ($order['items'] as $item): ?>
            <div><?= e((string) $item['name']) ?> × <?= e(fa_digits((string) $item['qty'])) ?></div>
          <?php endforeach; ?>
          <?php if ($canPay): ?>
            <p><a class="btn" href="<?= e(url('/account/orders/' . (string) $order['id'] . '/pay')) ?>">پرداخت آنلاین</a></p>
          <?php endif; ?>
        </div>
        <strong><?= e(money((int) $order['total'])) ?></strong>
      </article>
    <?php endforeach; ?>
  </div>
</section>
