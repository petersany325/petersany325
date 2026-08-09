<?php

/**
 * Restore full product menus/lookups on an install that was emptied.
 * Also set shop name: ?name=نام شرکت
 * Open: /public/restore-product-defaults.php
 * DELETE after use.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$base = dirname(__DIR__);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$msgs = [];
try {
    $shop = trim((string) ($_GET['name'] ?? ''));
    if ($shop === '' || preg_match('/سرزمین\s*هارد|HDD\s*LAND/iu', $shop)) {
        $shop = trim((string) env('APP_NAME', 'تعمیرگاه')) ?: 'تعمیرگاه';
        if (preg_match('/سرزمین\s*هارد|HDD\s*LAND/iu', $shop)) {
            $shop = 'تعمیرگاه';
        }
    }

    collect(['اینستاگرام', 'گوگل', 'معرفی دوستان', 'تابلو مغازه', 'سایت'])
        ->each(fn ($n) => App\Models\ReferralSource::query()->firstOrCreate(['name' => $n]));

    $lookupSeed = [
        'admission_type' => ['حضوری', 'پستی', 'پیک', 'نمایندگی'],
        'service_type' => ['تعمیر', 'بازیابی اطلاعات', 'تعویض قطعه', 'عیب‌یابی', 'نصب سیستم'],
        'repair_type' => ['سخت‌افزاری', 'نرم‌افزاری', 'دیتا ریکاوری', 'گارانتی'],
        'warranty_type' => ['فاقد گارانتی و بیمه', 'گارانتی شرکتی', 'گارانتی تعمیرگاه', 'بیمه'],
        'hdd_capacity' => ['120GB', '250GB', '320GB', '500GB', '1TB', '2TB', '4TB'],
        'brand_model' => ['WD My Passport', 'Seagate Backup Plus', 'Toshiba Canvio', 'Samsung T7', 'Laptop Generic'],
        'reported_fault' => ['روشن نمی‌شود', 'صدای غیرعادی', 'عدم شناسایی', 'کندی شدید', 'آسیب فیزیکی', 'نیاز به بازیابی اطلاعات'],
        'accessories' => ['ندارد', 'کابل USB', 'آداپتور', 'جعبه', 'کابل + جعبه'],
        'appearance' => ['سالم و بدون خط و خش', 'خط و خش سطحی', 'ضرب‌دیدگی', 'برچسب کنده شده', 'وضعیت متوسط'],
    ];
    foreach ($lookupSeed as $group => $names) {
        foreach ($names as $i => $name) {
            App\Models\LookupOption::query()->firstOrCreate(
                ['group_key' => $group, 'name' => $name],
                ['sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }
    collect(['روشن نمی‌شود', 'صدای غیرعادی', 'عدم شناسایی', 'بازیابی اطلاعات', 'آسیب فیزیکی', 'نرم‌افزاری'])
        ->each(fn ($n) => App\Models\FaultType::query()->firstOrCreate(['name' => $n]));

    App\Models\AppSetting::setValue('invoice_shop_name', $shop);
    App\Models\AppSetting::setValue('shop_tagline', 'سیستم مدیریت تعمیرات');
    App\Models\AppSetting::setValue('brand_logo_version', (string) time());

    $msgs[] = 'تعاریف/منوهای محصول برگردانده شد.';
    $msgs[] = 'نام نمایشی: '.$shop;
    $ok = true;
} catch (Throwable $e) {
    $ok = false;
    $msgs[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title>بازگردانی تعاریف</title>
<style>
body{font-family:Tahoma,sans-serif;background:#eef1f5;padding:24px}
.box{max-width:640px;margin:0 auto;background:#fff;border:1px solid #c9d0da;border-radius:10px;padding:18px}
.ok{background:#e8f8ef;color:#0f6b3a;padding:10px;border-radius:8px;margin:8px 0;border:1px solid #9dcfb0}
.bad{background:#fde8e8;color:#b42318;padding:10px;border-radius:8px;margin:8px 0;border:1px solid #f3b4b4}
a.btn{display:inline-block;margin-top:12px;background:#1d4f91;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px}
</style></head>
<body><div class="box">
<h1>بازگردانی نرم‌افزار کامل</h1>
<?php foreach ($msgs as $m): ?><div class="<?= !empty($ok)?'ok':'bad' ?>"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<p>برای نام شرکت: <code>?name=نام شرکت</code></p>
<a class="btn" href="/index.php/login">ورود ادمین</a>
<p style="font-size:12px;color:#667">بعد از کار این فایل را حذف کنید.</p>
</div></body></html>
