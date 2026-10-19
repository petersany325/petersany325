<?php
/** @var array $groups */
/** @var array $enabled */
/** @var array $machines */
/** @var array $prices */
/** @var array $stocks */
/** @var array $values */
/** @var App $app */
?>
<p class="muted">هر گزینه زیر یک افزونه جداست. برداشتن تیک، همان بخش را خاموش می‌کند و عددها و سفارش‌ها را پاک نمی‌کند. کاتالوگ ماشین‌ها افزونه دیگری است و با این صفحه حذف نمی‌شود. خاموش کردن کل فروشگاه از صفحه افزونه‌هاست.</p>
<p><a href="<?= e(url('/admin/shop/orders')) ?>">سفارش‌های فروشگاه</a> · <a href="<?= e(url('/admin/plugins')) ?>">فهرست افزونه‌ها</a></p>
<form method="post" action="<?= e(url('/admin/shop')) ?>" class="stack">
  <?= csrf_field() ?>
  <?php foreach ($groups as $group): ?>
    <fieldset>
      <legend><?= e((string) $group['title']) ?></legend>
      <?php foreach ($group['items'] as $id => $meta): ?>
        <label class="check">
          <input type="checkbox" name="plugins[]" value="<?= e((string) $id) ?>" <?= !empty($enabled[$id]) ? 'checked' : '' ?>>
          <?= e((string) $meta['title']) ?>
        </label>
        <p class="muted"><?= e((string) $meta['text']) ?></p>
        <?php if ($id === 'shop-prices'): ?>
          <label class="check"><input type="checkbox" name="call_for_zero" <?= $values['call_for_zero'] ? 'checked' : '' ?>> قیمت صفر با متن «تماس بگیرید»</label>
          <label class="check"><input type="checkbox" name="show_compare" <?= $values['show_compare'] ? 'checked' : '' ?>> نشان دادن قیمت قبل، اگر بیشتر از قیمت فعلی باشد</label>
        <?php elseif ($id === 'shop-stock'): ?>
          <label class="check"><input type="checkbox" name="show_qty" <?= $values['show_qty'] ? 'checked' : '' ?>> نشان دادن عدد موجودی</label>
          <label class="check"><input type="checkbox" name="allow_oversell" <?= $values['allow_oversell'] ? 'checked' : '' ?>> فروش وقتی موجودی صفر است</label>
        <?php elseif ($id === 'shop-cart'): ?>
          <label>حداکثر تعداد هر مدل
            <input name="max_qty" inputmode="numeric" value="<?= e((string) $values['max_qty']) ?>">
          </label>
        <?php elseif ($id === 'shop-checkout'): ?>
          <label>حداقل مبلغ سفارش، ریال (صفر یعنی بدون حداقل)
            <input name="min_total" inputmode="numeric" value="<?= e((string) $values['min_total']) ?>">
          </label>
        <?php elseif ($id === 'shop-pay-transfer'): ?>
          <label>توضیح واریز برای مشتری
            <textarea name="transfer_note"><?= e((string) $values['transfer_note']) ?></textarea>
          </label>
        <?php elseif ($id === 'shop-shipping'): ?>
          <div class="grid">
            <label>هزینه ارسال، ریال
              <input name="shipping_amount" inputmode="numeric" value="<?= e((string) $values['shipping_amount']) ?>">
            </label>
            <label>ارسال رایگان از این مبلغ کالا، ریال
              <input name="free_over" inputmode="numeric" value="<?= e((string) $values['free_over']) ?>">
            </label>
          </div>
        <?php elseif ($id === 'shop-tax'): ?>
          <div class="grid">
            <label>درصد مالیات
              <input name="tax_percent" inputmode="numeric" value="<?= e((string) $values['tax_percent']) ?>">
            </label>
            <label class="check"><input type="checkbox" name="on_shipping" <?= $values['on_shipping'] ? 'checked' : '' ?>> مالیات روی هزینه ارسال هم حساب شود</label>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>

  <h2>قیمت و موجودی مدل‌ها</h2>
  <?php if ($machines === []): ?>
    <p>مدلی در کاتالوگ نیست.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>مدل</th><th>قیمت ریال</th><th>قیمت قبل</th><th>موجودی</th><th>کنترل موجودی</th></tr>
        </thead>
        <tbody>
          <?php foreach ($machines as $machine): ?>
            <?php
              $id = (int) $machine['id'];
              $price = $prices[$id] ?? [];
              $stock = $stocks[$id] ?? null;
              $tracked = $stock === null || (int) ($stock['track'] ?? 1) === 1;
            ?>
            <tr>
              <td><?= e((string) $machine['name']) ?><div class="muted"><?= e((string) $machine['code']) ?></div></td>
              <td><input name="price[<?= e((string) $id) ?>]" inputmode="numeric" value="<?= e((string) ($price['price'] ?? '0')) ?>"></td>
              <td><input name="compare[<?= e((string) $id) ?>]" inputmode="numeric" value="<?= e((string) ($price['compare_at'] ?? '0')) ?>"></td>
              <td><input name="qty[<?= e((string) $id) ?>]" inputmode="numeric" value="<?= e((string) ($stock['qty'] ?? '0')) ?>"></td>
              <td><label class="check"><input type="checkbox" name="track[<?= e((string) $id) ?>]" <?= $tracked ? 'checked' : '' ?>> بله</label></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <button type="submit">ذخیره فروشگاه</button>
</form>
