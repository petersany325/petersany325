<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\SiteHost;
use App\Support\StudioAccountingCopy;
use App\Support\StudioDesignCopy;
use App\Support\StudioHomeConfig;
use App\Support\StudioProductMenus;
use Plugins\ThemeBuilder\src\PageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Plugins\Studio\src\Support\StudioSms;
use Plugins\Studio\Plugin as StudioPlugin;
use Symfony\Component\HttpFoundation\Response;

class StudioController
{
    public function home(): View
    {
        $homeTitle = 'تخصصی طراحی سایت و نرم‌افزار';
        if (class_exists(\App\Support\StudioHomeConfig::class)) {
            $cfg = \App\Support\StudioHomeConfig::get();
            $homeTitle = trim((string) ($cfg['hero_title'] ?? '')) ?: trim((string) ($cfg['tagline'] ?? '')) ?: $homeTitle;
        }

        return $this->page('home', $homeTitle);
    }

    public function design(): View
    {
        $title = StudioDesignCopy::seoTitle('design');
        $description = StudioDesignCopy::seoDescription('design');

        return $this->page('design', $title, [
            'description' => $description,
            'canonical' => 'https://hdd-land.ir/design',
            'ogTitle' => $title,
            'ogDescription' => $description,
            'ogImage' => asset('images/studio/studio-work-shop.jpg'),
            'hero' => StudioDesignCopy::hero(),
            'aboutBlurb' => StudioDesignCopy::aboutBlurb(),
            'services' => StudioDesignCopy::services(),
            'reasons' => StudioDesignCopy::reasons(),
            'steps' => StudioDesignCopy::steps(),
            'faqs' => StudioDesignCopy::faqs(),
            'cfg' => StudioDesignCopy::pageSettings(),
            'works' => class_exists(StudioHomeConfig::class) ? StudioHomeConfig::works() : [],
        ]);
    }

    public function designLead(Request $request): RedirectResponse
    {
        $back = (string) $request->input('back', url('/design'));
        $backPath = parse_url($back, PHP_URL_PATH) ?: '/design';
        if (! preg_match('#^/design(/[a-z0-9-]+)?$#', $backPath)) {
            $backPath = '/design';
        }
        $backTo = url($backPath).'#contact';
        $key = 'design-lead:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return redirect()->to($backTo)->withErrors(['phone' => 'چند درخواست پشت سر هم آمد. کمی بعد دوباره بفرستید.'])->withInput();
        }
        if (trim((string) $request->input('dg_hp', '')) !== '') {
            return redirect()->to($backTo)->with('design_lead', true);
        }
        $phone = strtr((string) $request->input('phone', ''), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $name = trim((string) $request->input('name', ''));
        $type = trim((string) $request->input('type', ''));
        $message = trim((string) $request->input('message', ''));
        $company = trim((string) $request->input('company', ''));
        $items = trim((string) $request->input('items', ''));
        $uses = trim((string) $request->input('uses_accounting', ''));
        $users = trim((string) $request->input('users', ''));
        $current = trim((string) $request->input('current', ''));
        $itemChoices = ['', 'کمتر از ۱۰۰', '۱۰۰ تا ۱۰۰۰', 'بیشتر از ۱۰۰۰'];
        $useChoices = ['', 'بله', 'نه', 'می‌خواهم تهیه کنم'];
        $userChoices = ['', 'کمتر از ۱۰', '۱۰ تا ۵۰', 'بیشتر از ۵۰'];
        $currentChoices = ['', 'اکسل و کاغذ', 'نرم‌افزار دیگر', 'هیچ سیستمی ندارم'];
        $types = ['سایت شرکتی', 'فروشگاه اینترنتی', 'سامانهٔ اختصاصی', 'هنوز نمی‌دانم', 'CRM و پیگیری مشتری', 'اتوماسیون اداری', 'رزرو و نوبت‌دهی', 'پورتال سازمانی', 'چیز دیگر'];
        if ($name === '' || mb_strlen($name) > 120 || mb_strlen($company) > 120 || ! in_array($type, $types, true) || ! in_array($items, $itemChoices, true) || ! in_array($uses, $useChoices, true) || ! in_array($users, $userChoices, true) || ! in_array($current, $currentChoices, true) || mb_strlen($message) > 2000 || ! StudioSms::isMobile($phone)) {
            return redirect()->to($backTo)->withErrors(['phone' => 'نام و موبایل ۱۱ رقمی را درست وارد کنید.'])->withInput();
        }
        RateLimiter::hit($key, 3600);
        $row = [
            'at' => date('c'),
            'name' => $name,
            'phone' => StudioSms::normalizePhone($phone),
            'type' => $type,
            'company' => $company,
            'items' => $items,
            'uses_accounting' => $uses,
            'users' => $users,
            'current' => $current,
            'message' => $message,
        ];
        try {
            $path = storage_path('app/design-leads.jsonl');
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, json_encode($row, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
        }

        return redirect()->to($backTo)->with('design_lead', true);
    }

    public function designService(string $slug): View|RedirectResponse
    {
        $item = StudioDesignCopy::service($slug);
        if ($item === null) {
            return redirect()->to(url('/design'));
        }

        $pageData = StudioDesignCopy::servicePage($slug) ?? [];

        return $this->page('design-service', $pageData['seo_title'] ?? $item['seo_title'], [
            'description' => $pageData['seo_description'] ?? $item['seo_description'],
            'canonical' => 'https://hdd-land.ir/design/'.$slug,
            'ogTitle' => $pageData['seo_title'] ?? $item['seo_title'],
            'ogDescription' => $pageData['seo_description'] ?? $item['seo_description'],
            'service' => $item,
            'servicePage' => $pageData,
            'cfg' => StudioDesignCopy::pageSettings(),
        ]);
    }

    public function about(): View
    {
        return $this->page('about', StudioDesignCopy::seoTitle('about'), [
            'description' => 'شرکت فراز نت آمل (سرزمین هارد)، شماره ثبت ۴۱۸۹ و شناسه صنفی ۱۴۰۵۳۱۳۳۷۹: بیش از ۲۰ سال بازیابی اطلاعات و ۱۸ سال طراحی سایت و نرم‌افزار، با حسابداری HDD Land و دستیار فارسی آفلاین.',
            'canonical' => url('/about'),
            'ogTitle' => 'درباره سرزمین هارد (فراز نت آمل)',
            'ogDescription' => 'از بازیابی اطلاعات تا حسابداری HDD Land و دستیار فارسی که روی کامپیوتر خودتان کار می‌کند.',
            'aboutPage' => StudioDesignCopy::about(),
            'reasons' => StudioDesignCopy::reasons(),
        ]);
    }

    public function ai(): View
    {
        return $this->page('ai', 'دستیار فارسی که روی کامپیوتر خودتان کار می‌کند | فراز نت آمل', [
            'description' => 'می‌پرسید، با صدا یا متن؛ دستیار حسابداری HDD Land از دفاتر خود شما جواب می‌دهد، گزارش را توضیح می‌دهد و پیش‌نویس ثبت می‌سازد. ثبت قطعی فقط با تأیید شماست و بدون اینترنت کار می‌کند.',
            'canonical' => url('/ai'),
            'ogTitle' => 'دستیار فارسی حسابداری HDD Land | فراز نت آمل',
            'ogDescription' => 'گفت‌وگوی صوتی فارسی، پیش‌نویس ثبت با تأیید، خواندن فاکتور و حسابرس هوشمند؛ روی همین کامپیوتر.',
            'twitterDescription' => 'دستیار فارسی روی کامپیوتر خودتان؛ ثبت فقط با تأیید شما.',
        ]);
    }

    public function accounting(): View
    {
        $web = StudioProductMenus::webAccountingNav();

        return $this->page('accounting-web', 'حسابداری وب و تعمیرگاه | فراز نت آمل', [
            'description' => 'حسابداری وب تعمیرکاران به‌همراه منوی کامل تعمیرگاه: پذیرش، قبض، ارجاع و صندوق. بقیه صنوف حسابداری کامپیوتری با SQL Server 2022 هستند.',
            'webGroups' => $web,
        ]);
    }

    public function computerAccounting(): View
    {
        return $this->page('computer-accounting', \App\Support\StudioComputerMenu::SEO_TITLE, [
            'description' => \App\Support\StudioComputerMenu::SEO_DESCRIPTION,
            'groups' => StudioProductMenus::accountingGroups(),
            'industries' => StudioAccountingCopy::industries(),
            'sqlNote' => StudioAccountingCopy::sqlNote(),
        ]);
    }

    public function accountingType(string $slug): View|RedirectResponse
    {
        if (StudioAccountingCopy::industry($slug) !== null) {
            return redirect()->to(url('/computer-accounting/'.$slug), 301);
        }

        return redirect()->to(url('/accounting'));
    }

    public function computerAccountingType(string $slug): View|RedirectResponse
    {
        if ($slug === 'supermarket') {
            return $this->page('accounting-supermarket', \App\Support\StudioComputerMenu::SUPERMARKET_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::SUPERMARKET_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'shop') {
            return $this->page('accounting-shop', \App\Support\StudioComputerMenu::SHOP_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::SHOP_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'distribution') {
            return $this->page('accounting-distribution', \App\Support\StudioComputerMenu::DISTRIBUTION_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::DISTRIBUTION_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'restaurant') {
            return $this->page('accounting-restaurant', \App\Support\StudioComputerMenu::RESTAURANT_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::RESTAURANT_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'contracting') {
            return $this->page('accounting-contracting', \App\Support\StudioComputerMenu::CONTRACTING_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::CONTRACTING_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'manufacturing') {
            return $this->page('accounting-production', \App\Support\StudioComputerMenu::PRODUCTION_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::PRODUCTION_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'cafe') {
            return $this->page('accounting-cafe', \App\Support\StudioComputerMenu::CAFE_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::CAFE_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'service') {
            return $this->page('accounting-service', \App\Support\StudioComputerMenu::SERVICE_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::SERVICE_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'data-migration') {
            return $this->page('accounting-migration', \App\Support\StudioComputerMenu::MIGRATION_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::MIGRATION_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'automation') {
            return $this->page('accounting-office', \App\Support\StudioComputerMenu::OFFICE_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::OFFICE_SEO_DESCRIPTION,
            ]);
        }

        if ($slug === 'corporate') {
            return $this->page('accounting-corporate', \App\Support\StudioComputerMenu::CORPORATE_SEO_TITLE, [
                'description' => \App\Support\StudioComputerMenu::CORPORATE_SEO_DESCRIPTION,
            ]);
        }

        $edition = \App\Support\StudioComputerMenu::edition($slug);
        if ($edition !== null) {
            return $this->page('accounting-edition', $edition['seo_title'], [
                'description' => $edition['seo_description'],
                'edition' => $edition,
            ]);
        }

        $industry = StudioAccountingCopy::industry($slug);
        if ($industry === null) {
            return redirect()->to(url('/computer-accounting'));
        }

        return $this->page('accounting-type', $industry['seo_title'], [
            'description' => $industry['seo_description'],
            'industry' => $industry,
            'industries' => StudioAccountingCopy::industries(),
            'sqlNote' => StudioAccountingCopy::sqlNote(),
        ]);
    }

    public function accountingItem(string $slug): View|RedirectResponse
    {
        if (StudioProductMenus::find('accounting', $slug) !== null) {
            return redirect()->to(url('/computer-accounting/m/'.$slug), 301);
        }

        return redirect()->to(url('/accounting'));
    }

    public function computerAccountingItem(string $slug): View|RedirectResponse
    {
        $item = StudioProductMenus::find('accounting', $slug);
        if ($item === null) {
            return redirect()->to(url('/computer-accounting'));
        }

        return $this->page('product-item', $item['title'].' | حسابداری کامپیوتر', [
            'item' => $item,
            'hubUrl' => '/computer-accounting',
            'hubLabel' => 'حسابداری کامپیوتر',
            'groups' => StudioProductMenus::accountingGroups(),
        ]);
    }

    public function projects(): View
    {
        return $this->page('projects', 'محصولات | فراز نت آمل', [
            'designOffers' => class_exists(\App\Support\StudioOffers::class) ? \App\Support\StudioOffers::design() : [],
        ]);
    }

    public function softwareOffer(string $slug): View|RedirectResponse
    {
        $map = [
            'medical-beauty' => 'med-beauty',
            'medical-opera' => 'med-opera',
            'medical-psychology' => 'med-psych',
        ];
        if ($slug === 'medical-opera') {
            return redirect()->to(url('/software/medical-physiotherapy'), 301);
        }

        if ($slug === 'medical-physiotherapy') {
            return $this->page('software-physio', 'نرم‌افزار مدیریت کلینیک فیزیوتراپی؛ نوبت‌دهی، پرونده، تسویه و پیامک | حساب مطب', [
                'description' => 'حساب مطب، نسخهٔ فیزیوتراپی: نوبت‌دهی واحدبه‌واحد، صف و فراخوان هر واحد، پرونده و شرح حال، پنل درمانگر، تسویه و نوبت بعدی، پیامک خودکار و آموزش فارسی داخل برنامه؛ کاملاً آفلاین.',
                'canonical' => 'https://hdd-land.ir/software/medical-physiotherapy',
                'ogTitle' => 'نرم‌افزار مدیریت کلینیک فیزیوتراپی | حساب مطب',
                'ogDescription' => 'نوبت‌دهی روی واحد، صف و فراخوان هر واحد، شرح حال، تسویه و پیامک خودکار؛ کاملاً آفلاین.',
                'twitterDescription' => 'نوبت‌دهی، پرونده، تسویه و پیامک خودکار؛ نسخهٔ فیزیوتراپی حساب مطب.',
            ]);
        }

        if ($slug === 'medical-dental') {
            return $this->page('software-dental', 'نرم‌افزار مدیریت مطب دندانپزشکی؛ نوبت‌دهی، چارت دندانی، پرونده و پیامک | حساب مطب', [
                'description' => 'حساب مطب، نسخهٔ دندانپزشکی: نوبت‌دهی یونیت‌به‌یونیت، چارت دندانی FDI، کارتابل دندان‌پزشک و لابراتوار، پرونده و گزارش بیمار، تسویه و نوبت بعدی، پیامک خودکار و آموزش فارسی داخل برنامه؛ کاملاً آفلاین.',
                'canonical' => 'https://hdd-land.ir/software/medical-dental',
                'ogTitle' => 'نرم‌افزار مدیریت مطب دندانپزشکی | حساب مطب',
                'ogDescription' => 'نوبت‌دهی روی یونیت، چارت دندانی FDI، کارتابل و لابراتوار، تسویه و پیامک خودکار؛ کاملاً آفلاین.',
                'twitterDescription' => 'نوبت‌دهی، چارت دندانی FDI، تسویه و پیامک خودکار؛ نسخهٔ دندانپزشکی حساب مطب.',
            ]);
        }

        if ($slug === 'medical-psychology') {
            return $this->page('software-psychology', 'نرم‌افزار مدیریت مطب و کلینیک روانشناسی؛ نوبت‌دهی، پرونده، پیامک و تسویه | حساب مطب', [
                'description' => 'حساب مطب، نسخهٔ روانشناسی: ثبت مراجع، نوبت‌دهی تلفنی و حضوری و اینترنتی، شماره نوبت خودکار، پرونده و گزارش، تسویه و نوبت جلسهٔ بعد، پیامک خودکار و آموزش فارسی داخل برنامه؛ کاملاً آفلاین.',
                'canonical' => 'https://hdd-land.ir/software/medical-psychology',
                'ogTitle' => 'نرم‌افزار مدیریت مطب و کلینیک روانشناسی | حساب مطب',
                'ogDescription' => 'نوبت‌دهی سه‌کاناله، شماره نوبت خودکار، پرونده مراجع، تسویه و جلسهٔ بعد، پیامک خودکار و آموزش کامل داخل برنامه؛ بدون نیاز به اینترنت.',
                'twitterDescription' => 'نوبت‌دهی، پرونده، تسویه و پیامک خودکار؛ نسخهٔ روانشناسی حساب مطب.',
            ]);
        }

        if ($slug === 'medical-beauty') {
            return $this->page('software-beauty', 'نرم‌افزار مدیریت کلینیک زیبایی و لیزر؛ نوبت‌دهی، پرونده، پیامک و فراخوان صوتی | حساب مطب', [
                'description' => 'حساب مطب، نسخهٔ کلینیک زیبایی: نوبت‌دهی تلفنی، حضوری و اینترنتی، شماره نوبت و اعلام صوتی فارسی، پرونده و گزارش کامل بیمار، تسویه و نوبت بعدی، پیامک خودکار، خدمات لیزر بانوان، انبار و سطح دسترسی؛ کاملاً آفلاین.',
                'canonical' => 'https://hdd-land.ir/software/medical-beauty',
                'ogTitle' => 'نرم‌افزار مدیریت کلینیک زیبایی و لیزر | حساب مطب',
                'ogDescription' => 'نوبت‌دهی سه‌کاناله، شماره نوبت و فراخوان صوتی فارسی، پرونده بیمار، تسویه و نوبت بعدی، پیامک خودکار و آموزش کامل داخل برنامه؛ بدون نیاز به اینترنت.',
                'twitterDescription' => 'نوبت‌دهی، فراخوان صوتی فارسی، پرونده بیمار، تسویه و پیامک خودکار؛ نسخهٔ کلینیک زیبایی حساب مطب.',
            ]);
        }

        $sku = $map[$slug] ?? '';
        $offer = $sku !== '' && class_exists(\App\Support\StudioOffers::class)
            ? \App\Support\StudioOffers::find($sku)
            : null;
        if ($offer === null) {
            return redirect()->to(url('/computer-accounting'));
        }

        return $this->page('software-offer', $offer['title'].' | فراز نت آمل', [
            'description' => $offer['short'],
            'offer' => $offer,
        ]);
    }

    public function receiptSoftware(): View
    {
        return $this->page('receipt', 'نرم‌افزار قبض تحت وب');
    }

    public function contact(): View
    {
        return $this->page('contact', StudioDesignCopy::seoTitle('contact'), [
            'description' => StudioDesignCopy::seoDescription('contact'),
            'canonical' => url('/contact'),
            'ogTitle' => 'تماس با سرزمین هارد (فراز نت آمل)',
            'ogDescription' => StudioDesignCopy::seoDescription('contact'),
            'contactCopy' => StudioDesignCopy::contact(),
        ]);
    }

    public function sites(): View
    {
        return $this->page('sites', 'نرم‌افزار حسابداری و تعمیرگاه', [
            'catalog' => StudioProductMenus::catalog(),
        ]);
    }

    public function sitesSlug(string $slug): View|RedirectResponse
    {
        if ($slug === 'repair-shop') {
            return $this->repairShop();
        }
        if ($slug === 'accounting') {
            return redirect()->to(url('/accounting'));
        }

        return $this->sites();
    }

    public function repairShop(): View
    {
        return $this->page('repair', 'سامانه وب مدیریت تعمیرگاه | فراز نت آمل', [
            'description' => 'سامانه وب تعمیرگاه: پذیرش، ارجاع، تأیید هزینه، انبار، پیامک و کارتابل مشتری. جدا از حسابداری کامپیوتری تعمیرکاران.',
            'staff' => StudioProductMenus::repairStaffTree(),
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    public function repairStaffMenu(): View
    {
        return $this->page('repair-staff', 'منوی کارکنان تعمیرگاه', [
            'staff' => StudioProductMenus::repairStaffTree(),
        ]);
    }

    public function repairCustomerMenu(): View
    {
        return $this->page('repair-customer', 'منوی کارتابل مشتری', [
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    public function repairItem(string $slug): View|RedirectResponse
    {
        $item = StudioProductMenus::find('repair', $slug);
        if ($item === null) {
            return redirect()->to(url('/repair-shop'));
        }

        return $this->page('product-item', $item['title'].' | تعمیرگاه', [
            'item' => $item,
            'hubUrl' => ($item['menu'] ?? '') === 'customer' ? '/repair-shop/customer-menu' : '/repair-shop/staff-menu',
            'hubLabel' => ($item['menu'] ?? '') === 'customer' ? 'منوی کارتابل مشتری' : 'منوی کارکنان',
            'staff' => StudioProductMenus::repairStaffTree(),
            'customer' => StudioProductMenus::repairCustomerTree(),
        ]);
    }

    public function manifest(): Response
    {
        $home = StudioHomeConfig::get();
        $icon = url('images/hdd-land-icon-192.png');
        $icon512 = url('images/hdd-land-icon-512.png');
        $payload = [
            'name' => 'فراز نت آمل',
            'short_name' => 'فرازنت',
            'description' => 'طراحی سایت حرفه‌ای، حسابداری وب تعمیرکاران، حسابداری کامپیوتر و نرم‌افزار تعمیرگاه — فراز نت آمل',
            'start_url' => url('/'),
            'scope' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => $home['bg'] ?? '#070b18',
            'theme_color' => $home['accent'] ?? '#7c5cff',
            'lang' => 'fa',
            'dir' => 'rtl',
            'icons' => [
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => [
                ['name' => 'طراحی سایت', 'url' => url('/design')],
                ['name' => 'حسابداری وب', 'url' => url('/accounting')],
                ['name' => 'حسابداری کامپیوتر', 'url' => url('/computer-accounting')],
                ['name' => 'تعمیرگاه', 'url' => url('/repair-shop')],
                ['name' => 'تماس', 'url' => url('/contact')],
            ],
        ];

        return response(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200)
            ->header('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->header('Cache-Control', 'no-cache');
    }

    public function serviceWorker(): Response
    {
        $js = <<<'JS'
const CACHE = 'studio-wa-v2';
const PRECACHE = ['/','/design','/accounting','/computer-accounting','/repair-shop','/contact','/about','/css/studio-agency.css'];
self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {})).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  if (url.origin !== self.location.origin) return;
  e.respondWith(fetch(e.request).then((res) => {
    const copy = res.clone();
    caches.open(CACHE).then((c) => c.put(e.request, copy)).catch(() => {});
    return res;
  }).catch(() => caches.match(e.request).then((hit) => hit || caches.match('/'))));
});
JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache');
    }

    public function dismissBanner(Request $request): Response
    {
        $request->session()->put('studio_webapp_banner_dismissed', true);

        return response()->json(['ok' => true]);
    }

    /** @param  array<string, mixed>  $extra */
    protected function page(string $slug, string $title, array $extra = []): View
    {
        StudioPlugin::ensureBooted();
        if (class_exists(\Plugins\ThemeBuilder\Plugin::class)) {
            \Plugins\ThemeBuilder\Plugin::registerViews();
        }
        $built = in_array($slug, ['about', 'computer-accounting', 'accounting-edition', 'accounting-corporate', 'accounting-supermarket', 'accounting-shop', 'accounting-distribution', 'accounting-restaurant', 'accounting-production', 'accounting-cafe', 'accounting-service', 'accounting-office', 'accounting-contracting', 'accounting-migration', 'software-beauty', 'software-psychology', 'software-dental', 'software-physio', 'ai', 'projects', 'contact', 'accounting-type', 'design', 'design-service', 'repair', 'repair-staff', 'repair-customer'], true) ? null : $this->builderForRequest();
        if ($built !== null) {
            $slug = 'builder';
            $title = trim((string) ($built['seo_title'] ?? '')) ?: trim((string) ($built['title'] ?? '')) ?: $title;
            $extra['description'] = $extra['description'] ?? ($built['seo_description'] ?? null);
            $extra['builderPage'] = $built;
        }

        return view('studio::page', array_merge([
            'slug' => $slug,
            'title' => $title,
            'description' => $extra['description'] ?? null,
            'menu' => SiteHost::studioMenu(),
            'receiptUrl' => SiteHost::receiptUrl(),
            'shopUrl' => SiteHost::shopUrl('/'),
        ], $extra));
    }

    /** @return array<string, mixed>|null */
    protected function builderForRequest(): ?array
    {
        if (! class_exists(PageBuilder::class)) {
            $file = base_path('plugins/ThemeBuilder/src/PageBuilder.php');
            if (is_file($file)) {
                try {
                    require_once $file;
                } catch (\Throwable) {
                    return null;
                }
            }
        }
        if (! class_exists(PageBuilder::class)) {
            return null;
        }
        try {
            PageBuilder::ensureReady();
            $path = trim((string) request()->path(), '/');
            $lookup = ($path === '' || $path === 'index.php') ? '/' : '/'.$path;
            $aliases = [
                '/sites/repair-shop' => '/repair-shop',
                '/sites/repair-shop/staff-menu' => '/repair-shop/staff-menu',
                '/sites/repair-shop/customer-menu' => '/repair-shop/customer-menu',
            ];
            $page = PageBuilder::findPublishedByPath($lookup);
            if ($page === null && isset($aliases[$lookup])) {
                $page = PageBuilder::findPublishedByPath($aliases[$lookup]);
            }
            if ($page === null && preg_match('#^/sites/repair-shop/m/([^/]+)$#', $lookup, $m)) {
                $page = PageBuilder::findPublishedByPath('/repair-shop/m/'.$m[1]);
            }
            if ($page !== null && class_exists(\App\Support\StudioSoftwareShots::class)) {
                $page = \App\Support\StudioSoftwareShots::mergeInto($page);
            }

            return $page;
        } catch (\Throwable) {
            return null;
        }
    }
}
