<?php

namespace Plugins\TrainingDesk\src\Support;

use App\Support\SettingsStore;

class TrainingCopy
{
    public const KEY = 'training_academy_page';

    /** @return list<string> */
    public static function slugs(): array
    {
        return ['data-recovery', 'hdd-repair', 'ssd-nvme', 'server-storage'];
    }

    /** @return array<string, string> */
    public static function prefixes(): array
    {
        return [
            'data-recovery' => 'rec',
            'hdd-repair' => 'fix',
            'ssd-nvme' => 'ssd',
            'server-storage' => 'srv',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return array_merge([
            'hub_kicker' => 'آکادمی تخصصی HDD Land',
            'hub_title' => 'آموزش بازیابی اطلاعات و تعمیر ذخیره‌سازی',
            'hub_lead' => 'شرکت ما فقط در همین مسیر آموزش می‌دهد: بازیابی اطلاعات، تعمیر هارد دیسک، بازیابی SSD / M.2 / NVMe، و کار روی سرور و استوریج. بیش از دو دهه در ایران و خارج از ایران.',
            'hub_intro' => 'آکادمی HDD Land آموزش عمومی فروشگاه یا نصب ویندوز نیست. اینجا مسیر آزمایشگاهی است؛ کارآموز روی کیس واقعی، خانواده فریمور و تجهیزات حرفه‌ای کار می‌کند تا بعد از دوره بتواند کیس بگیرد، نه فقط اسلاید حفظ کند.',
            'hub_stat_1' => '۲۰+ سال آموزش در ایران و خارج',
            'hub_stat_2' => 'کلاس محدود آزمایشگاهی',
            'hub_stat_3' => 'PC-3000 · MRT · DFL · SeDiv',
            'hub_stat_4' => 'گواهی پایان دوره',
            'tools_title' => 'تجهیزات کلاس و آزمایشگاه',
            'tools' => "PC-3000\nMRT PRO\nDFL\nDeepSpar\nSeDiv\nWD Marvel",
            'price_note' => 'هزینه‌ها برای دوره حضوری آزمایشگاهی است، شامل کار روی رسانه واقعی و دسترسی به تجهیزات کلاس. مبلغ نهایی پیش از ثبت‌نام تأیید می‌شود و بسته به محل برگزاری (آمل / کارگاه خارج از کشور) ممکن است تغییر کند.',
            'cta_title' => 'ثبت‌نام و مشاوره دوره',
            'cta_text' => 'ظرفیت هر دوره محدود است. برای سیلابس دقیق، تاریخ نزدیک‌ترین دوره و پیش‌فاکتور با واحد آموزش تماس بگیرید.',
            'cta_label' => 'تماس با واحد آموزش',
            'cta_url' => '/contact',
            'alt_label' => 'خدمات سازمانی آزمایشگاه',
            'alt_url' => '/services',
        ], self::recoveryDefaults(), self::repairDefaults(), self::ssdDefaults(), self::serverDefaults());
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = SettingsStore::get(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $out = array_merge(self::defaults(), is_array($raw) ? $raw : []);
        foreach (self::prefixes() as $prefix) {
            $out[$prefix.'_on'] = ! empty($out[$prefix.'_on']);
        }

        return $out;
    }

    public static function save(array $d): void
    {
        $s = self::defaults();
        foreach (self::prefixes() as $prefix) {
            $s[$prefix.'_on'] = ! empty($d[$prefix.'_on']);
        }
        foreach (self::textLimits() as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? $s[$k])), 0, $max);
        }
        SettingsStore::set(self::KEY, $s);
    }

    /** @return array<string, int> */
    public static function textLimits(): array
    {
        $limits = [
            'hub_kicker' => 80, 'hub_title' => 180, 'hub_lead' => 600, 'hub_intro' => 700,
            'hub_stat_1' => 80, 'hub_stat_2' => 80, 'hub_stat_3' => 80, 'hub_stat_4' => 80,
            'tools_title' => 80, 'tools' => 400, 'price_note' => 500,
            'cta_title' => 120, 'cta_text' => 400, 'cta_label' => 60, 'cta_url' => 200,
            'alt_label' => 60, 'alt_url' => 200,
        ];
        foreach (self::prefixes() as $prefix) {
            $limits += [
                $prefix.'_kicker' => 80,
                $prefix.'_title' => 160,
                $prefix.'_card' => 220,
                $prefix.'_lead' => 700,
                $prefix.'_intro' => 900,
                $prefix.'_audience' => 240,
                $prefix.'_level' => 80,
                $prefix.'_duration' => 80,
                $prefix.'_prereq' => 400,
                $prefix.'_includes' => 600,
                $prefix.'_syllabus' => 2000,
                $prefix.'_table' => 2500,
                $prefix.'_faq' => 1600,
                $prefix.'_cta' => 60,
            ];
        }

        return $limits;
    }

    /** @return list<array<string, mixed>> */
    public static function catalog(?array $copy = null): array
    {
        $copy ??= self::get();
        $out = [];
        foreach (self::prefixes() as $slug => $prefix) {
            if (empty($copy[$prefix.'_on'])) {
                continue;
            }
            $out[] = self::course($slug, $copy);
        }

        return $out;
    }

    /** @return array<string, mixed>|null */
    public static function course(string $slug, ?array $copy = null): ?array
    {
        $copy ??= self::get();
        $prefix = self::prefixes()[$slug] ?? null;
        if ($prefix === null || empty($copy[$prefix.'_on'])) {
            return null;
        }

        return [
            'slug' => $slug,
            'url' => '/training/'.$slug,
            'kicker' => (string) ($copy[$prefix.'_kicker'] ?? ''),
            'title' => (string) ($copy[$prefix.'_title'] ?? ''),
            'card' => (string) ($copy[$prefix.'_card'] ?? ''),
            'lead' => (string) ($copy[$prefix.'_lead'] ?? ''),
            'intro' => (string) ($copy[$prefix.'_intro'] ?? ''),
            'audience' => (string) ($copy[$prefix.'_audience'] ?? ''),
            'level' => (string) ($copy[$prefix.'_level'] ?? ''),
            'duration' => (string) ($copy[$prefix.'_duration'] ?? ''),
            'prereq' => self::lines((string) ($copy[$prefix.'_prereq'] ?? '')),
            'includes' => self::lines((string) ($copy[$prefix.'_includes'] ?? '')),
            'syllabus' => self::lines((string) ($copy[$prefix.'_syllabus'] ?? '')),
            'table' => self::table((string) ($copy[$prefix.'_table'] ?? '')),
            'faq' => self::faq((string) ($copy[$prefix.'_faq'] ?? '')),
            'cta' => (string) ($copy[$prefix.'_cta'] ?? 'ثبت‌نام این دوره'),
        ];
    }

    /** @return list<string> */
    public static function tools(?array $copy = null): array
    {
        return self::lines((string) (($copy ?? self::get())['tools'] ?? ''));
    }

    /** @return list<array{label:string,url:string}> */
    public static function navItems(?array $copy = null): array
    {
        $items = [['label' => 'آکادمی دوره‌ها', 'url' => '/training']];
        foreach (self::catalog($copy) as $course) {
            $items[] = ['label' => $course['title'], 'url' => $course['url']];
        }

        return $items;
    }

    /** @return list<string> */
    public static function lines(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /** @return list<array{brand:string,course:string,duration:string,level:string,price:string}> */
    public static function table(string $raw): array
    {
        $out = [];
        foreach (self::lines($raw) as $line) {
            $cols = array_map('trim', explode('|', $line));
            if (count($cols) < 2) {
                continue;
            }
            $out[] = [
                'brand' => $cols[0] ?? '',
                'course' => $cols[1] ?? '',
                'duration' => $cols[2] ?? '',
                'level' => $cols[3] ?? '',
                'price' => $cols[4] ?? '',
            ];
        }

        return $out;
    }

    /** @return list<array{q:string,a:string}> */
    public static function faq(string $raw): array
    {
        $out = [];
        foreach (self::lines($raw) as $line) {
            [$q, $a] = array_pad(explode('|', $line, 2), 2, '');
            $q = trim($q);
            $a = trim($a);
            if ($q !== '' && $a !== '') {
                $out[] = ['q' => $q, 'a' => $a];
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private static function recoveryDefaults(): array
    {
        return [
            'rec_on' => true,
            'rec_kicker' => 'آموزش بازیابی اطلاعات',
            'rec_title' => 'بازیابی اطلاعات هارد دیسک',
            'rec_card' => 'بیش از دو دهه آموزش بازیابی در ایران و خارج؛ کار روی خانواده فریمور و کیس واقعی.',
            'rec_lead' => 'شرکت ما در زمینه بازیابی اطلاعات بیش از دو دهه است که آموزش می‌دهد؛ هم در ایران و هم خارج از ایران. این دوره مسیر آزمایشگاهی بازگرداندن داده است، نه کلاس تئوری نرم‌افزار.',
            'rec_intro' => 'بازیابی اطلاعات یعنی وقتی رسانه دیگر با روش معمولی خوانده نمی‌شود — خرابی هد، فساد ناحیه سرویس، آسیب مترجم، رمزنگاری یا از بین رفتن پارتیشن — داده را با ابزار تخصصی برگردانیم. در HDD Land این کار روی PC-3000، MRT PRO، DFL، DeepSpar، SeDiv و WD Marvel و به‌تفکیک برند آموزش داده می‌شود. کارآموز خانواده هارد را تشخیص می‌دهد، ROM و Service Area را پشتیبان می‌گیرد، translator را ترمیم می‌کند و با Data Extractor تصویر امن می‌سازد.',
            'rec_audience' => 'برای تعمیرکار ذخیره‌سازی، آزمایشگاه بازیابی، واحد IT سازمانی و کسی که می‌خواهد کیس واقعی بگیرد.',
            'rec_level' => 'مقدماتی تا پیشرفته، به‌تفکیک برند',
            'rec_duration' => '۳ تا ۱۲ روز آزمایشگاهی',
            'rec_prereq' => "آشنایی با سخت‌افزار رایانه و ذخیره‌سازی\nتوان کار با ترمینال و ابزار دقیق\nترجیحاً سابقه تعمیر یا خدمات داده",
            'rec_includes' => "کار عملی روی هارد واقعی هر برند\nدسترسی به تجهیزات کلاس در ساعت دوره\nجزوه خانواده فریمور و چک‌لیست تشخیص\nگواهی پایان دوره HDD Land\nپشتیبانی فنی کوتاه بعد از دوره",
            'rec_syllabus' => "تشخیص اولیه، صدای مکانیکی و شناسایی خانواده هارد\nاتصال سریال / ترمینال و اشتباه‌های رایج شناسایی خانواده\nساختار میکروکد، ROM و راه‌اندازی در Kernel / Safe Mode\nناحیه سرویس (Service Area)، ماژول‌های حیاتی و پشتیبان‌گیری\nمترجم داده (Translator) و ترمیم جداول ترجمه\nنقشه هد، هدهای معیوب و فناوری جابه‌جایی هد (Hot-Swap / Head Map)\nکار با PC-3000 Data Extractor و تصویرگیری امن\nWestern Digital: ROM غیراصل، T2، SMR و SED\nSeagate F3 و Rosewood: ترمینال، Media Cache و آنلاک فریمور\nToshiba: ماژول‌های CP، G-List و مترجم مجازی\nHitachi / HGST و Samsung: چک‌لیست خانواده و بازیابی منطقی\nخانواده‌های ARM و تفاوت آن‌ها با معماری کلاسیک\nهارد سرور SAS و نکات تصویرگیری سازمانی",
            'rec_table' => "Western Digital|بازیابی WD / Marvell / ARM / SMR|۵ روز|پیشرفته|۲۸٬۰۰۰٬۰۰۰ تومان\nSeagate|بازیابی F3 / Rosewood|۴ روز|پیشرفته|۲۶٬۰۰۰٬۰۰۰ تومان\nToshiba|بازیابی خانواده Toshiba|۳ روز|متوسط تا پیشرفته|۲۲٬۰۰۰٬۰۰۰ تومان\nHitachi / HGST|بازیابی Hitachi و HGST|۳ روز|متوسط|۲۱٬۰۰۰٬۰۰۰ تومان\nSamsung|بازیابی Samsung HDD|۳ روز|متوسط|۲۰٬۰۰۰٬۰۰۰ تومان\nARM|خانواده‌های ARM (WD / Seagate)|۴ روز|پیشرفته|۲۴٬۰۰۰٬۰۰۰ تومان\nServer / SAS|بازیابی هارد سرور|۴ روز|پیشرفته|۳۲٬۰۰۰٬۰۰۰ تومان\nبسته جامع|بازیابی همه برندها + سرور|۱۲ روز|حرفه‌ای|۸۵٬۰۰۰٬۰۰۰ تومان",
            'rec_faq' => "دوره نرم‌افزار عمومی است؟|خیر. مسیر آزمایشگاهی فریمور، هد و تصویرگیری است؛ نه بازیابی با نرم‌افزار خانگی.\nگواهی می‌دهید؟|بله. در پایان هر ماژول یا بسته جامع، گواهی آکادمی HDD Land صادر می‌شود.\nخارج از ایران هم برگزار می‌شود؟|بله. کارگاه‌های خارج از کشور بنا به هماهنگی برگزار می‌شود؛ هزینه و تاریخ جدا اعلام می‌گردد.",
            'rec_cta' => 'ثبت‌نام بازیابی اطلاعات',
        ];
    }

    /** @return array<string, mixed> */
    private static function repairDefaults(): array
    {
        return [
            'fix_on' => true,
            'fix_kicker' => 'آموزش تعمیرات',
            'fix_title' => 'تعمیر تخصصی هارد دیسک',
            'fix_card' => 'تعمیر برد، هد و مکانیک تمام برندهای اصلی؛ سیلابس عملی و هزینه شفاف هر دوره.',
            'fix_lead' => 'تعمیر هارد دیسک در HDD Land یک مسیر جدا از بازیابی داده است: رسانه را پایدار می‌کنیم تا یا دوباره کار کند، یا برای استخراج داده آماده شود. آموزش برای تمام برندهای بالا، با تمرکز تخصصی روی تعمیر سخت‌افزاری است.',
            'fix_intro' => 'تعمیر تخصصی یعنی تشخیص برد، تعویض هد در شرایط کنترل‌شده، تراز مکانیکی، بایوس و ram برد، و شناخت محدودیت هر خانواده. Western Digital، Seagate، Toshiba، Hitachi/HGST، Samsung، معماری ARM و هارد سرور هر کدام نقطه ضعف و ابزار خود را دارند. کلاس کوتاه و حرفه‌ای است؛ کارآموز روی قطعه واقعی کار می‌کند، نه روی اسلاید.',
            'fix_audience' => 'برای تعمیرکار هارد، لابراتوار، و فنی‌ای که می‌خواهد تعمیر را از بازیابی جدا بلد باشد.',
            'fix_level' => 'عملی، برندبه‌برند',
            'fix_duration' => '۳ تا ۱۰ روز',
            'fix_prereq' => "مهارت کار با هویه و ابزار دقیق\nشناخت اولیه الکترونیک برد\nرعایت اتاق تمیز و آنتی‌استاتیک",
            'fix_includes' => "کار روی درایوهای آموزشی هر برند\nچک‌لیست تشخیص صدا و برد\nنکات donor و تطبیق هد / PCB\nگواهی پایان دوره تعمیر\nمشاوره راه‌اندازی میز تعمیر",
            'fix_syllabus' => "ایمنی، ESD و اصول اتاق تمیز برای باز کردن هارد\nتشخیص صدای کلیک، بوق و عدم اسپین\nخواندن برد، رگولاتور، TVS و بایوس\nتطبیق PCB و انتقال ROM\nتعویض هد: donor، شانه هد و محدودیت خانواده\nWestern Digital: برد Marvell / ARM و نکات SMR\nSeagate: برد F3، ترمینال سخت‌افزاری و donor\nToshiba و Hitachi/HGST: مکانیک و هد\nSamsung: برد و محدودیت قطعات\nهارد سرور: SAS، سینی و محدودیت سازمانی\nبعد از تعمیر: تست پایداری و تصمیم بازیابی یا تحویل",
            'fix_table' => "Western Digital|تعمیر برد و هد WD|۴ روز|عملی|۲۲٬۰۰۰٬۰۰۰ تومان\nSeagate|تعمیر سخت‌افزار Seagate|۴ روز|عملی|۲۱٬۰۰۰٬۰۰۰ تومان\nToshiba|تعمیر Toshiba|۳ روز|عملی|۱۸٬۰۰۰٬۰۰۰ تومان\nHitachi / HGST|تعمیر Hitachi و HGST|۳ روز|عملی|۱۸٬۰۰۰٬۰۰۰ تومان\nSamsung|تعمیر Samsung HDD|۳ روز|عملی|۱۷٬۰۰۰٬۰۰۰ تومان\nARM|تعمیر خانواده‌های ARM|۴ روز|پیشرفته|۲۳٬۰۰۰٬۰۰۰ تومان\nServer|تعمیر هارد سرور|۴ روز|پیشرفته|۲۶٬۰۰۰٬۰۰۰ تومان\nبسته جامع|تعمیر همه برندها|۱۰ روز|حرفه‌ای|۶۸٬۰۰۰٬۰۰۰ تومان",
            'fix_faq' => "با دوره بازیابی یکی است؟|خیر. اینجا تعمیر سخت‌افزار است؛ بازیابی داده دوره جدا دارد و می‌تواند مکمل باشد.\nاتاق تمیز لازم است؟|برای تعویض هد بله. در کلاس شرایط کنترل‌شده آزمایشگاه فراهم است.\nقطعه donor از کجاست؟|بخشی از درایوهای آموزشی کلاس است؛ برای کارگاه شخصی، منبع donor جدا توصیه می‌شود.",
            'fix_cta' => 'ثبت‌نام تعمیرات هارد',
        ];
    }

    /** @return array<string, mixed> */
    private static function ssdDefaults(): array
    {
        return [
            'ssd_on' => true,
            'ssd_kicker' => 'آموزش SSD / M.2 / NVMe',
            'ssd_title' => 'بازیابی SSD، M.2 و NVMe',
            'ssd_card' => 'کنترلر، مپینگ و XOR روی SSD ساتا، M.2 و NVMe؛ جدا از مسیر هارد مکانیکی.',
            'ssd_lead' => 'بازیابی SSD و M.2 NVMe مسیر جداگانه‌ای از هارد مکانیکی است. اینجا هد و صفحه وجود ندارد؛ کار روی کنترلر، جدول مپینگ، XOR و فریمور است. HDD Land این دوره را هم در ایران و هم در کارگاه‌های خارج برگزار می‌کند.',
            'ssd_intro' => 'SSD ساتا، ماژول M.2 و NVMe PCIe وقتی کنترلر قفل می‌شود، مپ خراب است یا فریمور آسیب دیده، با نرم‌افزار معمولی برنمی‌گردند. دوره روی خانواده‌های رایج Phison، Silicon Motion، Samsung و سناریوهای PCIe تمرکز دارد: تشخیص کنترلر، خواندن NAND در صورت پشتیبانی، مونتاژ XOR و استخراج منطقی. کارآموز یاد می‌گیرد چه کیسی قابل بازیابی آزمایشگاهی است و چه کیسی از نظر سخت‌افزاری بسته است.',
            'ssd_audience' => 'برای آزمایشگاه بازیابی، تعمیرکار لپ‌تاپ/سرور و کسی که کیس SSD سازمانی می‌گیرد.',
            'ssd_level' => 'متوسط تا پیشرفته',
            'ssd_duration' => '۳ تا ۸ روز',
            'ssd_prereq' => "گذراندن مبانی بازیابی یا سابقه کار آزمایشگاهی\nآشنایی با تفاوت SATA و NVMe\nدقت در کار روی برد چندلایه",
            'ssd_includes' => "کار روی SSD / M.2 آموزشی\nچک‌لیست تشخیص کنترلر\nمرز کیس قابل بازیابی و غیرقابل\nگواهی پایان دوره SSD\nبه‌روزرسانی کوتاه بعد از دوره برای خانواده جدید",
            'ssd_syllabus' => "معماری SSD: کنترلر، NAND، DRAM و جدول مپینگ\nتفاوت SATA SSD، M.2 SATA و NVMe PCIe\nتشخیص خانواده کنترلر و محدودیت هر نسل\nفریمور قفل‌شده، شناسه و حالت ایمن\nPhison و Silicon Motion: سناریوهای رایج آزمایشگاه\nSamsung NVMe و نکات اختصاصی\nXOR، مونتاژ صفحات و تصویرگیری\nآسیب فیزیکی برد و تصمیم تعویض کنترلر\nکیس سازمانی: لپ‌تاپ، سرور و استوریج فلش",
            'ssd_table' => "SATA SSD|بازیابی SSD ساتا|۳ روز|متوسط|۲۴٬۰۰۰٬۰۰۰ تومان\nM.2 SATA|بازیابی ماژول M.2 ساتا|۳ روز|متوسط|۲۲٬۰۰۰٬۰۰۰ تومان\nNVMe PCIe|بازیابی NVMe / M.2 NVMe|۴ روز|پیشرفته|۲۸٬۰۰۰٬۰۰۰ تومان\nPhison / SM|کنترلر Phison و Silicon Motion|۴ روز|پیشرفته|۳۰٬۰۰۰٬۰۰۰ تومان\nSamsung|SSD و NVMe سامسونگ|۳ روز|پیشرفته|۲۶٬۰۰۰٬۰۰۰ تومان\nبسته جامع|SSD + M.2 + NVMe|۸ روز|حرفه‌ای|۶۲٬۰۰۰٬۰۰۰ تومان",
            'ssd_faq' => "با نرم‌افزار کرک‌شده یکی است؟|خیر. مسیر آزمایشگاهی کنترلر و NAND است؛ ابزارهای عمومی کافی نیستند.\nهمه SSDها قابل بازیابی‌اند؟|خیر. بخشی از کنترلرها بسته یا رمزنگاری سخت‌افزاری دارند؛ در دوره همین مرز آموزش داده می‌شود.\nM.2 با NVMe فرق دارد؟|M.2 شکل فیزیکی است؛ روی آن هم SATA و هم NVMe می‌نشیند. دوره هر دو را جدا پوشش می‌دهد.",
            'ssd_cta' => 'ثبت‌نام دوره SSD / NVMe',
        ];
    }

    /** @return array<string, mixed> */
    private static function serverDefaults(): array
    {
        return [
            'srv_on' => true,
            'srv_kicker' => 'آموزش سرور و استوریج',
            'srv_title' => 'بازیابی سرور، RAID و استوریج',
            'srv_card' => 'RAID، NAS، SAN و استوریج سازمانی؛ بازسازی مجازی آرایه و کیس چنددیسکی.',
            'srv_lead' => 'وقتی سرور، NAS یا استوریج سازمانی از کار می‌افتد، مسئله یک هارد نیست؛ مسئله آرایه، کنترلر و ترتیب دیسک‌هاست. این دوره برای کسی است که کیس چنددیسکی سازمانی می‌گیرد.',
            'srv_intro' => 'آموزش سرور و استوریج در HDD Land روی RAID 0/1/5/6/10، leftover کنترلر، NASهای رایج و SAN/DAS سازمانی متمرکز است. کارآموز آرایه را مجازی بازسازی می‌کند، پاریتی را می‌سنجد، دیسک معیوب را جدا تصویر می‌گیرد و داده را بدون نوشتن روی رسانه اصلی استخراج می‌کند. این همان مسیری است که آزمایشگاه شرکت برای سازمان‌ها انجام می‌دهد.',
            'srv_audience' => 'برای واحد IT، همکار آزمایشگاه، و فنی استوریج سازمانی.',
            'srv_level' => 'پیشرفته',
            'srv_duration' => '۳ تا ۹ روز',
            'srv_prereq' => "آشنایی با RAID و مفهوم پاریتی\nترجیحاً دوره بازیابی هارد یا سابقه تصویرگیری\nدرک ریسک نوشتن روی آرایه زنده",
            'srv_includes' => "کیس آموزشی RAID / NAS\nروش تصویرگیری دیسک‌به‌دیسک\nچک‌لیست leftover و ترتیب اعضا\nگواهی دوره سرور و استوریج\nنمونه گزارش برای تحویل به سازمان",
            'srv_syllabus' => "تشخیص نوع آرایه و اشتباه‌های خطرناک (rebuild روی دیسک اشتباه)\nRAID 0/1/5/6/10: پاریتی، استرایپ و ترتیب اعضا\nLeftover کنترلر و متادیتای آرایه\nتصویرگیری جداگانه هر عضو قبل از هر نوشتن\nNAS: سناریوهای رایج سازمانی\nSAN / DAS و استوریج رک\nدیسک SAS سرور در کنار عضو SATA\nبازسازی مجازی و استخراج منطقی\nگزارش وضعیت برای مدیر IT و زنجیره تحویل",
            'srv_table' => "RAID کلاسیک|RAID 0 / 1 / 5 / 6 / 10|۳ روز|پیشرفته|۲۶٬۰۰۰٬۰۰۰ تومان\nNAS|بازیابی NAS سازمانی|۳ روز|پیشرفته|۲۴٬۰۰۰٬۰۰۰ تومان\nSAN / DAS|استوریج سازمانی و SAN|۴ روز|حرفه‌ای|۳۴٬۰۰۰٬۰۰۰ تومان\nLeftover|متادیتا و leftover کنترلر|۳ روز|پیشرفته|۲۸٬۰۰۰٬۰۰۰ تومان\nSAS Server|هارد و آرایه سرور|۴ روز|پیشرفته|۳۲٬۰۰۰٬۰۰۰ تومان\nبسته جامع|سرور + RAID + استوریج|۹ روز|حرفه‌ای|۷۲٬۰۰۰٬۰۰۰ تومان",
            'srv_faq' => "اگر سازمان rebuild زده باشد؟|بخشی از کیس‌ها بعد از rebuild بد قابل نجات نیستند. در دوره همین تصمیم‌گیری آموزش داده می‌شود.\nدوره برای فروشنده استوریج است؟|مخاطب اصلی فنی آزمایشگاه و IT است؛ فروشنده بدون پیش‌زمینه سخت‌افزاری توصیه نمی‌شود.\nهمراه با دوره هارد است؟|مکمل است. برای کیس ترکیبی معمولاً بازیابی هارد یا SSD را هم لازم دارید.",
            'srv_cta' => 'ثبت‌نام سرور و استوریج',
        ];
    }
}
