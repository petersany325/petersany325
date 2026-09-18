<?php

namespace Plugins\ServicesDesk\src\Support;

use App\Support\SettingsStore;

class ServicesCopy
{
    public const KEY = 'org_services_page';

    /** @return list<string> */
    public static function keys(): array
    {
        return ['supply', 'recovery', 'repair', 'warranty'];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'kicker' => 'خدمات سازمانی HDD Land',
            'title' => 'تأمین، بازیابی و پوشش ذخیره‌سازی برای سازمان',
            'lead' => 'سرزمین هارد برای سازمان، شرکت و فروشگاه چهار مسیر مشخص دارد: تأمین قطعه، بازیابی اطلاعات، تعمیر تخصصی، و پوشش گارانتی. هر مسیر خروجی قابل پیگیری دارد.',
            'intro' => 'این صفحه معرفی فروشگاه نیست. اینجا خدمات سازمانی است؛ یعنی سازمان چه مشکلی دارد و HDD Land چه تحویل می‌دهد.',
            's1_on' => true,
            's1_title' => 'تأمین و تجهیز ذخیره‌سازی',
            's1_audience' => 'برای سازمان، شعب، پروژه نظارتی و انبار فروشگاهی',
            's1_text' => 'استعلام مدل، پیش‌فاکتور رسمی و تأمین هارد، SSD، NVMe، NAS و هارد سرور. ظرفیت آرشیو و بکاپ را بر اساس بار واقعی پیشنهاد می‌دهیم، نه بر اساس کاتالوگ کلی.',
            's1_cta' => 'درخواست استعلام',
            's1_url' => '/contact',
            's2_on' => true,
            's2_title' => 'بازیابی اطلاعات سازمانی',
            's2_audience' => 'برای سرور، RAID، استوریج، NAS و موبایل سازمانی',
            's2_text' => 'وقتی داده سازمان از بین می‌رود، مسیر آزمایشگاهی جدا از فروش قطعه است. کیس با تجهیزات تخصصی بررسی می‌شود و وضعیت کار به واحد IT یا مدیر پروژه گزارش می‌شود.',
            's2_cta' => 'ثبت کیس بازیابی',
            's2_url' => '/contact',
            's3_on' => true,
            's3_title' => 'تعمیر تخصصی هارد و SSD',
            's3_audience' => 'برای کاهش خواب سیستم و نجات رسانه خراب',
            's3_text' => 'تعمیر هارد دیسک، SSD، M.2 و NVMe با PC-3000، MRT PRO، DFL، DeepSpar، SeDiv و WD Marvel. هدف، برگرداندن رسانه یا داده با دقت آزمایشگاهی است.',
            's3_cta' => 'مشاوره تعمیر',
            's3_url' => '/contact',
            's4_on' => true,
            's4_title' => 'پوشش و پشتیبانی گارانتی',
            's4_audience' => 'برای فروشگاه، شرکت و سازمانی که پوشش می‌خواهد',
            's4_text' => 'نماینده رسمی فروش و پشتیبانی SeDiv. استعلام سریال مشتری جداست؛ درخواست پوشش جدید از صفحه ثبت گارانتی ثبت می‌شود و وضعیت با پیامک اعلام می‌گردد.',
            's4_cta' => 'ثبت پوشش گارانتی',
            's4_url' => '/warranty-register',
            'lab_title' => 'زیرساخت آزمایشگاه',
            'tools' => "PC-3000\nMRT PRO\nDFL\nDeepSpar\nSeDiv\nWD Marvel",
            'cta_title' => 'واحد سازمانی HDD Land',
            'cta_text' => 'برای پیش‌فاکتور، کیس بازیابی یا پوشش گارانتی با واحد سازمانی تماس بگیرید.',
            'cta_label' => 'تماس با واحد سازمانی',
            'cta_url' => '/contact',
            'alt_label' => 'استعلام گارانتی سریال',
            'alt_url' => '/serial-check',
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = SettingsStore::get(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $out = array_merge(self::defaults(), is_array($raw) ? $raw : []);
        foreach (['s1_on', 's2_on', 's3_on', 's4_on'] as $k) {
            $out[$k] = ! empty($out[$k]);
        }

        return $out;
    }

    public static function save(array $d): void
    {
        $s = self::defaults();
        foreach (['s1_on', 's2_on', 's3_on', 's4_on'] as $k) {
            $s[$k] = ! empty($d[$k]);
        }
        foreach ([
            'kicker' => 80, 'title' => 180, 'lead' => 500, 'intro' => 400,
            's1_title' => 80, 's1_audience' => 160, 's1_text' => 500, 's1_cta' => 60, 's1_url' => 200,
            's2_title' => 80, 's2_audience' => 160, 's2_text' => 500, 's2_cta' => 60, 's2_url' => 200,
            's3_title' => 80, 's3_audience' => 160, 's3_text' => 500, 's3_cta' => 60, 's3_url' => 200,
            's4_title' => 80, 's4_audience' => 160, 's4_text' => 500, 's4_cta' => 60, 's4_url' => 200,
            'lab_title' => 80, 'tools' => 400,
            'cta_title' => 120, 'cta_text' => 300, 'cta_label' => 60, 'cta_url' => 200,
            'alt_label' => 60, 'alt_url' => 200,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k])), 0, $max);
        }
        SettingsStore::set(self::KEY, $s);
    }

    /** @return list<array{title:string,audience:string,text:string,cta:string,url:string}> */
    public static function cards(?array $copy = null): array
    {
        $c = $copy ?? self::get();
        $out = [];
        foreach ([1, 2, 3, 4] as $i) {
            if (empty($c['s'.$i.'_on'])) {
                continue;
            }
            $out[] = [
                'title' => (string) ($c['s'.$i.'_title'] ?? ''),
                'audience' => (string) ($c['s'.$i.'_audience'] ?? ''),
                'text' => (string) ($c['s'.$i.'_text'] ?? ''),
                'cta' => (string) ($c['s'.$i.'_cta'] ?? ''),
                'url' => (string) ($c['s'.$i.'_url'] ?? '/contact'),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function tools(?array $copy = null): array
    {
        $copy ??= self::get();
        $out = [];
        foreach (preg_split('/\R/u', (string) ($copy['tools'] ?? '')) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}
