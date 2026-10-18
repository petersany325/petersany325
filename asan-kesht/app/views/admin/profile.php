<?php /** @var array $user */ ?>
<form method="post" action="<?= e(url('/admin/profile')) ?>" class="stack">
  <?= csrf_field() ?>
  <label>نام <input name="name" required value="<?= e((string) $user['name']) ?>"></label>
  <label>موبایل برای ورود پیامکی <input name="phone" value="<?= e((string) ($user['phone'] ?? '')) ?>" placeholder="09..."></label>
  <label>رمز تازه (اگر نمی‌خواهید عوض شود خالی بگذارید) <input name="password" type="password" autocomplete="new-password"></label>
  <button type="submit">ذخیره</button>
</form>
