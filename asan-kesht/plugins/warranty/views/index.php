<?php
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $machines */
/** @var list<array<string, mixed>> $dealers */
/** @var string $today */
?>
<p class="muted">ثبت مدت گارانتی است و خودش سند مالی نیست. منقضی‌شدن از روی تاریخ پایان حساب می‌شود و تا وقتی باطل نکنید، ردیف پاک نمی‌شود.</p>
<p><a href="<?= e(url('/warranty')) ?>">استعلام عمومی گارانتی</a></p>
<form method="post" action="<?= e(url('/admin/warranty')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>مدل
    <select name="machine_id" required>
      <option value="">انتخاب کنید</option>
      <?php foreach ($machines as $machine): ?>
        <option value="<?= e((string) $machine['id']) ?>"><?= e((string) $machine['code']) ?> — <?= e((string) $machine['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>سریال، اختیاری اگر موبایل باشد <input name="serial"></label>
  <label>نام مالک <input name="holder_name" required></label>
  <label>موبایل مالک <input name="holder_phone" inputmode="numeric" placeholder="09120000000"></label>
  <label>نماینده
    <select name="dealer_id">
      <option value="0">—</option>
      <?php foreach ($dealers as $dealer): ?>
        <option value="<?= e((string) $dealer['id']) ?>"><?= e((string) $dealer['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>شروع <input type="date" name="started_on" required value="<?= e($today) ?>"></label>
  <label>مدت به ماه، ۱ تا ۱۲۰ <input name="months" required inputmode="numeric" value="12"></label>
  <label>یادداشت <input name="note"></label>
  <button type="submit">ثبت گارانتی</button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>مالک</th><th>مدل</th><th>سریال</th><th>شروع</th><th>پایان</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <?php $end = warranty_ends((string) $row['started_on'], (int) $row['months']); ?>
      <tr>
        <td><?= e((string) $row['holder_name']) ?><div class="muted"><?= e(fa_digits((string) $row['holder_phone'])) ?></div></td>
        <td><?= e((string) ($row['machine_name'] ?? '')) ?></td>
        <td><?= e((string) $row['serial']) ?></td>
        <td><?= e(jalali_date((string) $row['started_on'])) ?></td>
        <td><?= e(jalali_date($end)) ?></td>
        <td><?= e(warranty_label($row)) ?></td>
        <td>
          <?php if ((string) $row['status'] !== 'void'): ?>
            <form method="post" action="<?= e(url('/admin/warranty/void')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
              <button type="submit">ابطال</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
