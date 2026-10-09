<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>میانبر کیبورد و حالت کاربری</strong>
    <a class="btn" href="<?= e(url('/settings/shortcuts')) ?>">تنظیمات شورتکات منو</a>
  </div>
  <div class="bd" style="padding:12px;display:flex;gap:16px;flex-wrap:wrap;align-items:center;justify-content:space-between">
    <div style="font-size:12.5px;line-height:1.7;color:var(--muted)">
      کلیدهای میانبر استاندارد (F1–F12، Ctrl+S، Ctrl+Alt+J و …) قابل شخصی‌سازی هستند.
      حالت فعلی:
      <strong style="color:var(--text)"><?= ($workMode ?? 'accountant') === 'manager' ? 'مدیریتی' : 'حسابدار' ?></strong>
    </div>
    <a class="btn ghost" href="<?= e(url('/settings/shortcuts')) ?>">باز کردن جدول میانبرها</a>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:14px">
  <div class="panel">
    <div class="hd"><strong>تنظیمات موتور حسابداری</strong></div>
    <form method="post" action="<?= e(url('/settings/accounting')) ?>" class="form" style="padding:12px">
      <?= csrf_field() ?>
      <label>ساختار کدینگ حساب‌ها
        <select name="coding_pattern">
          <?php foreach (['1/2/4/6' => '۱ / ۲ / ۴ / ۶ رقمی', '2/4/6/8' => '۲ / ۴ / ۶ / ۸ رقمی', 'flex' => 'کدینگ انعطاف‌پذیر'] as $k => $lab): ?>
            <option value="<?= e($k) ?>" <?= ($settings['coding_pattern'] ?? '') === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>سیستم موجودی انبار
        <select name="inventory_method">
          <option value="perpetual" <?= ($settings['inventory_method'] ?? '') === 'perpetual' ? 'selected' : '' ?>>موجودی دائمی</option>
          <option value="periodic" <?= ($settings['inventory_method'] ?? '') === 'periodic' ? 'selected' : '' ?>>موجودی ادواری</option>
        </select>
      </label>
      <label><input type="checkbox" name="auto_post_subsystems" <?= ($settings['auto_post_subsystems'] ?? '0') === '1' ? 'checked' : '' ?>> صدور خودکار اسناد از زیرسیستم‌ها</label>
      <label><input type="checkbox" name="enable_dimensions" <?= ($settings['enable_dimensions'] ?? '0') === '1' ? 'checked' : '' ?>> فعال‌سازی تفصیلی شناور و ابعاد تحلیلی</label>
      <label><input type="checkbox" name="allow_negative_stock" <?= ($settings['allow_negative_stock'] ?? '0') === '1' ? 'checked' : '' ?>> اجازه موجودی منفی در انبار</label>
      <p style="color:var(--muted);font-size:12px;margin:0">درخت حساب‌های عملیاتی همگام‌سازی‌شده: <?= (int)$treeCount ?> رکورد</p>
      <button class="btn" type="submit">ذخیره تنظیمات</button>
    </form>
  </div>

  <div class="panel">
    <div class="hd"><strong>نگاشت حساب‌های پیش‌فرض (posting rules)</strong></div>
    <form method="post" action="<?= e(url('/settings/accounting')) ?>" class="bd">
      <?= csrf_field() ?>
      <table class="data">
        <thead><tr><th>عملیات</th><th>کد حساب مقصد</th></tr></thead>
        <tbody>
        <?php foreach ($rules as $r): ?>
          <tr>
            <td><?= e($r['title']) ?><div style="color:var(--muted);font-size:11px"><?= e($r['code']) ?></div></td>
            <td><input name="rule_moein[<?= (int)$r['id'] ?>]" value="<?= e($r['moein_code'] ?? '') ?>" class="num" style="width:120px"></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="padding:12px"><button class="btn" type="submit">ذخیره نگاشت</button></div>
    </form>
  </div>
</div>
