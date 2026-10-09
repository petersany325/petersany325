<?php /** @var array $s */ ?>
<form method="post" action="<?= e(url('/settings/sms')) ?>" class="form">
  <?= csrf_field() ?>
  <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
    <div class="panel">
      <div class="hd"><strong>اتصال نیازپرداز</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label><input type="checkbox" name="sms_enabled" <?= ($s['sms_enabled']??'')==='1'?'checked':'' ?>> فعال‌سازی ارسال پیامک</label>
        <label>حالت اتصال
          <select name="sms_mode">
            <option value="classic" <?= ($s['sms_mode']??'')==='classic'?'selected':'' ?>>کلاسیک (UserName/Password)</option>
            <option value="apikey" <?= ($s['sms_mode']??'')==='apikey'?'selected':'' ?>>REST با API Key</option>
          </select>
        </label>
        <label>نام کاربری پنل<input name="sms_username" value="<?= e($s['sms_username']??'') ?>" dir="ltr"></label>
        <label>رمز عبور پنل<input name="sms_password" type="password" value="<?= e($s['sms_password']??'') ?>" dir="ltr" autocomplete="new-password"></label>
        <label>API Key<input name="sms_api_key" value="<?= e($s['sms_api_key']??'') ?>" dir="ltr"></label>
        <label>شماره فرستنده<input name="sms_from" value="<?= e($s['sms_from']??'') ?>" dir="ltr" placeholder="1000xxxx"></label>
        <label>Base URL کلاسیک<input name="sms_base_url" value="<?= e($s['sms_base_url']??'https://panel.niazpardaz-sms.com') ?>" dir="ltr"></label>
        <p style="color:var(--muted);font-size:12px;margin:0">مستندات: niazpardaz-sms.com/webservice</p>
      </div>
    </div>
    <div class="panel">
      <div class="hd"><strong>قالب پیامک</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label>قالب OTP ورود<textarea name="sms_otp_template" rows="3"><?= e($s['sms_otp_template']??'') ?></textarea></label>
        <label>قالب اطلاع فاکتور<textarea name="sms_invoice_template" rows="3"><?= e($s['sms_invoice_template']??'') ?></textarea></label>
        <p style="color:var(--muted);font-size:12px;margin:0">متغیرها: {code} {app} {number} {amount}</p>
        <button class="btn" type="submit" name="op" value="save">ذخیره تنظیمات SMS</button>
      </div>
    </div>
    <div class="panel" style="grid-column:1/-1">
      <div class="hd"><strong>ارسال آزمایشی</strong></div>
      <div class="bd" style="padding:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
        <label style="min-width:180px">موبایل گیرنده<input name="test_phone" placeholder="0912xxxxxxx" dir="ltr"></label>
        <label style="flex:1;min-width:220px">متن<input name="test_text" value="تست پیامک از سامانه حساب"></label>
        <button class="btn ghost" type="submit" name="op" value="test">ارسال تست</button>
        <a class="btn ghost" href="<?= e(url('/settings')) ?>">بازگشت</a>
      </div>
    </div>
  </div>
</form>
