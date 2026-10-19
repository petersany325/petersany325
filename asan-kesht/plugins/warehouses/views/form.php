<?php
/** @var array<string, mixed> $house */
/** @var list<array<string, mixed>> $dealers */
$dealers = $dealers ?? [];
$nav = dirname(__DIR__, 2) . '/accounting/views/nav.php';
if (is_file($nav)) {
    require $nav;
}
?>
<p class="muted">ویرایش نام، محل، کد و وضعیت همین انبار است. گردش‌های قبلی عوض نمی‌شوند. اگر موجودی یا گردش داشته باشد حذف نمی‌شود. غیرفعال کردن، ورود تازه را می‌بندد و موجودی را پاک نمی‌کند.</p>
<h2>ویرایش <?= e((string) $house['name']) ?></h2>
<form method="post" action="<?= e(url('/admin/accounting/warehouses/' . $house['id'])) ?>" class="grid">
  <?= csrf_field() ?>
  <label>نام <input name="name" required value="<?= e((string) $house['name']) ?>"></label>
  <label>کد <input name="code" required value="<?= e((string) $house['code']) ?>"></label>
  <label>محل <input name="place" value="<?= e((string) $house['place']) ?>"></label>
  <label>نوع
    <select name="kind">
      <?php foreach (warehouse_kinds() as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= (string) ($house['kind'] ?? 'central') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>نماینده، برای انبار امانی
    <select name="dealer_id">
      <option value="0">بدون نماینده</option>
      <?php foreach ($dealers as $dealer): ?>
        <option value="<?= e((string) $dealer['id']) ?>" <?= (int) ($house['dealer_id'] ?? 0) === (int) $dealer['id'] ? 'selected' : '' ?>><?= e((string) $dealer['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="check"><input type="checkbox" name="active" <?= (int) $house['active'] === 1 ? 'checked' : '' ?>> فعال</label>
  <label class="check"><input type="checkbox" name="is_default" <?= (int) $house['is_default'] === 1 ? 'checked' : '' ?>> پیش‌فرض</label>
  <button type="submit">ذخیره ویرایش</button>
</form>
