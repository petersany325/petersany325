<?php /** @var array $rows */ /** @var array $orders */ ?>
<form method="post" action="<?= e(url('/rep/requests')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>نوع
    <select name="type">
      <?php foreach (dealer_request_types() as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>سفارش، برای مرجوعی
    <select name="order_id"><option value="0">—</option><?php foreach ($orders as $order): ?><option value="<?= e((string) $order['id']) ?>"><?= e((string) $order['number']) ?></option><?php endforeach; ?></select>
  </label>
  <label>مبلغ درخواستی یا تعداد روز تمدید <input name="amount"></label>
  <label>تاریخ پایان قرارداد، برای تمدید <input type="date" name="until_date"></label>
  <label>شرح <textarea name="body" required></textarea></label>
  <button type="submit">ثبت درخواست</button>
</form>
<ul>
  <?php foreach ($rows as $row): ?>
    <li><?= e(dealer_request_types()[$row['type']] ?? '') ?> — <?= e((string) $row['status']) ?> — <?= e((string) $row['body']) ?></li>
  <?php endforeach; ?>
</ul>
