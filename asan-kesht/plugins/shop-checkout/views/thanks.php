<?php
/** @var array $thanks */
/** @var string $method */
/** @var string $note */
/** @var bool $paid */
/** @var bool $online */
/** @var int $orderId */
$paid = $paid ?? false;
$online = $online ?? false;
$orderId = $orderId ?? 0;
?>
<section class="section">
  <div class="wrap">
    <p class="kicker">فروشگاه</p>
    <h1><?= $paid ? 'پرداخت تأیید شد' : 'سفارش ثبت شد' ?></h1>
    <p class="lead">شماره <?= e((string) ($thanks['number'] ?? '')) ?> — <?= e(money((int) ($thanks['total'] ?? 0))) ?></p>
    <?php if ($paid): ?>
      <p>روش پرداخت: <?= e($method) ?>. بانک پرداخت را تأیید کرد و این خرید در فاکتور فروش و سود و زیان شناسایی شد.</p>
    <?php elseif ($online): ?>
      <p>روش پرداخت: <?= e($method) ?>. سفارش مانده است و هنوز تأیید بانک ندارد. از خریدهای من دوباره به درگاه بروید.</p>
      <?php if ($orderId > 0): ?>
        <p><a class="btn" href="<?= e(url('/account/orders/' . (string) $orderId . '/pay')) ?>">ادامه پرداخت</a></p>
      <?php endif; ?>
    <?php else: ?>
      <p>روش پرداخت: <?= e($method) ?>. این خرید پس از تأیید سفارش در فروش شناسایی می‌شود.</p>
    <?php endif; ?>
    <?php if ($note !== ''): ?><p class="note"><?= e($note) ?></p><?php endif; ?>
    <p><a class="btn" href="<?= e(url('/account/orders')) ?>">پیگیری در حساب</a></p>
  </div>
</section>
