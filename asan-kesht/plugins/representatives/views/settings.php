<?php /** @var array $values */ /** @var array $rules */ /** @var array $lines */ ?>
<p class="muted">این تنظیم‌ها سیاست همکاری را مشخص می‌کند: انحصار شهر، مدل خرید مجدد یا پورسانت، سقف و مهلت اعتبار، و اینکه پورسانت روی مبلغ فروش است یا سود ناخالص و کی قطعی می‌شود. سود ناخالص برابر مبلغ فروش منهای بهای تمام‌شده است. اگر بهای تمام‌شده ثبت نشده باشد، پایه همان مبلغ فروش می‌ماند و مبنا همچنان سود ناخالص است. اگر بهای تمام‌شده به اندازه فروش یا بیشتر باشد، برای آن ردیف پورسانتی ساخته نمی‌شود.</p>
<form method="post" action="<?= e(url('/admin/dealers/settings')) ?>" class="stack">
  <?= csrf_field() ?>
  <label class="check"><input type="checkbox" name="enforce_exclusive" <?= $values['enforce_exclusive'] === '1' ? 'checked' : '' ?>> انحصار شهر اجرا شود</label>
  <label class="check"><input type="checkbox" name="allow_resale" <?= $values['allow_resale'] === '1' ? 'checked' : '' ?>> مدل خرید از شرکت و فروش دوباره مجاز است</label>
  <label class="check"><input type="checkbox" name="allow_commission" <?= $values['allow_commission'] === '1' ? 'checked' : '' ?>> مدل فروش برای شرکت و پورسانت مجاز است</label>
  <div class="grid">
    <label>مهلت اعتبار پیش‌فرض (روز) <input name="default_credit_days" value="<?= e($values['default_credit_days']) ?>"></label>
    <label>پورسانت پیش‌فرض (درصد) <input name="default_commission_percent" value="<?= e($values['default_commission_percent']) ?>"></label>
    <label>مهلت اثر مرجوعی (روز) <input name="return_days" value="<?= e($values['return_days']) ?>"></label>
    <label>مبنای پورسانت
      <select name="commission_basis">
        <option value="sale" <?= $values['commission_basis'] === 'sale' ? 'selected' : '' ?>>مبلغ فروش</option>
        <option value="gross" <?= $values['commission_basis'] === 'gross' ? 'selected' : '' ?>>سود ناخالص (فروش منهای بهای تمام‌شده)</option>
      </select>
    </label>
    <label>زمان قطعی شدن
      <select name="commission_settle">
        <option value="collection" <?= $values['commission_settle'] === 'collection' ? 'selected' : '' ?>>هنگام وصول وجه</option>
        <option value="sale" <?= $values['commission_settle'] === 'sale' ? 'selected' : '' ?>>هنگام تأیید فروش</option>
      </select>
    </label>
  </div>
  <button type="submit">ذخیره تنظیمات</button>
</form>
<?php if ($app->pluginEnabled('cooperation')): ?>
  <p><a href="<?= e(url('/admin/dealers/cooperation')) ?>">قیمت همکاری و حاشیه سطح‌ها</a></p>
<?php endif; ?>
<h2>قاعده خاص پورسانت</h2>
<form method="post" action="<?= e(url('/admin/dealers/rules')) ?>" class="grid">
  <?= csrf_field() ?>
  <label>شناسه نماینده، صفر یعنی همه <input name="dealer_id" value="0"></label>
  <label>سطح <input name="level" placeholder="طلایی"></label>
  <label>گروه کالا
    <select name="line">
      <option value="">همه</option>
      <?php foreach ($lines as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>درصد <input name="percent" required></label>
  <label>مبنا
    <select name="basis"><option value="sale">مبلغ فروش</option><option value="gross">سود ناخالص</option></select>
  </label>
  <label>قطعی شدن
    <select name="settle_on"><option value="collection">وصول وجه</option><option value="sale">تأیید فروش</option></select>
  </label>
  <button type="submit">ثبت قاعده</button>
</form>
<ul>
  <?php foreach ($rules as $rule): ?>
    <li>نماینده <?= e(fa_digits((string) $rule['dealer_id'])) ?> — سطح <?= e((string) $rule['level']) ?> — <?= e(fa_digits((string) $rule['percent'])) ?>٪ — <?= e(dealer_basis_label((string) $rule['basis'])) ?> — <?= e(dealer_settle_label((string) $rule['settle_on'])) ?></li>
  <?php endforeach; ?>
</ul>
