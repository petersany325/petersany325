<?php
/** @var list<array<string, mixed>> $books */
/** @var list<array<string, mixed>> $rows */
require __DIR__ . '/nav.php';
?>
<h2>دسته‌چک</h2>
<form method="post" action="<?= e(url('/admin/accounting/checkbooks')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>عنوان <input name="title" required></label>
  <label>بانک <input name="bank"></label>
  <label>از شماره <input name="start_no" required inputmode="numeric"></label>
  <label>تا شماره <input name="end_no" required inputmode="numeric"></label>
  <button type="submit">ثبت دسته‌چک</button>
</form>
<ul>
  <?php foreach ($books as $book): ?>
    <li><?= e((string) $book['title']) ?> — <?= e((string) $book['bank']) ?> — <?= e(fa_digits((string) $book['start_no'])) ?> تا <?= e(fa_digits((string) $book['end_no'])) ?></li>
  <?php endforeach; ?>
</ul>
<h2>چک</h2>
<?php if ($app->pluginEnabled('received-checks')): ?>
  <p class="muted">چک دریافتی مشتری، با بانک و شعبه و شماره صیاد، در <a href="<?= e(url('/admin/received-checks')) ?>">چک دریافتی</a> ثبت می‌شود. ثبت چک سند فروش نیست.</p>
<?php endif; ?>
<form method="post" action="<?= e(url('/admin/accounting/checks')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>جهت
    <select name="direction">
      <option value="in">دریافتی</option>
      <option value="out">پرداختی</option>
    </select>
  </label>
  <label>دسته‌چک
    <select name="checkbook_id">
      <option value="0">بدون دسته</option>
      <?php foreach ($books as $book): ?>
        <option value="<?= e((string) $book['id']) ?>"><?= e((string) $book['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>شماره <input name="number" required></label>
  <label>بانک <input name="bank"></label>
  <label>مبلغ ریال <input name="amount" required inputmode="numeric"></label>
  <label>سررسید <input name="due_on" type="date" required value="<?= e(date('Y-m-d')) ?>"></label>
  <label>طرف <input name="party_name"></label>
  <label>یادداشت <input name="note"></label>
  <button type="submit">ثبت چک</button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>جهت</th><th>شماره</th><?php if ($app->pluginEnabled('received-checks')): ?><th>صیاد</th><th>شعبه</th><th>دریافت</th><?php endif; ?><th>طرف</th><th>مبلغ</th><th>سررسید</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['direction'] === 'in' ? 'دریافتی' : 'پرداختی') ?></td>
        <td><?= e((string) $row['number']) ?></td>
        <?php if ($app->pluginEnabled('received-checks')): ?>
          <td><?= e(fa_digits((string) ($row['sayad'] ?? ''))) ?></td>
          <td><?= e((string) ($row['branch'] ?? '')) ?></td>
          <td><?= e(jalali_date(isset($row['received_on']) && $row['received_on'] !== null ? (string) $row['received_on'] : '')) ?></td>
        <?php endif; ?>
        <td><?= e((string) $row['party_name']) ?></td>
        <td><?= e(money((int) $row['amount'])) ?></td>
        <td><?= e((string) $row['due_on']) ?></td>
        <td>
          <form method="post" action="<?= e(url('/admin/accounting/checks/status')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
            <select name="status">
              <?php foreach (['pending' => 'در جریان', 'cleared' => 'وصول یا پاس', 'bounced' => 'برگشت', 'void' => 'باطل'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= (string) $row['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
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
