<?php
/** @var list<array<string, mixed>> $rows */
?>
<p class="muted">پذیرش این فرم فقط یادداشت بررسی است. نماینده، کاربر و حساب خرید ساخته نمی‌شود. پرونده نمایندگی را جدا از منوی نمایندگان بسازید.</p>
<p><a href="<?= e(url('/dealership')) ?>">فرم عمومی</a></p>
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>شهر</th><th>شرح</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td>
          <?= e((string) $row['name']) ?>
          <div class="muted"><?= e(fa_digits((string) $row['phone'])) ?></div>
          <?php if ((string) $row['shop'] !== ''): ?><div class="muted"><?= e((string) $row['shop']) ?></div><?php endif; ?>
        </td>
        <td><?= e((string) $row['province']) ?> — <?= e((string) $row['city']) ?></td>
        <td>
          <?= e((string) $row['body']) ?>
          <?php if ($app->pluginEnabled('attachments') && (string) ($row['file'] ?? '') !== ''): ?>
            <div><a href="<?= e(url('/files/' . (string) $row['file'])) ?>">پیوست</a></div>
          <?php endif; ?>
        </td>
        <td>
          <form method="post" action="<?= e(url('/admin/dealership')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
            <select name="status">
              <?php foreach (['new', 'reviewing', 'accepted', 'rejected'] as $status): ?>
                <option value="<?= e($status) ?>" <?= (string) $row['status'] === $status ? 'selected' : '' ?>><?= e(dealership_status_label($status)) ?></option>
              <?php endforeach; ?>
            </select>
            <input name="note" value="<?= e((string) $row['note']) ?>" placeholder="یادداشت داخلی">
            <button type="submit">ذخیره</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
