<?php /** @var array $dealer */ /** @var int $balance */ /** @var int $overdue */ /** @var array $orders */ /** @var array $circulars */ ?>
<div class="cards">
  <div class="card"><span>کد استعلام</span><strong><?= e((string) $dealer['code']) ?></strong></div>
  <div class="card"><span><?= e($balance > 0 ? 'بدهکار شما' : ($balance < 0 ? 'بستانکار شما' : 'حساب تسویه')) ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
  <div class="card"><span>سقف اعتبار</span><strong><?= e(money((int) $dealer['credit_limit'])) ?></strong></div>
  <div class="card"><span>بدهی سررسیدشده</span><strong><?= e(money($overdue)) ?></strong></div>
  <a class="card" href="<?= e(url('/rep/circulars')) ?>"><span>بخشنامه دیده‌نشده</span><strong><?= e(fa_digits((string) ($circulars['c'] ?? 0))) ?></strong></a>
</div>
<p>مدل همکاری: <?= e(dealer_sale_models()[$dealer['sale_model']] ?? '') ?> — وضعیت <?= e(dealer_statuses()[$dealer['status']] ?? '') ?></p>
<h2>آخرین سفارش‌ها</h2>
<ul><?php foreach ($orders as $order): ?><li><?= e((string) $order['number']) ?> — <?= e(money((int) $order['total'])) ?> — <?= e(dealer_order_statuses()[$order['status']] ?? '') ?></li><?php endforeach; ?></ul>
