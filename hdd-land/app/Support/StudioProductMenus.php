<?php

namespace App\Support;

/**
 * Public product menus for the studio site: web accounting + repair shop.
 * These used to live in MegaMenu website-sales and Accounting adminMenu.
 */
class StudioProductMenus
{
    /** @return list<array{label:string,url:string,children?:list<array{label:string,url:string,children?:list<array{label:string,url:string}>}>}> */
    public static function headerItems(): array
    {
        return [
            [
                'label' => 'حسابداری وب',
                'url' => '/accounting',
                'children' => array_map(static function (array $group): array {
                    return [
                        'label' => $group['title'],
                        'url' => '/accounting#'.$group['slug'],
                        'children' => array_map(static fn (array $item): array => [
                            'label' => $item['title'],
                            'url' => '/accounting/m/'.$item['slug'],
                        ], $group['items']),
                    ];
                }, self::accountingGroups()),
            ],
            [
                'label' => 'تعمیرگاه',
                'url' => '/repair-shop',
                'children' => array_map(static function (array $section): array {
                    $kids = [];
                    foreach ($section['children'] ?? [] as $child) {
                        $kids[] = [
                            'label' => $child['title'],
                            'url' => '/repair-shop/m/'.$child['slug'],
                        ];
                    }

                    return [
                        'label' => $section['title'],
                        'url' => '/repair-shop/m/'.$section['slug'],
                        'children' => $kids,
                    ];
                }, self::repairStaffTree()),
            ],
        ];
    }

    /**
     * Merge the two product dropdowns into the admin-editable header list.
     *
     * @param  list<array{label:string,url:string}>  $items
     * @return list<array{label:string,url:string,children?:list<array<string,mixed>>}>
     */
    public static function attachToNav(array $items): array
    {
        $dropUrls = [
            '/accounting', '/repair-shop', '/sites/repair-shop',
            '/receipt-software', '/sites',
        ];
        $dropLabels = ['نرم‌افزار', 'حسابداری وب', 'تعمیرگاه', 'تعمیرکاران', 'حسابداری هوش مصنوعی', 'نرم‌افزار قبض تحت وب'];
        $kept = [];
        foreach ($items as $item) {
            $url = rtrim((string) ($item['url'] ?? ''), '/') ?: '/';
            $label = trim((string) ($item['label'] ?? ''));
            if (in_array($url, $dropUrls, true) || in_array($label, $dropLabels, true)) {
                continue;
            }
            $kept[] = $item;
        }

        $products = self::headerItems();
        $insertAt = 1;
        $hasDesign = false;
        foreach ($kept as $i => $item) {
            $url = rtrim((string) ($item['url'] ?? ''), '/') ?: '/';
            $label = trim((string) ($item['label'] ?? ''));
            if ($url === '/design' || $label === 'طراحی سایت') {
                if (class_exists(StudioDesignCopy::class)) {
                    $kept[$i] = StudioDesignCopy::navItem();
                }
                $insertAt = $i + 1;
                $hasDesign = true;
                break;
            }
            if ($url === '/') {
                $insertAt = $i + 1;
            }
        }
        if (! $hasDesign && class_exists(StudioDesignCopy::class)) {
            array_splice($kept, $insertAt, 0, [StudioDesignCopy::navItem()]);
            $insertAt++;
        }

        array_splice($kept, $insertAt, 0, $products);

        return $kept;
    }

    /** @return list<array{slug:string,title:string,items:list<array{slug:string,title:string,short:string}>}> */
    public static function accountingGroups(): array
    {
        return [
            [
                'slug' => 'docs',
                'title' => 'اسناد و فاکتور',
                'items' => [
                    ['slug' => 'hub', 'title' => 'داشبورد حسابداری', 'short' => 'میز کار دفتر: اسناد باز، مانده و میانبر ثبت'],
                    ['slug' => 'invoices', 'title' => 'فاکتور و پیش‌فاکتور', 'short' => 'فهرست فاکتور فروش، خرید و پیش‌فاکتور'],
                    ['slug' => 'sale', 'title' => 'فاکتور فروش جدید', 'short' => 'صدور فاکتور فروش و اتصال به سند'],
                    ['slug' => 'proforma', 'title' => 'پیش‌فاکتور جدید', 'short' => 'پیشنهاد قیمت قبل از قطعی شدن فروش'],
                    ['slug' => 'purchase', 'title' => 'فاکتور خرید', 'short' => 'ثبت خرید کالا و طرف حساب تأمین‌کننده'],
                    ['slug' => 'journal', 'title' => 'دفتر روزنامه', 'short' => 'همه اسناد روزانه به ترتیب تاریخ'],
                    ['slug' => 'voucher', 'title' => 'ثبت سند دستی', 'short' => 'بدهکار و بستانکار با تأیید حسابدار'],
                    ['slug' => 'chart', 'title' => 'سرفصل حساب‌ها', 'short' => 'کدینگ و درخت حساب‌ها'],
                    ['slug' => 'ledger', 'title' => 'دفتر معین', 'short' => 'گردش یک حساب در بازه تاریخ'],
                    ['slug' => 'trial', 'title' => 'تراز آزمایشی', 'short' => 'جمع بدهکار و بستانکار برای کنترل دفتر'],
                ],
            ],
            [
                'slug' => 'warehouse',
                'title' => 'انبار',
                'items' => [
                    ['slug' => 'warehouses', 'title' => 'انبارها', 'short' => 'تعریف انبار اصلی و انبارهای چندگانه'],
                    ['slug' => 'stock', 'title' => 'حواله و رسید', 'short' => 'ورود و خروج کالا با سند انبار'],
                    ['slug' => 'kardex', 'title' => 'کارتکس کالا', 'short' => 'گردش موجودی و میانگین قیمت'],
                ],
            ],
            [
                'slug' => 'finance',
                'title' => 'مالی و خزانه',
                'items' => [
                    ['slug' => 'banks', 'title' => 'بانک‌ها', 'short' => 'حساب بانکی، شبا و موجودی اول دوره'],
                    ['slug' => 'expenses', 'title' => 'هزینه‌ها', 'short' => 'ثبت هزینه با سرفصل و طرف حساب'],
                    ['slug' => 'checks', 'title' => 'چک‌ها', 'short' => 'دسته چک، وصول و برگشت'],
                    ['slug' => 'installments', 'title' => 'اقساط مشتریان', 'short' => 'تقسیم مبلغ فروش و پیگیری سررسید'],
                    ['slug' => 'debtors', 'title' => 'بدهکاران', 'short' => 'مانده مشتریان و پیگیری وصول'],
                ],
            ],
            [
                'slug' => 'hr',
                'title' => 'حقوق و کمیسیون',
                'items' => [
                    ['slug' => 'payroll', 'title' => 'حقوق و دستمزد', 'short' => 'لیست حقوق دوره و محاسبه کارکرد'],
                    ['slug' => 'commission', 'title' => 'کمیسیون فروش', 'short' => 'درصد کمیسیون روی فروش قطعی'],
                ],
            ],
            [
                'slug' => 'reports',
                'title' => 'گزارش‌ها',
                'items' => [
                    ['slug' => 'reports-hub', 'title' => 'مرکز گزارش‌ها', 'short' => 'همه گزارش‌های دفتر از یک صفحه'],
                    ['slug' => 'report-sales', 'title' => 'گزارش خرید و فروش', 'short' => 'جمع فروش، خرید و سود دوره'],
                    ['slug' => 'report-staff', 'title' => 'گزارش کارمندان', 'short' => 'عملکرد نیرو و سهم کمیسیون'],
                    ['slug' => 'report-payroll', 'title' => 'گزارش حقوق', 'short' => 'خلاصه لیست حقوق دوره‌ها'],
                    ['slug' => 'report-vouchers', 'title' => 'گزارش اسناد', 'short' => 'اسناد ثبت‌شده با فیلتر نوع و تاریخ'],
                    ['slug' => 'report-warehouse', 'title' => 'گزارش انبار', 'short' => 'موجودی، ارزش و گردش کالا'],
                    ['slug' => 'report-customers', 'title' => 'گزارش مشتریان', 'short' => 'مانده و گردش حساب مشتری'],
                    ['slug' => 'report-checks', 'title' => 'گزارش چک‌ها', 'short' => 'چک‌های در جریان، وصول و برگشتی'],
                    ['slug' => 'report-installments', 'title' => 'گزارش اقساط', 'short' => 'سررسید و معوق اقساط'],
                ],
            ],
            [
                'slug' => 'settings',
                'title' => 'تنظیمات',
                'items' => [
                    ['slug' => 'settings', 'title' => 'تنظیمات حسابداری', 'short' => 'سال مالی، مالیات و قوانین ثبت سند'],
                ],
            ],
        ];
    }

    /** @return list<array{num:string,slug:string,title:string,short:string,children?:list<array{slug:string,title:string,short:string}>}> */
    public static function repairStaffTree(): array
    {
        return [
            [
                'num' => '۱', 'slug' => 'desk', 'title' => 'میز کار',
                'short' => 'ورود روزانه نیرو به کارتابل و میانبرهای کاری',
                'children' => [
                    ['slug' => 'trainee-portal', 'title' => 'پرتال کارآموز (پیش‌نمایش)', 'short' => 'ورود محدود کارآموز و ثبت دفتر روز'],
                ],
            ],
            [
                'num' => '۲', 'slug' => 'reception', 'title' => 'پذیرش',
                'short' => 'میز ورود دستگاه به تعمیرگاه؛ قبض می‌سازید، پیدا می‌کنید و تحویل می‌دهید',
                'children' => [
                    ['slug' => 'reception-new', 'title' => 'پذیرش جدید', 'short' => 'ثبت قبض تکی یا گروهی دستگاه'],
                    ['slug' => 'remote-intake', 'title' => 'ورود قطعه از راه دور', 'short' => 'پیش‌سفارش با عکس و باربری قبل از رسیدن فیزیکی کالا'],
                    ['slug' => 'receipt-search', 'title' => 'جستجوی قبض', 'short' => 'پیدا کردن قبض با سریال، موبایل یا شماره قبض'],
                    ['slug' => 'receipt-list', 'title' => 'لیست قبض‌ها', 'short' => 'مرور و فیلتر همه پذیرش‌ها'],
                    ['slug' => 'group-delivery', 'title' => 'تحویل گروهی', 'short' => 'خروج هم‌زمان چند قبض آماده‌تحویل'],
                    ['slug' => 'reception-trash', 'title' => 'سطل زباله', 'short' => 'بازیابی یا حذف دائم قبض‌های حذف‌شده'],
                ],
            ],
            [
                'num' => '۳', 'slug' => 'referral-section', 'title' => 'ارجاع / کارتابل تعمیر',
                'short' => 'جابه‌جایی دستگاه بین پذیرش و تعمیرکار',
                'children' => [
                    ['slug' => 'referral', 'title' => 'کارتابل ارجاع', 'short' => 'تأیید دریافت، دست تعمیر، بازگشت'],
                    ['slug' => 'referral-report', 'title' => 'گزارش ارجاع / محل', 'short' => 'محل فعلی دستگاه و تاریخچه ارجاع'],
                ],
            ],
            [
                'num' => '۴', 'slug' => 'notifications', 'title' => 'اعلان‌ها',
                'short' => 'اعلان‌های داخلی کارتابل برای نیروها',
            ],
            [
                'num' => '۵', 'slug' => 'daybook', 'title' => 'دفتر روز',
                'short' => 'ثبت خدمات روزانه و گزارش کار روز',
                'children' => [
                    ['slug' => 'daybook-today', 'title' => 'ثبت امروز', 'short' => 'ثبت خدمت انجام‌شده در همان روز'],
                    ['slug' => 'daybook-report', 'title' => 'گزارش همه', 'short' => 'مرور ثبت‌های دفتر روز'],
                ],
            ],
            [
                'num' => '۶', 'slug' => 'cost-section', 'title' => 'تأیید هزینه',
                'short' => 'لینک تأیید مبلغ برای مشتری و خدمات مشمول',
                'children' => [
                    ['slug' => 'cost-approval', 'title' => 'کارتابل تأییدها', 'short' => 'ارسال لینک و پیگیری تأیید/رد مشتری'],
                    ['slug' => 'cost-services', 'title' => 'خدمات مشمول', 'short' => 'تعیین خدماتی که تأیید مشتری لازم دارند'],
                ],
            ],
            [
                'num' => '۷', 'slug' => 'customers', 'title' => 'مشتریان',
                'short' => 'فهرست و ثبت مشتری تعمیرگاه',
                'children' => [
                    ['slug' => 'customer-list', 'title' => 'فهرست مشتریان', 'short' => 'جستجو و مدیریت مشتریان'],
                    ['slug' => 'customer-new', 'title' => 'مشتری جدید', 'short' => 'ثبت مشتری با موبایل و مشخصات'],
                ],
            ],
            [
                'num' => '۸', 'slug' => 'warehouse', 'title' => 'انبار',
                'short' => 'موجودی، رسید، حواله و کارتکس',
                'children' => [
                    ['slug' => 'warehouse-desk', 'title' => 'میز انبار', 'short' => 'نمای کلی عملیات انبار'],
                    ['slug' => 'warehouse-multi', 'title' => 'انبارهای چندگانه', 'short' => 'مدیریت چند انبار'],
                    ['slug' => 'warehouse-in', 'title' => 'رسید ورود', 'short' => 'ورود کالا به انبار'],
                    ['slug' => 'warehouse-out', 'title' => 'حواله خروج', 'short' => 'خروج کالا از انبار'],
                    ['slug' => 'warehouse-kardex', 'title' => 'کارتکس / گردش', 'short' => 'گردش موجودی کالا'],
                    ['slug' => 'warehouse-value', 'title' => 'ارزش موجودی', 'short' => 'ارزش ریالی موجودی'],
                    ['slug' => 'warehouse-item-new', 'title' => 'کالای جدید', 'short' => 'تعریف کالای انبار'],
                ],
            ],
            [
                'num' => '۹', 'slug' => 'employees', 'title' => 'کارمندان',
                'short' => 'نیرو، کارآموز، دسترسی و کمیسیون',
                'children' => [
                    ['slug' => 'staff', 'title' => 'کارتابل کارمند', 'short' => 'تعریف نیرو، وظیفه و دسترسی'],
                    ['slug' => 'staff-new', 'title' => 'کارمند جدید', 'short' => 'ساخت حساب کارمند جدید'],
                    ['slug' => 'trainee', 'title' => 'کارتابل کارآموز', 'short' => 'ثبت دوره و پرتال کارآموز'],
                    ['slug' => 'trainee-new', 'title' => 'کارآموز جدید', 'short' => 'ثبت کارآموز و بازه دوره'],
                    ['slug' => 'welcome-sms', 'title' => 'متن SMS خوش‌آمد', 'short' => 'قالب پیامک ورود نیرو'],
                    ['slug' => 'tech-commission', 'title' => 'تخصص و کمیسیون تعمیرکار', 'short' => 'تخصص و درصد کمیسیون'],
                    ['slug' => 'tech-new-price', 'title' => 'تعمیرکار جدید (قیمت)', 'short' => 'تعریف تعمیرکار با نرخ/قیمت'],
                ],
            ],
            [
                'num' => '۱۰', 'slug' => 'sms', 'title' => 'پیامک‌ها',
                'short' => 'گزارش و قالب پیامک قبض‌ها',
                'children' => [
                    ['slug' => 'sms-receipt-report', 'title' => 'گزارش پیامک قبض‌ها', 'short' => 'پیگیری پیامک‌های ارسال‌شده'],
                    ['slug' => 'sms-templates', 'title' => 'تعریف وضعیت / قالب', 'short' => 'قالب پیامک وضعیت‌ها'],
                ],
            ],
            [
                'num' => '۱۱', 'slug' => 'accounting', 'title' => 'حسابداری',
                'short' => 'اسناد، سرفصل، معین و بدهکاران داخل تعمیرگاه',
                'children' => [
                    ['slug' => 'accounting-desk', 'title' => 'میز حسابداری', 'short' => 'نمای کلی حسابداری تعمیرگاه'],
                    ['slug' => 'repair-journal', 'title' => 'اسناد روزنامه', 'short' => 'اسناد حسابداری روز'],
                    ['slug' => 'chart-of-accounts', 'title' => 'سرفصل حساب‌ها', 'short' => 'تعریف سرفصل‌ها'],
                    ['slug' => 'repair-ledger', 'title' => 'دفتر معین', 'short' => 'دفتر معین حساب‌ها'],
                    ['slug' => 'trial-balance', 'title' => 'تراز آزمایشی', 'short' => 'تراز آزمایشی'],
                    ['slug' => 'repair-debtors', 'title' => 'بدهکاران', 'short' => 'لیست بدهکاران'],
                    ['slug' => 'manual-voucher', 'title' => 'سند دستی', 'short' => 'ثبت سند دستی'],
                ],
            ],
            [
                'num' => '۱۲', 'slug' => 'reports', 'title' => 'گزارش‌ها',
                'short' => 'عملکرد، صندوق، محل دستگاه و پیام‌ها',
                'children' => [
                    ['slug' => 'report-tech-performance', 'title' => 'عملکرد تعمیرکاران', 'short' => 'گزارش کار و کمیسیون تعمیرکار'],
                    ['slug' => 'report-customers', 'title' => 'گزارش مشتریان', 'short' => 'گزارش مشتریان'],
                    ['slug' => 'report-parts-used', 'title' => 'کالای خرج‌شده', 'short' => 'کالای مصرف‌شده در تعمیر'],
                    ['slug' => 'report-workshop', 'title' => 'عملیات کارگاه', 'short' => 'خلاصه عملیات کارگاه'],
                    ['slug' => 'report-device-location', 'title' => 'ارجاع / محل دستگاه', 'short' => 'محل فعلی دستگاه‌ها'],
                    ['slug' => 'report-cash', 'title' => 'صندوق و دریافت‌ها', 'short' => 'صندوق و دریافت‌ها'],
                    ['slug' => 'report-bank-receipt', 'title' => 'تأیید فیش بانکی', 'short' => 'تأیید فیش‌های بانکی'],
                    ['slug' => 'report-customer-msg', 'title' => 'پیام مشتری', 'short' => 'پیام‌های مشتری'],
                    ['slug' => 'report-sms', 'title' => 'گزارش پیامک', 'short' => 'گزارش پیامک‌ها'],
                ],
            ],
            [
                'num' => '۱۳', 'slug' => 'system-tools', 'title' => 'ابزارهای سیستم',
                'short' => 'نگهداری و پشتیبان‌گیری',
                'children' => [
                    ['slug' => 'backup', 'title' => 'نگهداری و بکاپ', 'short' => 'پشتیبان و نگهداری سیستم'],
                ],
            ],
            [
                'num' => '۱۴', 'slug' => 'settings', 'title' => 'تنظیمات',
                'short' => 'تنظیمات سیستم، دفتر روز و پروفایل',
                'children' => [
                    ['slug' => 'settings-system', 'title' => 'تنظیمات سیستم', 'short' => 'تنظیمات کلی سیستم'],
                    ['slug' => 'settings-daybook', 'title' => 'تنظیمات دفتر روز', 'short' => 'خدمات و تنظیمات دفتر روز'],
                    ['slug' => 'settings-profile', 'title' => 'پروفایل من', 'short' => 'پروفایل کاربر واردشده'],
                ],
            ],
        ];
    }

    /** @return list<array{num:string,slug:string,title:string,short:string}> */
    public static function repairCustomerTree(): array
    {
        return [
            ['num' => '۱', 'slug' => 'customer-track', 'title' => 'پیگیری سفارش / وضعیت قبض', 'short' => 'مشتری وضعیت تعمیر را از پرتال می‌بیند'],
            ['num' => '۲', 'slug' => 'customer-cost-link', 'title' => 'تأیید هزینه (لینک مشتری)', 'short' => 'مشاهده مبلغ و تأیید یا رد هزینه'],
            ['num' => '۳', 'slug' => 'customer-pay', 'title' => 'پرداخت', 'short' => 'پرداخت آنلاین یا ثبت فیش از سمت مشتری'],
            ['num' => '۴', 'slug' => 'customer-warranty', 'title' => 'گارانتی / وضعیت گارانتی', 'short' => 'پیگیری گارانتی دستگاه'],
            ['num' => '۵', 'slug' => 'customer-messages', 'title' => 'پیام‌ها', 'short' => 'پیام بین مشتری و تعمیرگاه'],
            ['num' => '۶', 'slug' => 'customer-profile', 'title' => 'پروفایل مشتری', 'short' => 'مشخصات و موبایل مشتری در پرتال'],
        ];
    }

    /** @return list<array{slug:string,title:string,short:string,url:string}> */
    public static function catalog(): array
    {
        return [
            [
                'slug' => 'accounting',
                'title' => 'حسابداری وب',
                'short' => 'دفتر فاکتور، سند، انبار، خزانه و گزارش — با لایه هوش مصنوعی',
                'url' => '/accounting',
            ],
            [
                'slug' => 'repair-shop',
                'title' => 'سایت مدیریت تعمیرگاه',
                'short' => 'پذیرش، ارجاع تعمیرکار، تأیید هزینه، انبار و حسابداری تعمیرگاه',
                'url' => '/repair-shop',
            ],
        ];
    }

    /** @return array{product:string,slug:string,title:string,short:string,parent?:array{slug:string,title:string}}|null */
    public static function find(string $product, string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        if ($product === 'accounting') {
            foreach (self::accountingGroups() as $group) {
                foreach ($group['items'] as $item) {
                    if ($item['slug'] === $slug) {
                        return array_merge($item, [
                            'product' => 'accounting',
                            'parent' => ['slug' => $group['slug'], 'title' => $group['title']],
                        ]);
                    }
                }
            }

            return null;
        }

        foreach ([
            'staff' => self::repairStaffTree(),
            'customer' => self::repairCustomerTree(),
        ] as $menu => $tree) {
            foreach ($tree as $section) {
                if (($section['slug'] ?? '') === $slug) {
                    return [
                        'product' => 'repair',
                        'menu' => $menu,
                        'slug' => $section['slug'],
                        'title' => $section['title'],
                        'short' => (string) ($section['short'] ?? $section['title']),
                    ];
                }
                foreach ($section['children'] ?? [] as $child) {
                    if (($child['slug'] ?? '') === $slug) {
                        return [
                            'product' => 'repair',
                            'menu' => $menu,
                            'slug' => $child['slug'],
                            'title' => $child['title'],
                            'short' => (string) ($child['short'] ?? $child['title']),
                            'parent' => ['slug' => $section['slug'], 'title' => $section['title']],
                        ];
                    }
                }
            }
        }

        return null;
    }
}
