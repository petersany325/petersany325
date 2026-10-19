<?php
/** @var array $lines */
/** @var array $totals */
/** @var array $methods */
/** @var array $access */
/** @var string $name */
/** @var string $phone */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="kicker">فروشگاه</p>
    <h1>ثبت سفارش</h1>
    <p class="lead">مبلغ همین‌جا پرداخت نمی‌شود. روش را انتخاب کنید تا سفارش برای کارخانه بماند.</p>
  </div>
</section>
<section class="section">
  <div class="wrap contact-grid">
    <div>
      <?php if ($lines === []): ?>
        <p>سبد خالی است.</p>
      <?php else: ?>
        <ul class="side-list">
          <?php foreach ($lines as $line): ?>
            <li><?= e((string) $line['machine']['name']) ?> — <?= e(fa_digits((string) $line['qty'])) ?> — <?= e(money((int) $line['line'])) ?></li>
          <?php endforeach; ?>
        </ul>
        <dl class="totals">
          <div><dt>جمع کالا</dt><dd><?= e(money((int) $totals['subtotal'])) ?></dd></div>
          <div><dt>ارسال</dt><dd><?= e(money((int) $totals['shipping'])) ?></dd></div>
          <div><dt>مالیات</dt><dd><?= e(money((int) $totals['tax'])) ?></dd></div>
          <div><dt>قابل پرداخت</dt><dd><?= e(money((int) $totals['total'])) ?></dd></div>
        </dl>
      <?php endif; ?>
    </div>
    <div>
      <?php if (!$access['ok']): ?>
        <p><?= e((string) $access['message']) ?></p>
        <p><a class="btn" href="<?= e(url('/login')) ?>">ورود</a></p>
      <?php elseif ($lines === [] || $methods === []): ?>
        <p><?= $methods === [] ? 'هیچ روش پرداختی روشن نیست.' : 'اول یک مدل به سبد اضافه کنید.' ?></p>
      <?php else: ?>
        <form method="post" action="<?= e(url('/checkout')) ?>">
          <?= csrf_field() ?>
          <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
          <label>نام <input name="name" required maxlength="120" value="<?= e($name) ?>"></label>
          <label>موبایل <input name="phone" required inputmode="numeric" value="<?= e($phone) ?>"></label>
          <div class="pair">
            <label>استان <input name="province" required maxlength="80"></label>
            <label>شهر <input name="city" required maxlength="80"></label>
          </div>
          <label>نشانی <textarea name="address" required maxlength="300"></textarea></label>
          <label>توضیح <textarea name="note" maxlength="500"></textarea></label>
          <fieldset>
            <legend>روش پرداخت</legend>
            <?php $first = true; ?>
            <?php foreach ($methods as $key => $label): ?>
              <label class="check-line">
                <input type="radio" name="payment" value="<?= e((string) $key) ?>" <?= $first ? 'required' : '' ?> <?= $first ? 'checked' : '' ?>>
                <?= e((string) $label) ?>
              </label>
              <?php $first = false; ?>
            <?php endforeach; ?>
          </fieldset>
          <button class="btn" type="submit">ثبت سفارش</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
