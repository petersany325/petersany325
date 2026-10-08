<div class="m-login">
  <div class="m-login-hero">
    <h1><?= e($appName) ?></h1>
    <p>نسخه موبایل تخصصی حسابداری — ثبت سند، خزانه و گزارش‌های ضروری در دسترس سریع.</p>
  </div>
  <form class="m-login-card m-form" method="post" action="<?= e(url('/m/login')) ?>">
    <?= csrf_field() ?>
    <h2>ورود به حساب</h2>
    <?php if (!empty($flash)): ?>
      <div class="m-flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <label>ایمیل
      <input type="email" name="email" required autocomplete="username" value="admin@hesab.local" inputmode="email">
    </label>
    <label>رمز عبور
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="m-btn block" type="submit">ورود به نسخه موبایل</button>
    <a class="m-btn ghost block" href="<?= e(url('/login?desktop=1')) ?>">رفتن به نسخه ویندوزی</a>
  </form>
</div>
