<?php
$licLabel = class_exists('License') ? License::statusLabel() : '—';
$cards = [
    ['title' => 'تنظیمات حسابداری', 'desc' => 'کدینگ، موجودی، posting rules', 'href' => '/settings/accounting', 'need' => 'settings.manage'],
    ['title' => 'میانبر کیبورد', 'desc' => 'شورتکات و حالت حسابدار/مدیریتی', 'href' => '/settings/shortcuts', 'need' => 'settings.manage'],
    ['title' => 'فاکتور و چاپ پیشرفته', 'desc' => 'هویت شرکت، مالیات، قالب چاپ A4/حرارتی', 'href' => '/settings/invoice', 'need' => 'invoices.manage'],
    ['title' => 'پیامک نیازپرداز', 'desc' => 'OTP ورود، قالب ویزیتور، تست ارسال', 'href' => '/settings/sms', 'need' => 'sms.manage'],
    ['title' => 'ویزیتور و کارتابل', 'desc' => 'تعریف، درصد پورسانت، بازدید، گزارش', 'href' => '/visitors', 'need' => 'visitors.manage'],
    ['title' => 'لایسنس و فروش', 'desc' => 'فعال‌سازی، وضعیت، صدور کلید', 'href' => '/settings/license', 'need' => 'license.manage'],
    ['title' => 'کاربران و دسترسی', 'desc' => 'نقش‌ها، موبایل، مجوزها', 'href' => '/users', 'need' => 'users.manage'],
    ['title' => 'پروفایل من', 'desc' => 'نام، موبایل، تغییر رمز', 'href' => '/settings/profile', 'need' => null],
];
$user = current_user();
?>
<div class="panel" style="margin-bottom:12px">
  <div class="hd"><strong>مرکز تنظیمات سامانه</strong></div>
  <div class="bd" style="padding:12px;color:var(--muted);font-size:12.5px;line-height:1.7">
    وضعیت لایسنس: <strong style="color:var(--text)"><?= e($licLabel) ?></strong>
    · اثرانگشت نصب: <code dir="ltr"><?= e(License::fingerprint()) ?></code>
  </div>
</div>
<div class="report-grid">
  <?php foreach ($cards as $c): ?>
    <?php if ($c['need'] && !Permission::can($user, $c['need']) && $c['need'] !== null) continue; ?>
    <a href="<?= e(url($c['href'])) ?>">
      <strong><?= e($c['title']) ?></strong>
      <div style="color:var(--muted);margin-top:6px;font-size:12px"><?= e($c['desc']) ?></div>
    </a>
  <?php endforeach; ?>
</div>
