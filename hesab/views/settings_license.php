<?php
/** @var array $lic */
/** @var string $fingerprint */
$plans = [
    ['code' => 'starter', 'title' => 'استارتر', 'seats' => 3, 'months' => 12, 'modules' => ['accounting','reports','invoices']],
    ['code' => 'pro', 'title' => 'حرفه‌ای', 'seats' => 10, 'months' => 12, 'modules' => ['accounting','treasury','reports','invoices','sms']],
    ['code' => 'enterprise', 'title' => 'سازمانی', 'seats' => 50, 'months' => 12, 'modules' => ['*']],
];
?>
<div class="grid" style="grid-template-columns:1.2fr 1fr;gap:12px">
  <div class="panel">
    <div class="hd"><strong>وضعیت لایسنس نصب</strong></div>
    <div class="bd" style="padding:12px;line-height:1.9;font-size:13px">
      <div>وضعیت: <strong><?= e(License::statusLabel()) ?></strong> <?= License::isValid() ? '✅' : '⚠️' ?></div>
      <div>مشتری: <?= e((string)($lic['customer'] ?? '—')) ?></div>
      <div>نوع: <?= e((string)($lic['type'] ?? '—')) ?></div>
      <div>انقضا: <span dir="ltr"><?= e((string)($lic['expires_at'] ?? '—')) ?></span></div>
      <div>نشست‌ها (seats): <?= (int)($lic['seats'] ?? 0) ?></div>
      <div>ماژول‌ها: <span dir="ltr"><?= e(implode(', ', $lic['modules'] ?? [])) ?></span></div>
      <div>اثرانگشت این نصب: <code dir="ltr"><?= e($fingerprint) ?></code></div>
      <div>دامنه ثبت‌شده: <?= e((string)($lic['domain'] ?? '—')) ?></div>
    </div>
    <form method="post" action="<?= e(url('/settings/license')) ?>" class="form" style="padding:12px;border-top:1px solid var(--line)">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="activate">
      <label>کلید لایسنس
        <textarea name="license_key" rows="3" dir="ltr" placeholder="XXXX.YYYY" required></textarea>
      </label>
      <button class="btn" type="submit">فعال‌سازی</button>
    </form>
    <form method="post" action="<?= e(url('/settings/license')) ?>" class="form" style="padding:0 12px 12px">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="clear">
      <button class="btn ghost" type="submit" onclick="return confirm('لایسنس پاک شود و به حالت آزمایشی برگردد؟')">بازگشت به آزمایشی</button>
    </form>
  </div>

  <div class="panel">
    <div class="hd"><strong>بسته‌های فروش</strong></div>
    <div class="bd">
      <table class="data">
        <thead><tr><th>بسته</th><th>کاربر</th><th>مدت</th><th>ماژول</th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): ?>
          <tr>
            <td><?= e($p['title']) ?></td>
            <td><?= (int)$p['seats'] ?></td>
            <td><?= (int)$p['months'] ?> ماه</td>
            <td style="font-size:11px" dir="ltr"><?= e(implode(', ', $p['modules'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="<?= e(url('/settings/license')) ?>" class="form" style="padding:12px;border-top:1px solid var(--line)">
      <?= csrf_field() ?>
      <strong>صدور کلید (فروش / نمایندگی)</strong>
      <label>نام مشتری<input name="customer" required></label>
      <label>بسته
        <select name="plan">
          <?php foreach ($plans as $p): ?>
            <option value="<?= e($p['code']) ?>"><?= e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>اتصال به این نصب
        <select name="bind">
          <option value="any">آزاد (ANY)</option>
          <option value="fp">قفل به اثرانگشت فعلی</option>
        </select>
      </label>
      <button class="btn" type="submit" name="op" value="issue">صدور کلید لایسنس</button>
    </form>
    <?php if (!empty($issuedKey)): ?>
      <div class="bd" style="padding:12px">
        <div class="flash ok">کلید صادر شد — کپی کنید:</div>
        <textarea rows="4" dir="ltr" readonly style="width:100%"><?= e($issuedKey) ?></textarea>
      </div>
    <?php endif; ?>
  </div>
</div>
