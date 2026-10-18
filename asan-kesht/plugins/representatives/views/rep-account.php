<?php /** @var array $dealer */ /** @var int $balance */ /** @var array $ledger */ /** @var array $checks */ /** @var array $payments */ /** @var array $orders */ ?>
<p><?= e($balance > 0 ? 'بدهکار شما' : ($balance < 0 ? 'بستانکار شما' : 'حساب تسویه')) ?>: <?= e(money(abs($balance))) ?> — مهلت تسویه <?= e(fa_digits((string) $dealer['credit_days'])) ?> روز</p>
<h2>پرداخت</h2>
<form method="post" action="<?= e(url('/rep/payments')) ?>" enctype="multipart/form-data" class="grid">
  <?= csrf_field() ?>
  <label>مبلغ ریال <input name="amount" required></label>
  <label>روش <select name="method"><option value="cash">نقد</option><option value="bank">بانکی</option></select></label>
  <label>سفارش مرتبط
    <select name="order_id"><option value="0">—</option><?php foreach ($orders as $order): ?><option value="<?= e((string) $order['id']) ?>"><?= e((string) $order['number']) ?></option><?php endforeach; ?></select>
  </label>
  <label>رسید <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp"></label>
  <label>شرح <input name="note"></label>
  <button type="submit">ارسال برای تأیید حسابدار</button>
</form>
<h2>چک</h2>
<form method="post" action="<?= e(url('/rep/checks')) ?>" enctype="multipart/form-data" class="grid">
  <?= csrf_field() ?>
  <label>مبلغ <input name="amount" required></label>
  <label>بانک <input name="bank" required></label>
  <label>سریال <input name="serial" required></label>
  <label>سررسید <input type="date" name="due_date" required></label>
  <label>سفارش <select name="order_id"><option value="0">—</option><?php foreach ($orders as $order): ?><option value="<?= e((string) $order['id']) ?>"><?= e((string) $order['number']) ?></option><?php endforeach; ?></select></label>
  <label>تصویر <input type="file" name="image" accept=".pdf,.jpg,.jpeg,.png,.webp"></label>
  <button type="submit">ثبت چک</button>
</form>
<h2>گردش</h2>
<?php foreach ($ledger as $row): ?>
  <form method="post" action="<?= e(url('/rep/disputes')) ?>" class="grid">
    <?= csrf_field() ?>
    <input type="hidden" name="ledger_id" value="<?= e((string) $row['id']) ?>">
    <p><?= e(jalali_date((string) $row['created_at'])) ?> — <?= e($row['direction'] === 'debit' ? 'بدهکار' : 'بستانکار') ?> <?= e(money((int) $row['amount'])) ?> — <?= e((string) $row['note']) ?></p>
    <input name="body" placeholder="اگر این ردیف محل اعتراض است بنویسید">
    <button type="submit">ثبت مغایرت</button>
  </form>
<?php endforeach; ?>
<h2>چک‌های من</h2>
<ul><?php foreach ($checks as $check): ?><li><?= e((string) $check['serial']) ?> — <?= e(jalali_date((string) $check['due_date'])) ?> — <?= e(money((int) $check['amount'])) ?> — <?= e((string) $check['status']) ?></li><?php endforeach; ?></ul>
<h2>پرداخت‌های من</h2>
<ul><?php foreach ($payments as $payment): ?><li><?= e(money((int) $payment['amount'])) ?> — <?= e((string) $payment['status']) ?></li><?php endforeach; ?></ul>
