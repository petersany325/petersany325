<?php /** @var array $rows */ /** @var array $lines */ /** @var array $line_images */ ?>
<p class="lead">کادر عکس محصول و دسته‌بندی یکی است: نسبت ۴ به ۳، برش یکسان، و در فروشگاه همه با یک اندازه دیده می‌شوند.</p>
<h2>کادر دسته‌بندی</h2>
<div class="line-cards">
  <?php foreach ($lines as $key => $label): ?>
    <?php $image = (string) ($line_images[$key] ?? ''); ?>
    <form method="post" action="<?= e(url('/admin/machines/line')) ?>" enctype="multipart/form-data" class="stack line-card">
      <?= csrf_field() ?>
      <input type="hidden" name="line" value="<?= e((string) $key) ?>">
      <?= catalog_frame($image, (string) $key, (string) $label) ?>
      <strong><?= e((string) $label) ?></strong>
      <label>عکس کادر <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
      <?php if ($image !== ''): ?><label class="check"><input type="checkbox" name="clear_image"> حذف عکس</label><?php endif; ?>
      <button type="submit">ذخیره کادر</button>
    </form>
  <?php endforeach; ?>
</div>
<h2>افزودن محصول</h2>
<form method="post" action="<?= e(url('/admin/machines')) ?>" enctype="multipart/form-data" class="stack">
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
    <label>عکس کادر <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
    <label class="check"><input type="checkbox" name="active" checked> فعال</label>
  </div>
  <button type="submit">افزودن</button>
</form>
<?php foreach ($rows as $row): ?>
  <?php $image = (string) ($row['image'] ?? ''); ?>
  <form method="post" action="<?= e(url('/admin/machines')) ?>" enctype="multipart/form-data" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
    <div class="edit-machine">
      <?= catalog_frame($image, (string) ($row['line'] ?? ''), (string) $row['name']) ?>
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
        <label>عکس کادر <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
        <label class="check"><input type="checkbox" name="active" <?= (int) $row['active'] === 1 ? 'checked' : '' ?>> فعال</label>
        <?php if (catalog_photo_name($image)): ?><label class="check"><input type="checkbox" name="clear_image"> حذف عکس</label><?php endif; ?>
      </div>
    </div>
    <button type="submit">ذخیره</button>
  </form>
<?php endforeach; ?>
