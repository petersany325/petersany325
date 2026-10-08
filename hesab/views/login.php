<div class="auth-wrap">
  <form class="auth-card form" method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <h1><?= e(cfg('app_name', 'حساب')) ?></h1>
    <p>ورود به سامانه حسابداری</p>
    <?php if (!empty($flash)): ?>
      <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <label>ایمیل
      <input type="email" name="email" required value="admin@hesab.local">
    </label>
    <label>رمز عبور
      <input type="password" name="password" required>
    </label>
    <button class="btn" type="submit">ورود</button>
  </form>
</div>
