<?php

namespace Plugins\CorpDesk\src\Support;

use App\Support\SettingsStore;

class CorpCopy
{
    public const KEY_ENTERPRISE = 'corp_landing_enterprise';

    public const KEY_CCTV = 'corp_landing_cctv';

    /** @return array<string,string> */
    public static function slugs(): array
    {
        return [
            'enterprise-storage' => 'enterprise',
            'org-hdd' => 'enterprise',
            'cctv-projects' => 'cctv',
            'cctv' => 'cctv',
        ];
    }

    public static function keyFor(string $page): string
    {
        return $page === 'cctv' ? self::KEY_CCTV : self::KEY_ENTERPRISE;
    }

    /** @return array<string,mixed> */
    public static function defaults(string $page): array
    {
        return $page === 'cctv' ? self::cctvDefaults() : self::enterpriseDefaults();
    }

    /** @return array<string,mixed> */
    public static function get(string $page): array
    {
        $raw = SettingsStore::get(self::keyFor($page), []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_merge(self::defaults($page), is_array($raw) ? $raw : []);
    }

    public static function save(string $page, array $d): void
    {
        $s = self::defaults($page);
        foreach (self::limits($page) as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k])), 0, $max);
        }
        SettingsStore::set(self::keyFor($page), $s);
    }

    /** @return array<string,int> */
    public static function limits(string $page): array
    {
        $base = [
            'kicker' => 80, 'title' => 180, 'lead' => 700, 'hero_image' => 400,
            'cta1_label' => 60, 'cta1_url' => 200, 'cta2_label' => 60, 'cta2_url' => 200,
            'intro_title' => 120, 'intro' => 1800,
            'features_title' => 120, 'features' => 2000,
            'steps_title' => 120, 'steps' => 1600,
            'stats' => 400, 'cases_title' => 120, 'cases' => 1600,
            'brands_title' => 80, 'brands' => 400,
            'faq_title' => 80, 'faq' => 2000,
            'cta_title' => 140, 'cta_text' => 400, 'cta_label' => 60, 'cta_url' => 200,
            'alt_label' => 60, 'alt_url' => 200,
        ];
        if ($page === 'cctv') {
            $base['hero_image'] = 400;
        }

        return $base;
    }

    /** @return list<array{title:string,text:string}> */
    public static function lines(string $block): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $block) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $out[] = [
                'title' => $parts[0] ?? '',
                'text' => $parts[1] ?? '',
            ];
        }

        return $out;
    }

    /** @return list<string> */
    public static function list(string $block): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $block) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /** @return array<string,mixed> */
    private static function enterpriseDefaults(): array
    {
        return [
            'kicker' => 'تأمین سازمانی HDD Land',
            'title' => 'تأمین هارد سازمانی برای شرکت، سازمان و دیتاسنتر',
            'lead' => 'موجودی واقعی، مشاوره مدل، پیش‌فاکتور رسمی و تحویل با فاکتور حقوقی. از یک هارد سرور تا تجهیز کامل انبار ذخیره‌سازی، مسیر مشخص است و قابل پیگیری.',
            'hero_image' => 'images/home/corp-enterprise-hero.jpg',
            'cta1_label' => 'درخواست پیش‌فاکتور',
            'cta1_url' => '/contact',
            'cta2_label' => 'ورود به فروشگاه',
            'cta2_url' => '/products',
            'intro_title' => 'سازمان چه می‌خرد و HDD Land چه تحویل می‌دهد',
            'intro' => "تأمین هارد سازمانی با خرید تک‌قطعه از ویترین فروشگاه فرق دارد. واحد IT یا تدارکات باید مدل درست، ظرفیت واقعی، گارانتی قابل استعلام و فاکتور رسمی بگیرد؛ نه یک لینک قیمت که فردا عوض می‌شود.\nسرزمین هارد برای سازمان، مدل را بر اساس بار کاری پیشنهاد می‌دهد: هارد سازمانی / NAS / سرور / آرشیو سرد. موجودی اعلام‌شده قابل سفارش است و تحویل با فاکتور حقوقی انجام می‌شود.",
            'features_title' => 'خروجی واحد تأمین سازمانی',
            'features' => "مشاوره مدل|انتخاب سری Enterprise، NAS یا Archive بر اساس IOPS، ظرفیت و محیط نصب.\nموجودی واقعی|اعلام موجودی قابل سفارش، نه کاتالوگ کلی که بعد از پرداخت خالی دربیاید.\nپیش‌فاکتور رسمی|استعلام کتبی با مدل، ظرفیت، گارانتی و زمان تحویل برای واحد تدارکات.\nفاکتور حقوقی|صدور فاکتور برای شخصیت حقوقی با مشخصات ثبتی سازمان.\nگارانتی شفاف|استعلام پوشش با سریال؛ جدا از ثبت پوشش جدید.\nتأمین عمده|سفارش چندظرفیتی و چندبرندی برای تجهیز شعب یا دیتاسنتر.",
            'steps_title' => 'مسیر سفارش سازمانی',
            'steps' => "۱. اعلام نیاز|ظرفیت، تعداد، محیط (سرور / NAS / آرشیو) و مهلت تحویل را بفرستید.\n۲. پیشنهاد مدل|واحد فروش مدل سازگار و موجود را با قیمت و گارانتی می‌نویسد.\n۳. پیش‌فاکتور|سند استعلام برای تأیید مالی و تدارکات صادر می‌شود.\n۴. تحویل و فاکتور|کالا با فاکتور رسمی ارسال می‌شود و سریال‌ها قابل پیگیری می‌مانند.",
            'stats' => "تأمین تخصصی|هارد، SSD، NVMe\nخرید حقوقی|پیش‌فاکتور و فاکتور\nگارانتی|استعلام با سریال\nپشتیبانی|۹ تا ۱۹",
            'cases_title' => 'برای چه سازمان‌هایی کار می‌کنیم',
            'cases' => "شرکت و هلدینگ|تجهیز انبار IT و جایگزینی دیسک‌های از رده خارج با مدل سازمانی.\nشعب و فروشگاه زنجیره‌ای|یک مدل مشخص برای چند نقطه، با فاکتور مرکزی.\nدیتاسنتر و میزبانی|هارد سرور، SAS و ظرفیت آرشیو با تحویل زمان‌بندی‌شده.\nادارات و نهادها|مسیر استعلام، پیش‌فاکتور و فاکتور رسمی بدون واسطه مبهم.",
            'brands_title' => 'برندهای تأمین',
            'brands' => "Western Digital\nSeagate\nToshiba\nSamsung\nSanDisk",
            'faq_title' => 'پرسش واحد تدارکات',
            'faq' => "فرق هارد سازمانی با هارد دسکتاپ چیست؟|هارد سازمانی برای کارکرد شبانه‌روزی، بار تصادفی و گارانتی طولانی‌تر طراحی شده. برای سرور و NAS نباید سری دسکتاپ گذاشت.\nپیش‌فاکتور چقدر اعتبار دارد؟|تا تأیید موجودی و مهلت درج‌شده روی سند. بعد از تأیید، سفارش قفل می‌شود.\nگارانتی را چطور چک کنیم؟|با سریال روی صفحه استعلام گارانتی. ثبت پوشش جدید مسیر جدا دارد.\nارسال به شهرستان دارید؟|بله. زمان ارسال روی پیش‌فاکتور نوشته می‌شود.",
            'cta_title' => 'واحد تأمین سازمانی آماده استعلام است',
            'cta_text' => 'مدل، ظرفیت و تعداد را بفرستید تا پیش‌فاکتور رسمی صادر شود.',
            'cta_label' => 'تماس با واحد فروش',
            'cta_url' => '/contact',
            'alt_label' => 'استعلام گارانتی سریال',
            'alt_url' => '/serial-check',
        ];
    }

    /** @return array<string,mixed> */
    private static function cctvDefaults(): array
    {
        return [
            'kicker' => 'پروژه‌های نظارتی HDD Land',
            'title' => 'تأمین ذخیره‌سازی برای دوربین، NVR و آرشیو نظارتی',
            'lead' => 'هارد مناسب دوربین، محاسبه ظرفیت آرشیو و تأمین عمده برای پروژه‌های CCTV. مدل را بر اساس تعداد کانال، رزولوشن و روز نگهداری پیشنهاد می‌دهیم؛ نه بر اساس یک عدد تبلیغاتی.',
            'hero_image' => 'images/home/corp-cctv-hero.jpg',
            'cta1_label' => 'استعلام پروژه نظارتی',
            'cta1_url' => '/contact',
            'cta2_label' => 'هاردهای فروشگاه',
            'cta2_url' => '/products?part_type=hdd',
            'intro_title' => 'دوربین بدون ذخیره‌سازی درست، پروژه ناقص است',
            'intro' => "در پروژه نظارتی، دوربین فقط تصویر می‌گیرد. آنچه پروژه را سر پا نگه می‌دارد، دیسک مناسب NVR/DVR و ظرفیت کافی برای نگهداری است. هارد دسکتاپ زیر بار نوشتن مداوم ۲۴ساعته زود از مدار خارج می‌شود.\nسرزمین هارد برای پیمانکار، سازمان و integrators مدل Surveillance (مثل سری Purple / SkyHawk) پیشنهاد می‌دهد، ظرفیت را با تعداد کانال و روز آرشیو حساب می‌کند و عمده را با فاکتور رسمی تحویل می‌دهد.",
            'features_title' => 'آنچه برای پروژه CCTV تحویل می‌دهیم',
            'features' => "هارد Surveillance|دیسک مخصوص نوشتن مداوم دوربین، نه سری دسکتاپ یا گیمینگ.\nمحاسبه ظرفیت|بر اساس کانال، رزولوشن، فشرده‌سازی و روز نگهداری.\nتأمین عمده|چند ظرفیت همزمان برای یک پروژه یا چند سایت.\nسازگاری NVR|بررسی سازگاری با برند NVR/DVR قبل از صدور پیش‌فاکتور.\nآرشیو و بکاپ|پیشنهاد دیسک دوم برای آرشیو سرد یا نسخه پشتیبان.\nفاکتور پروژه|پیش‌فاکتور و فاکتور به نام پیمانکار یا کارفرما.",
            'steps_title' => 'از طرح تا تحویل دیسک',
            'steps' => "۱. مشخصات پروژه|تعداد دوربین، رزولوشن، روز نگهداری و مدل NVR را بفرستید.\n۲. محاسبه ظرفیت|حجم لازم و تعداد دیسک را شفاف می‌نویسیم.\n۳. پیشنهاد مدل|سری Surveillance موجود با گارانتی و زمان تحویل.\n۴. تأمین و فاکتور|ارسال عمده و فاکتور رسمی برای پروژه.",
            'stats' => "کانال و ظرفیت|محاسبه دقیق\nسری Surveillance|نوشتن ۲۴ساعته\nتأمین پروژه|عمده و مرحله‌ای\nگارانتی|قابل استعلام",
            'cases_title' => 'کاربری‌های رایج',
            'cases' => "ساختمان و مجتمع|NVR چندکاناله با نگهداری ۳۰ تا ۹۰ روز.\nفروشگاه زنجیره‌ای|یک ظرفیت استاندارد برای شعب، فاکتور مرکزی.\nکارخانه و انبار|دوربین صنعتی و آرشیو طولانی‌تر.\nپیمانکار CCTV|تأمین عمده برای چند پروژه همزمان.",
            'brands_title' => 'سری‌های پیشنهادی',
            'brands' => "WD Purple\nSeagate SkyHawk\nToshiba Surveillance\nWD Gold / Red (آرشیو و NAS)",
            'faq_title' => 'پرسش پیمانکار و کارفرما',
            'faq' => "چرا هارد معمولی برای دوربین کافی نیست؟|بار نوشتن CCTV پیوسته است. سری دسکتاپ برای این الگو ساخته نشده و نرخ خرابی‌اش در NVR بالاتر است.\nظرفیت را چطور حساب می‌کنید؟|با تعداد کانال، بیت‌ریت تقریبی رزولوشن و روز نگهداری. عدد نهایی روی پیش‌فاکتور می‌آید.\nاگر NVR برند خاصی باشد چه؟|مدل NVR را بفرستید تا سازگاری دیسک را قبل از خرید چک کنیم.\nگارانتی پروژه جداست؟|گارانتی روی سریال هر دیسک استعلام می‌شود. پوشش اضافه مسیر ثبت گارانتی است.",
            'cta_title' => 'پروژه نظارتی را با ظرفیت درست ببندید',
            'cta_text' => 'تعداد دوربین، روز آرشیو و مدل NVR را بفرستید تا استعلام ظرفیت و پیش‌فاکتور بیاید.',
            'cta_label' => 'تماس برای پروژه CCTV',
            'cta_url' => '/contact',
            'alt_label' => 'تأمین هارد سازمانی',
            'alt_url' => '/enterprise-storage',
        ];
    }
}
