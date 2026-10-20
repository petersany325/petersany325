<?php
/** @var array $values */
/** @var array $fields */
/** @var array $lines */
/** @var array $entities */
/** @var array $cooperations */
/** @var array $sale_models */
/** @var array $rows */
/** @var array $statuses */
/** @var array $kinds */
$flag = static function (string $key) use ($values): string {
    return ($values[$key] ?? '0') === '1' ? 'checked' : '';
};
$showKeys = [
    'username' => 'نام کاربری',
    'province' => 'استان',
    'city' => 'شهر',
    'address' => 'نشانی',
    'national_id' => 'کد ملی یا شناسه',
    'shop' => 'نام فروشگاه',
    'note' => 'توضیح',
];
$offered = array_filter(explode(',', (string) ($values['offered_lines'] ?? '')));
?>
<p class="muted">این صفحه تنها جای تنظیم فرم عمومی است. بازدیدکننده فیلد، متن، اجبار رمز یا باز و بسته بودن ثبت‌نام را عوض نمی‌کند. مشتریِ بدون نیاز به بررسی همان لحظه وارد می‌شود. درخواست نمایندگی تا تأیید شما وارد نمی‌شود.</p>
<form method="post" action="<?= e(url('/admin/register')) ?>" class="stack">
  <?= csrf_field() ?>
  <fieldset>
    <legend>باز بودن</legend>
    <label class="check"><input type="checkbox" name="open" <?= $flag('open') ?>> فرم ثبت‌نام روی سایت باز باشد</label>
    <label class="check"><input type="checkbox" name="allow_customer" <?= $flag('allow_customer') ?>> ثبت‌نام مشتری مجاز است</label>
    <label class="check"><input type="checkbox" name="allow_representative" <?= $flag('allow_representative') ?>> درخواست نمایندگی شهرستان مجاز است</label>
    <label class="check"><input type="checkbox" name="customer_needs_review" <?= $flag('customer_needs_review') ?>> مشتری هم تا تأیید مدیر وارد نشود</label>
    <label class="check"><input type="checkbox" name="rep_activate" <?= $flag('rep_activate') ?>> با تأیید درخواست، پرونده نمایندگی هم فعال شود و بتواند سفارش بدهد</label>
  </fieldset>
  <fieldset>
    <legend>رمز و شرایط</legend>
    <label class="check"><input type="checkbox" name="require_password" <?= $flag('require_password') ?>> رمز در فرم اجباری باشد</label>
    <label class="check"><input type="checkbox" name="require_terms" <?= $flag('require_terms') ?>> پذیرش متن شرایط اجباری باشد</label>
    <label>حداقل طول رمز
      <input name="min_password" value="<?= e((string) $values['min_password']) ?>" inputmode="numeric">
    </label>
    <label>موبایل خبر ثبت‌نام تازه، خالی یعنی فقط اگر تلفن قالب پر باشد
      <input name="notify_phone" value="<?= e((string) $values['notify_phone']) ?>" placeholder="09...">
    </label>
  </fieldset>
  <fieldset>
    <legend>فیلدهای فرم</legend>
    <div class="table-wrap">
      <table>
        <thead><tr><th>فیلد</th><th>نمایش</th><th>اجباری</th></tr></thead>
        <tbody>
        <?php foreach ($showKeys as $key => $label): ?>
          <tr>
            <td><?= e($label) ?></td>
            <td><input type="checkbox" name="show_<?= e($key) ?>" <?= $flag('show_' . $key) ?>></td>
            <td><input type="checkbox" name="require_<?= e($key) ?>" <?= $flag('require_' . $key) ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <label class="check"><input type="checkbox" name="show_entity" <?= $flag('show_entity') ?>> انتخاب شخص یا شرکت در فرم باشد</label>
    <label class="check"><input type="checkbox" name="show_cooperation" <?= $flag('show_cooperation') ?>> نوع همکاری در فرم نمایندگی باشد</label>
    <label class="check"><input type="checkbox" name="show_sale_model" <?= $flag('show_sale_model') ?>> مدل فروش در فرم نمایندگی باشد</label>
    <label class="check"><input type="checkbox" name="show_lines" <?= $flag('show_lines') ?>> گروه کالا در فرم نمایندگی باشد</label>
  </fieldset>
  <fieldset>
    <legend>پیش‌فرض پرونده نمایندگی</legend>
    <p class="muted">اگر فیلد روی فرم خاموش باشد، همین مقدار در پرونده می‌نشیند. مدل فروش همچنان تابع روشن بودن آن مدل در تنظیمات نمایندگی است.</p>
    <div class="grid">
      <label>شخص یا شرکت
        <select name="default_kind">
          <?php foreach ($entities as $key => $label): ?>
            <option value="<?= e((string) $key) ?>" <?= ($values['default_kind'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>همکاری
        <select name="default_cooperation">
          <?php foreach ($cooperations as $key => $label): ?>
            <option value="<?= e((string) $key) ?>" <?= ($values['default_cooperation'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>مدل فروش
        <select name="default_sale_model">
          <?php foreach ($sale_models as $key => $label): ?>
            <option value="<?= e((string) $key) ?>" <?= ($values['default_sale_model'] ?? '') === $key ? 'selected' : '' ?>><?= e((string) $label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="line-picks">
      <?php foreach ($lines as $key => $label): ?>
        <label class="check"><input type="checkbox" name="offered_lines[]" value="<?= e((string) $key) ?>" <?= in_array($key, $offered, true) ? 'checked' : '' ?>> <?= e((string) $label) ?></label>
      <?php endforeach; ?>
    </div>
  </fieldset>
  <fieldset>
    <legend>متن‌هایی که روی فرم دیده می‌شود</legend>
    <label>معرفی <textarea name="intro"><?= e((string) $values['intro']) ?></textarea></label>
    <label>شرایط <textarea name="terms"><?= e((string) $values['terms']) ?></textarea></label>
    <label>پیام فرمِ بسته <textarea name="closed_message"><?= e((string) $values['closed_message']) ?></textarea></label>
    <label>پیام مشتریِ فعال‌شده <textarea name="success_customer"><?= e((string) $values['success_customer']) ?></textarea></label>
    <label>پیام مشتریِ در انتظار <textarea name="success_review"><?= e((string) $values['success_review']) ?></textarea></label>
    <label>پیام درخواست نمایندگی <textarea name="success_representative"><?= e((string) $values['success_representative']) ?></textarea></label>
  </fieldset>
  <button type="submit">ذخیره تنظیمات</button>
</form>

<h2>صف درخواست‌ها</h2>
<div class="table-wrap">
  <table>
    <thead><tr><th>زمان</th><th>درخواست</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e(jalali_date((string) $row['created_at'])) ?></td>
        <td>
          <strong><?= e((string) $row['name']) ?></strong>
          <div class="muted"><?= e($kinds[(string) $row['kind']] ?? (string) $row['kind']) ?> — <?= e(fa_digits((string) $row['phone'])) ?></div>
          <div class="muted"><?= e(trim((string) $row['province'] . ' ' . (string) $row['city'])) ?> <?= e((string) $row['shop_name']) ?></div>
          <?php if ((string) $row['note'] !== ''): ?><div class="muted"><?= e((string) $row['note']) ?></div><?php endif; ?>
        </td>
        <td><?= e($statuses[(string) $row['status']] ?? (string) $row['status']) ?></td>
        <td>
          <?php if ((int) ($row['dealer_id'] ?? 0) > 0): ?>
            <a href="<?= e(url('/admin/dealers/' . (int) $row['dealer_id'])) ?>">پرونده</a>
          <?php endif; ?>
          <?php if ((string) $row['status'] === 'pending'): ?>
            <form method="post" action="<?= e(url('/admin/register/decide')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
              <input type="hidden" name="decision" value="approve">
              <button type="submit">تأیید</button>
            </form>
            <form method="post" action="<?= e(url('/admin/register/decide')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
              <input type="hidden" name="decision" value="reject">
              <button type="submit">رد</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
