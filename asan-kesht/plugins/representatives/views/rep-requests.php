<?php /** @var array $rows */ /** @var array $orders */ ?>
<?php $filesOn = $app->pluginEnabled('attachments'); ?>
<?php require __DIR__ . '/rep-nav.php'; ?>
<form method="post" action="<?= e(url('/rep/requests')) ?>" class="stack"<?= $filesOn ? ' enctype="multipart/form-data"' : '' ?>>
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
  <label>شرح <textarea name="body"<?= $filesOn ? '' : ' required' ?>></textarea></label>
  <?php if ($filesOn): ?>
    <label>پیوست PDF، JPG یا TXT، اختیاری <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt"></label>
  <?php endif; ?>
  <button type="submit">ثبت درخواست</button>
</form>
<ul>
  <?php foreach ($rows as $row): ?>
    <li><?= e(dealer_request_types()[$row['type']] ?? '') ?> — <?= e((string) $row['status']) ?> — <?= e((string) $row['body']) ?>
      <?php if ($filesOn && (string) ($row['file'] ?? '') !== ''): ?> <a href="<?= e(url('/files/' . (string) $row['file'])) ?>">پیوست</a><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
