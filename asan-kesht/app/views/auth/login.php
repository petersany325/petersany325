<?php
/** @var App $app */
$sms = $app->pluginEnabled('sms-login');
$registerOpen = $app->pluginEnabled('register') && setting($app, 'register', 'open', '1') === '1';
$methods = [];
if ($sms) {
    $methods[] = ['id' => 'sms', 'icon' => 'phone', 'label' => 'پیامک', 'hint' => 'شماره را بنویسید تا کد بیاید.'];
    $methods[] = ['id' => 'code', 'icon' => 'code', 'label' => 'کد', 'hint' => 'همان موبایل و کد پنج‌رقمی.'];
}
$methods[] = ['id' => 'pass', 'icon' => 'key', 'label' => 'مدیر', 'hint' => 'نام کاربری و رمز مدیریت.'];
$start = $methods[0]['id'];
?>
<section class="section auth-stage">
  <div class="auth-card">
    <header class="auth-head">
      <span class="auth-mark"><?= ui_icon('user') ?></span>
      <div>
        <p class="kicker">ورود</p>
        <h1>حساب کاربری</h1>
      </div>
    </header>
    <nav class="auth-switch" aria-label="روش ورود">
      <?php foreach ($methods as $method): ?>
        <a href="#<?= e($method['id']) ?>"<?= $method['id'] === $start ? ' class="is-start"' : '' ?>>
          <?= ui_icon($method['icon']) ?>
          <span><?= e($method['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php if ($sms): ?>
      <form id="sms" class="auth-pane<?= $start === 'sms' ? ' is-start' : '' ?>" method="post" action="<?= e(url('/login/otp')) ?>">
        <?= csrf_field() ?>
        <p class="auth-hint">شماره را بنویسید تا کد بیاید.</p>
        <label class="field">نام
          <input name="name" maxlength="120" placeholder="برای مشتری جدید" autocomplete="name">
        </label>
        <label class="field">موبایل
          <input name="phone" required inputmode="numeric" placeholder="0912xxxxxxx" autocomplete="tel">
        </label>
        <button class="btn" type="submit">دریافت کد</button>
      </form>
      <form id="code" class="auth-pane" method="post" action="<?= e(url('/login/verify')) ?>">
        <?= csrf_field() ?>
        <p class="auth-hint">همان موبایل و کد پنج‌رقمی.</p>
        <label class="field">موبایل
          <input name="phone" required inputmode="numeric" autocomplete="tel">
        </label>
        <label class="field">کد پنج‌رقمی
          <input name="code" required inputmode="numeric" maxlength="8" autocomplete="one-time-code">
        </label>
        <button class="btn" type="submit">تأیید و ورود</button>
      </form>
    <?php endif; ?>
    <form id="pass" class="auth-pane<?= $start === 'pass' ? ' is-start' : '' ?>" method="post" action="<?= e(url('/login/password')) ?>">
      <?= csrf_field() ?>
      <p class="auth-hint">نام کاربری و رمز مدیریت.</p>
      <label class="field">نام کاربری
        <input name="username" required autocomplete="username">
      </label>
      <label class="field">رمز
        <input name="password" type="password" required autocomplete="current-password">
      </label>
      <button class="btn" type="submit">ورود</button>
    </form>
    <?php if ($registerOpen): ?>
      <p class="auth-foot"><a href="<?= e(url('/register')) ?>"><?= ui_icon('plus') ?> فرم ثبت‌نام</a></p>
    <?php endif; ?>
  </div>
</section>
