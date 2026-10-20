<?php /** @var array $rows */ /** @var bool $accounting */ ?>
<div class="table-wrap">
  <table>
    <thead><tr><th>زمان</th><th>نام</th><th>موبایل</th><th>مدل</th><th>تراکتور</th><th>شرح</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e(jalali_date((string) $row['created_at'])) ?></td>
        <td><?= e((string) $row['name']) ?><div class="muted"><?= e((string) $row['province']) ?></div></td>
        <td><?= e(fa_digits((string) $row['phone'])) ?></td>
        <td><?= e((string) $row['model_code']) ?></td>
        <td><?= e((string) $row['tractor']) ?></td>
        <td><?= e((string) $row['message']) ?></td>
        <td>
          <form method="post" action="<?= e(url('/admin/quotes/status')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
            <select name="status">
              <?php foreach (['new' => 'جدید', 'reviewing' => 'در بررسی', 'quoted' => 'پیش‌فاکتور شد', 'closed' => 'بسته'] as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= $row['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit">ثبت</button>
          </form>
          <?php if ($accounting): ?>
            <form method="post" action="<?= e(url('/admin/accounting/from-quote')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="quote_id" value="<?= e((string) $row['id']) ?>">
              <button type="submit">ساخت فاکتور</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
