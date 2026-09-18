<?php

namespace Plugins\AboutDesk\src\Support;

use App\Support\SettingsStore;

class AboutCopy
{
    public const KEY = 'about_page_hddland';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'kicker' => 'HDD Land · ایران و جهان',
            'title_3d' => 'سرزمین هارد',
            'brand' => 'HDD Land',
            'legal' => 'فرازنت آمل',
            'lead' => 'در ایران و جهان با برند HDD Land شناخته می‌شود.',
            'p1' => 'شرکت سرزمین هارد (فرازنت آمل) در ایران و جهان با برند HDD Land شناخته می‌شود و از طریق وب‌سایت‌های www.hdd-land.com و www.hddgod.com با مشتریان، همکاران و شرکای بین‌المللی در ارتباط است.',
            'p2' => 'این مجموعه بیش از دو دهه در بازیابی اطلاعات و تعمیر تخصصی تجهیزات ذخیره‌سازی فعالیت می‌کند. حوزه کار آن بازیابی داده از هارد دیسک، سرور، موبایل، SSD، M.2، NVMe و انواع استوریج، و همچنین تعمیر تخصصی هارد دیسک، SSD، M.2 و NVMe است.',
            'p3' => 'HDD Land نماینده رسمی فروش و پشتیبانی محصولات SeDiv در جهان و تنها نماینده برتر این برند است. این جایگاه نتیجه سال‌ها کار تخصصی، تجهیز آزمایشگاه و تعهد به استانداردهای بین‌المللی بازیابی و تعمیر است.',
            'p4' => 'آزمایشگاه شرکت به تجهیزات روز دنیا مجهز است؛ از جمله PC-3000، MRT PRO، DFL، DeepSpar، SeDiv و WD Marvel، به‌همراه نرم‌افزارهای تخصصی بازیابی اطلاعات و تعمیر هارد دیسک. این زیرساخت امکان کار روی کیس‌های پیچیده سازمانی و حساس را با دقت آزمایشگاهی فراهم می‌کند.',
            'p5' => 'از افتخارات این مجموعه، در اختیار داشتن چندین نمایندگی انحصاری در سطح جهان است؛ دستاوردی که حاصل تلاش مستمر تیم HDD Land و اعتماد شرکای بین‌المللی به کیفیت خدمات این شرکت است.',
            'lab_title' => 'آزمایشگاه تخصصی',
            'tools' => "PC-3000\nMRT PRO\nDFL\nDeepSpar\nSeDiv\nWD Marvel",
            'site_1_label' => 'hdd-land.com',
            'site_1_url' => 'https://www.hdd-land.com',
            'site_2_label' => 'hddgod.com',
            'site_2_url' => 'https://www.hddgod.com',
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = SettingsStore::get(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_merge(self::defaults(), is_array($raw) ? $raw : []);
    }

    public static function save(array $d): void
    {
        $s = self::defaults();
        foreach ([
            'kicker' => 80, 'title_3d' => 40, 'brand' => 40, 'legal' => 80, 'lead' => 200,
            'p1' => 800, 'p2' => 800, 'p3' => 800, 'p4' => 900, 'p5' => 800,
            'lab_title' => 80, 'tools' => 400,
            'site_1_label' => 80, 'site_1_url' => 200,
            'site_2_label' => 80, 'site_2_url' => 200,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k])), 0, $max);
        }
        SettingsStore::set(self::KEY, $s);
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

    /** @return list<string> */
    public static function paragraphs(?array $copy = null): array
    {
        $copy ??= self::get();
        $out = [];
        foreach (['p1', 'p2', 'p3', 'p4', 'p5'] as $k) {
            $t = trim((string) ($copy[$k] ?? ''));
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }
}
