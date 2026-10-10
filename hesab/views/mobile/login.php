<div class="m-login">
  <div class="m-login-hero">
    <h1><?= e($appName) ?></h1>
    <p>نسخه موبایل تخصصی حسابداری — ورود با ایمیل، موبایل یا پیامک.</p>
  </div>
  <div class="m-login-card">
    <?php if (!empty($flash)): ?>
      <div class="m-flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <div style="display:flex;gap:6px;margin-bottom:10px">
      <a class="m-btn <?= ($tab ?? 'email')==='email'?'':'ghost' ?>" href="<?= e(url('/m/login?tab=email&mobile=1')) ?>">ایمیل</a>
      <a class="m-btn <?= ($tab ?? '')==='phone'?'':'ghost' ?>" href="<?= e(url('/m/login?tab=phone&mobile=1')) ?>">موبایل</a>
      <a class="m-btn <?= ($tab ?? '')==='otp'?'':'ghost' ?>" href="<?= e(url('/m/login?tab=otp&mobile=1')) ?>">پیامک</a>
    </div>

    <?php if (($tab ?? 'email') === 'email'): ?>
      <form class="m-form" method="post" action="<?= e(url('/m/login')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="email">
        <h2>ورود به حساب</h2>
        <label>ایمیل<input type="email" name="email" required value="admin@hesab.local"></label>
        <label>رمز عبور<input type="password" name="password" required></label>
        <button class="m-btn block" type="submit">ورود</button>
      </form>
    <?php elseif (($tab ?? '') === 'phone'): ?>
      <form class="m-form" method="post" action="<?= e(url('/m/login')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="phone">
        <h2>ورود با موبایل</h2>
        <label>موبایل<input name="phone" required dir="ltr" placeholder="0912xxxxxxx"></label>
        <label>رمز عبور<input type="password" name="password" required></label>
        <button class="m-btn block" type="submit">ورود</button>
      </form>
    <?php else: ?>
      <form class="m-form" method="post" action="<?= e(url('/login/otp/send')) ?>" style="margin-bottom:10px">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="mobile">
        <h2>ورود با پیامک</h2>
        <label>موبایل<input name="phone" required dir="ltr" placeholder="0912xxxxxxx" value="<?= e($otpPhone ?? '') ?>"></label>
        <button class="m-btn ghost block" type="submit">ارسال کد</button>
      </form>
      <form class="m-form" method="post" action="<?= e(url('/login/otp/verify')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="mobile">
        <input type="hidden" name="phone" value="<?= e($otpPhone ?? '') ?>">
        <label>کد تأیید<input name="code" required dir="ltr" inputmode="numeric"></label>
        <button class="m-btn block" type="submit">تأیید و ورود</button>
      </form>
    <?php endif; ?>
    <a class="m-btn ghost block" href="<?= e(url('/login?desktop=1')) ?>" style="margin-top:8px">رفتن به نسخه ویندوزی</a>
  </div>
</div>
