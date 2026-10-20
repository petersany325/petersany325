<?php /** @var array<string, string> $sheet */ ?>
<?php require __DIR__ . '/nav.php'; ?>
<form method="post" action="<?= e(url('/admin/accounting/invoice-settings')) ?>" class="stack">
  <?= csrf_field() ?>
  <fieldset>
    <legend>سربرگ فاکتور</legend>
    <div class="grid">
      <label>نام فروشنده <input name="sheet_title" required value="<?= e($sheet['title']) ?>"></label>
      <label>شعار <input name="sheet_subtitle" value="<?= e($sheet['subtitle']) ?>"></label>
      <label>تلفن <input name="sheet_phone" value="<?= e($sheet['phone']) ?>"></label>
      <label>ایمیل <input name="sheet_email" value="<?= e($sheet['email']) ?>"></label>
      <label>نشانی <input name="sheet_address" value="<?= e($sheet['address']) ?>"></label>
      <label>کد پستی <input name="sheet_postal" value="<?= e($sheet['postal']) ?>"></label>
      <label>شناسه ملی <input name="sheet_national" value="<?= e($sheet['national']) ?>"></label>
      <label>کد اقتصادی <input name="sheet_economic" value="<?= e($sheet['economic']) ?>"></label>
    </div>
    <label>متن زیر سربرگ <input name="sheet_header" value="<?= e($sheet['header']) ?>"></label>
    <label>متن پایین برگه <textarea name="sheet_footer"><?= e($sheet['footer']) ?></textarea></label>
    <div class="letterhead">
      <strong><?= e($sheet['title']) ?></strong>
      <span><?= e($sheet['subtitle']) ?></span>
      <span><?= e($sheet['phone']) ?> <?= e($sheet['address']) ?></span>
    </div>
  </fieldset>
  <fieldset>
    <legend>تنظیم فاکتور</legend>
    <div class="grid">
      <label>پیشوند پیش‌فاکتور <input name="prefix_proforma" maxlength="6" value="<?= e($sheet['prefix_proforma']) ?>"></label>
      <label>پیشوند فروش <input name="prefix_sale" maxlength="6" value="<?= e($sheet['prefix_sale']) ?>"></label>
      <label>پیشوند خرید <input name="prefix_purchase" maxlength="6" value="<?= e($sheet['prefix_purchase']) ?>"></label>
      <label>درصد مالیات <input name="sheet_tax_percent" inputmode="numeric" value="<?= e($sheet['tax_percent']) ?>"></label>
      <label>اعتبار پیش‌فاکتور (روز) <input name="sheet_proforma_days" inputmode="numeric" value="<?= e($sheet['proforma_days']) ?>"></label>
      <label>عنوان امضای فروشنده <input name="sheet_sign_seller" value="<?= e($sheet['sign_seller']) ?>"></label>
      <label>عنوان امضای خریدار <input name="sheet_sign_buyer" value="<?= e($sheet['sign_buyer']) ?>"></label>
    </div>
    <label class="check"><input type="checkbox" name="sheet_tax" <?= $sheet['tax_on'] === '1' ? 'checked' : '' ?>> مالیات روی برگه چاپ محاسبه شود</label>
    <label class="check"><input type="checkbox" name="sheet_show_paid" <?= $sheet['show_paid'] === '1' ? 'checked' : '' ?>> دریافت‌ها در چاپ بیاید</label>
    <label class="check"><input type="checkbox" name="sheet_show_status" <?= $sheet['show_status'] === '1' ? 'checked' : '' ?>> وضعیت سند در چاپ بیاید</label>
    <p class="muted">مالیات فقط روی برگه چاپ به جمع اقلام اضافه می‌شود. مبلغ ثبت‌شده در حساب همان جمع ردیف‌ها می‌ماند. پیشوند جدید روی شماره‌های بعدی اثر می‌گذارد.</p>
  </fieldset>
  <button type="submit">ذخیره سربرگ و تنظیم فاکتور</button>
</form>
