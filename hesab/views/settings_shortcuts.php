<?php
/** @var array $catalog */
/** @var array $map */
/** @var string $mode */
/** @var array $conflicts */
/** @var array $groups */
$conflicts = $conflicts ?? [];
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd">
    <strong>تنظیمات میانبر کیبورد</strong>
    <a class="btn ghost" href="<?= e(url('/settings/accounting')) ?>">بازگشت به تنظیمات حسابداری</a>
  </div>
  <div class="bd" style="padding:12px;color:var(--muted);font-size:12.5px;line-height:1.7">
    میانبرها مطابق استاندارد داخلی حسابداری ERP تعریف شده‌اند و برای هر کاربر قابل شخصی‌سازی هستند.
    در «حالت حسابدار» تمرکز روی ثبت سریع با کیبورد است؛ در «حالت مدیریتی» میانبرها فعال می‌مانند ولی رابط برای گزارش و داشبورد مناسب‌تر است.
    موارد خاکستری هنوز در سامانه پیاده‌سازی نشده‌اند و فقط به‌عنوان رزرو استاندارد نمایش داده می‌شوند.
  </div>
</div>

<?php if ($conflicts): ?>
  <div class="flash warn" style="margin-bottom:10px">
    تداخل کلیدها: <?= e(implode(' | ', $conflicts)) ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= e(url('/settings/shortcuts')) ?>" class="form" id="shortcuts-form">
  <?= csrf_field() ?>

  <div class="panel" style="margin-bottom:12px">
    <div class="hd"><strong>حالت کاربری</strong></div>
    <div class="bd" style="padding:12px;display:flex;gap:18px;flex-wrap:wrap;align-items:center">
      <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
        <input type="radio" name="work_mode" value="accountant" <?= $mode === 'accountant' ? 'checked' : '' ?>>
        <span><strong>حالت حسابدار</strong> — ثبت سریع با کیبورد</span>
      </label>
      <label style="display:flex;gap:8px;align-items:center;cursor:pointer">
        <input type="radio" name="work_mode" value="manager" <?= $mode === 'manager' ? 'checked' : '' ?>>
        <span><strong>حالت مدیریتی</strong> — داشبورد و گزارش</span>
      </label>
    </div>
  </div>

  <?php foreach ($groups as $groupName => $rows): ?>
  <div class="panel" style="margin-bottom:12px">
    <div class="hd"><strong><?= e($groupName) ?></strong></div>
    <div class="bd" style="overflow:auto">
      <table class="data shortcuts-table">
        <thead>
          <tr>
            <th style="width:42%">عملکرد</th>
            <th style="width:22%">کلید میانبر</th>
            <th>وضعیت</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $id => $meta): ?>
          <?php $disabled = empty($meta['available']); ?>
          <tr class="<?= $disabled ? 'sc-disabled' : '' ?>">
            <td>
              <?= e($meta['label']) ?>
              <?php if (!empty($meta['hint']) && $disabled): ?>
                <div style="color:var(--muted);font-size:11px"><?= e($meta['hint']) ?></div>
              <?php elseif (!empty($meta['hint']) && ($meta['action'] ?? '') === 'nav'): ?>
                <div style="color:var(--muted);font-size:11px"><?= e($meta['hint']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <input
                class="sc-key"
                name="keys[<?= e($id) ?>]"
                value="<?= e($map[$id] ?? $meta['key']) ?>"
                placeholder="مثلاً Ctrl+Alt+J"
                autocomplete="off"
                <?= $disabled ? 'readonly' : '' ?>
              >
            </td>
            <td>
              <?php if ($disabled): ?>
                <span class="badge warn">رزرو / به‌زودی</span>
              <?php else: ?>
                <span class="badge ok">فعال</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="panel">
    <div class="hd"><strong>قابلیت‌های کیبوردی همراه</strong></div>
    <div class="bd" style="padding:12px;font-size:12.5px;line-height:1.8">
      <ul style="margin:0;padding-right:18px">
        <li>جداکننده سه‌رقمی خودکار در فیلدهای مبلغ سند</li>
        <li>حرکت بین ردیف‌های سند با Tab / Enter و افزودن ردیف با Insert</li>
        <li>نمایش میانبر کنار گزینه‌های منوی اصلی</li>
        <li>جلوگیری از ثبت دوباره هنگام فشردن متوالی ذخیره</li>
        <li>ماشین‌حساب سریع با F7</li>
      </ul>
    </div>
    <div class="hd" style="justify-content:flex-start;gap:8px">
      <button class="btn" type="submit" name="op" value="save">ذخیره میانبرها</button>
      <button class="btn ghost" type="submit" name="op" value="reset" onclick="return confirm('بازگشت به پیش‌فرض استاندارد؟')">بازگردانی پیش‌فرض</button>
    </div>
  </div>
</form>

<script>
(function () {
  document.querySelectorAll('.sc-key').forEach((input) => {
    if (input.readOnly) return;
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') return;
      e.preventDefault();
      if (e.key === 'Backspace' || e.key === 'Delete') {
        input.value = '';
        return;
      }
      const parts = [];
      if (e.ctrlKey || e.metaKey) parts.push('Ctrl');
      if (e.altKey) parts.push('Alt');
      if (e.shiftKey) parts.push('Shift');
      let k = e.key;
      if (k === 'Escape') k = 'Esc';
      else if (k === ' ') k = 'Space';
      else if (k.length === 1) k = k.toUpperCase();
      else if (k === 'Control' || k === 'Shift' || k === 'Alt' || k === 'Meta') return;
      parts.push(k);
      input.value = parts.join('+');
    });
  });
})();
</script>
