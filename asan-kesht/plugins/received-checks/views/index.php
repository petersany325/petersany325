<?php
/** @var list<array<string, mixed>> $rows */
/** @var list<string> $banks */
/** @var bool $ready */
/** @var string $today */
$nav = dirname(__DIR__, 2) . '/accounting/views/nav.php';
if (is_file($nav)) {
    require $nav;
}
?>
<p class="muted">ثبت چک دریافتی سند فروش نیست و تا وصول در سود نمی‌آید. تغییر وضعیت به وصول یا برگشت هم درآمد نمی‌سازد و فاکتور صادر نمی‌کند.</p>
<?php if (!$ready): ?>
  <p class="flash flash-error">افزونه حسابداری خاموش است یا جدول چک هنوز ساخته نشده. چک دریافتی به همان جدول چک وصل می‌شود.</p>
<?php else: ?>
<form method="post" action="<?= e(url('/admin/received-checks')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>مشتری <input name="party_name" required></label>
  <label>بانک
    <select name="bank" required>
      <option value="">انتخاب کنید</option>
      <?php foreach ($banks as $bank): ?>
        <option value="<?= e($bank) ?>"><?= e($bank) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>شعبه <input name="branch" required></label>
  <label>شماره چک <input name="number" required></label>
  <label>شماره صیاد، ۱۶ رقم <input name="sayad" required inputmode="numeric" maxlength="16"></label>
  <label>مبلغ ریال <input name="amount" required inputmode="numeric"></label>
  <label>تاریخ دریافت <input type="date" name="received_on" required value="<?= e($today) ?>"></label>
  <label>تاریخ وصول <input type="date" name="due_on" required value="<?= e($today) ?>"></label>
  <label>یادداشت <input name="note"></label>
  <button type="submit">ثبت چک دریافتی</button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>مشتری</th><th>بانک</th><th>شعبه</th><th>شماره</th><th>صیاد</th><th>مبلغ</th><th>دریافت</th><th>وصول</th><th>وضعیت</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['party_name']) ?></td>
        <td><?= e((string) $row['bank']) ?></td>
        <td><?= e((string) ($row['branch'] ?? '')) ?></td>
        <td><?= e((string) $row['number']) ?></td>
        <td><?= e(fa_digits((string) ($row['sayad'] ?? ''))) ?></td>
        <td><?= e(money((int) $row['amount'])) ?></td>
        <td><?= e(jalali_date(isset($row['received_on']) ? (string) $row['received_on'] : '')) ?></td>
        <td><?= e(jalali_date((string) $row['due_on'])) ?></td>
        <td>
          <form method="post" action="<?= e(url('/admin/received-checks/status')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
            <select name="status">
              <?php foreach (['pending' => 'در جریان', 'cleared' => 'وصول', 'bounced' => 'برگشت', 'void' => 'باطل'] as $value => $label): ?>
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
<?php endif; ?>
