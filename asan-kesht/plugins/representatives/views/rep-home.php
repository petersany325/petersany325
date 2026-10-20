<?php /** @var array $dealer */ /** @var int $balance */ /** @var int $overdue */ /** @var array $orders */ /** @var array $circulars */ /** @var int|null $earned */ /** @var int|null $potential */ ?>
<?php $earned = $earned ?? null; $potential = $potential ?? null; ?>
<?php require __DIR__ . '/rep-nav.php'; ?>
<div class="cards">
  <div class="card"><span>کد استعلام</span><strong><?= e((string) $dealer['code']) ?></strong></div>
  <div class="card"><span><?php if ($earned !== null): ?>حساب خرید<?php if ($balance > 0): ?>، بدهکار<?php elseif ($balance < 0): ?>، بستانکار<?php endif; ?><?php else: ?><?= e($balance > 0 ? 'بدهکار شما' : ($balance < 0 ? 'بستانکار شما' : 'حساب تسویه')) ?><?php endif; ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
  <div class="card"><span>سقف اعتبار</span><strong><?= e(money((int) $dealer['credit_limit'])) ?></strong></div>
  <div class="card"><span>بدهی سررسیدشده</span><strong><?= e(money($overdue)) ?></strong></div>
  <a class="card" href="<?= e(url('/rep/circulars')) ?>"><span>بخشنامه دیده‌نشده</span><strong><?= e(fa_digits((string) ($circulars['c'] ?? 0))) ?></strong></a>
  <?php if ($earned !== null): ?>
    <div class="card"><span>پورسانت قطعی</span><strong><?= e(money((int) $earned)) ?></strong></div>
    <div class="card"><span>سود بالقوه</span><strong><?= e(money((int) $potential)) ?></strong></div>
  <?php endif; ?>
</div>
<?php if ($earned !== null): ?>
  <p class="muted">حساب خرید همین مانده بالاست. پورسانت قطعی جداست. سود بالقوه قابل برداشت نیست و با پورسانت جمع نمی‌شود.</p>
<?php endif; ?>
<p>مدل همکاری: <?= e(dealer_sale_models()[$dealer['sale_model']] ?? '') ?> — وضعیت <?= e(dealer_statuses()[$dealer['status']] ?? '') ?></p>
<h2>آخرین سفارش‌ها</h2>
<ul><?php foreach ($orders as $order): ?><li><?= e((string) $order['number']) ?> — <?= e(money((int) $order['total'])) ?> — <?= e(dealer_order_statuses()[$order['status']] ?? '') ?></li><?php endforeach; ?></ul>
