<?php /** @var array $values */ ?>
<form method="post" action="<?= e(url('/admin/theme')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>نام سایت <input name="site_name" required value="<?= e($values['site_name']) ?>"></label>
  <label>شعار <input name="tagline" value="<?= e($values['tagline']) ?>"></label>
  <label>تلفن <input name="phone" value="<?= e($values['phone']) ?>"></label>
  <label>نشانی <input name="address" value="<?= e($values['address']) ?>"></label>
  <label>ایمیل <input name="email" value="<?= e($values['email']) ?>"></label>
  <div class="grid">
    <label>رنگ تأکید <input type="color" name="color_oxide" value="<?= e($values['color_oxide']) ?>"></label>
    <label>رنگ متن <input type="color" name="color_ink" value="<?= e($values['color_ink']) ?>"></label>
    <label>رنگ زمینه <input type="color" name="color_paper" value="<?= e($values['color_paper']) ?>"></label>
    <label>رنگ تیره <input type="color" name="color_dark" value="<?= e($values['color_dark']) ?>"></label>
  </div>
  <button type="submit">ذخیره قالب</button>
</form>
