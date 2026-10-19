<?php
/** @var array $thanks */
/** @var string $method */
/** @var string $note */
?>
<section class="section">
  <div class="wrap">
    <p class="kicker">فروشگاه</p>
    <h1>سفارش ثبت شد</h1>
    <p class="lead">شماره <?= e((string) ($thanks['number'] ?? '')) ?> — <?= e(money((int) ($thanks['total'] ?? 0))) ?></p>
    <p>روش پرداخت: <?= e($method) ?>. مبلغ از این صفحه برداشته نمی‌شود.</p>
    <?php if ($note !== ''): ?><p class="note"><?= e($note) ?></p><?php endif; ?>
    <p><a class="btn" href="<?= e(url('/account/orders')) ?>">پیگیری در حساب</a></p>
  </div>
</section>
