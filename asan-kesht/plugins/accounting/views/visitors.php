<?php
/** @var list<array<string, mixed>> $rows */
/** @var array<string, mixed>|null $edit */
$edit = is_array($edit ?? null) ? $edit : null;
require __DIR__ . '/nav.php';
?>
<p class="muted">سهم ویزیتور از فروش قطعی یک‌بار ثبت می‌شود. مبنای «فروش» درصد مبلغ فاکتور است و مبنای «سود ناخالص» درصد تفاوت مبلغ و بهای تمام‌شده. پیش‌فاکتور سهم نمی‌سازد. در سفارش فروشگاه، درصد روی جمع کالاست و مالیات و ارسال در پایه سهم نمی‌آیند. سهم ثبت‌شده در سود و زیان هزینه فروش است. تغییر درصد، سهم‌های قبلی را عوض نمی‌کند.</p>
<h2><?= $edit ? 'ویرایش ویزیتور' : 'تعریف ویزیتور' ?></h2>
<form method="post" action="<?= e(url('/admin/accounting/visitors')) ?>" class="grid">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e((string) ($edit['id'] ?? 0)) ?>">
  <label>نام <input name="name" required value="<?= e((string) ($edit['name'] ?? '')) ?>"></label>
  <label>موبایل <input name="phone" inputmode="numeric" value="<?= e((string) ($edit['phone'] ?? '')) ?>"></label>
  <label>کد <input name="code" required placeholder="V01" value="<?= e((string) ($edit['code'] ?? '')) ?>"></label>
  <label>درصد <input name="percent" required inputmode="numeric" value="<?= e((string) ($edit['percent'] ?? 0)) ?>"></label>
  <label>مبنا
    <select name="basis">
      <option value="sale" <?= ($edit['basis'] ?? '') === 'sale' ? 'selected' : '' ?>>درصد از مبلغ فروش</option>
      <option value="gross" <?= ($edit['basis'] ?? '') === 'gross' ? 'selected' : '' ?>>درصد از سود ناخالص</option>
    </select>
  </label>
  <label class="check"><input type="checkbox" name="active" <?= !$edit || (int) ($edit['active'] ?? 0) === 1 ? 'checked' : '' ?>> فعال</label>
  <button type="submit"><?= $edit ? 'ذخیره ویزیتور' : 'ثبت ویزیتور' ?></button>
</form>
<div class="table-wrap">
  <table>
    <thead><tr><th>نام</th><th>کد</th><th>درصد</th><th>مبنا</th><th>وضعیت</th><th>سهم ثبت‌شده</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['name']) ?><div class="muted"><?= e(fa_digits((string) $row['phone'])) ?></div></td>
        <td><?= e((string) $row['code']) ?></td>
        <td><?= e(fa_digits((string) $row['percent'])) ?>٪</td>
        <td><?= (string) $row['basis'] === 'gross' ? 'سود ناخالص' : 'مبلغ فروش' ?></td>
        <td><?= (int) $row['active'] === 1 ? 'فعال' : 'غیرفعال' ?></td>
        <td><?= e(money((int) $row['earned'])) ?></td>
        <td><a href="<?= e(url('/admin/accounting/visitors?id=' . (string) $row['id'])) ?>">ویرایش</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
