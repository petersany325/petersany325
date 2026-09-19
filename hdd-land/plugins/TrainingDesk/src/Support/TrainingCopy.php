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
            'price_note' => 'هزینه هر دوره بعد از مشاوره و اعلام سطح کارآموز مشخص می‌شود. برای استعلام روی «تماس بگیرید» بزنید تا به واحد آموزش وصل شوید.',
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
        $d = self::defaults();
        foreach (['rec_path_title', 'rec_path', 'rec_modules', 'rec_special_title', 'rec_special'] as $k) {
            if (trim((string) ($out[$k] ?? '')) === '') {
                $out[$k] = $d[$k];
            }
        }
        if (str_contains((string) ($out['rec_table'] ?? ''), 'بازیابی WD')) {
            foreach (['rec_table', 'rec_lead', 'rec_intro', 'rec_card', 'rec_level', 'rec_duration', 'rec_prereq', 'rec_includes', 'rec_syllabus', 'rec_faq', 'rec_audience'] as $k) {
                $out[$k] = $d[$k];
            }
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
                $prefix.'_path_title' => 80,
                $prefix.'_path' => 900,
                $prefix.'_modules' => 16000,
                $prefix.'_special_title' => 80,
                $prefix.'_special' => 1400,
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
            'path_title' => (string) ($copy[$prefix.'_path_title'] ?? ''),
            'path' => self::path((string) ($copy[$prefix.'_path'] ?? '')),
            'modules' => self::modules((string) ($copy[$prefix.'_modules'] ?? '')),
            'special_title' => (string) ($copy[$prefix.'_special_title'] ?? ''),
            'special' => self::lines((string) ($copy[$prefix.'_special'] ?? '')),
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
                'price' => self::priceLabel($cols[4] ?? ''),
            ];
        }

        return $out;
    }

    public static function priceLabel(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/تومان|[0-9۰-۹]{3,}/u', $raw)) {
            return 'تماس بگیرید';
        }

        return $raw;
    }

    /** @return list<array{code:string,title:string,courses:string}> */
    public static function path(string $raw): array
    {
        $out = [];
        foreach (self::lines($raw) as $line) {
            $cols = array_map('trim', explode('|', $line));
            if (($cols[0] ?? '') === '') {
                continue;
            }
            $out[] = [
                'code' => $cols[0],
                'title' => $cols[1] ?? '',
                'courses' => $cols[2] ?? '',
            ];
        }

        return $out;
    }

    /** @return list<array{code:string,title:string,en:string,level:string,audience:string,syllabus:list<string>,lab:list<string>}> */
    public static function modules(string $raw): array
    {
        $out = [];
        foreach (preg_split('/^\s*---\s*$/m', $raw) ?: [] as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            $lines = preg_split('/\R/u', $block) ?: [];
            $head = array_map('trim', explode('|', (string) array_shift($lines)));
            if (($head[0] ?? '') === '') {
                continue;
            }
            $syl = [];
            $lab = [];
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if (str_starts_with($line, 'SYL:')) {
                    $syl[] = trim(substr($line, 4));
                } elseif (str_starts_with($line, 'LAB:')) {
                    $lab[] = trim(substr($line, 4));
                }
            }
            $out[] = [
                'code' => $head[0],
                'title' => $head[1] ?? '',
                'en' => $head[2] ?? '',
                'level' => $head[3] ?? '',
                'audience' => $head[4] ?? '',
                'syllabus' => $syl,
                'lab' => $lab,
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
            'rec_kicker' => 'HDD Land Data Recovery Academy',
            'rec_title' => 'آکادمی بازیابی اطلاعات',
            'rec_card' => 'مسیر ۸دوره‌ای از مبانی تا کیس واقعی؛ نه آموزش کار با یک نرم‌افزار.',
            'rec_lead' => 'این صفحه آموزش یک ابزار نیست. مسیر حرفه‌ای HDD Land از مبانی، تشخیص، Logical Recovery، سخت‌افزار، Firmware و Service Area، Imaging و سپس تخصص برند به کیس واقعی می‌رسد. بیش از دو دهه در ایران و خارج از ایران.',
            'rec_intro' => 'آموزش معتبر بازیابی روی معماری HDD، تشخیص خرابی، Firmware/Service Area، Imaging، Data Extractor و کیس واقعی تأکید دارد؛ بعد از آن WD و Seagate جدا می‌شوند. صفحه را با ده‌ها دوره شلوغ نمی‌کنیم: پنج مسیر اصلی، هشت دوره، و تخصص‌های بعدی وقتی آماده باشند اضافه می‌شوند.',
            'rec_audience' => 'برای تازه‌وارد جدی، تعمیرکار ذخیره‌سازی، آزمایشگاه و کسی که می‌خواهد کیس واقعی جلو ببرد.',
            'rec_level' => 'پنج سطح: Foundation تا Master',
            'rec_duration' => '۸ دوره آزمایشگاهی، به‌ترتیب مسیر',
            'rec_prereq' => "آشنایی با سخت‌افزار رایانه و ذخیره‌سازی\nتوان کار دقیق و ثبت کیس\nدوره‌های پیشرفته پس از مبانی و Logical",
            'rec_includes' => "کار روی کیس و رسانه واقعی\nدسترسی به PC-3000، MRT PRO، DFL، DeepSpar، SeDiv و WD Marvel\nچک‌لیست تشخیص، Donor و Imaging\nگواهی هر سطح یا بسته مسیر\nگزارش نمونه برای تحویل به مشتری",
            'rec_syllabus' => "01 مبانی بازیابی اطلاعات\n02 Logical Data Recovery\n03 HDD Hardware & Diagnostics\n04 HDD Firmware & Service Area\n05 Professional HDD Imaging\n06 Western Digital Data Recovery\n07 Seagate Data Recovery\n08 Advanced Case Studies",
            'rec_table' => "01|مبانی بازیابی اطلاعات|۳ روز|مقدماتی|تماس بگیرید\n02|Logical Data Recovery|۴ روز|مقدماتی تا متوسط|تماس بگیرید\n03|HDD Hardware & Diagnostics|۴ روز|متوسط|تماس بگیرید\n04|HDD Firmware & Service Area|۵ روز|پیشرفته|تماس بگیرید\n05|Professional HDD Imaging|۴ روز|پیشرفته|تماس بگیرید\n06|Western Digital Data Recovery|۴ روز|پیشرفته|تماس بگیرید\n07|Seagate Data Recovery|۴ روز|پیشرفته|تماس بگیرید\n08|Advanced Case Studies|۵ روز|Master|تماس بگیرید",
            'rec_path_title' => 'مسیر حرفه‌ای آکادمی',
            'rec_path' => "LEVEL 1 — FOUNDATION|مبانی|01 مبانی بازیابی · 02 Logical Recovery\nLEVEL 2 — HDD ENGINEERING|مهندسی هارد|03 سخت‌افزار و تشخیص · 04 فریمور و Service Area\nLEVEL 3 — PROFESSIONAL RECOVERY|بازیابی حرفه‌ای|05 Imaging و استخراج داده\nLEVEL 4 — VENDOR SPECIALIZATION|تخصص برند|06 Western Digital · 07 Seagate\nLEVEL 5 — MASTER|کارگاه نهایی|08 کیس‌های واقعی از پذیرش تا تحویل",
            'rec_modules' => self::recoveryModules(),
            'rec_special_title' => 'دوره‌های تخصصی بعدی',
            'rec_special' => "Toshiba HDD Data Recovery\nHitachi / HGST Data Recovery\nSamsung HDD Data Recovery\nSSD / NVMe Data Recovery\nUSB HDD Data Recovery\nFlash / NAND / Monolith\nRAID Data Recovery\nForensic Data Recovery\nATA Shell و تحلیل ROM / Translator\nCleanroom & Mechanical Recovery",
            'rec_faq' => "این آموزش کار با یک نرم‌افزار است؟|خیر. مسیر مهندسی است: مبانی، تشخیص، Logical، سخت‌افزار، SA، Imaging، برند و کیس واقعی.\nباید هر ۸ دوره را پشت سر هم گرفت؟|مسیر پیشنهادی همین ترتیب است. ورود به سطح بالاتر بعد از تسلط سطح قبل توصیه می‌شود.\nچرا فقط WD و Seagate جدا هستند؟|این دو برند محور اصلی کیس‌ها و آموزش‌های تخصصی صنعت‌اند. توشیبا، هیتاچی، سامسونگ، SSD و RAID به‌عنوان Specialized بعدی می‌آیند تا صفحه شلوغ نشود.\nگواهی می‌دهید؟|بله. برای هر دوره یا بسته مسیر، گواهی آکادمی HDD Land صادر می‌شود.",
            'rec_cta' => 'ثبت‌نام مسیر بازیابی',
        ];
    }

    private static function recoveryModules(): string
    {
        return <<<'TXT'
01|مبانی بازیابی اطلاعات|Data Recovery Fundamentals|مقدماتی|افراد تازه‌وارد به Data Recovery
SYL:مفهوم Data Recovery و انواع خرابی اطلاعات
SYL:Logical / Firmware / Physical Failure
SYL:ساختار HDD: Platter، Head، Spindle Motor، PCB
SYL:LBA، Sector، CHS و ساختارهای قدیمی
SYL:تفاوت CMR و SMR و ظرفیت واقعی / Addressing
SYL:Partition Table، MBR و GPT
SYL:File Systemهای NTFS، FAT، exFAT، EXT
SYL:تفاوت Recovery و Repair
SYL:حفظ اطلاعات و جلوگیری از آسیب بیشتر
SYL:ایجاد Image از هارد
SYL:معرفی ابزارهای تخصصی و اصول مدیریت Case
LAB:تشخیص نوع خرابی چند HDD
LAB:بررسی SMART، ظرفیت و LBA
LAB:تفکیک Logical و Physical Failure
---
02|Logical Data Recovery|Logical Data Recovery Professional|مقدماتی تا متوسط|پس از مبانی یا سابقه کار با فایل‌سیستم
SYL:NTFS، FAT32، exFAT، EXT
SYL:ساختار Partition، MBR / GPT، Boot Sector
SYL:MFT، File Record، Directory Structure
SYL:Deleted Files و Deleted Partition
SYL:Quick Format، Full Format، Corrupted Partition
SYL:RAW Drive، Missing Files، Damaged File System
SYL:Lost Partition و Corrupted MFT
SYL:ابزارها: PC-3000، MRT PRO، DFL، DeepSpar
SYL:Image از هارد سالم و هارد دارای Bad Sector
SYL:مدیریت Read Error، اولویت خواندن، Resume Imaging
SYL:بررسی Image و File Recovery از Image
---
03|سخت‌افزار و تشخیص هارد|Professional HDD Hardware & Diagnostics|متوسط|یکی از مهم‌ترین دوره‌های HDD Land
SYL:معماری کامل HDD: HDA، PCB، Spindle، Head Stack
SYL:Platter، Preamp، Motor، VCM، Head Parking
SYL:Head Crash، Stiction، PCB / Power / Motor Failure
SYL:Head Failure و Media Damage
SYL:BIOS Detection، Capacity، 0 LBA، Wrong Capacity
SYL:Slow Detection، Clicking، Buzzing، Grinding
SYL:Spin-up Failure و علائم Head / PCB
SYL:انتخاب Donor: Family، PCB Number، Firmware Revision
SYL:Head / ROM Compatibility، Adaptation، Matching Patient / Donor
LAB:باز کردن صحیح HDD و اصول Clean Environment
LAB:بررسی Head، Platter، PCB و Spindle
LAB:تعویض Head و تعویض PCB
---
04|فریمور و Service Area|HDD Firmware & Service Area Professional|پیشرفته|پس از سخت‌افزار و تشخیص
SYL:Firmware، MCU، ROM، RAM، Service Area / System Area
SYL:Modules، Tracks، Zones، Adaptive Data
SYL:ساختار SA، Module Header / ID، Copy و Backup
SYL:Read / Write / Compare Module و Module Corruption
SYL:Serial Terminal، UART، RX / TX / GND
SYL:Terminal Commands، Diagnostic Mode، Boot Mode
SYL:Firmware Corruption، Missing / Damaged Module، ROM Problem
SYL:Translator، 0 LBA، Wrong Capacity، Slow Response
SYL:BSY، ERR، Init Problems
SYL:ROM Backup / Analysis / Adaptives
SYL:Firmware Backup و Repair، SA Recovery
SYL:Head Map، Head Disable، تشخیص مبتنی بر فریمور
---
05|Imaging حرفه‌ای و استخراج داده|Advanced HDD Imaging & Data Extraction|پیشرفته|Recovery سخت بدون Imaging جدا ممکن نیست
SYL:اصول Professional Imaging
SYL:Sector-by-Sector و Head-by-Head Imaging
SYL:Read Instability، Bad / Weak / Slow Sector
SYL:Unstable Head و Damaged Media
SYL:Read Retries، Timeout، Head Map
SYL:Reverse / Forward / Multi-pass Imaging
SYL:Skip Strategy، Read Speed Control، Head Selection
SYL:Selective Imaging، Map Management، Resume
SYL:Image Verification
SYL:Data Extractor، Raw Recovery، File System Recovery
SYL:Image Mount، File Extraction، Recovery از Image ناقص
---
06|بازیابی Western Digital|Western Digital HDD Data Recovery|پیشرفته|برندمحور؛ پس از فریمور و Imaging
SYL:WD Architecture، Families، Firmware، ROM
SYL:WD Service Area، Modules، Adaptive Data، Translator
SYL:WD USB و SATA، USB Bridge، Native SATA Conversion
SYL:WD SMR و CMR
SYL:کیس 0 LBA، BSY، Slow HDD، Clicking
SYL:Head Failure، SA Failure، Firmware / Translator / ROM
SYL:Locked Drive، SED، Non-original ROM
SYL:SMR Recovery و کیس عملی آزمایشگاه
---
07|بازیابی Seagate|Seagate HDD Data Recovery|پیشرفته|پس از مبانی HDD و ترجیحاً دوره فریمور
SYL:Seagate Architecture و F3
SYL:Firmware، ROM، Service Area، Modules، System Files
SYL:Translator، Adaptive Data، Terminal
SYL:ATA Commands، Diagnostic Commands، Serial Communication
SYL:کیس BSY، 0 LBA، Slow Responding
SYL:Firmware / Translator Failure
SYL:Head / Media / SMART / PCB / ROM Problems
SYL:USB Seagate Drives
SYL:Terminal Diagnostics، ATA Shell، تحلیل ماژول
SYL:Imaging Strategy و Data Extraction
---
08|کارگاه کیس واقعی|Advanced Data Recovery — Real Case Workshop|Master|دوره نهایی مسیر HDD Land
SYL:مسیر کیس: پذیرش → تشخیص → Backup → استراتژی → Imaging → Recovery → Verification
SYL:Dead / Clicking / No Spin
SYL:0 LBA، Wrong Capacity، BSY، Slow HDD
SYL:Bad Sector، Weak Head، Damaged Head
SYL:Firmware / SA / Translator / ROM / PCB Failure
SYL:USB HDD، SMR HDD، خرابی ترکیبی سخت‌افزار و فریمور
SYL:ثبت Case، عکس‌برداری، تشخیص اولیه
SYL:انتخاب Donor، Backup، Imaging، Recovery
SYL:Verification، گزارش نهایی و تحویل به مشتری
LAB:حل کیس واقعی از ابتدا تا انتها در آزمایشگاه
TXT;
    }

    /** @return array<string, mixed> */
    private static function repairDefaults(): array
    {
        return [
            'fix_on' => true,
            'fix_kicker' => 'آموزش تعمیرات',
            'fix_title' => 'تعمیر تخصصی هارد دیسک',
            'fix_card' => 'تعمیر برد، هد و مکانیک تمام برندهای اصلی؛ سیلابس عملی و استعلام هزینه از واحد آموزش.',
            'fix_lead' => 'تعمیر هارد دیسک در HDD Land یک مسیر جدا از بازیابی داده است: رسانه را پایدار می‌کنیم تا یا دوباره کار کند، یا برای استخراج داده آماده شود. آموزش برای تمام برندهای بالا، با تمرکز تخصصی روی تعمیر سخت‌افزاری است.',
            'fix_intro' => 'تعمیر تخصصی یعنی تشخیص برد، تعویض هد در شرایط کنترل‌شده، تراز مکانیکی، بایوس و ram برد، و شناخت محدودیت هر خانواده. Western Digital، Seagate، Toshiba، Hitachi/HGST، Samsung، معماری ARM و هارد سرور هر کدام نقطه ضعف و ابزار خود را دارند. کلاس کوتاه و حرفه‌ای است؛ کارآموز روی قطعه واقعی کار می‌کند، نه روی اسلاید.',
            'fix_audience' => 'برای تعمیرکار هارد، لابراتوار، و فنی‌ای که می‌خواهد تعمیر را از بازیابی جدا بلد باشد.',
            'fix_level' => 'عملی، برندبه‌برند',
            'fix_duration' => '۳ تا ۱۰ روز',
            'fix_prereq' => "مهارت کار با هویه و ابزار دقیق\nشناخت اولیه الکترونیک برد\nرعایت اتاق تمیز و آنتی‌استاتیک",
            'fix_includes' => "کار روی درایوهای آموزشی هر برند\nچک‌لیست تشخیص صدا و برد\nنکات donor و تطبیق هد / PCB\nگواهی پایان دوره تعمیر\nمشاوره راه‌اندازی میز تعمیر",
            'fix_syllabus' => "ایمنی، ESD و اصول اتاق تمیز برای باز کردن هارد\nتشخیص صدای کلیک، بوق و عدم اسپین\nخواندن برد، رگولاتور، TVS و بایوس\nتطبیق PCB و انتقال ROM\nتعویض هد: donor، شانه هد و محدودیت خانواده\nWestern Digital: برد Marvell / ARM و نکات SMR\nSeagate: برد F3، ترمینال سخت‌افزاری و donor\nToshiba و Hitachi/HGST: مکانیک و هد\nSamsung: برد و محدودیت قطعات\nهارد سرور: SAS، سینی و محدودیت سازمانی\nبعد از تعمیر: تست پایداری و تصمیم بازیابی یا تحویل",
            'fix_table' => "Western Digital|تعمیر برد و هد WD|۴ روز|عملی|تماس بگیرید\nSeagate|تعمیر سخت‌افزار Seagate|۴ روز|عملی|تماس بگیرید\nToshiba|تعمیر Toshiba|۳ روز|عملی|تماس بگیرید\nHitachi / HGST|تعمیر Hitachi و HGST|۳ روز|عملی|تماس بگیرید\nSamsung|تعمیر Samsung HDD|۳ روز|عملی|تماس بگیرید\nARM|تعمیر خانواده‌های ARM|۴ روز|پیشرفته|تماس بگیرید\nServer|تعمیر هارد سرور|۴ روز|پیشرفته|تماس بگیرید\nبسته جامع|تعمیر همه برندها|۱۰ روز|حرفه‌ای|تماس بگیرید",
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
            'ssd_table' => "SATA SSD|بازیابی SSD ساتا|۳ روز|متوسط|تماس بگیرید\nM.2 SATA|بازیابی ماژول M.2 ساتا|۳ روز|متوسط|تماس بگیرید\nNVMe PCIe|بازیابی NVMe / M.2 NVMe|۴ روز|پیشرفته|تماس بگیرید\nPhison / SM|کنترلر Phison و Silicon Motion|۴ روز|پیشرفته|تماس بگیرید\nSamsung|SSD و NVMe سامسونگ|۳ روز|پیشرفته|تماس بگیرید\nبسته جامع|SSD + M.2 + NVMe|۸ روز|حرفه‌ای|تماس بگیرید",
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
            'srv_table' => "RAID کلاسیک|RAID 0 / 1 / 5 / 6 / 10|۳ روز|پیشرفته|تماس بگیرید\nNAS|بازیابی NAS سازمانی|۳ روز|پیشرفته|تماس بگیرید\nSAN / DAS|استوریج سازمانی و SAN|۴ روز|حرفه‌ای|تماس بگیرید\nLeftover|متادیتا و leftover کنترلر|۳ روز|پیشرفته|تماس بگیرید\nSAS Server|هارد و آرایه سرور|۴ روز|پیشرفته|تماس بگیرید\nبسته جامع|سرور + RAID + استوریج|۹ روز|حرفه‌ای|تماس بگیرید",
            'srv_faq' => "اگر سازمان rebuild زده باشد؟|بخشی از کیس‌ها بعد از rebuild بد قابل نجات نیستند. در دوره همین تصمیم‌گیری آموزش داده می‌شود.\nدوره برای فروشنده استوریج است؟|مخاطب اصلی فنی آزمایشگاه و IT است؛ فروشنده بدون پیش‌زمینه سخت‌افزاری توصیه نمی‌شود.\nهمراه با دوره هارد است؟|مکمل است. برای کیس ترکیبی معمولاً بازیابی هارد یا SSD را هم لازم دارید.",
            'srv_cta' => 'ثبت‌نام سرور و استوریج',
        ];
    }
}
