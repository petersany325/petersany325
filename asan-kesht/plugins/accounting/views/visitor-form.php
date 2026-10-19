<?php
/** @var array<string, mixed> $person */
require __DIR__ . '/nav.php';
?>
<p class="muted">ویرایش نام، موبایل، کد، درصد، مبنا و وضعیت همین ویزیتور است. سهم‌هایی که قبلاً ثبت شده‌اند با این ذخیره عوض نمی‌شوند. اگر سهم، فاکتور یا سفارش داشته باشد حذف نمی‌شود؛ برای بستن سهم تازه، تیک فعال را بردارید.</p>
<h2>ویرایش <?= e((string) $person['name']) ?></h2>
<form method="post" action="<?= e(url('/admin/accounting/visitors/' . $person['id'])) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="name" required value="<?= e((string) $person['name']) ?>"></label>
  <label>موبایل <input name="phone" inputmode="numeric" value="<?= e((string) ($person['phone'] ?? '')) ?>"></label>
  <label>کد <input name="code" required value="<?= e((string) $person['code']) ?>"></label>
  <label>درصد <input name="percent" required inputmode="numeric" value="<?= e((string) $person['percent']) ?>"></label>
  <label>مبنا
    <select name="basis">
      <option value="sale" <?= (string) $person['basis'] === 'sale' ? 'selected' : '' ?>>درصد از مبلغ فروش</option>
      <option value="gross" <?= (string) $person['basis'] === 'gross' ? 'selected' : '' ?>>درصد از سود ناخالص</option>
    </select>
  </label>
  <label class="check"><input type="checkbox" name="active" <?= (int) $person['active'] === 1 ? 'checked' : '' ?>> فعال</label>
  <button type="submit">ذخیره ویرایش</button>
</form>
