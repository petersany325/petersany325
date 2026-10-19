<?php
/** @var list<array<string, mixed>> $rows */
require __DIR__ . '/nav.php';
?>
<p class="muted">سهم ویزیتور از فروش قطعی یک‌بار ثبت می‌شود. مبنای «فروش» درصد مبلغ فاکتور است و مبنای «سود ناخالص» درصد تفاوت مبلغ و بهای تمام‌شده. پیش‌فاکتور سهم نمی‌سازد. در سفارش فروشگاه، درصد روی جمع کالاست و مالیات و ارسال در پایه سهم نمی‌آیند. سهم ثبت‌شده در سود و زیان هزینه فروش است. تغییر درصد، سهم‌های قبلی را عوض نمی‌کند. ویزیتوری که سهم، فاکتور یا سفارش دارد حذف نمی‌شود و از صفحه ویرایش غیرفعال می‌شود.</p>
<h2>ویزیتور تازه</h2>
<form method="post" action="<?= e(url('/admin/accounting/visitors')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="name" required></label>
  <label>موبایل <input name="phone" inputmode="numeric"></label>
  <label>کد <input name="code" required placeholder="V01"></label>
  <label>درصد <input name="percent" required inputmode="numeric" value="0"></label>
  <label>مبنا
    <select name="basis">
      <option value="sale">درصد از مبلغ فروش</option>
      <option value="gross">درصد از سود ناخالص</option>
    </select>
  </label>
  <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  <button type="submit">ثبت ویزیتور</button>
</form>
<h2>فهرست</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>کد</th><th>درصد</th><th>مبنا</th><th>وضعیت</th><th>سهم ثبت‌شده</th><th>ویرایش و حذف</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['name']) ?><div class="muted"><?= e(fa_digits((string) $row['phone'])) ?></div></td>
        <td><?= e((string) $row['code']) ?></td>
        <td><?= e(fa_digits((string) $row['percent'])) ?>٪</td>
        <td><?= (string) $row['basis'] === 'gross' ? 'سود ناخالص' : 'مبلغ فروش' ?></td>
        <td><?= (int) $row['active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
        <td><?= e(money((int) $row['earned'])) ?></td>
        <td>
          <div class="user-actions">
            <a class="btn" href="<?= e(url('/admin/accounting/visitors/' . $row['id'])) ?>">ویرایش</a>
            <form method="post" action="<?= e(url('/admin/accounting/visitors/' . $row['id'] . '/delete')) ?>">
              <?= csrf_field() ?>
              <label class="check"><input type="checkbox" name="confirm" value="1"> حذف شود</label>
              <button type="submit">حذف</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
      <tr><td colspan="7">ویزیتوری ثبت نشده است.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
