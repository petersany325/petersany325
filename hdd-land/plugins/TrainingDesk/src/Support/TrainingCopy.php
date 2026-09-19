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
        foreach ([
            'rec_path_title', 'rec_path', 'rec_modules', 'rec_special_title', 'rec_special', 'rec_mods_title',
            'fix_path_title', 'fix_path', 'fix_modules', 'fix_special_title', 'fix_special', 'fix_special_lead', 'fix_tracks_title', 'fix_tracks', 'fix_mods_title',
            'rec_special_lead',
            'ssd_path_title', 'ssd_path', 'ssd_modules', 'ssd_special_title', 'ssd_special', 'ssd_special_lead', 'ssd_tracks_title', 'ssd_tracks', 'ssd_mods_title',
            'srv_path_title', 'srv_path', 'srv_modules', 'srv_special_title', 'srv_special', 'srv_special_lead', 'srv_tracks_title', 'srv_tracks', 'srv_mods_title',
        ] as $k) {
            if (trim((string) ($out[$k] ?? '')) === '') {
                $out[$k] = $d[$k] ?? '';
            }
        }
        if (str_contains((string) ($out['rec_table'] ?? ''), 'بازیابی WD')) {
            foreach (['rec_table', 'rec_lead', 'rec_intro', 'rec_card', 'rec_level', 'rec_duration', 'rec_prereq', 'rec_includes', 'rec_syllabus', 'rec_faq', 'rec_audience'] as $k) {
                $out[$k] = $d[$k];
            }
        }
        $fixStale = str_contains((string) ($out['fix_table'] ?? ''), 'تعمیر برد و هد WD')
            || str_contains((string) ($out['fix_title'] ?? ''), 'تعمیر تخصصی')
            || ! str_contains((string) ($out['fix_modules'] ?? ''), 'SEC:معماری Seagate');
        if ($fixStale) {
            foreach (array_keys(self::repairDefaults()) as $k) {
                $out[$k] = $d[$k];
            }
        }
        $ssdStale = str_contains((string) ($out['ssd_table'] ?? ''), 'بازیابی SSD ساتا')
            || str_contains((string) ($out['ssd_title'] ?? ''), 'بازیابی SSD، M.2')
            || ! str_contains((string) ($out['ssd_modules'] ?? ''), 'SEC:شناخت M.2 NVMe');
        if ($ssdStale) {
            foreach (array_keys(self::ssdDefaults()) as $k) {
                $out[$k] = $d[$k];
            }
        }
        $srvStale = str_contains((string) ($out['srv_table'] ?? ''), 'RAID کلاسیک')
            || str_contains((string) ($out['srv_kicker'] ?? ''), 'آموزش سرور و استوریج')
            || ! str_contains((string) ($out['srv_modules'] ?? ''), 'SEC:مبانی Storage');
        if ($srvStale) {
            foreach (array_keys(self::serverDefaults()) as $k) {
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
                $prefix.'_path' => 1400,
                $prefix.'_modules' => 32000,
                $prefix.'_special_title' => 80,
                $prefix.'_special' => 1400,
                $prefix.'_special_lead' => 400,
                $prefix.'_tracks_title' => 80,
                $prefix.'_tracks' => 1200,
                $prefix.'_mods_title' => 80,
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
            'special_lead' => (string) ($copy[$prefix.'_special_lead'] ?? ''),
            'mods_title' => (string) ($copy[$prefix.'_mods_title'] ?? ''),
            'tracks_title' => (string) ($copy[$prefix.'_tracks_title'] ?? ''),
            'tracks' => self::path((string) ($copy[$prefix.'_tracks'] ?? '')),
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

    /** @return list<array{code:string,title:string,en:string,level:string,audience:string,syllabus:list<array{type:string,text:string}>,lab:list<string>,flow:list<string>}> */
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
            $flow = [];
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if (str_starts_with($line, 'SEC:')) {
                    $syl[] = ['type' => 'sec', 'text' => trim(substr($line, 4))];
                } elseif (str_starts_with($line, 'SYL:')) {
                    $syl[] = ['type' => 'item', 'text' => trim(substr($line, 4))];
                } elseif (str_starts_with($line, 'LAB:')) {
                    $lab[] = trim(substr($line, 4));
                } elseif (str_starts_with($line, 'WFL:')) {
                    $flow[] = trim(substr($line, 4));
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
                'flow' => $flow,
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
            'rec_mods_title' => 'سیلابس هشت دوره',
            'rec_path_title' => 'مسیر حرفه‌ای آکادمی',
            'rec_path' => "LEVEL 1 — FOUNDATION|مبانی|01 مبانی بازیابی · 02 Logical Recovery\nLEVEL 2 — HDD ENGINEERING|مهندسی هارد|03 سخت‌افزار و تشخیص · 04 فریمور و Service Area\nLEVEL 3 — PROFESSIONAL RECOVERY|بازیابی حرفه‌ای|05 Imaging و استخراج داده\nLEVEL 4 — VENDOR SPECIALIZATION|تخصص برند|06 Western Digital · 07 Seagate\nLEVEL 5 — MASTER|کارگاه نهایی|08 کیس‌های واقعی از پذیرش تا تحویل",
            'rec_modules' => self::recoveryModules(),
            'rec_special_title' => 'دوره‌های تخصصی بعدی',
            'rec_special_lead' => 'این‌ها مسیرهای جدا هستند و از ابتدا صفحه را شلوغ نمی‌کنند؛ وقتی آماده باشند به همین آکادمی اضافه می‌شوند.',
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
            'fix_kicker' => 'HDD LAND Professional HDD Repair Academy',
            'fix_title' => 'آکادمی حرفه‌ای تعمیر هارد دیسک',
            'fix_card' => 'سیلابس برند + سطح فنی + نوع خرابی؛ نه آموزش کار با یک نرم‌افزار.',
            'fix_lead' => 'اگر هدف دوره‌های تخصصی تعمیر و بازیابی HDD است، سرفصل‌ها باید روی برند، سطح فنی و نوع خرابی باشد؛ نه فقط آموزش نرم‌افزار. مسیر با Hardware، Firmware، Service Area و Imaging شروع می‌شود و سپس Seagate، Western Digital، Toshiba، Hitachi/HGST، Samsung، Fujitsu و External جدا پوشش داده می‌شوند.',
            'fix_intro' => 'هر برند مسیر خودش را دارد: Basic → Firmware → Terminal / ROM / SA → کیس پیشرفته. SeDiv و ابزار آزمایشگاه داخل همان دوره برند معرفی می‌شوند تا صفحه تبدیل به تبلیغ نرم‌افزار نشود. تخصص PCB، مکانیک Head و Platter، Imaging مشترک و کارگاه کیس واقعی بعد از مبانی می‌آید. هارد اکسترنال دوره جداست؛ چون Bridge، رمزنگاری و برد USB مسیر Recovery را عوض می‌کند.',
            'fix_audience' => 'برای تعمیرکار هارد، آزمایشگاه بازیابی، و فنی‌ای که می‌خواهد برندبه‌برند کیس واقعی بگیرد.',
            'fix_level' => 'HDD-101 تا HDD-401 — مقدماتی تا Master',
            'fix_duration' => '۱۳ دوره آزمایشگاهی، برند و تخصص جدا',
            'fix_prereq' => "HDD-101 پیش‌نیاز ورود به دوره‌های برند است\nمهارت کار با هویه و ESD برای PCB و مکانیک\nاتاق تمیز برای Head / Platter\nدوره Imaging برای همه برندها مشترک است",
            'fix_includes' => "کار روی Patient و Donor واقعی\nPC-3000، Data Extractor، SeDiv و ابزار تشخیص آزمایشگاه\nچک‌لیست Donor، ROM و PCB\nگواهی هر کد دوره\nگزارش کیس از تشخیص تا Imaging",
            'fix_syllabus' => "HDD-101 مبانی و تشخیص حرفه‌ای\nHDD-201 تا 207 برندها و External\nHDD-301 فریمور و Service Area\nHDD-302 تعمیر PCB\nHDD-303 مکانیک Head / Platter\nHDD-304 Imaging حرفه‌ای\nHDD-401 کیس واقعی",
            'fix_table' => "HDD-101|HDD Fundamentals & Diagnostics|۳ روز|مقدماتی|تماس بگیرید\nHDD-201|Seagate HDD Repair & Recovery|۴ روز|پیشرفته|تماس بگیرید\nHDD-202|Western Digital HDD Repair & Recovery|۴ روز|پیشرفته|تماس بگیرید\nHDD-203|Toshiba HDD Repair & Recovery|۳ روز|پیشرفته|تماس بگیرید\nHDD-204|Hitachi / HGST HDD Repair & Recovery|۳ روز|پیشرفته|تماس بگیرید\nHDD-205|Samsung HDD Repair & Recovery|۳ روز|پیشرفته|تماس بگیرید\nHDD-206|Fujitsu HDD Repair & Recovery|۳ روز|پیشرفته|تماس بگیرید\nHDD-207|External HDD Repair & Data Recovery|۳ روز|پیشرفته|تماس بگیرید\nHDD-301|HDD Firmware & Service Area|۴ روز|تخصصی|تماس بگیرید\nHDD-302|HDD PCB & Electronics Repair|۳ روز|تخصصی|تماس بگیرید\nHDD-303|Head / Platter / Mechanical Recovery|۴ روز|تخصصی|تماس بگیرید\nHDD-304|Professional HDD Imaging|۴ روز|تخصصی|تماس بگیرید\nHDD-401|Advanced HDD Case Studies|۵ روز|Master|تماس بگیرید",
            'fix_path_title' => 'مسیر اصلی دوره‌ها',
            'fix_path' => "HDD-100|Foundation — مبانی|HDD-101 مبانی هارد و تشخیص حرفه‌ای\nHDD-200|Brand Repair — تعمیر برند|201 Seagate · 202 Western Digital · 203 Toshiba · 204 Hitachi/HGST · 205 Samsung · 206 Fujitsu · 207 External\nHDD-300|Specialized — مهندسی تخصصی|301 Firmware و Service Area · 302 PCB · 303 Mechanical · 304 Imaging\nHDD-400|Master — کارگاه نهایی|401 کیس واقعی از پذیرش تا Verification",
            'fix_mods_title' => 'سیلابس کامل دوره‌ها',
            'fix_modules' => self::repairModules(),
            'fix_tracks_title' => 'مسیر برندمحور',
            'fix_tracks' => "Seagate|Basic → Firmware → Terminal → Rosewood → Advanced Cases\nWestern Digital|Basic → Firmware → ROM → SA → CMR/SMR → Advanced Cases\nToshiba|Basic → Firmware → SA → ARM → Advanced Cases\nHitachi / HGST|Basic → Firmware → SA → Adaptive → Enterprise → Advanced Cases\nSamsung|Basic → Firmware → SA → Hardware → Advanced Cases\nFujitsu|Basic → Firmware → Hardware → Advanced Cases\nExternal HDD|USB → Bridge → Native USB → Encryption → Firmware → Imaging → Recovery",
            'fix_special_title' => 'صفحه مستقل هر برند (مرحله بعد)',
            'fix_special_lead' => 'برای هر برند صفحه جدا با نمای کلی دوره، سطح، سیلابس کامل، کیس عملی، ابزار لازم، پیش‌نیاز و گواهی می‌سازیم. SeDiv داخل همان برند معرفی می‌شود؛ این صفحه تبلیغ نرم‌افزار نیست.',
            'fix_special' => "Seagate — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nWestern Digital — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nToshiba — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nHitachi / HGST — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nSamsung — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nFujitsu — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی\nExternal HDD — Overview، سطح، سیلابس، کیس، ابزار، پیش‌نیاز، گواهی",
            'fix_faq' => "این آموزش نرم‌افزار است؟|خیر. سیلابس روی برند، نوع خرابی و سطح فنی است. ابزار آزمایشگاه داخل همان دوره معرفی می‌شود، نه به‌جای سرفصل.\nبا آکادمی بازیابی یکی است؟|مکمل است. اینجا تعمیر، پایدارسازی و برندمحور است؛ بازیابی مسیر جدا دارد.\nاز کدام دوره شروع کنم؟|HDD-101. دوره‌های برند بعد از تشخیص، تخصص‌های ۳۰۰ بعد از برند یا موازی با آن.\nچرا External جدا است؟|هارد اکسترنال فقط یک HDD داخل قاب نیست. USB Bridge، رمزنگاری و برد اصلی مسیر Recovery را عوض می‌کند.\nاتاق تمیز لازم است؟|برای HDD-303 بله. PCB و Imaging محیط آزمایشگاهی جدا دارند.",
            'fix_cta' => 'ثبت‌نام آکادمی تعمیر',
        ];
    }

    private static function repairModules(): string
    {
        return <<<'TXT'
HDD-101|مبانی هارد و تشخیص حرفه‌ای|HDD Fundamentals & Professional Diagnostics|مقدماتی|ورود به آکادمی تعمیر
SEC:مبانی هارد دیسک
SYL:ساختار داخلی HDD: Platter، Head، Head Stack Assembly
SYL:Spindle Motor، VCM، Preamp، PCB
SYL:ROM، RAM، Service Area، User Area
SYL:Sector، Track، Cylinder، LBA، Zone
SYL:P-List / G-List و Adaptive Data
SEC:تشخیص نوع خرابی
SYL:Logical Failure
SYL:Firmware Failure
SYL:PCB Failure
SYL:Mechanical Failure
SYL:Head Failure
SYL:Media Damage
SYL:Power Failure
SYL:Interface Failure
SEC:علائم خرابی
SYL:HDD Not Detected، 0 LBA، Wrong Capacity، BSY
SYL:Slow Response، Clicking، No Spin، Spin Down
SYL:Bad Sectors، Read Instability، SMART Errors
SEC:ابزارهای تشخیص
SYL:BIOS / UEFI، ATA Identification، SMART، Terminal
SYL:ابزار تشخیص حرفه‌ای: PC-3000 / Data Extractor، SeDiv و سایر ابزار آزمایشگاه
LAB:تشخیص نوع خرابی چند هارد واقعی
LAB:بررسی SMART، ظرفیت، LBA و وضعیت اسپین
---
HDD-201|تعمیر و بازیابی Seagate|Seagate HDD Repair & Data Recovery|پیشرفته|پس از HDD-101 — شامل Maxtor
SEC:معماری Seagate
SYL:شناخت خانواده‌های Seagate و نسل‌های Firmware
SYL:معماری F3، ROM، RAM، System Area
SYL:Firmware Modules، Adaptive Data، Translator، Head Map
SYL:Initialization Process و تحلیل علت خرابی
SEC:فریمور
SYL:Firmware Diagnosis، تحلیل / Backup / Repair ماژول
SYL:دسترسی Service Area
SYL:مشکلات Translator، ROM و Adaptive
SEC:ترمینال و فرمان‌ها
SYL:اتصال Terminal و ارتباط سریال
SYL:ATA Commands، Diagnostic Modes، بررسی وضعیت HDD
SYL:تحلیل پاسخ‌های Firmware
SEC:خرابی‌های متداول
SYL:BSY، 0 LBA، Slow Responding، Wrong Capacity
SYL:Firmware Corruption، Bad Sector
SYL:Head / PCB / ROM / SA Failure
SEC:Seagate Rosewood
SYL:معماری Rosewood، خانواده‌ها و SMR
SYL:ساختار Firmware، مشکلات Head و Slow
SYL:معماری USB/SATA و استراتژی Imaging
LAB:اتصال ترمینال، پشتیبان ماژول و تشخیص F3
LAB:استراتژی Imaging برای کیس Rosewood / SMR
---
HDD-202|تعمیر و بازیابی Western Digital|Western Digital HDD Repair & Data Recovery|پیشرفته|پس از HDD-101 — SMR بخش مستقل
SEC:معماری WD
SYL:خانواده‌های WD، ROM، RAM، Service Area
SYL:Firmware Modules، Adaptive Data، Translator، Head Map، Initialization
SEC:فریمور WD
SYL:تحلیل / Backup / Repair ماژول و دسترسی SA
SYL:تحلیل ROM، Adaptive Data، Translator و فساد فریمور
SEC:خرابی‌های متداول
SYL:0 LBA، BSY، Slow Responding، Wrong Capacity، Clicking
SYL:مشکلات Head، Bad Sector، SA، ROM و Translator
SEC:WD CMR / SMR
SYL:تفاوت معماری CMR و SMR
SYL:Recovery و Imaging روی SMR، رفتار فریمور و مشکلات Head
SEC:برد WD
SYL:شناسایی PCB، سازگاری، انتقال ROM
SYL:تشخیص الکترونیک و مشکلات ریل تغذیه
LAB:تشخیص خانواده WD، پشتیبان SA و کیس SMR
LAB:شناسایی PCB و تصمیم انتقال ROM
---
HDD-203|تعمیر و بازیابی Toshiba|Toshiba HDD Repair & Recovery|پیشرفته|پس از HDD-101
SEC:معماری Toshiba
SYL:خانواده‌های Toshiba: ۲٫۵ اینچ / ۳٫۵ اینچ، لپ‌تاپ، دسکتاپ، USB
SYL:ROM، Firmware، Service Area، Adaptive Data
SEC:فریمور
SYL:ساختار ماژول، دسترسی SA، Backup و تحلیل ماژول
SYL:Translator و Defect Management
SEC:خرابی‌های متداول
SYL:No Detection، 0 LBA، Wrong Capacity، Slow HDD
SYL:Bad Sector، Clicking، Head / PCB / Firmware Failure
SEC:Toshiba ARM
SYL:معماری ARM، ساختار Firmware، Service Area و Adaptive
SYL:استراتژی Recovery روی خانواده‌های ARM
LAB:تشخیص خانواده Toshiba و دسترسی SA
---
HDD-204|تعمیر و بازیابی Hitachi / HGST|Hitachi / HGST HDD Repair & Recovery|پیشرفته|پس از HDD-101 — شامل Enterprise
SEC:معماری Hitachi / HGST
SYL:خانواده‌های Hitachi و HGST: Desktop، Laptop، Enterprise
SYL:SATA و SAS، ROM، SA، Firmware، Adaptive Data
SEC:فریمور
SYL:ساختار ماژول، Service Area، Defect Management
SYL:P-List / G-List، Adaptive Parameters، Backup و تحلیل فریمور
SEC:خرابی‌های متداول
SYL:0 LBA، Wrong Capacity، Slow Response
SYL:Firmware / Head / Media / PCB / SA Failure
SEC:Enterprise / HGST
SYL:معماری هارد سازمانی، تفاوت SAS و SATA
SYL:محیط RAID، ظرفیت بالا و Imaging حرفه‌ای
LAB:تشخیص خانواده HGST و کار روی Defect List
---
HDD-205|تعمیر و بازیابی Samsung|Samsung HDD Repair & Recovery|پیشرفته|پس از HDD-101
SEC:معماری Samsung
SYL:خانواده‌های Samsung ۲٫۵ اینچ / ۳٫۵ اینچ
SYL:ROM، Firmware، Service Area، Adaptive Data
SEC:فریمور
SYL:تحلیل ماژول، دسترسی SA، Backup / Repair
SYL:Adaptive Data و Defect Lists
SEC:سخت‌افزار
SYL:تشخیص PCB، Motor، Head، Preamp، ROM
SYL:انتخاب Donor
SEC:کیس‌های متداول
SYL:No Detection، 0 LBA، Bad Sector، Slow HDD، Clicking
SYL:Firmware / Head / PCB Failure
LAB:تشخیص PCB و انتخاب Donor برای Samsung
---
HDD-206|تعمیر و بازیابی Fujitsu|Fujitsu HDD Repair & Recovery|پیشرفته|پس از HDD-101 — شامل نسل Legacy
SEC:معماری Fujitsu
SYL:خانواده‌های Fujitsu ۲٫۵ اینچ / ۳٫۵ اینچ
SYL:معماری Legacy، ROM، Firmware، Service Area، Adaptive Data
SEC:سخت‌افزار
SYL:PCB، Head، Spindle، Preamp، Motor
SYL:انتخاب Donor
SEC:فریمور
SYL:ساختار SA، ماژول‌ها، Defect Management
SYL:تشخیص فریمور و روند Recovery
SEC:کیس‌های متداول
SYL:No Detection، Wrong Capacity، Bad Sector
SYL:Firmware / Head / PCB / Mechanical Failure
LAB:کیس Legacy Fujitsu و تطبیق Donor
---
HDD-207|تعمیر هارد اکسترنال|External HDD Repair & Data Recovery|پیشرفته|جدا از هارد داخلی — Bridge و رمزنگاری مسیر را عوض می‌کند
SEC:معماری External HDD
SYL:USB HDD، پل USB-SATA، USB-PCB
SYL:درایو Native USB در برابر هارد SATA داخل قاب
SYL:کنترلر Bridge، تغذیه USB، ۵ ولت / ۱۲ ولت
SEC:برندها
SYL:WD External، Seagate External، Toshiba External
SYL:Samsung External، Hitachi External و سایر قاب‌ها
SEC:مشکلات متداول
SYL:USB Not Detected، Detected but No Data، Wrong Capacity، RAW
SYL:CRC Error، I/O Error، Slow USB، Disconnect / Reconnect
SYL:مشکلات تغذیه و خرابی Bridge
SEC:رمزنگاری
SYL:رمزنگاری USB، سخت‌افزاری و وابسته به Bridge
SYL:اهمیت نگهداری PCB اصلی و برد USB اورجینال
SYL:بازیابی Native USB
SEC:استراتژی Recovery
SYL:تشخیص Bridge در برابر HDD و بررسی PCB
SYL:دسترسی مستقیم SATA فقط در موارد مناسب
SYL:Imaging، بازیابی فایل‌سیستم و استخراج داده
LAB:تشخیص Bridge، تصمیم SATA مستقیم و نگهداری برد اصلی
---
HDD-301|فریمور و Service Area|HDD Firmware & Service Area|تخصصی|پس از مبانی؛ مکمل دوره‌های برند
SEC:معماری فریمور
SYL:MCU، ROM، RAM، Service Area / System Area
SYL:Modules، Adaptive Data، Translator، Head Map
SEC:کار روی ماژول
SYL:خواندن، نوشتن، مقایسه و Backup ماژول
SYL:دسترسی SA و تشخیص فساد ماژول
SEC:ترمینال
SYL:UART، Diagnostic Mode، Boot Mode
SYL:کیس Corruption، Missing Module، 0 LBA، BSY، ERR، Init
SEC:ROM
SYL:Backup، Transfer و سازگاری ROM
SYL:معرفی SeDiv و ابزار SA داخل همان کیس برند — نه به‌جای سیلابس
LAB:پشتیبان SA، مقایسه ماژول و کار ترمینال
---
HDD-302|تعمیر الکترونیک PCB|Professional HDD PCB Repair|تخصصی|هویه و ابزار دقیق لازم است
SEC:الکترونیک برد
SYL:معماری PCB، ریل ۵ ولت و ۱۲ ولت
SYL:دیود TVS، فیوز، رگولاتور ولتاژ
SYL:کنترلر موتور، MCU، رابط Preamp
SEC:عیب‌یابی
SYL:اتصال کوتاه، قطعه سوخته، اضافه ولتاژ، پلاریته معکوس
SYL:No Power، No Spin، PCB Not Detected
SEC:ROM
SYL:Backup و Transfer ROM، Adaptive Data
SYL:انتخاب Donor PCB و سازگاری
SEC:ابزار
SYL:مولتی‌متر، اسیلوسکوپ، منبع آزمایشگاهی
SYL:میکروسکوپ و تجهیزات هویه / ریورک
LAB:اندازه‌گیری ریل تغذیه و تعویض TVS / فیوز
LAB:انتقال ROM روی Donor سازگار
---
HDD-303|بازیابی مکانیکی Head و Platter|Professional Head & Platter Recovery|تخصصی|اتاق تمیز الزامی
SEC:ساختار مکانیکی
SYL:HDA، Platter، Head Stack، VCM
SYL:Spindle، Preamp، Ramp، سیستم پارک هد
SEC:خرابی مکانیکی
SYL:Head Crash، Head Stiction، Head Failure
SYL:خرابی Spindle / Motor، آسیب Platter، خراش، آلودگی
SEC:انتخاب Donor
SYL:Model، Family، Firmware، سازگاری Head
SYL:Date Code، PCB، Preamp، سازگاری مکانیکی
SEC:کار عملی
SYL:باز کردن HDA، بازرسی و تعویض Head / Head Stack
SYL:کار روی Spindle، جابه‌جایی Platter، محیط تمیز
SYL:تشخیص بعد از تعمیر
LAB:باز کردن کنترل‌شده HDA و تعویض Head Stack
LAB:جابه‌جایی Platter و تست پایداری بعد از تعمیر
---
HDD-304|Imaging حرفه‌ای|Advanced Imaging & Data Extraction|تخصصی|مشترک برای تمام برندها
SEC:Imaging
SYL:Sector-by-Sector Imaging
SYL:Read Instability، Bad Sector، Weak Sector، Slow Sector
SYL:Head Instability، Read Retry، Timeout، Skip Strategy
SYL:Multi-pass، Reverse Imaging، Selective Imaging
SEC:Imaging مبتنی بر Head
SYL:Head Map، Head-by-Head Imaging
SYL:غیرفعال کردن هد معیوب، Imaging انتخابی، اولویت Recovery
SEC:استخراج داده
SYL:Raw Recovery، File System Recovery
SYL:Image Mount، Partial Image، Verification، استخراج فایل
LAB:Imaging چندمرحله‌ای و Head-by-Head روی رسانه ناپایدار
LAB:ماونت ایمیج و استخراج فایل با Data Extractor
---
HDD-401|کارگاه کیس واقعی|Advanced HDD Case Studies|Master|پایان تئوری؛ شروع کیس مشتری
SEC:کیس‌های عملی
SYL:Dead HDD، No Spin، Clicking HDD
SYL:0 LBA، BSY، Slow HDD، Wrong Capacity
SYL:Firmware Corruption، SA Failure، ROM Failure
SYL:PCB Failure، Head Failure، Bad Sector، Media Damage
SYL:SMR Recovery، External HDD، USB HDD، Encrypted External
WFL:پذیرش کیس مشتری
WFL:تشخیص اولیه
WFL:تحلیل سخت‌افزار
WFL:تحلیل فریمور
WFL:استراتژی Recovery
WFL:Backup
WFL:تعمیر / پایدارسازی
WFL:Imaging حرفه‌ای
WFL:بازیابی فایل
WFL:Verification
LAB:حل کیس واقعی از پذیرش تا تحویل در آزمایشگاه
TXT;
    }

    /** @return array<string, mixed> */
    private static function ssdDefaults(): array
    {
        return [
            'ssd_on' => true,
            'ssd_kicker' => 'M.2 NVMe SSD Repair & Data Recovery — Professional Training',
            'ssd_title' => 'آکادمی تعمیر و بازیابی M.2 NVMe',
            'ssd_card' => 'مسیر ۱۲دوره‌ای مستقل از SSD ساتا: تشخیص، PCB، Firmware، FTL، NAND و Chip-Off.',
            'ssd_lead' => 'From NVMe Diagnostics and PCB Repair to Firmware, FTL, NAND Analysis, Imaging and Advanced Data Recovery. این آکادمی با SSD ساتا قاطی نمی‌شود؛ مسیر مهندسی M.2 NVMe است، نه آموزش کار با یک نرم‌افزار.',
            'ssd_intro' => 'شش سطح از معماری M.2 و NVMe تا تشخیص PCIe، تعمیر PCB، کنترلر و فریمور، FTL، Imaging، رمزگذاری، NAND و Chip-Off، سپس کیس برند و Master. در NVMe مدرن اولویت اغلب حفظ کنترلر اصلی است؛ چون رمزگذاری سخت‌افزاری خواندن خام NAND را بی‌نتیجه می‌کند. آموزش بر اساس Controller + NAND + Firmware Revision است، نه فقط اسم برند.',
            'ssd_audience' => 'برای آزمایشگاه بازیابی، تعمیرکار لپ‌تاپ و سرور، و فنی‌ای که کیس NVMe سازمانی می‌گیرد.',
            'ssd_level' => 'شش سطح: Fundamentals تا Master',
            'ssd_duration' => '۱۲ دوره آزمایشگاهی، مستقل از SSD ساتا',
            'ssd_prereq' => "آشنایی با الکترونیک پایه\nآشنایی مقدماتی با SSD\nآشنایی با File System\nآشنایی با کار ابزارهای Data Recovery",
            'ssd_includes' => "کار روی ماژول M.2 NVMe واقعی\nچک‌لیست تشخیص کنترلر، PCIe و ریل تغذیه\nکارگاه میکروهویه و ریورک BGA\nگواهی هر کد دوره\nگزارش کیس از تشخیص تا Imaging",
            'ssd_syllabus' => "NVME-101 مبانی M.2 NVMe\nNVME-201 تشخیص تخصصی\nNVME-301 تعمیر PCB و الکترونیک\nNVME-302 کنترلر و فریمور\nNVME-303 پروتکل و فرمان‌ها\nNVME-401 FTL و Mapping\nNVME-402 بازیابی داده\nNVME-501 NAND Flash\nNVME-502 Chip-Off\nNVME-503 رمزگذاری و امنیت\nNVME-601 برند و کنترلر\nNVME-602 کیس واقعی",
            'ssd_table' => "NVME-101|M.2 NVMe Fundamentals|۳ روز|مقدماتی تا متوسط|تماس بگیرید\nNVME-201|Professional NVMe Diagnostics|۳ روز|پیشرفته|تماس بگیرید\nNVME-301|NVMe Electronics & PCB Repair|۳ روز|تخصصی|تماس بگیرید\nNVME-302|NVMe Controller & Firmware|۴ روز|تخصصی|تماس بگیرید\nNVME-303|NVMe Protocol & Commands|۳ روز|تخصصی|تماس بگیرید\nNVME-401|NVMe FTL & Mapping|۴ روز|تخصصی|تماس بگیرید\nNVME-402|Professional NVMe Data Recovery|۴ روز|تخصصی|تماس بگیرید\nNVME-501|NAND Flash Recovery for NVMe|۴ روز|تخصصی|تماس بگیرید\nNVME-502|M.2 NVMe Chip-Off|۴ روز|تخصصی|تماس بگیرید\nNVME-503|NVMe Encryption & Security|۳ روز|تخصصی|تماس بگیرید\nNVME-601|M.2 NVMe Brands & Controllers|۴ روز|پیشرفته|تماس بگیرید\nNVME-602|Advanced M.2 NVMe Case Studies|۵ روز|Master|تماس بگیرید",
            'ssd_path_title' => 'مسیر حرفه‌ای آکادمی NVMe',
            'ssd_path' => "LEVEL 1 — FUNDAMENTALS|مبانی|NVME-101 معماری M.2، NVMe، قطعات SSD و NAND\nLEVEL 2 — DIAGNOSTICS|تشخیص|NVME-201 تشخیص NVMe، تحلیل PCIe، تغذیه و شناسایی کنترلر\nLEVEL 3 — REPAIR|تعمیر|NVME-301 PCB · 302 Firmware / Controller · 303 Protocol\nLEVEL 4 — DATA RECOVERY|بازیابی|NVME-401 FTL و Mapping · 402 Imaging و File System\nLEVEL 5 — ADVANCED|پیشرفته|NVME-501 NAND · 502 Chip-Off · 503 Encryption\nLEVEL 6 — MASTER|کارگاه نهایی|NVME-601 برند و کنترلر · 602 کیس واقعی",
            'ssd_mods_title' => 'سیلابس دوازده دوره',
            'ssd_modules' => self::ssdModules(),
            'ssd_tracks_title' => 'مسیر کنترلر و برند',
            'ssd_tracks' => "Phison|Identify → Firmware → FTL → Imaging → Cases\nSilicon Motion|Identify → Firmware → FTL → Imaging → Cases\nSamsung|Controller → NAND → Health → Firmware → Cases\nWD / SanDisk|Controller → NAND → Firmware → Cases\nMicron / Crucial|Controller → NAND → Firmware → Cases\nMarvell / Maxio|Identify → Firmware → Recovery\nIntel / Solidigm|Architecture → Controller → NAND → Firmware → Cases\nKingston / ADATA / XPG|Phison · SM · Maxio — شناسایی کنترلر، نه فقط برند",
            'ssd_special_title' => 'دوره‌های تخصصی بعدی',
            'ssd_special_lead' => 'این آکادمی فقط M.2 NVMe است. SSD ساتا و مسیرهای نزدیک جدا می‌مانند تا با پروتکل PCIe قاطی نشوند.',
            'ssd_special' => "SATA SSD Repair & Data Recovery\nM.2 SATA (AHCI) Recovery\nUSB / Portable NVMe Enclosure\nEnterprise U.2 / U.3 NVMe\nForensic NVMe Imaging",
            'ssd_faq' => "با SSD ساتا یکی است؟|خیر. M.2 شکل فیزیکی است؛ این آکادمی فقط پروتکل NVMe / PCIe است. ساتا دوره جدا می‌ماند.\nاین آموزش نرم‌افزار است؟|خیر. مسیر مهندسی است: تشخیص، PCB، کنترلر، فریمور، FTL، Imaging، رمزگذاری و در سطح پیشرفته NAND / Chip-Off.\nChip-Off همیشه جواب می‌دهد؟|خیر. در بسیاری از NVMeهای مدرن داده به کنترلر و کلید رمز وابسته است؛ اولویت اغلب احیای کنترلر اصلی است.\nآموزش برند است یا کنترلر؟|بر اساس Controller + NAND + Firmware Revision. یک برند در نسل‌های مختلف کنترلر متفاوت دارد.\nگواهی می‌دهید؟|بله. برای هر کد دوره یا بسته مسیر، گواهی آکادمی HDD Land صادر می‌شود.",
            'ssd_cta' => 'ثبت‌نام آکادمی NVMe',
        ];
    }

    private static function ssdModules(): string
    {
        return <<<'TXT'
NVME-101|مبانی M.2 NVMe|M.2 NVMe Fundamentals|مقدماتی تا متوسط|ورود به آکادمی NVMe
SEC:شناخت M.2 NVMe
SYL:M.2 چیست و تفاوت Form Factor با Protocol
SYL:M.2 SATA در برابر M.2 NVMe
SYL:M-Key / B-Key
SYL:PCIe، x1 / x2 / x4 و نسل Gen3 / Gen4 / Gen5
SYL:معماری NVMe: Controller، NAND Flash، DRAM
SYL:SSD بدون DRAM و HMB
SEC:ساختار SSD
SYL:Controller، NAND، DRAM، PMIC، Crystal / Clock
SYL:ریل تغذیه، Firmware، FTL
SYL:Mapping Tables و Metadata
LAB:شناسایی Key، نسل PCIe و نوع پروتکل چند ماژول
---
NVME-201|تشخیص تخصصی M.2 NVMe|Professional NVMe Diagnostics|پیشرفته|پس از NVME-101
SEC:مراحل تشخیص
SYL:بررسی ظاهری PCB، شناسایی Controller و NAND
SYL:بررسی Power، ۳٫۳ ولت و ریل‌های تغذیه
SYL:اتصال PCIe، BIOS / UEFI، Device Manager
SYL:وضعیت NVMe Controller، Namespace، SMART / Health
SYL:آیا درایو PCIe Enumeration می‌کند یا کنترلر اصلاً بالا نمی‌آید
SEC:خرابی‌های متداول
SYL:NVMe Not Detected، BIOS Not Detected، Windows Not Detected
SYL:0 Capacity، Wrong Capacity، Read Only
SYL:Slow Performance، Random Disconnect، Overheating
SYL:Controller / Firmware / NAND Failure
LAB:تفکیک عدم Enumeration از کنترلر مرده و کیس ظرفیت صفر
---
NVME-301|تعمیر الکترونیک و PCB|NVMe Electronics & PCB Repair|تخصصی|هویه و ابزار دقیق لازم است
SEC:مدار تغذیه
SYL:ورودی ۳٫۳ ولت، ولتاژ هسته کنترلر، NAND و DRAM
SYL:PMIC، LDO، DC/DC و ترتیب روشن‌شدن (Power Sequencing)
SEC:عیب‌یابی
SYL:اتصال کوتاه، اضافه جریان، PMIC معیوب، قطعه سوخته
SYL:محافظ TVS، خازن و سلف معیوب، نبود ولتاژ، جریان بیش از حد
SEC:ابزار عملی
SYL:مولتی‌متر، منبع آزمایشگاهی، اسیلوسکوپ
SYL:دوربین حرارتی، میکروسکوپ، ایستگاه هوای گرم، میکروهویه
SYL:در NVMe تعمیر برد و بازگرداندن کنترلر اصلی اغلب حیاتی است؛ مخصوصاً وقتی رمزگذاری به همان کنترلر وابسته باشد
LAB:اندازه‌گیری ریل ۳٫۳ ولت و هسته کنترلر
LAB:تعویض TVS / PMIC روی ماژول آموزشی
---
NVME-302|کنترلر و فریمور NVMe|NVMe Controller & Firmware|تخصصی|پس از تشخیص و ترجیحاً PCB
SEC:کنترلر
SYL:شناسایی کنترلر، خانواده، نسخه فریمور
SYL:سازگاری NAND و تفاوت DRAM / DRAM-less
SEC:خانواده‌های کنترلر — کیس‌محور
SYL:Phison، Silicon Motion، Samsung
SYL:SanDisk / WD، Micron، Marvell، Maxio
SYL:Intel، SK hynix
SEC:فریمور
SYL:معماری فریمور، فساد، Panic و Recovery
SYL:حالت Safe / Diagnostic و Initialization
SYL:Backup و تحلیل فریمور
LAB:شناسایی خانواده کنترلر و نسخه فریمور روی چند ماژول
---
NVME-303|پروتکل و فرمان‌های NVMe|NVMe Protocol & Commands|تخصصی|پس از مبانی NVMe
SEC:معماری پروتکل
SYL:NVMe Controller، Admin Queue، Submission / Completion Queue
SYL:Namespace، Queue Depth، لینک PCIe و Enumeration
SEC:فرمان‌ها
SYL:Identify Controller / Namespace، Get Log Page
SYL:Read، Write، Format، فرمان‌های Firmware، SMART / Health
SEC:تشخیص با فرمان
SYL:وضعیت کنترلر و Namespace، ظرفیت، Health
SYL:Error Log، دما، Firmware Revision
SYL:هدف این است که هنرجو وضعیت Controller و PCIe را بفهمد، نه فقط یک نرم‌افزار بازیابی را اجرا کند
LAB:Identify، Log Page و خواندن SMART روی درایو زنده
---
NVME-401|لایه FTL و Mapping|NVMe FTL & Mapping|تخصصی|از مهم‌ترین دوره‌های مسیر
SEC:Flash Translation Layer
SYL:FTL، LBA و آدرس فیزیکی NAND
SYL:Logical-to-Physical Mapping، جداول و Metadata
SYL:Translation، Garbage Collection، Wear Leveling، Over Provisioning
SEC:بازیابی Mapping
SYL:فساد FTL، Mapping گم‌شده یا آسیب‌دیده
SYL:بازیابی Metadata، بازسازی Mapping و داده منطقی
SYL:خرابی FTL می‌تواند NAND را سالم بگذارد ولی دسترسی عادی را ببندد
LAB:تشخیص کیس Mapping در برابر خرابی NAND
---
NVME-402|بازیابی داده NVMe|Professional NVMe Data Recovery|تخصصی|پس از FTL و تشخیص
SEC:گردش کار Recovery
WFL:تشخیص
WFL:تحلیل کنترلر
WFL:تحلیل فریمور
WFL:تحلیل FTL
WFL:حالت Recovery
WFL:Imaging
WFL:بازیابی فایل‌سیستم
SEC:کیس‌ها
SYL:Deleted Partition، RAW، File System خراب، Lost Partition
SYL:Wrong Capacity، Read-Only
SYL:Firmware / FTL / Namespace Failure، NVMe ناپایدار
SEC:Imaging
SYL:Sector-by-Sector، LBA Imaging، Read Retry
SYL:Selective / Partial Imaging، مدیریت خطا، Verification
LAB:Imaging ناپایدار و بازیابی فایل‌سیستم از ایمیج
---
NVME-501|بازیابی NAND برای NVMe|NAND Flash Recovery for NVMe|تخصصی|پس از FTL و Imaging
SEC:معماری NAND
SYL:سلول NAND: SLC، MLC، TLC، QLC و 3D NAND
SYL:Page، Block، Plane، Die، Channel، CE
SEC:شناسایی NAND
SYL:NAND ID، سازنده، چگالی، Page / Block Size
SYL:پیکربندی Plane و Die
SEC:Raw NAND
SYL:NAND Dump، Spare Area، ECC، XOR، Scrambling
SYL:Interleaving، تبدیل Page / Block
SYL:مدیریت Bad Block
LAB:خواندن ID و تخمین سازماندهی صفحه / بلوک
---
NVME-502|Chip-Off ماژول M.2 NVMe|Advanced Chip-Off Recovery|تخصصی|ریورک BGA و سطح پیشرفته
SEC:سخت‌افزار
SYL:BGA NAND، جدا کردن، ریورک، تمیزکاری و خواندن
SEC:پردازش دامپ
SYL:Raw Dump، ECC، XOR، Scrambling، Interleave
SYL:ترتیب Page / Block، Channel Mapping، CE Mapping
SYL:بازسازی منطقی
SEC:محدودیت مهم
SYL:Chip-Off در NVMe مدرن همیشه راه‌حل مستقیم نیست
SYL:رمزگذاری و وابستگی داده به کنترلر اغلب خواندن NAND را بی‌نتیجه می‌کند
SYL:اولویت بسیاری از کیس‌ها حفظ و احیای Original Controller است
LAB:ریورک آموزشی و تصمیم Chip-Off در برابر احیای کنترلر
---
NVME-503|رمزگذاری و امنیت NVMe|NVMe SSD Encryption Recovery|تخصصی|مسیر Recovery را عوض می‌کند
SEC:موضوعات
SYL:رمزگذاری SSD و رمز مبتنی بر کنترلر، AES
SYL:Self-Encrypting Drive، TCG Opal، رمز سخت‌افزاری
SYL:کلیدها، Secure Erase، Sanitize، Format
SEC:استراتژی Recovery
SYL:تشخیص Encryption و حفظ Original Controller
SYL:جلوگیری از عملیات فریمور خطرناک
SYL:بررسی امکان Technological Recovery، تعمیر کنترلر، Imaging پس از دسترسی
LAB:تشخیص SED / Opal و ممنوعیت Format / Sanitize روی کیس مشتری
---
NVME-601|برندها و کنترلرهای M.2 NVMe|M.2 NVMe Brands & Controllers|پیشرفته|پس از فریمور و FTL — کیس‌محور
SEC:اصل آموزش
SYL:طراحی روی Controller + NAND + Firmware Revision، نه فقط اسم برند
SEC:Samsung NVMe
SYL:کنترلر، شناسایی NAND، Firmware، Health، تحلیل خرابی، کیس Recovery
SEC:WD / SanDisk NVMe
SYL:کنترلر، NAND، Firmware، تحلیل خرابی، کیس Recovery
SEC:Seagate NVMe
SYL:کنترلر، Firmware، NAND، کیس Recovery
SEC:Kingston NVMe
SYL:Phison، Silicon Motion، Maxio — شناسایی کنترلر و Recovery
SEC:Crucial / Micron
SYL:کنترلر، NAND، Firmware، Recovery
SEC:Intel / Solidigm
SYL:معماری NVMe، کنترلر، NAND، Firmware، Recovery
SEC:ADATA / XPG
SYL:شناسایی کنترلر، NAND، Firmware، Recovery
LAB:شناسایی کنترلر چند برند و انتخاب مسیر Recovery
---
NVME-602|کارگاه کیس واقعی NVMe|Advanced M.2 NVMe Case Studies|Master|پایان تئوری؛ Master Class
SEC:کیس‌های عملی
SYL:Case 01 — NVMe کاملاً Dead
SYL:Case 02 — در BIOS دیده نمی‌شود
SYL:Case 03 — در BIOS هست ولی ظرفیت صفر است
SYL:Case 04 — ظرفیت اشتباه
SYL:Case 05 — Firmware Failure
SYL:Case 06 — FTL / Mapping Failure
SYL:Case 07 — Controller Failure
SYL:Case 08 — PMIC / Power Failure
SYL:Case 09 — NAND Failure
SYL:Case 10 — Overheating
SYL:Case 11 — Read-Only NVMe
SYL:Case 12 — Encrypted NVMe
SYL:Case 13 — File System خراب
SYL:Case 14 — داده حذف‌شده
SYL:Case 15 — Chip-Off NVMe
WFL:تشخیص
WFL:تحلیل کنترلر
WFL:تحلیل فریمور
WFL:تحلیل FTL
WFL:حالت Recovery
WFL:Imaging
WFL:بازیابی فایل‌سیستم
LAB:حل کیس واقعی از تشخیص تا تحویل در آزمایشگاه
TXT;
    }

    /** @return array<string, mixed> */
    private static function serverDefaults(): array
    {
        return [
            'srv_on' => true,
            'srv_kicker' => 'Server, RAID & Enterprise Storage Data Recovery',
            'srv_title' => 'آکادمی بازیابی سرور، RAID و استوریج',
            'srv_card' => 'مسیر ۱۲دوره‌ای جدا از HDD/SSD: بازسازی آرایه، NAS، SAN، VM و استوریج سازمانی.',
            'srv_lead' => 'Professional Training in RAID Reconstruction, Server Recovery, NAS, SAN, Virtual Storage and Advanced Enterprise Data Recovery. این آکادمی از مسیر هارد و NVMe جداست؛ از مبانی Storage تا بازسازی آرایه و استوریج مدرن.',
            'srv_intro' => 'هفت سطح به‌علاوه Master: مبانی Storage و RAID، بازسازی 0/1/5/6/10 و 50/60، کنترلر و leftover، سرور و NAS، SAN سازمانی، Storage Spaces / ZFS / Btrfs، دیسک مجازی، سپس دیتابیس و رمزگذاری. اصل ثابت: روی Original ننویسید، Rebuild نزنید، از هر Member ایمیج بگیرید و ترتیب دیسک و Metadata را حفظ کنید.',
            'srv_audience' => 'برای آزمایشگاه بازیابی، واحد IT و فنی استوریج سازمانی که کیس چنددیسکی می‌گیرد.',
            'srv_level' => 'هفت سطح + Master — RAID-101 تا RAID-401',
            'srv_duration' => '۱۲ دوره آزمایشگاهی، جدا از HDD و NVMe',
            'srv_prereq' => "آشنایی با ذخیره‌سازی و مفهوم RAID\nدرک ریسک نوشتن و Rebuild روی آرایه اصلی\nترجیحاً مبانی بازیابی یا تصویرگیری دیسک",
            'srv_includes' => "کار روی آرایه و NAS آموزشی\nتصویرگیری جداگانه هر Member قبل از هر نوشتن\nچک‌لیست ترتیب دیسک، Stripe و leftover کنترلر\nگواهی هر کد دوره\nگزارش نمونه برای تحویل به سازمان",
            'srv_syllabus' => "RAID-101 مبانی Storage و RAID\nRAID-201 بازیابی RAID\nRAID-202 پیشرفته 5 / 6 / 10\nRAID-203 کنترلر و سخت‌افزار\nRAID-204 NAS\nRAID-205 SAN و Enterprise\nRAID-206 سرور\nRAID-301 ZFS / Btrfs / Storage Spaces\nRAID-302 ماشین مجازی\nRAID-303 دیتابیس روی استوریج\nRAID-304 استوریج رمزگذاری‌شده\nRAID-401 کیس واقعی",
            'srv_table' => "RAID-101|Storage & RAID Fundamentals|۳ روز|مقدماتی|تماس بگیرید\nRAID-201|RAID Data Recovery|۴ روز|متوسط|تماس بگیرید\nRAID-202|Advanced RAID 5/6/10 Recovery|۴ روز|پیشرفته|تماس بگیرید\nRAID-203|RAID Controller & Hardware Recovery|۳ روز|پیشرفته|تماس بگیرید\nRAID-204|NAS Data Recovery|۴ روز|پیشرفته|تماس بگیرید\nRAID-205|SAN & Enterprise Storage Recovery|۴ روز|تخصصی|تماس بگیرید\nRAID-206|Server Data Recovery|۴ روز|تخصصی|تماس بگیرید\nRAID-301|ZFS / Btrfs / Storage Spaces Recovery|۴ روز|پیشرفته|تماس بگیرید\nRAID-302|Virtual Machine & Virtual Disk Recovery|۴ روز|پیشرفته|تماس بگیرید\nRAID-303|Database Recovery from Storage Systems|۳ روز|تخصصی|تماس بگیرید\nRAID-304|Encrypted Storage Recovery|۳ روز|تخصصی|تماس بگیرید\nRAID-401|Advanced RAID & Storage Case Studies|۵ روز|Master|تماس بگیرید",
            'srv_path_title' => 'مسیر حرفه‌ای آکادمی RAID و استوریج',
            'srv_path' => "LEVEL 1 — FOUNDATION|مبانی|RAID-101 معماری Storage و سطوح RAID\nLEVEL 2 — RAID RECOVERY|بازسازی آرایه|RAID-201 تشخیص و بازسازی · RAID-202 پیشرفته 5/6/10 و 50/60\nLEVEL 3 — SERVER & NAS|سرور و NAS|RAID-203 کنترلر · RAID-204 NAS · RAID-206 سرور\nLEVEL 4 — ENTERPRISE STORAGE|استوریج سازمانی|RAID-205 SAN، Fabric، LUN و Storage Pool\nLEVEL 5 — MODERN STORAGE|استوریج مدرن|RAID-301 Storage Spaces · ZFS · Btrfs\nLEVEL 6 — VIRTUALIZATION|مجازی‌سازی|RAID-302 VMware · Hyper-V · VHD/VMDK\nLEVEL 7 — ADVANCED|پیشرفته|RAID-303 دیتابیس · RAID-304 رمزگذاری\nMASTER|کارگاه نهایی|RAID-401 کیس واقعی سرور و RAID",
            'srv_mods_title' => 'سیلابس دوازده دوره',
            'srv_modules' => self::serverModules(),
            'srv_tracks_title' => 'مسیر کیس‌محور',
            'srv_tracks' => "RAID کلاسیک|Identify → Clone → Analyze → Reconstruct → Verify → Extract\nRAID 5 / 6 / 10|Parity · Rotation · Missing Member · Degraded · Failed Rebuild\nکنترلر|HBA / SAS · Cache / BBU · Leftover · Foreign Config\nNAS|Synology · QNAP · WD · Netgear · Asustor · Buffalo · Drobo\nSAN / Enterprise|FC · iSCSI · LUN · Pool · Snapshot · Replication\nاستوریج مدرن|Storage Spaces · ZFS RAIDZ · Btrfs\nزنجیره ترکیبی|RAID → VM / VMFS → دیسک مجازی → فایل‌سیستم → دیتابیس",
            'srv_special_title' => 'دوره‌های تخصصی بعدی',
            'srv_special_lead' => 'این آکادمی از مسیر HDD و NVMe جداست. موضوع‌های خیلی خاص وقتی آماده باشند اضافه می‌شوند تا صفحه شلوغ نشود.',
            'srv_special' => "Ceph / Distributed Storage\nTape / VTL Recovery\nObject Storage Recovery\nForensic Server Imaging\nCloud Block Volume Recovery",
            'srv_faq' => "با دوره هارد یا NVMe یکی است؟|خیر. اینجا مسئله آرایه، کنترلر و استوریج چنددیسکی است؛ مکمل مسیر HDD و NVMe است نه جایگزین.\nاگر سازمان Rebuild زده باشد؟|بخشی از کیس‌ها بعد از Rebuild بد قابل نجات نیستند. در دوره همین تصمیم‌گیری آموزش داده می‌شود.\nروی دیسک اصلی می‌نویسیم؟|هرگز. اول Clone/Image از Memberها، بدون Rebuild روی Original، با ثبت ترتیب و Metadata.\nاز کدام دوره شروع کنم؟|RAID-101. ورود به 5/6/10، NAS و SAN بعد از تسلط تشخیص و تصویرگیری Member.\nگواهی می‌دهید؟|بله. برای هر کد دوره یا بسته مسیر، گواهی آکادمی HDD Land صادر می‌شود.",
            'srv_cta' => 'ثبت‌نام آکادمی RAID و استوریج',
        ];
    }

    private static function serverModules(): string
    {
        return <<<'TXT'
RAID-101|مبانی Storage و RAID|Storage & RAID Fundamentals|مقدماتی|ورود به آکادمی سرور و استوریج
SEC:مبانی Storage
SYL:DAS، NAS، SAN و Server / Enterprise Storage
SYL:Local در برابر Shared Storage
SYL:Direct Attached، Network Attached و Storage Area Network
SEC:RAID چیست؟
SYL:معماری RAID: Striping، Mirroring، Parity
SYL:Block، Stripe، Chunk، Disk Order، Offset
SYL:Rotation و توزیع Parity
SEC:سطوح RAID
SYL:JBOD، RAID 0، RAID 1، RAID 2 / 3 / 4
SYL:RAID 5، RAID 6، RAID 10، RAID 01
SYL:RAID 50 و RAID 60
SYL:پیکربندی‌های غیرمعمول و Vendor-specific
LAB:تشخیص Level، Stripe و ترتیب دیسک روی آرایه آموزشی
---
RAID-201|بازیابی RAID|RAID Data Recovery|متوسط|پس از RAID-101
SEC:تشخیص اولیه
SYL:شناسایی Memberها، Disk Order، اندازه دیسک و Sector
SYL:Level، Stripe / Block Size، Offset، Parity و Rotation
SYL:Missing Disk
SEC:گردش کار Recovery
WFL:Identify
WFL:Clone
WFL:Analyze
WFL:Reconstruct
WFL:Verify
WFL:Extract
SEC:مهم‌ترین اصل
SYL:جلوگیری از Write و عدم Rebuild روی Original
SYL:تهیه Clone/Image از هر Member و ثبت وضعیت هر دیسک
SYL:حفظ Disk Order و Metadata
SEC:ابزارهای تحلیلی
SYL:Hex Analysis، Content Analysis، RAID Detection
SYL:Parity Analysis و Entropy Analysis برای Level، Block Size، ترتیب و Missing Member
LAB:تصویرگیری Memberها و بازسازی مجازی بدون نوشتن روی اصل
---
RAID-202|RAID 5 / 6 / 10 پیشرفته|Advanced RAID 5/6/10 Recovery|پیشرفته|کاملاً عملی — پس از RAID-201
SEC:RAID 5
SYL:Single / Distributed Parity، Stripe و Rotation
SYL:Missing Member، یک یا چند دیسک خراب، Degraded
SYL:Rebuild Failure و Reinitialized RAID
SEC:RAID 6
SYL:Dual Parity، P و Q، دو Member خراب
SYL:Multiple Failure و تحلیل Rebuild
SEC:RAID 10
SYL:جفت Mirror، Stripe، Disk Order
SYL:Missing Members، Multiple Failure، Rebuild
SEC:RAID 50 / 60
SYL:Nested RAID، چند گروه، تشخیص Group و Member Mapping
SYL:استراتژی Recovery
LAB:بازسازی RAID 5 با یک Member مفقود
LAB:کیس RAID 6 دوخطا و RAID 10 با ترتیب نامشخص
---
RAID-203|کنترلر و سخت‌افزار RAID|RAID Controller & Hardware Recovery|پیشرفته|پس از تشخیص RAID
SEC:سخت‌افزار
SYL:RAID Controller، HBA، SAS / SATA Controller
SYL:Cache، Battery / BBU، Flash-backed Cache و ماژول Cache
SEC:خرابی کنترلر
SYL:Controller Dead، Cache / Firmware Failure
SYL:از دست رفتن Configuration، تعویض کنترلر، Foreign Configuration
SEC:Metadata کنترلر
SYL:محل Metadata، عضویت دیسک، اطلاعات آرایه
SEC:کیس پیشرفته
SYL:کنترلر مرده، Config گم‌شده، Foreign RAID
SYL:کنترلر اشتباه و چند کنترلر خراب
LAB:خواندن leftover و تصمیم Import / Reconstruct مجازی
---
RAID-204|بازیابی NAS|NAS Data Recovery|پیشرفته|پس از بازسازی RAID
SEC:معماری NAS
SYL:NAS مبتنی بر RAID، Embedded Linux، Firmware و Metadata
SYL:چند آرایه روی یک دستگاه
SEC:فایل‌سیستم
SYL:EXT3 / EXT4، XFS، Btrfs، ZFS
SEC:برندها
SYL:Synology، QNAP، Western Digital، Netgear
SYL:Asustor، Buffalo، Drobo
SEC:خرابی‌ها
SYL:NAS Not Booting، RAID Degraded، چند دیسک خراب
SYL:Failed Rebuild، Metadata / File System خراب
SYL:خرابی کنترلر یا Firmware
SEC:گردش کار
WFL:NAS
WFL:Member Disks
WFL:Imaging
WFL:بازسازی RAID
WFL:فایل‌سیستم
WFL:استخراج داده
LAB:خارج کردن دیسک‌های NAS و بازسازی بدون روشن کردن دستگاه اصلی
---
RAID-205|SAN و استوریج سازمانی|SAN & Enterprise Storage Recovery|تخصصی|پس از RAID و کنترلر
SEC:SAN
SYL:معماری SAN، Storage Fabric
SYL:Fibre Channel، iSCSI، SAS Storage، Shared Storage
SEC:استوریج سازمانی
SYL:Storage Controller، Storage Pool، RAID Groups
SYL:LUN، Volume، Thin Provisioning
SYL:Snapshots، Replication، Storage Tiering
SEC:بازیابی
SYL:کنترلر استوریج خراب، LUN گم‌شده، Volume خراب
SYL:RAID Group خراب، چند دیسک، فساد Metadata، شکست Pool
LAB:تشخیص LUN / Pool و تصمیم تصویرگیری Member در برابر Volume
---
RAID-206|بازیابی سرور|Server Data Recovery|تخصصی|پس از کنترلر RAID
SEC:سخت‌افزار سرور
SYL:Motherboard، RAID Controller، HBA
SYL:SAS، SATA، HDD، SSD، NVMe
SYL:Backplane و Expander
SEC:خرابی سرور
SYL:Server Not Booting، RAID Not Detected
SYL:چند دیسک خراب، کنترلر / Backplane / تغذیه
SYL:Firmware و Config آرایه خراب
SEC:بازیابی
SYL:تشخیص سرور، استخراج دیسک، Imaging
SYL:بازسازی RAID، فایل‌سیستم، VM و دیتابیس
LAB:خارج کردن دیسک از سینی سرور و تصویرگیری قبل از روشن کردن کنترلر
---
RAID-301|ZFS / Btrfs / Storage Spaces|ZFS / Btrfs / Storage Spaces Recovery|پیشرفته|استوریج مدرن پس از RAID کلاسیک
SEC:Windows Storage Spaces
SYL:Storage Pool، Virtual Disk، Resiliency
SYL:Parity، Mirror، Metadata و Recovery
SEC:ZFS
SYL:Pool، VDEV، RAIDZ / RAIDZ2 / RAIDZ3
SYL:Dataset، Zvol، Metadata
SEC:Btrfs
SYL:معماری Btrfs، RAID، Chunk، Metadata
SYL:Data Profiles و Recovery
LAB:تشخیص Pool / VDEV و بازیابی بدون Import مخرب
---
RAID-302|ماشین مجازی و دیسک مجازی|Virtual Machine & Virtual Disk Recovery|پیشرفته|پس از بازسازی Storage
SEC:دیسک مجازی
SYL:VHD، VHDX، VMDK، QCOW2، RAW، IMG
SEC:مجازی‌سازی
SYL:VMware، Hyper-V، Proxmox، KVM
SEC:بازیابی
SYL:دیسک مجازی خراب، VM حذف‌شده، Snapshot شکسته
SYL:VMDK مفقود، زنجیره Snapshot
SYL:کیس ترکیبی RAID + VM و Storage + VM
SEC:زنجیره‌های ترکیبی
SYL:RAID → VMFS → VMDK → NTFS → Files
SYL:RAID → Storage Pool → VM → Virtual Disk → Database
LAB:باز کردن VMDK / VHDX از روی آرایه بازسازی‌شده
---
RAID-303|بازیابی دیتابیس از استوریج|Database Recovery from Storage Systems|تخصصی|پس از فایل‌سیستم یا VM
SEC:ذخیره دیتابیس
SYL:SQL Server، MySQL، PostgreSQL، Oracle
SYL:فایل دیتابیس، Log و Transaction Log
SEC:سناریوها
SYL:دیتابیس حذف یا خراب، شکست RAID / Storage
SYL:فایل مفقود، VM خراب، Snapshot شکست‌خورده
SEC:گردش کار
WFL:بازیابی Storage
WFL:دیسک مجازی
WFL:فایل‌سیستم
WFL:فایل دیتابیس
WFL:بازیابی دیتابیس
LAB:استخراج MDF/LDF یا IBData از ایمیج آرایه
---
RAID-304|استوریج رمزگذاری‌شده|Encrypted Storage Recovery|تخصصی|مسیر Recovery را عوض می‌کند
SEC:رمزگذاری
SYL:BitLocker، FileVault
SYL:Self-Encrypting و رمز سخت‌افزاری / نرم‌افزاری
SYL:Encrypted RAID و Encrypted NAS
SEC:بازیابی
SYL:تشخیص Encryption و حفظ دیسک‌های اصلی
SYL:مدیریت کلید و حفظ Metadata
SYL:تحلیل Volume رمز و Recovery بعد از بازسازی RAID
LAB:تشخیص BitLocker / SED قبل از هر Reconstruct مخرب
---
RAID-401|کارگاه کیس واقعی RAID و استوریج|Advanced RAID & Storage Case Studies|Master|پایان تئوری؛ Master Course
SEC:کیس‌های عملی
SYL:Case 01 — RAID 5 با یک HDD خراب
SYL:Case 02 — RAID 5 با دو HDD خراب
SYL:Case 03 — RAID 6 با چند Member مشکل‌دار
SYL:Case 04 — RAID 10 با Disk Order نامشخص
SYL:Case 05 — RAID Rebuild ناموفق
SYL:Case 06 — RAID Reinitialized
SYL:Case 07 — RAID Controller Failure
SYL:Case 08 — Foreign Configuration
SYL:Case 09 — NAS با چند Disk Failure
SYL:Case 10 — NAS File System Corruption
SYL:Case 11 — Server + RAID + VM
SYL:Case 12 — RAID + VMware
SYL:Case 13 — RAID + Database
SYL:Case 14 — Encrypted RAID
SYL:Case 15 — Storage Spaces
SYL:Case 16 — ZFS RAIDZ
SYL:Case 17 — Btrfs RAID
SYL:Case 18 — SAN / Enterprise Storage
WFL:Identify
WFL:Clone
WFL:Analyze
WFL:Reconstruct
WFL:Verify
WFL:Extract
LAB:حل کیس واقعی از پذیرش تا گزارش سازمانی
TXT;
    }
}
