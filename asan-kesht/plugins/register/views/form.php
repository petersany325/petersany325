<?php
/** @var array $values */
/** @var array $kinds */
/** @var array $fields */
/** @var array $lines */
/** @var array $entities */
/** @var array $cooperations */
/** @var array $sale_models */
$open = ($values['open'] ?? '0') === '1';
$min = (string) max(6, min(32, (int) ($values['min_password'] ?? 8)));
$show = static function (string $key) use ($values): bool {
    return ($values['show_' . $key] ?? '0') === '1';
};
$need = static function (string $key) use ($values, $show): bool {
    return $show($key) && ($values['require_' . $key] ?? '0') === '1';
};
?>
<section class="section">
  <div class="wrap contact-grid">
    <div>
      <p class="kicker">ثبت‌نام</p>
      <h1>حساب مشتری یا درخواست نمایندگی</h1>
      <p class="lead"><?= e((string) ($values['intro'] ?? '')) ?></p>
      <?php if (!$open): ?>
        <p class="note"><?= e((string) ($values['closed_message'] ?? '')) ?></p>
        <p><a class="btn" href="<?= e(url('/login')) ?>">ورود</a></p>
      <?php else: ?>
        <ul class="facts">
          <li><h3>مشتری</h3><p>پیگیری خرید و گفتگو با شرکت، اگر این نوع در تنظیمات باز باشد.</p></li>
          <li><h3>نمایندگی شهرستان</h3><p>درخواست همکاری تا تأیید شرکت وارد کارتابل نمی‌شود.</p></li>
        </ul>
      <?php endif; ?>
    </div>
    <?php if ($open && $kinds !== []): ?>
      <form class="quote-form" method="post" action="<?= e(url('/register')) ?>">
        <?= csrf_field() ?>
        <p class="hp"><label>وب‌سایت <input name="website" tabindex="-1" autocomplete="off"></label></p>
        <h2>فرم ثبت‌نام</h2>
        <?php if (count($kinds) === 1): ?>
          <?php $only = (string) array_key_first($kinds); ?>
          <input type="hidden" name="kind" value="<?= e($only) ?>">
          <p class="note">نوع: <?= e((string) $kinds[$only]) ?></p>
        <?php else: ?>
          <div class="kind-pick">
            <?php foreach ($kinds as $key => $label): ?>
              <label>
                <input type="radio" name="kind" value="<?= e((string) $key) ?>" <?= $key === 'customer' ? 'checked' : '' ?> required>
                <span><?= e((string) $label) ?></span>
                <small><?= $key === 'customer' ? 'برای خرید و پشتیبانی' : 'همکاری فروش در شهرستان' ?></small>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <label class="field">نام و نام خانوادگی
          <input name="name" required maxlength="160" autocomplete="name">
        </label>
        <label class="field">موبایل
          <input name="phone" required inputmode="numeric" maxlength="20" placeholder="0912xxxxxxx" autocomplete="tel">
        </label>
        <?php if ($show('username')): ?>
          <label class="field">نام کاربری<?= $need('username') ? '' : '، اختیاری' ?>
            <input name="username" <?= $need('username') ? 'required' : '' ?> maxlength="60" autocomplete="username" placeholder="حروف انگلیسی">
          </label>
        <?php endif; ?>
        <div class="pair">
          <label class="field">رمز<?= ($values['require_password'] ?? '0') === '1' ? '' : '، اختیاری' ?>
            <input name="password" type="password" <?= ($values['require_password'] ?? '0') === '1' ? 'required' : '' ?> minlength="<?= e($min) ?>" maxlength="72" autocomplete="new-password">
          </label>
          <label class="field">تکرار رمز
            <input name="password_confirm" type="password" <?= ($values['require_password'] ?? '0') === '1' ? 'required' : '' ?> maxlength="72" autocomplete="new-password">
          </label>
        </div>
        <?php if ($show('entity')): ?>
          <label class="field">شخص یا شرکت
            <select name="entity">
              <?php foreach ($entities as $key => $label): ?>
                <option value="<?= e((string) $key) ?>" <?= ($values['default_kind'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>
        <div class="pair">
          <?php if ($show('province')): ?>
            <label class="field">استان
              <input name="province" <?= $need('province') ? 'required' : '' ?> maxlength="80">
            </label>
          <?php endif; ?>
          <?php if ($show('city')): ?>
            <label class="field">شهر
              <input name="city" <?= $need('city') ? 'required' : '' ?> maxlength="80">
            </label>
          <?php endif; ?>
        </div>
        <?php if ($show('address')): ?>
          <label class="field">نشانی
            <input name="address" <?= $need('address') ? 'required' : '' ?> maxlength="240">
          </label>
        <?php endif; ?>
        <?php if ($show('national_id')): ?>
          <label class="field">کد ملی یا شناسه
            <input name="national_id" <?= $need('national_id') ? 'required' : '' ?> maxlength="30" inputmode="numeric">
          </label>
        <?php endif; ?>
        <?php if ($show('shop')): ?>
          <label class="field">نام فروشگاه یا نمایندگی
            <input name="shop_name" <?= $need('shop') ? 'required' : '' ?> maxlength="160">
          </label>
        <?php endif; ?>
        <?php if (($values['show_cooperation'] ?? '0') === '1' && isset($kinds['representative'])): ?>
          <label class="field">نوع همکاری، برای درخواست نمایندگی
            <select name="cooperation">
              <?php foreach ($cooperations as $key => $label): ?>
                <option value="<?= e((string) $key) ?>" <?= ($values['default_cooperation'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>
        <?php if (($values['show_sale_model'] ?? '0') === '1' && isset($kinds['representative'])): ?>
          <label class="field">مدل فروش، برای درخواست نمایندگی
            <select name="sale_model">
              <?php foreach ($sale_models as $key => $label): ?>
                <option value="<?= e((string) $key) ?>" <?= ($values['default_sale_model'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endif; ?>
        <?php if (($values['show_lines'] ?? '0') === '1' && $lines !== [] && isset($kinds['representative'])): ?>
          <fieldset>
            <legend>گروه کالا برای درخواست نمایندگی</legend>
            <div class="line-picks">
              <?php foreach ($lines as $key => $label): ?>
                <label class="check-line"><input type="checkbox" name="lines[]" value="<?= e((string) $key) ?>"> <?= e((string) $label) ?></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endif; ?>
        <?php if ($show('note')): ?>
          <label class="field">توضیح
            <textarea name="note" <?= $need('note') ? 'required' : '' ?> maxlength="1000"></textarea>
          </label>
        <?php endif; ?>
        <?php if (($values['require_terms'] ?? '0') === '1'): ?>
          <div class="terms-box"><?= nl2br(e((string) ($values['terms'] ?? ''))) ?></div>
          <label class="check-line"><input type="checkbox" name="accept_terms" required> شرایط را خواندم و می‌پذیرم</label>
        <?php endif; ?>
        <button class="btn" type="submit">ارسال ثبت‌نام</button>
        <p class="muted-link"><a href="<?= e(url('/login')) ?>">حساب دارم؛ ورود</a></p>
      </form>
    <?php elseif ($open): ?>
      <p class="note">هیچ نوع ثبت‌نامی در تنظیمات مدیریت باز نیست.</p>
    <?php endif; ?>
  </div>
</section>
