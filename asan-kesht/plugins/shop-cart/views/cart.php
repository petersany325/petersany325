<?php
/** @var array $lines */
/** @var array $totals */
/** @var bool $checkout */
/** @var int $max */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="kicker">فروشگاه</p>
    <h1>سبد خرید</h1>
  </div>
</section>
<section class="section">
  <div class="wrap">
    <?php if ($lines === []): ?>
      <p>سبد خالی است.</p>
      <p><a class="btn" href="<?= e(url('/machines')) ?>">مشاهده ماشین‌ها</a></p>
    <?php else: ?>
      <form method="post" action="<?= e(url('/cart/update')) ?>">
        <?= csrf_field() ?>
        <?php foreach ($lines as $line): ?>
          <?php $machine = $line['machine']; ?>
          <div class="cart-line">
            <div>
              <strong><?= e((string) $machine['name']) ?></strong>
              <div class="muted"><?= e((string) $machine['code']) ?> — <?= e(money((int) $line['price'])) ?></div>
            </div>
            <label>تعداد
              <input name="qty[<?= e((string) $machine['id']) ?>]" inputmode="numeric" value="<?= e((string) $line['qty']) ?>">
            </label>
            <strong><?= e(money((int) $line['line'])) ?></strong>
          </div>
        <?php endforeach; ?>
        <button class="btn" type="submit">به‌روزرسانی تعداد</button>
      </form>
      <?php foreach ($lines as $line): ?>
        <form class="cart-add" method="post" action="<?= e(url('/cart/remove')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="machine_id" value="<?= e((string) $line['machine']['id']) ?>">
          <button class="btn btn-ghost" type="submit">حذف <?= e((string) $line['machine']['name']) ?></button>
        </form>
      <?php endforeach; ?>
      <dl class="totals">
        <div><dt>جمع کالا</dt><dd><?= e(money((int) $totals['subtotal'])) ?></dd></div>
        <div><dt>ارسال</dt><dd><?= e(money((int) $totals['shipping'])) ?></dd></div>
        <div><dt>مالیات</dt><dd><?= e(money((int) $totals['tax'])) ?></dd></div>
        <div><dt>قابل پرداخت</dt><dd><?= e(money((int) $totals['total'])) ?></dd></div>
      </dl>
      <p class="muted">حداکثر هر مدل <?= e(fa_digits((string) $max)) ?> عدد است. پرداخت آنلاین در این صفحه انجام نمی‌شود.</p>
      <?php if ($checkout): ?>
        <p><a class="btn" href="<?= e(url('/checkout')) ?>">ادامه ثبت سفارش</a></p>
      <?php else: ?>
        <p>ثبت سفارش خاموش است.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
