<?php /** @var array $me */ ?>
<form method="post" action="<?= e(url('/settings/profile')) ?>" class="panel form" style="max-width:520px">
  <?= csrf_field() ?>
  <div class="hd"><strong>تنظیمات کاربر (پروفایل من)</strong></div>
  <div class="bd" style="padding:12px;display:grid;gap:8px">
    <label>نام نمایشی<input name="name" value="<?= e($me['name'] ?? '') ?>" required></label>
    <label>ایمیل<input type="email" value="<?= e($me['email'] ?? '') ?>" disabled></label>
    <label>موبایل (ورود OTP)<input name="phone" value="<?= e($me['phone'] ?? '') ?>" dir="ltr" placeholder="0912xxxxxxx"></label>
    <label>رمز عبور جدید<input type="password" name="new_pass" autocomplete="new-password" placeholder="خالی = بدون تغییر"></label>
    <label>تکرار رمز جدید<input type="password" name="new_pass2" autocomplete="new-password"></label>
    <div style="display:flex;gap:8px">
      <button class="btn" type="submit">ذخیره پروفایل</button>
      <a class="btn ghost" href="<?= e(url('/settings')) ?>">بازگشت</a>
    </div>
  </div>
</form>
