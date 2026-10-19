<?php
/** @var array $orders */
/** @var array $statuses */
/** @var array $methods */
?>
<?php if ($orders === []): ?><p>هنوز سفارشی ثبت نشده.</p><?php endif; ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>سفارش</th><th>خریدار</th><th>اقلام</th><th>مبلغ</th><th>وضعیت</th></tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td>
            <?= e((string) $order['number']) ?>
            <div class="muted"><?= e(jalali_date((string) $order['created_at'])) ?></div>
          </td>
          <td>
            <?= e((string) $order['name']) ?>
            <div class="muted"><?= e(fa_digits((string) $order['phone'])) ?></div>
            <div class="muted"><?= e((string) $order['province']) ?>، <?= e((string) $order['city']) ?></div>
            <div class="muted"><?= e((string) $methods[(string) $order['payment_method']] ?? (string) $order['payment_method']) ?> — <?= e(shop_payment_label((string) ($order['payment_status'] ?? 'offline'))) ?></div>
          </td>
          <td>
            <?php foreach ($order['items'] as $item): ?>
              <div><?= e((string) $item['name']) ?> × <?= e(fa_digits((string) $item['qty'])) ?></div>
            <?php endforeach; ?>
            <?php if ((string) $order['note'] !== ''): ?><div class="muted"><?= e((string) $order['note']) ?></div><?php endif; ?>
          </td>
          <td>
            <?= e(money((int) $order['total'])) ?>
            <div class="muted">کالا <?= e(money((int) $order['subtotal'])) ?></div>
            <div class="muted">ارسال <?= e(money((int) $order['shipping'])) ?>، مالیات <?= e(money((int) $order['tax'])) ?></div>
          </td>
          <td>
            <form method="post" action="<?= e(url('/admin/shop/orders/status')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e((string) $order['id']) ?>">
              <select name="status">
                <?php foreach ($statuses as $key => $label): ?>
                  <option value="<?= e((string) $key) ?>" <?= (string) $order['status'] === (string) $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit">ذخیره</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="muted">لغو کردن سفارشی که موجودی‌اش کم شده، همان تعداد را به موجودی کنترل‌شده برمی‌گرداند.</p>
