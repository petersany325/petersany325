<?php /** @var App $app */ ?>
<section class="section">
  <div class="wrap contact-grid">
    <div>
      <p class="kicker">ورود کارکنان و مشتریان</p>
      <h1>با موبایل و کد پیامکی وارد شوید</h1>
      <p class="lead">مشتری با شماره موبایل حساب می‌سازد. کارمند و نماینده را شرکت می‌سازد و بعد با همان موبایل کد می‌گیرند. تا وقتی پنل نیازپرداز وصل نشده، ورود با نام کاربری و رمز انجام می‌شود.</p>
    </div>
    <div>
      <?php if ($app->pluginEnabled('sms-login')): ?>
        <form class="quote-form" method="post" action="<?= e(url('/login/otp')) ?>">
          <?= csrf_field() ?>
          <h2>ورود یا عضویت با پیامک</h2>
          <label class="field">نام
            <input name="name" maxlength="120" placeholder="برای مشتری جدید">
          </label>
          <label class="field">موبایل
            <input name="phone" required inputmode="numeric" placeholder="0912xxxxxxx">
          </label>
          <button class="btn" type="submit">دریافت کد</button>
        </form>
        <form method="post" action="<?= e(url('/login/verify')) ?>">
          <?= csrf_field() ?>
          <h2>کد پیامک</h2>
          <label class="field">موبایل
            <input name="phone" required inputmode="numeric">
          </label>
          <label class="field">کد پنج‌رقمی
            <input name="code" required inputmode="numeric" maxlength="8">
          </label>
          <button class="btn btn-ghost" type="submit">تأیید و ورود</button>
        </form>
      <?php endif; ?>
      <form method="post" action="<?= e(url('/login/password')) ?>">
        <?= csrf_field() ?>
        <h2>ورود مدیر با رمز</h2>
        <label class="field">نام کاربری
          <input name="username" required autocomplete="username">
        </label>
        <label class="field">رمز
          <input name="password" type="password" required autocomplete="current-password">
        </label>
        <button class="btn" type="submit">ورود</button>
      </form>
    </div>
  </div>
</section>
