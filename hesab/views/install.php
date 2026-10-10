<div class="auth-wrap">
  <form class="auth-card form" method="post" action="<?= e(url('/install')) ?>" style="width:min(520px,94vw)">
    <?= csrf_field() ?>
    <h1>نصب سامانه حساب</h1>
    <p>اتصال دیتابیس و ایجاد مدیر سیستم</p>
    <?php if (!empty($flash)): ?>
      <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <label>آدرس سایت
      <input name="base_url" value="https://hdd-land.ir/hesab" required>
    </label>
    <label>هاست دیتابیس
      <input name="db_host" value="localhost" required>
    </label>
    <label>نام دیتابیس
      <input name="db_name" value="DBNAME_hesab" required>
    </label>
    <label>کاربر دیتابیس
      <input name="db_user" value="DBUSER_hesab_user" required>
    </label>
    <label>رمز دیتابیس
      <input name="db_pass" value="" required placeholder="رمز دیتابیس cPanel">
    </label>
    <label>نام مدیر
      <input name="admin_name" value="مدیر سیستم" required>
    </label>
    <label>ایمیل مدیر
      <input type="email" name="admin_email" value="admin@hesab.local" required>
    </label>
    <label>رمز مدیر
      <input type="password" name="admin_pass" value="Admin@12345" required>
    </label>
    <button class="btn" type="submit">نصب و راه‌اندازی</button>
  </form>
</div>
