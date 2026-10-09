<div class="auth-wrap">
  <div class="auth-card form">
    <h1><?= e(cfg('app_name', 'حساب')) ?></h1>
    <p>ورود به سامانه حسابداری</p>
    <?php if (!empty($flash)): ?>
      <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="login-tabs" style="display:flex;gap:6px;margin-bottom:10px">
      <a class="btn <?= ($tab ?? 'email')==='email'?'':'ghost' ?>" href="<?= e(url('/login?tab=email')) ?>">ایمیل / رمز</a>
      <a class="btn <?= ($tab ?? '')==='phone'?'':'ghost' ?>" href="<?= e(url('/login?tab=phone')) ?>">موبایل + رمز</a>
      <a class="btn <?= ($tab ?? '')==='otp'?'':'ghost' ?>" href="<?= e(url('/login?tab=otp')) ?>">ورود با پیامک</a>
    </div>

    <?php if (($tab ?? 'email') === 'email'): ?>
      <form method="post" action="<?= e(url('/login')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="email">
        <label>ایمیل<input type="email" name="email" required value="admin@hesab.local"></label>
        <label>رمز عبور<input type="password" name="password" required></label>
        <button class="btn" type="submit">ورود</button>
      </form>
    <?php elseif (($tab ?? '') === 'phone'): ?>
      <form method="post" action="<?= e(url('/login')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="phone">
        <label>موبایل<input name="phone" required dir="ltr" placeholder="0912xxxxxxx" value="<?= e($_GET['phone'] ?? '') ?>"></label>
        <label>رمز عبور<input type="password" name="password" required></label>
        <button class="btn" type="submit">ورود با موبایل</button>
      </form>
    <?php else: ?>
      <form method="post" action="<?= e(url('/login/otp/send')) ?>" style="margin-bottom:10px">
        <?= csrf_field() ?>
        <label>موبایل<input name="phone" required dir="ltr" placeholder="0912xxxxxxx" value="<?= e($otpPhone ?? '') ?>"></label>
        <button class="btn ghost" type="submit">ارسال کد پیامکی</button>
      </form>
      <form method="post" action="<?= e(url('/login/otp/verify')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="phone" value="<?= e($otpPhone ?? '') ?>">
        <label>کد تأیید<input name="code" required dir="ltr" inputmode="numeric" placeholder="۶ رقم"></label>
        <button class="btn" type="submit">تأیید و ورود</button>
      </form>
    <?php endif; ?>

    <a class="btn ghost" href="<?= e(url('/m/login?mobile=1')) ?>" style="text-align:center;margin-top:8px">ورود به نسخه موبایل</a>
  </div>
</div>
