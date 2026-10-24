<?php

namespace Plugins\ThemeBuilder\src;

use App\Support\StudioAccountingCopy;
use App\Support\StudioDesignCopy;
use App\Support\StudioHomeConfig;
use App\Support\StudioProductMenus;

/**
 * Every public studio menu / option becomes a page-builder document.
 */
class StudioPageCatalog
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $pages = [];
        $pages[] = self::home();
        $pages[] = self::designHub();
        foreach (self::designServices() as $page) {
            $pages[] = $page;
        }
        $pages[] = self::ai();
        $pages[] = self::about();
        $pages[] = self::contact();
        $pages[] = self::projects();
        $pages[] = self::receipt();
        $pages[] = self::accountingHub();
        $pages[] = self::computerHub();
        foreach (self::industries() as $page) {
            $pages[] = $page;
        }
        foreach (self::accountingItems() as $page) {
            $pages[] = $page;
        }
        $pages[] = self::repairHub();
        $pages[] = self::repairStaff();
        $pages[] = self::repairCustomer();
        foreach (self::repairItems() as $page) {
            $pages[] = $page;
        }
        $pages[] = self::sites();

        return $pages;
    }

    /** @return list<string> */
    public static function paths(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $page): string => (string) $page['path'],
            self::all()
        )));
    }

    /** @return array<string, mixed> */
    protected static function home(): array
    {
        $c = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::get() : [];
        $services = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::services($c) : [];
        $stats = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::stats($c) : [];
        $works = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::works($c) : [];

        return self::spec('/', 'home', 'خانه استودیو', 'خانه', 'صفحه اول', [
            'seo_title' => trim((string) ($c['hero_title'] ?? 'طراحی سایت، نرم‌افزار و هوش مصنوعی')).' | فراز نت آمل',
            'seo_description' => (string) ($c['hero_text'] ?? ''),
            'blocks' => [
                self::hero(
                    'شرکت فراز نت آمل',
                    (string) ($c['hero_title'] ?? 'طراحی سایت، نرم‌افزار و هوش مصنوعی'),
                    (string) ($c['hero_text'] ?? ''),
                    (string) ($c['scene_image'] ?? 'images/studio/studio-hero-scene.jpg'),
                    (string) ($c['cta1_label'] ?? 'درخواست مشاوره رایگان'),
                    (string) ($c['cta1_url'] ?? '/contact'),
                    (string) ($c['cta2_label'] ?? 'مشاهده نمونه کارها'),
                    (string) ($c['cta2_url'] ?? '/projects')
                ),
                self::cards('خدمات', $services),
                self::statsBlock('آمار', $stats),
                self::worksBlock((string) ($c['works_title'] ?? 'نمونه کارها'), $works),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function designHub(): array
    {
        $hero = StudioDesignCopy::hero();
        $cards = [];
        foreach (StudioDesignCopy::services() as $row) {
            $cards[] = [
                'title' => $row['title'],
                'text' => $row['short'],
                'url' => '/design/'.$row['slug'],
            ];
        }
        $steps = [];
        foreach (StudioDesignCopy::steps() as $row) {
            $steps[] = ['title' => $row['title'], 'text' => $row['text']];
        }

        return self::spec('/design', 'design', 'طراحی سایت', 'طراحی سایت', 'طراحی سایت', [
            'seo_title' => StudioDesignCopy::seoTitle('design'),
            'seo_description' => StudioDesignCopy::seoDescription('design'),
            'blocks' => [
                self::hero($hero['kicker'], $hero['title'], $hero['lead'], '', 'درخواست مشاوره رایگان', '/contact', 'مشاهده نمونه‌کارها', '/projects'),
                self::rich('درباره تیم طراحی', StudioDesignCopy::aboutBlurb()),
                self::cards('خدمات طراحی', $cards),
                self::list('چرا فراز نت آمل؟', StudioDesignCopy::reasons()),
                self::steps('مراحل همکاری', $steps),
                self::cta('آماده‌اید سایت کسب‌وکارتان را به سطح بعدی ببرید؟', 'مشاوره رایگان طراحی سایت، فروشگاه یا سامانه اختصاصی.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return list<array<string, mixed>> */
    protected static function designServices(): array
    {
        $out = [];
        foreach (StudioDesignCopy::services() as $row) {
            $slug = (string) $row['slug'];
            $blocks = [
                self::hero('خدمات فراز نت آمل', $row['title'], $row['text'], self::designImage($slug), 'درخواست مشاوره رایگان', '/contact', 'همه خدمات طراحی', '/design'),
                self::rich($row['title'], $row['text']."\n\n".$row['short']),
                self::cta('برای این خدمت مشاوره می‌خواهید؟', 'کارشناس فراز نت آمل مسیر، زمان و هزینه را شفاف می‌گوید.', 'تماس با ما', '/contact'),
            ];
            if ($slug === 'corporate') {
                $blocks = self::corporateBlocks($row);
            }
            $out[] = self::spec('/design/'.$slug, 'design-'.$slug, $row['title'], $row['title'], 'طراحی سایت', [
                'seo_title' => $row['seo_title'],
                'seo_description' => $row['seo_description'],
                'blocks' => $blocks,
            ]);
        }

        return $out;
    }

    /**
     * @param  array{title:string,short:string,text:string,seo_title:string,seo_description:string}  $row
     * @return list<array<string, mixed>>
     */
    protected static function corporateBlocks(array $row): array
    {
        return [
            self::hero(
                'خدمات فراز نت آمل',
                $row['title'],
                $row['text'],
                'images/studio/studio-work-corp.jpg',
                'درخواست مشاوره رایگان',
                '/contact',
                'همه خدمات طراحی',
                '/design'
            ),
            self::rich(
                'سایت شرکتی ویترین اعتماد است',
                "سایت شرکتی باید در چند ثانیه بگوید شما کیستید، چه می‌کنید و چرا باید تماس گرفته شود. ظاهر مدرن می‌ماند؛ متن، عکس، خدمات و نمونه کار از همین صفحه‌ساز به‌روز می‌شود — بدون وابستگی به طراح برای هر تغییر کوچک.\n\nما صفحه خدمات، درباره، نمونه کار، تماس و فرم استعلام را روی لاراول می‌نویسیم تا برند شما مال خودش باشد، نه یک قالب آماده."
            ),
            self::cards('چه صفحه‌هایی می‌سازیم', [
                ['title' => 'خدمات', 'text' => 'هر خدمت یک صفحه جدا با متن و عکس خودش.', 'url' => '/design'],
                ['title' => 'درباره ما', 'text' => 'داستان برند، تیم و اعتمادسازی.', 'url' => '/about'],
                ['title' => 'نمونه کار', 'text' => 'پروژه‌های واقعی، نه کارت تزئینی.', 'url' => '/projects'],
                ['title' => 'تماس و استعلام', 'text' => 'فرم و شماره؛ مسیر تبدیل بازدیدکننده به مشتری.', 'url' => '/contact'],
            ]),
            self::list('بعد از تحویل دست شماست', [
                'پنل مدیریت برای متن و عکس همان صفحه',
                'نسخه موبایل جدا طراحی می‌شود',
                'سئو از روز اول داخل ساختار است',
                'پشتیبانی واقعی بعد از راه‌اندازی',
            ]),
            self::cta('آماده شروع سایت شرکتی هستید؟', 'مشاوره رایگان با فراز نت آمل در آمل.', 'تماس با ما', '/contact'),
        ];
    }

    /** @return array<string, mixed> */
    protected static function ai(): array
    {
        return self::spec('/ai', 'ai', 'هوش مصنوعی', 'هوش مصنوعی', 'هوش مصنوعی', [
            'seo_title' => 'هوش مصنوعی برای سایت و نرم‌افزار | فراز نت آمل',
            'seo_description' => 'هوش مصنوعی لایه سوم کار فراز نت آمل: داخل سایت، سامانه و حسابداری — آفلاین و بدون ارسال داده به اینترنت.',
            'blocks' => [
                self::hero('هوش مصنوعی شرکت فراز نت آمل', 'هوش مصنوعی برای سایت و نرم‌افزار', 'ما شرکت طراحی سایت و ساخت نرم‌افزار هستیم. هوش مصنوعی لایه سوم همان کار است: داخل سایت، داخل سامانه و داخل حسابداری — آفلاین، روی سیستم شما، بدون ارسال داده به اینترنت.', '', 'درخواست مشاوره رایگان', '/contact', 'طراحی سایت', '/design'),
                self::cards('سه کار مشخص', [
                    ['title' => 'روی سایت', 'text' => 'جستجو، پاسخ به مشتری و راهنمای صفحات داخل همان وب‌سایت — نه چت عمومی اینترنتی.'],
                    ['title' => 'داخل نرم‌افزار', 'text' => 'گفتگو با سامانه، توضیح گزارش و پیشنهاد کار. ثبت قطعی فقط با تأیید شماست.'],
                    ['title' => 'روی حسابداری', 'text' => 'خواندن فاکتور، پیش‌نویس سند و کنترل حسابرس هوشمند؛ عدد از دفتر می‌آید نه از حدس مدل.'],
                ]),
                self::cta('AI را روی محصول خودتان می‌خواهید؟', 'مشاوره رایگان برای سایت، سامانه و حسابداری.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function about(): array
    {
        $about = StudioDesignCopy::about();
        $html = implode("\n\n", $about['body']);

        return self::spec('/about', 'about', $about['title'], 'درباره ما', 'طراحی سایت', [
            'seo_title' => StudioDesignCopy::seoTitle('about'),
            'seo_description' => StudioDesignCopy::seoDescription('about'),
            'blocks' => [
                self::hero('درباره ما', $about['title'], $about['lead'], '', 'تماس با ما', '/contact', 'طراحی سایت', '/design'),
                self::rich($about['title'], $html),
                self::list('چرا فراز نت آمل؟', StudioDesignCopy::reasons()),
                self::cta('همکاری را شروع کنیم؟', $about['lead'], 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function contact(): array
    {
        $copy = StudioDesignCopy::contact();

        return self::spec('/contact', 'contact', 'تماس و مشاوره', 'تماس', 'طراحی سایت', [
            'seo_title' => StudioDesignCopy::seoTitle('contact'),
            'seo_description' => StudioDesignCopy::seoDescription('contact'),
            'blocks' => [
                self::hero('تماس با فراز نت آمل', $copy['title'], $copy['lead'], '', $copy['cta'], '/contact', 'درباره ما', '/about'),
                self::rich('مشاوره رایگان', $copy['lead']."\n\nبرای طراحی سایت شرکتی، فروشگاه اینترنتی یا سامانه اختصاصی لاراول، با کارشناسان فراز نت آمل در آمل تماس بگیرید."),
                self::cta($copy['title'], $copy['lead'], $copy['cta'], '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function projects(): array
    {
        return self::spec('/projects', 'projects', 'نمونه کارها', 'نمونه کارها', 'طراحی سایت', [
            'seo_title' => 'پروژه‌های سایت | فراز نت آمل',
            'seo_description' => 'نمونه پروژه‌های استقرارشده فراز نت آمل؛ قالب تزئینی نیستند.',
            'blocks' => [
                self::hero('پروژه‌های سایت', 'نمونه از همین سیستم', 'پروژه‌هایی که استقرار شده‌اند؛ قالب تزئینی نیستند.', '', 'شروع پروژه مشابه', '/contact', 'طراحی سایت', '/design'),
                self::cards('نمونه‌ها', [
                    ['title' => 'مدیریت تعمیرگاه', 'text' => 'پذیرش، قبض، ارجاع و تحویل.', 'url' => '/repair-shop'],
                    ['title' => 'دفتر متصل به فروش', 'text' => 'یک فروش = یک سند.', 'url' => '/computer-accounting'],
                    ['title' => 'وب‌اپ و کارت ویزیت', 'text' => 'نصب روی گوشی و عضویت پیامکی.', 'url' => '/app'],
                ]),
                self::cta('پروژه مشابه می‌خواهید؟', 'مسیر، زمان و هزینه را شفاف می‌گوییم.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function receipt(): array
    {
        return self::spec('/receipt-software', 'receipt-software', 'نرم‌افزار قبض تحت وب', 'نرم‌افزار قبض', 'حسابداری وب', [
            'seo_title' => 'نرم‌افزار قبض تحت وب | فراز نت آمل',
            'seo_description' => 'پذیرش، پیگیری و تحویل روی قبض؛ سامانه زنده جدا از سایت است.',
            'blocks' => [
                self::hero('نرم‌افزار قبض تحت وب', 'پذیرش، پیگیری و تحویل روی قبض', 'سامانه زنده قبض جدا است. داده و دیتابیس آن از این سایت جابه‌جا یا پاک نمی‌شود.', '', 'درخواست استقرار', '/contact', 'حسابداری وب', '/accounting'),
                self::list('چه کارهایی روی قبض هست', [
                    'ثبت قبض تکی و گروهی',
                    'جستجو با سریال، موبایل یا شماره قبض',
                    'ارجاع به تعمیرکار و تحویل',
                    'پیگیری مشتری روی سامانه جدا',
                ]),
                self::cta('استقرار قبض برای تعمیرگاه', 'دیتابیس قبض قاطی این سایت نمی‌شود.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function accountingHub(): array
    {
        $cards = [];
        if (class_exists(StudioProductMenus::class)) {
            foreach (StudioProductMenus::webAccountingNav() as $col) {
                $cards[] = [
                    'title' => (string) ($col['label'] ?? ''),
                    'text' => 'منوی حسابداری وب و تعمیرگاه',
                    'url' => (string) ($col['url'] ?? '/accounting'),
                ];
            }
        }

        return self::spec('/accounting', 'accounting', 'حسابداری وب', 'حسابداری وب', 'حسابداری وب', [
            'seo_title' => 'حسابداری وب و تعمیرگاه | فراز نت آمل',
            'seo_description' => 'حسابداری وب تعمیرکاران به‌همراه منوی کامل تعمیرگاه: پذیرش، قبض، ارجاع و صندوق.',
            'blocks' => [
                self::hero('حسابداری وب', 'حسابداری وب و تعمیرگاه', 'حسابداری وب تعمیرکاران به‌همراه منوی کامل تعمیرگاه: پذیرش، قبض، ارجاع و صندوق. بقیه صنوف حسابداری کامپیوتری با SQL Server 2022 هستند.', '', 'درخواست دمو', '/contact', 'تعمیرگاه', '/repair-shop'),
                self::cards('منوی حسابداری وب', $cards),
                self::cta('دمو حسابداری وب', 'برای تعمیرکاران و قبض تعمیرگاه.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function computerHub(): array
    {
        $cards = [];
        if (class_exists(StudioAccountingCopy::class)) {
            foreach (StudioAccountingCopy::industries() as $row) {
                $cards[] = [
                    'title' => (string) $row['title'],
                    'text' => (string) ($row['short'] ?? ''),
                    'url' => '/computer-accounting/'.$row['slug'],
                ];
            }
        }

        return self::spec('/computer-accounting', 'computer-accounting', 'حسابداری کامپیوتر', 'حسابداری کامپیوتر', 'حسابداری کامپیوتر', [
            'seo_title' => 'حسابداری کامپیوتر با SQL Server 2022 | فراز نت آمل',
            'seo_description' => 'حسابداری کامپیوتری صنوف روی SQL Server 2022 با هوش مصنوعی آفلاین داخل نرم‌افزار.',
            'blocks' => [
                self::hero('حسابداری کامپیوتر', 'حسابداری کامپیوتر با SQL Server 2022', 'حسابداری کامپیوتری صنوف روی SQL Server 2022 با هوش مصنوعی آفلاین داخل نرم‌افزار: شرکتی، فروشگاهی، سوپرمارکت، پخش، رستوران، تولیدی و پیمانکاری.', '', 'درخواست مشاوره و دمو', '/contact', 'حسابداری وب', '/accounting'),
                self::cards('صنوف', $cards),
                self::rich('نکته نسخه', class_exists(StudioAccountingCopy::class) ? StudioAccountingCopy::sqlNote() : ''),
                self::cta('کدام صنف شماست؟', 'هر صنف صفحه خودش را در صفحه‌ساز دارد.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return list<array<string, mixed>> */
    protected static function industries(): array
    {
        if (! class_exists(StudioAccountingCopy::class)) {
            return [];
        }
        $out = [];
        foreach (StudioAccountingCopy::industries() as $row) {
            $body = implode("\n\n", $row['body'] ?? []);
            $highlights = $row['highlights'] ?? [];
            $out[] = self::spec('/computer-accounting/'.$row['slug'], 'acc-'.$row['slug'], $row['title'], $row['title'], 'حسابداری کامپیوتر', [
                'seo_title' => (string) ($row['seo_title'] ?? $row['title']),
                'seo_description' => (string) ($row['seo_description'] ?? ($row['short'] ?? '')),
                'blocks' => [
                    self::hero((string) ($row['kicker'] ?? 'حسابداری کامپیوتر'), $row['title'], (string) ($row['lead'] ?? $row['short'] ?? ''), '', 'درخواست مشاوره و دمو', '/contact', 'همه حسابداری‌های کامپیوتر', '/computer-accounting'),
                    self::list('نقاط قوت این نسخه', $highlights),
                    self::rich($row['title'].' چیست؟', $body),
                    self::cta('دمو این نسخه', 'روی SQL Server 2022 و گردش کار همان کسب‌وکار شما.', 'تماس با ما', '/contact'),
                ],
            ]);
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    protected static function accountingItems(): array
    {
        if (! class_exists(StudioProductMenus::class)) {
            return [];
        }
        $out = [];
        foreach (StudioProductMenus::accountingGroups() as $group) {
            foreach ($group['items'] as $item) {
                $out[] = self::spec('/computer-accounting/m/'.$item['slug'], 'acc-m-'.$item['slug'], $item['title'], $item['title'], 'حسابداری کامپیوتر', [
                    'seo_title' => $item['title'].' | حسابداری کامپیوتر',
                    'seo_description' => (string) ($item['short'] ?? $item['title']),
                    'blocks' => [
                        self::hero($group['title'], $item['title'], (string) ($item['short'] ?? ''), '', 'درخواست دمو و استقرار', '/contact', 'حسابداری کامپیوتر', '/computer-accounting'),
                        self::rich($item['title'], (string) ($item['short'] ?? '')."\n\nاین صفحه معرفی همان منوی نرم‌افزار است. متن و عکس را از صفحه‌ساز همین صفحه عوض کنید."),
                        self::cta('دمو این ماژول', 'استقرار روی دفتر شما.', 'تماس با ما', '/contact'),
                    ],
                ]);
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected static function repairHub(): array
    {
        return self::spec('/repair-shop', 'repair-shop', 'سایت مدیریت تعمیرگاه', 'تعمیرگاه', 'تعمیرگاه', [
            'seo_title' => 'سایت مدیریت تعمیرگاه | فراز نت آمل',
            'seo_description' => 'پذیرش، ارجاع تعمیرکار، تأیید هزینه، انبار و حسابداری تعمیرگاه.',
            'blocks' => [
                self::hero('نرم‌افزار تعمیرگاه', 'سایت مدیریت تعمیرگاه', 'پذیرش، قبض، ارجاع، تأیید هزینه، انبار و حسابداری تعمیرگاه — کارتابل نیرو و مشتری جدا.', '', 'منوی کارکنان', '/repair-shop/staff-menu', 'کارتابل مشتری', '/repair-shop/customer-menu'),
                self::cards('دو منوی اصلی', [
                    ['title' => 'منوی کارکنان', 'text' => 'پذیرش تا تنظیمات و گزارش‌ها.', 'url' => '/repair-shop/staff-menu'],
                    ['title' => 'کارتابل مشتری', 'text' => 'پیگیری قبض، تأیید هزینه و پرداخت.', 'url' => '/repair-shop/customer-menu'],
                ]),
                self::cta('استقرار تعمیرگاه', 'داده قبض روی سامانه جدا می‌ماند.', 'تماس با ما', '/contact'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function repairStaff(): array
    {
        $cards = [];
        if (class_exists(StudioProductMenus::class)) {
            foreach (StudioProductMenus::repairStaffTree() as $section) {
                $cards[] = [
                    'title' => (string) $section['title'],
                    'text' => (string) ($section['short'] ?? 'منوی کارکنان تعمیرگاه'),
                    'url' => '/repair-shop/m/'.$section['slug'],
                ];
            }
        }

        return self::spec('/repair-shop/staff-menu', 'repair-staff', 'منوی کارکنان تعمیرگاه', 'منوی کارکنان', 'تعمیرگاه', [
            'seo_title' => 'منوی کارکنان تعمیرگاه | فراز نت آمل',
            'seo_description' => 'پذیرش، ارجاع، انبار، حسابداری و تنظیمات کارکنان تعمیرگاه.',
            'blocks' => [
                self::hero('تعمیرگاه', 'منوی کارکنان تعمیرگاه', 'همه بخش‌های کارتابل نیرو؛ هر آیتم صفحه خودش را در صفحه‌ساز دارد.', '', 'تعمیرگاه', '/repair-shop', 'کارتابل مشتری', '/repair-shop/customer-menu'),
                self::cards('بخش‌ها', $cards),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    protected static function repairCustomer(): array
    {
        $cards = [];
        if (class_exists(StudioProductMenus::class)) {
            foreach (StudioProductMenus::repairCustomerTree() as $row) {
                $cards[] = [
                    'title' => (string) $row['title'],
                    'text' => (string) ($row['short'] ?? ''),
                    'url' => '/repair-shop/m/'.$row['slug'],
                ];
            }
        }

        return self::spec('/repair-shop/customer-menu', 'repair-customer', 'منوی کارتابل مشتری', 'کارتابل مشتری', 'تعمیرگاه', [
            'seo_title' => 'منوی کارتابل مشتری | فراز نت آمل',
            'seo_description' => 'پیگیری قبض، تأیید هزینه، پرداخت و گارانتی از سمت مشتری.',
            'blocks' => [
                self::hero('تعمیرگاه', 'منوی کارتابل مشتری', 'مشتری وضعیت تعمیر را از پرتال می‌بیند؛ هر آیتم صفحه خودش را دارد.', '', 'تعمیرگاه', '/repair-shop', 'منوی کارکنان', '/repair-shop/staff-menu'),
                self::cards('آیتم‌های مشتری', $cards),
            ],
        ]);
    }

    /** @return list<array<string, mixed>> */
    protected static function repairItems(): array
    {
        if (! class_exists(StudioProductMenus::class)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach (['staff' => StudioProductMenus::repairStaffTree(), 'customer' => StudioProductMenus::repairCustomerTree()] as $menu => $tree) {
            $hub = $menu === 'customer' ? '/repair-shop/customer-menu' : '/repair-shop/staff-menu';
            $hubLabel = $menu === 'customer' ? 'منوی کارتابل مشتری' : 'منوی کارکنان';
            foreach ($tree as $section) {
                $rows = [];
                if (! empty($section['slug'])) {
                    $rows[] = [
                        'slug' => $section['slug'],
                        'title' => $section['title'],
                        'short' => (string) ($section['short'] ?? $section['title']),
                        'parent' => null,
                    ];
                }
                foreach ($section['children'] ?? [] as $child) {
                    $rows[] = [
                        'slug' => $child['slug'],
                        'title' => $child['title'],
                        'short' => (string) ($child['short'] ?? $child['title']),
                        'parent' => $section['title'],
                    ];
                }
                foreach ($rows as $item) {
                    $slug = (string) $item['slug'];
                    if ($slug === '' || isset($seen[$slug])) {
                        continue;
                    }
                    $seen[$slug] = true;
                    $kicker = $item['parent'] ?: $hubLabel;
                    $out[] = self::spec('/repair-shop/m/'.$slug, 'repair-m-'.$slug, $item['title'], $item['title'], 'تعمیرگاه', [
                        'seo_title' => $item['title'].' | تعمیرگاه',
                        'seo_description' => $item['short'],
                        'blocks' => [
                            self::hero($kicker, $item['title'], $item['short'], '', 'درخواست دمو و استقرار', '/contact', $hubLabel, $hub),
                            self::rich($item['title'], $item['short']."\n\nاین صفحه معرفی همان منوی نرم‌افزار است. متن و عکس را از صفحه‌ساز همین صفحه ویرایش کنید. داده قبض روی سامانه جدا می‌ماند."),
                            self::cta('استقرار این بخش', 'قبض و دیتابیس تعمیرگاه قاطی این سایت نمی‌شود.', 'تماس با ما', '/contact'),
                        ],
                    ]);
                }
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected static function sites(): array
    {
        $cards = class_exists(StudioProductMenus::class) ? StudioProductMenus::catalog() : [];

        return self::spec('/sites', 'sites', 'نرم‌افزار حسابداری و تعمیرگاه', 'فروش سایت', 'فروش سایت', [
            'seo_title' => 'نرم‌افزار حسابداری و تعمیرگاه | فراز نت آمل',
            'seo_description' => 'حسابداری وب، حسابداری کامپیوتر و مدیریت تعمیرگاه.',
            'blocks' => [
                self::hero('نرم‌افزار', 'نرم‌افزار حسابداری و تعمیرگاه', 'سه محصول اصلی فراز نت آمل برای دفتر و تعمیرگاه.', '', 'حسابداری وب', '/accounting', 'تعمیرگاه', '/repair-shop'),
                self::cards('محصولات', $cards),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected static function spec(string $path, string $slug, string $title, string $menu, string $group, array $extra): array
    {
        return array_merge([
            'path' => $path,
            'slug' => $slug,
            'title' => $title,
            'menu_label' => $menu,
            'menu_group' => $group,
            'status' => 'publish',
            'seo_title' => $title.' | فراز نت آمل',
            'seo_description' => $title,
            'blocks' => [],
        ], $extra);
    }

    /** @return array<string, mixed> */
    protected static function hero(string $kicker, string $title, string $text, string $image, string $cta1, string $cta1Url, string $cta2 = '', string $cta2Url = ''): array
    {
        return [
            'type' => 'hero',
            'kicker' => $kicker,
            'title' => $title,
            'text' => $text,
            'image' => $image,
            'cta1_label' => $cta1,
            'cta1_url' => $cta1Url,
            'cta2_label' => $cta2,
            'cta2_url' => $cta2Url,
        ];
    }

    /** @return array<string, mixed> */
    protected static function rich(string $title, string $text): array
    {
        $html = '';
        foreach (preg_split('/\n{2,}/', trim($text)) ?: [] as $para) {
            $para = trim($para);
            if ($para !== '') {
                $html .= '<p>'.htmlspecialchars($para, ENT_QUOTES, 'UTF-8').'</p>';
            }
        }

        return ['type' => 'richtext', 'title' => $title, 'html' => $html];
    }

    /**
     * @param  list<array<string, mixed>|string>  $items
     * @return array<string, mixed>
     */
    protected static function cards(string $title, array $items): array
    {
        $norm = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $norm[] = ['title' => $item, 'text' => '', 'url' => '', 'image' => ''];
                continue;
            }
            $norm[] = [
                'title' => (string) ($item['title'] ?? $item['label'] ?? ''),
                'text' => (string) ($item['text'] ?? $item['short'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'image' => (string) ($item['image'] ?? ''),
            ];
        }

        return ['type' => 'cards', 'title' => $title, 'items' => $norm];
    }

    /**
     * @param  list<string>  $items
     * @return array<string, mixed>
     */
    protected static function list(string $title, array $items): array
    {
        return ['type' => 'list', 'title' => $title, 'items' => array_values(array_filter(array_map('strval', $items)))];
    }

    /**
     * @param  list<array{title?:string,text?:string}>  $items
     * @return array<string, mixed>
     */
    protected static function steps(string $title, array $items): array
    {
        return ['type' => 'steps', 'title' => $title, 'items' => $items];
    }

    /**
     * @param  list<array{value?:string,label?:string}>  $items
     * @return array<string, mixed>
     */
    protected static function statsBlock(string $title, array $items): array
    {
        return ['type' => 'stats', 'title' => $title, 'items' => $items];
    }

    /**
     * @param  list<array{title?:string,cat?:string,url?:string,image?:string}>  $items
     * @return array<string, mixed>
     */
    protected static function worksBlock(string $title, array $items): array
    {
        return ['type' => 'works', 'title' => $title, 'items' => $items];
    }

    /** @return array<string, mixed> */
    protected static function cta(string $title, string $text, string $label, string $url): array
    {
        return ['type' => 'cta', 'title' => $title, 'text' => $text, 'cta1_label' => $label, 'cta1_url' => $url];
    }

    protected static function designImage(string $slug): string
    {
        return match ($slug) {
            'corporate' => 'images/studio/studio-work-corp.jpg',
            'shop' => 'images/studio/studio-work-shop.jpg',
            'seo' => 'images/studio/studio-work-seo.jpg',
            default => 'images/studio/studio-work-dash.jpg',
        };
    }
}
