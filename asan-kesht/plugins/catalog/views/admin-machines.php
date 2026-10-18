<?php /** @var array $rows */ /** @var array $lines */ ?>
<form method="post" action="<?= e(url('/admin/machines')) ?>" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="0">
  <div class="grid">
    <label>کد <input name="code" required placeholder="AK-MP-3"></label>
    <label>نام <input name="name" required></label>
    <label>خط
      <select name="line"><?php foreach ($lines as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
    </label>
    <label>توان <input name="hp"></label>
    <label>عرض <input name="width"></label>
    <label>ترتیب <input name="sort_order" value="0"></label>
    <label>شرح <input name="summary"></label>
    <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  </div>
  <button type="submit">افزودن</button>
</form>
<?php foreach ($rows as $row): ?>
  <form method="post" action="<?= e(url('/admin/machines')) ?>" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
    <div class="grid">
      <label>کد <input name="code" value="<?= e((string) $row['code']) ?>"></label>
      <label>نام <input name="name" value="<?= e((string) $row['name']) ?>"></label>
      <label>خط
        <select name="line"><?php foreach ($lines as $key => $label): ?><option value="<?= e($key) ?>" <?= $row['line'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
      </label>
      <label>توان <input name="hp" value="<?= e((string) $row['hp']) ?>"></label>
      <label>عرض <input name="width" value="<?= e((string) $row['width']) ?>"></label>
      <label>ترتیب <input name="sort_order" value="<?= e((string) $row['sort_order']) ?>"></label>
      <label>شرح <input name="summary" value="<?= e((string) $row['summary']) ?>"></label>
      <label class="check"><input type="checkbox" name="active" <?= (int) $row['active'] === 1 ? 'checked' : '' ?>> فعال</label>
    </div>
    <button type="submit">ذخیره</button>
  </form>
<?php endforeach; ?>
