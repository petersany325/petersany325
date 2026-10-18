<?php /** @var array $dealer */ /** @var array $machines */ /** @var array $rows */ ?>
<form method="post" action="<?= e(url('/rep/orders')) ?>" class="stack">
  <?= csrf_field() ?>
  <?php foreach ($machines as $machine): ?>
    <div class="grid">
      <input type="hidden" name="machine_id[]" value="<?= e((string) $machine['id']) ?>">
      <p><?= e((string) $machine['name']) ?> — <?= e(money((int) $machine['unit_price'])) ?></p>
      <label>تعداد <input name="qty[]" value="0"></label>
    </div>
  <?php endforeach; ?>
  <label>روش پرداخت
    <select name="pay_mode">
      <option value="cash">نقد</option>
      <option value="check">چک</option>
      <option value="credit">نسیه</option>
      <option value="mixed">ترکیبی</option>
    </select>
  </label>
  <div class="grid">
    <label>درصد نقد <input name="cash_percent" value="0"></label>
    <label>درصد چک <input name="check_percent" value="0"></label>
    <label>درصد نسیه <input name="credit_percent" value="0"></label>
  </div>
  <label>توضیح <input name="note"></label>
  <button type="submit">ثبت سفارش</button>
</form>
<h2>سفارش‌های قبلی</h2>
<ul><?php foreach ($rows as $row): ?><li><?= e((string) $row['number']) ?> — <?= e(money((int) $row['total'])) ?> — <?= e(dealer_order_statuses()[$row['status']] ?? '') ?></li><?php endforeach; ?></ul>
