<?php

namespace Plugins\MegaMenu;

use App\Support\BasePlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Plugins\MegaMenu\src\Models\MegaMenuItem;

class Plugin extends BasePlugin
{
    public function id(): string
    {
        return 'mega-menu';
    }

    public function name(): string
    {
        return 'مگامنو حرفه‌ای (Uber / Quad style)';
    }

    public function description(): string
    {
        return 'مگامنو ساز حرفه‌ای با درخت درگ‌اند‌دراپ، منوی اصلی/زیرمنو، تصویر، انیمیشن، تب و فرم';
    }

    /** @return array<string, string> */
    public static function types(): array
    {
        return [
            'link' => 'لینک',
            'category' => 'دسته محصول',
            'heading' => 'عنوان ستون',
            'column' => 'ستون',
            'html' => 'HTML',
            'promo' => 'پرومو تصویری',
            'search' => 'جعبه جستجو',
            'tab' => 'تب',
            'form' => 'فرم بازشو',
        ];
    }

    public function version(): string
    {
        return '3.6.0';
    }

    public const SETTINGS_KEY = 'mega_menu_settings';

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        $raw = \App\Support\SettingsStore::get(self::SETTINGS_KEY, null);
        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true) ?: [];
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        $out = array_merge([
            'nav_align' => 'right',
            'nav_style' => 'pills',
            'dropdown_size' => 'auto',
            'show_icons' => true,
            'accent' => '#e23d12',
            'open_mode' => 'hover',
            'gap_brand' => 18,
            'header_bg' => 'white',
            'header_bg_color' => '#ffffff',
            'header_opacity' => 100,
            'header_blur' => true,
            'panel_fx' => 'shadow',
            'panel_bg' => 'white',
            'panel_layout' => 'graphic',
            'panel_cols' => 4,
            'nav_item_gap' => 4,
            'panel_col_gap' => 16,
            'panel_row_gap' => 12,
            'panel_padding' => 16,
            // Keep mega panels inside the boxed site frame
            'panel_contain' => true,
            'panel_width_mode' => 'shell', // shell = full nav/shell width, item = under trigger
            'font_family' => 'Vazirmatn',
            'nav_font_size' => 14,
            'panel_font_size' => 13,
            'org_promo_enabled' => true,
            'org_promo_title' => 'پیشنهاد سازمانی',
            'org_promo_desc' => 'تأمین هارد و SSD برای کسب‌وکارها با گارانتی شفاف',
            'org_promo_button' => 'مشاهده',
            'org_promo_url' => '/products',
            'org_promo_image' => '/images/home/mega-promo.jpg',
            'repair_guides' => [],
        ], is_array($decoded) ? $decoded : []);

        // فونت‌های خیلی ریز قدیمی را به اندازه خواناتر ارتقا بده
        $navFs = (int) ($out['nav_font_size'] ?? 14);
        $panelFs = (int) ($out['panel_font_size'] ?? 13);
        if ($navFs > 0 && $navFs <= 12) {
            $navFs = 14;
        }
        if ($panelFs > 0 && $panelFs <= 12) {
            $panelFs = 13;
        }
        $out['nav_font_size'] = max(12, min(18, $navFs ?: 14));
        $out['panel_font_size'] = max(12, min(16, $panelFs ?: 13));
        if (($out['font_family'] ?? '') === 'Estedad' || ($out['font_family'] ?? '') === '') {
            $out['font_family'] = 'Vazirmatn';
        }

        return $out;
    }

    /** @return array<string, string> */
    public static function panelLayouts(): array
    {
        return [
            'graphic' => 'گرافیکی انگلیسی (نوار کناری + پنل)',
            'columns' => 'ستونی (مگا منو)',
            'cascade' => 'آبشاری (کشویی)',
            'list' => 'لیستی تک‌ستونه',
            'dense' => 'فشرده چندستونه',
        ];
    }

    /** @param  array<string, mixed>  $data
     *  @return array<string, mixed>
     */
    public static function saveSettings(array $data): array
    {
        $current = static::settings();
        $promoImage = trim((string) ($data['org_promo_image'] ?? $current['org_promo_image'] ?? ''));
        if ($promoImage === '') {
            $promoImage = '/images/home/mega-promo.jpg';
        }
        $promoUrl = trim((string) ($data['org_promo_url'] ?? $current['org_promo_url'] ?? '/products'));
        if ($promoUrl === '') {
            $promoUrl = '/products';
        }

        $merged = array_merge($current, [
            'nav_align' => in_array(($data['nav_align'] ?? ''), ['right', 'center', 'left'], true) ? $data['nav_align'] : 'right',
            'nav_style' => in_array(($data['nav_style'] ?? ''), ['minimal', 'pills', 'underline', 'boxed'], true) ? $data['nav_style'] : 'pills',
            'dropdown_size' => in_array(($data['dropdown_size'] ?? ''), ['auto', 'compact', 'medium'], true) ? $data['dropdown_size'] : 'auto',
            'show_icons' => ! empty($data['show_icons']),
            'accent' => (string) ($data['accent'] ?? '#e23d12'),
            'open_mode' => in_array(($data['open_mode'] ?? ''), ['hover', 'click'], true) ? $data['open_mode'] : 'hover',
            'gap_brand' => max(8, min(48, (int) ($data['gap_brand'] ?? 18))),
            'header_bg' => in_array(($data['header_bg'] ?? ''), ['white', 'soft', 'transparent', 'glass', 'custom'], true) ? $data['header_bg'] : 'white',
            'header_bg_color' => (string) ($data['header_bg_color'] ?? '#ffffff'),
            'header_opacity' => max(0, min(100, (int) ($data['header_opacity'] ?? 100))),
            'header_blur' => ! empty($data['header_blur']),
            'panel_fx' => in_array(($data['panel_fx'] ?? ''), ['soft', 'glass', 'shadow', 'glow', 'lift', 'none'], true) ? $data['panel_fx'] : 'soft',
            'panel_bg' => in_array(($data['panel_bg'] ?? ''), ['white', 'soft', 'glass', 'transparent'], true) ? $data['panel_bg'] : 'white',
            'panel_layout' => in_array(($data['panel_layout'] ?? ''), ['graphic', 'columns', 'cascade', 'list', 'dense'], true) ? $data['panel_layout'] : 'graphic',
            'panel_cols' => max(2, min(6, (int) ($data['panel_cols'] ?? 4))),
            'nav_item_gap' => max(0, min(32, (int) ($data['nav_item_gap'] ?? 4))),
            'panel_col_gap' => max(4, min(48, (int) ($data['panel_col_gap'] ?? 16))),
            'panel_row_gap' => max(4, min(48, (int) ($data['panel_row_gap'] ?? 12))),
            'panel_padding' => max(8, min(48, (int) ($data['panel_padding'] ?? 16))),
            'panel_contain' => array_key_exists('panel_contain', $data)
                ? in_array((string) (is_array($data['panel_contain']) ? end($data['panel_contain']) : $data['panel_contain']), ['1', 'true', 'on', 'yes'], true)
                : ! empty($current['panel_contain']),
            'panel_width_mode' => in_array(($data['panel_width_mode'] ?? ''), ['shell', 'item'], true) ? $data['panel_width_mode'] : 'shell',
            'font_family' => array_key_exists((string) ($data['font_family'] ?? 'Vazirmatn'), self::fonts())
                ? (string) ($data['font_family'] ?? 'Vazirmatn')
                : 'Vazirmatn',
            'nav_font_size' => max(12, min(18, (int) ($data['nav_font_size'] ?? 14))),
            'panel_font_size' => max(12, min(16, (int) ($data['panel_font_size'] ?? 13))),
        ]);

        // همیشه فیلدهای پیشنهاد سازمانی را از درخواست ذخیره کن (فرم یکپارچه / AJAX)
        if (array_key_exists('org_promo_title', $data) || array_key_exists('org_promo_image', $data) || array_key_exists('org_promo_enabled', $data) || array_key_exists('org_promo_button', $data) || array_key_exists('org_promo_desc', $data) || array_key_exists('org_promo_url', $data)) {
            $enabledRaw = $data['org_promo_enabled'] ?? 0;
            if (is_array($enabledRaw)) {
                $enabledRaw = end($enabledRaw);
            }
            $merged['org_promo_enabled'] = in_array((string) $enabledRaw, ['1', 'true', 'on', 'yes'], true);
            $merged['org_promo_title'] = mb_substr(trim((string) ($data['org_promo_title'] ?? 'پیشنهاد سازمانی')), 0, 120) ?: 'پیشنهاد سازمانی';
            $merged['org_promo_desc'] = mb_substr(trim((string) ($data['org_promo_desc'] ?? '')), 0, 255);
            $merged['org_promo_button'] = mb_substr(trim((string) ($data['org_promo_button'] ?? 'مشاهده')), 0, 60) ?: 'مشاهده';
            $merged['org_promo_url'] = mb_substr($promoUrl, 0, 500);
            $merged['org_promo_image'] = mb_substr($promoImage, 0, 500);
        }

        if (array_key_exists('repair_guides', $data) && is_array($data['repair_guides'])) {
            $merged['repair_guides'] = static::sanitizeRepairGuidesInput($data['repair_guides']);
        }

        $payload = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \RuntimeException('رمزگذاری تنظیمات مگامنو ناموفق بود.');
        }
        // ستون value از نوع text است؛ JSON رشته‌ای پایدارتر از پاس‌دادن آرایه است
        \App\Support\SettingsStore::set(self::SETTINGS_KEY, $payload);

        return $merged;
    }

    /**
     * Inline CSS for storefront header background.
     *
     * @return array{class:string, style:string, bg:string, settings:array}
     */
    public static function headerAppearance(): array
    {
        $s = static::settings();
        $mode = (string) ($s['header_bg'] ?? 'white');
        $opacityPct = max(0, min(100, (int) ($s['header_opacity'] ?? 100)));
        $opacity = $opacityPct / 100;
        $hex = (string) ($s['header_bg_color'] ?? '#ffffff');
        $blur = ! empty($s['header_blur']);
        $rgb = static::hexToRgb($hex) ?: [255, 255, 255];

        // Always respect opacity slider; mode only picks color base / alpha curve.
        if ($mode === 'transparent') {
            $rgb = [255, 255, 255];
            $alpha = 0.0;
        } elseif ($mode === 'soft') {
            $rgb = [255, 255, 255];
            $alpha = round($opacity * 0.75, 3);
        } elseif ($mode === 'glass') {
            $rgb = [255, 255, 255];
            $alpha = round($opacity * 0.58, 3);
        } elseif ($mode === 'custom') {
            $alpha = round($opacity, 3);
        } else { // white
            $rgb = [255, 255, 255];
            $alpha = round($opacity, 3);
        }

        $bg = sprintf('rgba(%d,%d,%d,%.3f)', $rgb[0], $rgb[1], $rgb[2], $alpha);

        $classes = ['site-header', 'mm-header-'.$mode];
        if ($blur && $alpha > 0.02) {
            $classes[] = 'mm-header-blur';
        }
        if ($mode === 'glass' || ($s['panel_fx'] ?? '') === 'glass') {
            $classes[] = 'mm-header-glass';
        }
        if ($alpha < 0.15) {
            $classes[] = 'mm-header-clear';
        }

        $style = implode(';', [
            '--mm-header-bg:'.$bg,
            '--mm-header-alpha:'.$alpha,
            '--mega-accent:'.($s['accent'] ?? '#e23d12'),
            'background:'.$bg.' !important',
        ]);

        return [
            'class' => implode(' ', $classes),
            'style' => $style,
            'bg' => $bg,
            'settings' => $s,
        ];
    }

    /** @return array{0:int,1:int,2:int}|null */
    public static function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public function isCore(): bool
    {
        return true;
    }

    public function boot(): void
    {
        if (! Cache::get('mega_menu_schema_360')) {
            static::ensureSchema();
            static::fixLegacyTrackUrls();
            static::syncWebsiteSalesMenu();
            Cache::put('mega_menu_schema_360', true, now()->addDay());
        }
        static::syncReceiptPortalUrl();
        parent::boot();

        try {
            $wd = base_path('plugins/WarrantyDesk/Plugin.php');
            if (is_file($wd)) {
                require_once $wd;
                if (class_exists(\Plugins\WarrantyDesk\Plugin::class)) {
                    \Plugins\WarrantyDesk\Plugin::ensureBooted();
                }
            }
        } catch (\Throwable) {
        }

        try {
            $ad = base_path('plugins/AboutDesk/Plugin.php');
            if (is_file($ad)) {
                require_once $ad;
                if (class_exists(\Plugins\AboutDesk\Plugin::class)) {
                    \Plugins\AboutDesk\Plugin::ensureBooted();
                }
            }
        } catch (\Throwable) {
        }

        try {
            $sd = base_path('plugins/ServicesDesk/Plugin.php');
            if (is_file($sd)) {
                require_once $sd;
                if (class_exists(\Plugins\ServicesDesk\Plugin::class)) {
                    \Plugins\ServicesDesk\Plugin::ensureBooted();
                }
            }
        } catch (\Throwable) {
        }

        try {
            $cd = base_path('plugins/ContactDesk/Plugin.php');
            if (is_file($cd)) {
                require_once $cd;
                if (class_exists(\Plugins\ContactDesk\Plugin::class)) {
                    \Plugins\ContactDesk\Plugin::ensureBooted();
                }
            }
        } catch (\Throwable) {
        }

        try {
            $td = base_path('plugins/TrainingDesk/Plugin.php');
            if (is_file($td)) {
                require_once $td;
                if (class_exists(\Plugins\TrainingDesk\Plugin::class)) {
                    \Plugins\TrainingDesk\Plugin::ensureBooted();
                }
            }
        } catch (\Throwable) {
        }

        try {
            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Auth\Events\Login::class,
                [static::class, 'preferStorefrontAfterAdminLogin']
            );
            $router = app('router');
            $web = $router->getMiddlewareGroups()['web'] ?? [];
            if (! in_array(\App\Http\Middleware\PreferStorefrontAfterAdminLogin::class, $web, true)) {
                $router->pushMiddlewareToGroup('web', \App\Http\Middleware\PreferStorefrontAfterAdminLogin::class);
            }
        } catch (\Throwable) {
        }
    }

    public static function preferStorefrontAfterAdminLogin(object $event): void
    {
        $user = $event->user ?? null;
        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return;
        }
        session(['admin_land_on_site' => true]);
        $intended = (string) session('url.intended', '');
        $path = trim((string) (parse_url($intended, PHP_URL_PATH) ?: ''), '/');
        $backend = $path === 'admin' || str_starts_with($path, 'admin/')
            || $path === 'staff' || str_starts_with($path, 'staff/')
            || in_array($path, ['login', 'register', 'account'], true)
            || str_starts_with($path, 'account/');
        if ($intended === '' || $backend) {
            session(['url.intended' => url('/')]);
        }
    }

    /**
     * راهنمای بخش‌های صفحه فروش تعمیرکاران (کارتابل‌ها و …)
     *
     * @return list<array{slug:string,title:string,short:string,body:string,aparat_url:string,is_active:bool,sort:int}>
     */
    public static function repairShopGuideDefaults(): array
    {
        return [
            [
                'slug' => 'referral',
                'title' => 'کارتابل ارجاع',
                'short' => 'رسید دیجیتال جابه‌جایی دستگاه؛ تأیید دریافت، گزارش کار، بازگشت',
                'body' => <<<'TXT'
کارتابل ارجاع چیست؟

کارتابل ارجاع، میز کنترل جابه‌جایی دستگاه داخل تعمیرگاه است. هر بار که هارد یا لپ‌تاپ از پذیرش به تعمیرکار می‌رود یا برمی‌گردد، باید در سیستم ثبت و تأیید شود — مثل رسید تحویل دستی، ولی دیجیتال و قابل پیگیری.

هدف برای مشتری و مدیریت: دیگر کسی نگوید «نمی‌دانم دستگاه دست کیست»؛ هر انتقال با نام، زمان و تأیید گیرنده ثبت می‌شود.

مشکل رایجی که این کارتابل حل می‌کند

• دستگاه رفته تعمیر، ولی معلوم نیست کی گرفته
• تعمیرکار می‌گوید «من نگرفتم»
• هزینه اعلام شده قبل از اتمام کار
• تحویل به مشتری در حالی که دستگاه هنوز روی میز تعمیر است
• اختلاف بین پذیرش، تعمیرکار و حسابداری

کارتابل ارجاع با قانون Chain of Custody این‌ها را قفل می‌کند: بدون تأیید و گزارش کار، هزینه و خروج جلو نمی‌رود.

جریان کامل کار (از پذیرش تا برگشت)

1. پذیرش دستگاه: قبض ساخته می‌شود؛ دستگاه هنوز نزد پذیرش است.
2. ارجاع به تعمیرکار: منشی/پذیرش از روی قبض، تعمیرکار را انتخاب و ارجاع می‌زند. وضعیت می‌شود: در انتظار تأیید دریافت.
3. تأیید در کارتابل ارجاع: تعمیرکار وارد کارتابل می‌شود و می‌بیند چه دستگاه‌هایی برایش آمده. دو دکمه دارد: «بله، دریافت کردم» و «خیر، دریافت نکردم». تا وقتی تأیید نکند، دستگاه رسماً «دست او» حساب نمی‌شود.
4. دست تعمیرکار: بعد از تأیید، دستگاه در لیست دست تعمیر می‌آید. آمار زنده هم هست: چند تا در انتظار، چند تا دست تعمیر، تأیید/رد امروز.
5. گزارش کار (اجباری): تعمیرکار باید روی همان قبض گزارش کار بنویسد. بدون گزارش کار، اعلام هزینه قفل است و بازگشت/تحویل کامل جلو نمی‌رود.
6. ارجاع بازگشت به پذیرش: فقط همان تعمیرکاری که دستگاه نزد اوست می‌تواند بازگشت بزند. دوباره برای منشی/حسابدار تأیید دریافت می‌آید.
7. تأیید بازگشت: پذیرش تأیید می‌کند دستگاه برگشته. از این لحظه مسیر اعلام هزینه / تسویه / تحویل باز می‌شود.

داخل صفحه کارتابل چه چیزهایی می‌بینید؟

• جستجو با شماره قبض / سریال / نام مشتری / موبایل
• فیلتر نمایش: در انتظار تأیید، دست تعمیرکار، تأیید شده، رد شده، همه ارجاع‌ها
• آمار بالای صفحه: در انتظار، تأیید امروز، رد امروز، دست تعمیر
• لیست دستگاه‌های دست تعمیر + وضعیت گزارش کار
• لینک به گزارش کامل ارجاع / محل دستگاه

چه کسانی با آن کار می‌کنند؟

• پذیرش / منشی: ارجاع به تعمیرکار، تأیید بازگشت، پیگیری محل دستگاه
• تعمیرکار: تأیید دریافت، ثبت گزارش کار، درخواست بازگشت
• حسابدار / مدیر: کنترل اینکه بدون گزارش و بازگشت، هزینه و خروج زده نشود

هر نقش فقط کارتابل مربوط به خودش را می‌بیند (تعمیرکار معمولاً فقط ارجاع‌های خودش).

قفل‌های هوشمند (نکته قوی فروش)

سیستم جلوی این‌ها را می‌گیرد:

• تحویل به مشتری وقتی دستگاه هنوز نزد تعمیرکار است
• اعلام هزینه قبل از گزارش کار
• ارجاع هم‌زمان تکراری روی یک قبض
• بازگشت توسط کسی غیر از تعمیرکار نگهدارنده

یعنی فرآیند تعمیرگاه اجباری و استاندارد می‌شود، نه سلیقه‌ای.

روی قبض چه چیزی نشان داده می‌شود؟

چک‌لیست وضعیت ارجاع:

1. تأیید دریافت تعمیرکار
2. ثبت گزارش کار
3. تأیید بازگشت به پذیرش

مدیر یک نگاه می‌فهمد کار کجای مسیر است.

فایده برای صاحب تعمیرگاه

• شفافیت کامل: دستگاه الان کجاست؟
• کاهش گم‌شدن و اختلاف بین نیروها
• نظم بین پذیرش ↔ تعمیر ↔ حسابداری
• امکان گزارش عملکرد و محل دستگاه
• اعتماد بیشتر مشتری به‌خاطر پیگیری دقیق سریال و قبض

یک جمله خلاصه برای دمو:

«کارتابل ارجاع، رسید دیجیتالی جابه‌جایی دستگاه داخل تعمیرگاه است؛ هر تحویل به تعمیرکار و هر برگشت به پذیرش باید تأیید شود، گزارش کار اجباری است، و تا این مسیر کامل نشود هزینه و خروج قفل می‌ماند.»
TXT,
                'aparat_url' => '',
                'is_active' => true,
                'sort' => 1,
            ],
            [
                'slug' => 'cost-approval',
                'title' => 'کارتابل تأیید هزینه',
                'short' => 'لینک تأیید مبلغ برای مشتری؛ پیگیری مشاهده/تأیید/رد',
                'body' => <<<'TXT'
کارتابل تأیید هزینه چیست؟

کارتابل تأیید هزینه، میز کنترل موافقت مشتری با مبلغ تعمیر است. برای کارهای پرهزینه مثل جراحی هارد و بازیابی اطلاعات، قبل از ادامه کار یا تسویه، لینک یک‌بارمصرف برای مشتری فرستاده می‌شود تا مبلغ، اجرت، قطعات، تخفیف و شرایط را ببیند و خودش تأیید یا رد کند.

هدف برای مشتری و مدیریت: دیگر اختلافی سر «من این مبلغ را قبول نکرده بودم» پیش نیاید؛ هر تأیید با زمان، مبلغ و نسخه مشخص در سیستم ثبت می‌شود.

مشکل رایجی که این کارتابل حل می‌کند

• هزینه اعلام شده، ولی مشتری بعداً می‌گوید خبر نداشته
• اختلاف بین پذیرش، تعمیرکار و مشتری سر مبلغ نهایی
• شروع کار پرهزینه بدون رضایت کتبی/دیجیتالی مشتری
• گم شدن پیامک یا فراموش شدن پیگیری تأیید
• مشخص نبودن اینکه مشتری لینک را دیده، تأیید کرده یا رد کرده

کارتابل تأیید هزینه این مسیر را شفاف می‌کند: لینک ساخته می‌شود، وضعیت پیگیری می‌شود، و تاریخچه نسخه‌ها می‌ماند.

جریان کامل کار (از اعلام هزینه تا تصمیم مشتری)

1. اعلام هزینه روی قبض: اجرت، قطعات، تخفیف و مبلغ کل مشخص می‌شود.
2. تشخیص خدمت مشمول: اگر خدمت از نوع جراحی / بازیابی / دیتا ریکاوری (طبق تنظیمات) باشد، تأیید مشتری لازم است.
3. ارسال لینک تأیید: از روی قبض یا از داخل کارتابل تأیید هزینه، لینک یک‌بارمصرف ساخته و معمولاً با پیامک برای مشتری فرستاده می‌شود.
4. مشاهده توسط مشتری: مشتری لینک را باز می‌کند؛ وضعیت می‌شود «مشاهده‌شده». مبلغ، جزئیات و شرایط را می‌بیند.
5. تأیید مشتری: با پذیرش شرایط، هزینه را تأیید می‌کند. مبلغ و زمان تأیید روی قبض ثبت می‌شود و به پذیرش/میز کار اعلان می‌رسد.
6. رد مشتری: مشتری می‌تواند هزینه را رد کند (با دلیل اختیاری). وضعیت رد ثبت می‌شود و تیم باید مبلغ را اصلاح و لینک جدید بفرستد.
7. ارسال مجدد در صورت تغییر مبلغ: هر بار مبلغ عوض شود، نسخه جدید ساخته می‌شود و لینک‌های قبلی از اعتبار خارج می‌شوند.

داخل صفحه کارتابل چه چیزهایی می‌بینید؟

• آمار: در انتظار تأیید، تأییدشده، ردشده، کل
• لیست قبض‌های نیازمند تأیید + دکمه ارسال لینک
• جستجو با کد تأیید / شماره قبض / سریال / نام مشتری / موبایل
• فیلتر وضعیت لینک‌ها: ارسال‌شده، مشاهده‌شده، تأیید، رد، منقضی، جایگزین‌شده و …
• تاریخچه نسخه‌ها: مبلغ، وضعیت، زمان ارسال/مشاهده/تصمیم، کد تأیید
• تنظیم خدمات مشمول و متن شرایط نمایش‌داده‌شده به مشتری

چه کسانی با آن کار می‌کنند؟

• پذیرش / منشی: اعلام هزینه، ارسال لینک، پیگیری وضعیت تأیید
• تعمیرکار / حسابدار / مدیر: مشاهده وضعیت و هماهنگی قبل از ادامه کار پرهزینه
• مشتری: باز کردن لینک عمومی، دیدن جزئیات مبلغ، تأیید یا رد

قفل‌ها و نکته‌های مهم فروش

• برای خدمات مشمول، لینک تأیید هزینه ساخته و پیگیری می‌شود
• بدون مبلغ معتبر و بدون موبایل مشتری، لینک ارسال نمی‌شود
• لینک معمولاً ۴۸ ساعت اعتبار دارد؛ بعد از انقضا باید لینک جدید فرستاده شود
• اگر مبلغ تغییر کند، نسخه قبلی باطل و نسخه جدید جایگزین می‌شود
• می‌توان چندمرحله‌ای هزینه داشت (مثلاً خرید قطعه / بازیابی / تست) و برای هر مرحله تأیید جدا گرفت
• پیش‌نیاز منطقی اعلام هزینه در مسیر تعمیر: اگر دستگاه نزد تعمیرکار بوده، معمولاً باید گزارش کار ثبت شده باشد

روی لینک مشتری چه چیزی نشان داده می‌شود؟

• مبلغ کل، اجرت، قطعات، تخفیف
• شرح کار / توضیحات هزینه
• شرایط تأیید هزینه
• دو اقدام اصلی: تأیید هزینه یا رد هزینه

فایده برای صاحب تعمیرگاه

• رضایت مکتوب/دیجیتالی مشتری قبل از کار سنگین
• کاهش دعوا و برگشت‌خوردن هزینه
• پیگیری دقیق: مشتری دیده؟ تأیید کرده؟ رد کرده؟
• تاریخچه نسخه‌ها برای حسابداری و پشتیبانی
• اعتماد بیشتر مشتری به‌خاطر شفافیت مبلغ

یک جمله خلاصه برای دمو:

«کارتابل تأیید هزینه، رضایت دیجیتالی مشتری از مبلغ تعمیر است؛ لینک یک‌بارمصرف می‌فرستید، مشتری جزئیات را می‌بیند و تأیید یا رد می‌کند، و تا وضعیت مشخص نشود تیم دقیقاً می‌داند اجازه ادامه کار پرهزینه را دارد یا نه.»
TXT,
                'aparat_url' => '',
                'is_active' => true,
                'sort' => 2,
            ],
            [
                'slug' => 'staff',
                'title' => 'کارتابل کارمند',
                'short' => 'تعریف نیرو، وظیفه، دسترسی و ورود با پیامک یا رمز',
                'body' => <<<'TXT'
کارتابل کارمند چیست؟

کارتابل کارمند، میز مدیریت نیروی انسانی داخل سیستم سرزمین هارد است. از این بخش مدیر مشخص می‌کند چه کسانی وارد سیستم شوند، وظیفه‌شان چیست، با موبایل یا رمز وارد شوند، و به کدام بخش‌های کارتابل دسترسی داشته باشند.

هدف برای صاحب تعمیرگاه: دیگر لازم نیست همه با یک یوزر مشترک کار کنند؛ هر نفر حساب خودش را دارد، دسترسی‌اش محدود و قابل کنترل است، و ورود با پیامک امن‌تر و ساده‌تر می‌شود.

مشکل رایجی که این کارتابل حل می‌کند

• همه با یک اکانت مدیر وارد می‌شوند و مشخص نیست چه کسی چه کاری کرده
• تعمیرکار به بخش حسابداری یا تنظیمات دسترسی دارد
• کارمند جدید آمده ولی هنوز ورود و دسترسی‌اش آماده نیست
• نیروی خارج‌شده هنوز می‌تواند وارد سیستم شود
• مشخص نیست چه کسانی ورود SMS دارند و چه کسانی ورود با رمز

کارتابل کارمند این‌ها را یکجا مدیریت می‌کند: لیست نیروها، وظیفه، دسترسی، وضعیت فعال/غیرفعال، و پیامک خوش‌آمد ورود.

جریان کامل کار (از تعریف نیرو تا ورود)

1. ساخت کارمند جدید: نام، موبایل، و در صورت نیاز ایمیل/رمز ثبت می‌شود.
2. انتخاب وظیفه: مدیر / پذیرش / تعمیرکار / حسابدار / کارمند / کارآموز.
3. پیشنهاد خودکار دسترسی‌ها: با انتخاب وظیفه، دسترسی‌های پیشنهادی همان نقش پر می‌شود؛ مدیر می‌تواند کم یا زیاد کند.
4. تنظیم نحوه ورود: ورود با پیامک (OTP)، ورود با رمز، یا هر دو؛ فعال/غیرفعال بودن حساب.
5. اگر نقش تعمیرکار باشد: تخصص و درصد کمیسیون هم روی همان فرم تنظیم می‌شود.
6. ارسال SMS خوش‌آمد: لینک ورود کارتابل برای کارمند جدید پیامک می‌شود.
7. ورود کارمند: با موبایل ثبت‌شده وارد می‌شود و فقط منوهایی را می‌بیند که دسترسی‌اش اجازه می‌دهد.
8. ویرایش بعدی: تغییر وظیفه، دسترسی، قطع دسترسی، حذف کاربر، یا ارسال مجدد SMS خوش‌آمد.

داخل صفحه کارتابل چه چیزهایی می‌بینید؟

• آمار: کل کارمندان، فعال‌ها، ورود SMS، ورود رمز
• کارت هر کارمند: نام، وظیفه، موبایل، وضعیت فعال، نوع ورود، چند دسترسی اصلی
• دکمه‌ها: ویرایش دسترسی، SMS خوش‌آمد، حذف کاربر
• میانبرها: کارمند جدید، کارآموز جدید، کارتابل کارآموز، متن SMS خوش‌آمد
• در تنظیمات هم تب «کارتابل کارمند» برای نمای سریع و رفتن به کارتابل کامل هست

چه کسانی با آن کار می‌کنند؟

• مدیر سیستم: ساخت و ویرایش نیروها، تعیین وظیفه و دسترسی، فعال/غیرفعال کردن
• کارمند / پذیرش / تعمیرکار / حسابدار: از این صفحه مدیریت نمی‌شوند؛ فقط با دسترسی خودشان وارد کارتابل کاری می‌شوند
• کارآموز: در کارتابل جداگانه «کارتابل کارآموز» مدیریت می‌شود (هم‌خانواده همین بخش)

دسترسی‌هایی که معمولاً از همین‌جا کنترل می‌شود

• میز کار / داشبورد
• پذیرش و قبض‌ها
• ارجاع دستگاه / کارتابل تعمیر
• تأیید هزینه
• گزارش‌ها
• اعلان‌ها و دفتر روزانه
• پروفایل و سایر ماژول‌های فعال سیستم

قفل‌ها و نکته‌های مهم فروش

• فقط کسی که دسترسی «کارمندان و دسترسی‌ها» دارد این کارتابل را می‌بیند (معمولاً مدیر)
• مدیر نمی‌تواند آخرین ادمین سیستم را حذف کند
• کسی نمی‌تواند حساب خودش را حذف کند
• موبایل برای ورود پیامکی ضروری است و نباید تکراری باشد
• با غیرفعال کردن حساب، ورود همان لحظه قطع می‌شود
• نقش ادمین همیشه کامل‌ترین دسترسی را دارد و قفل منطقی دارد
• این کارتابل، صندوق کار قبض نیست؛ کار قبض در کارتابل ارجاع و بخش‌های عملیاتی انجام می‌شود

تفاوت مهم با بقیه کارتابل‌ها

• کارتابل کارمند = مدیریت افراد و دسترسی‌ها
• کارتابل ارجاع = جابه‌جایی دستگاه بین پذیرش و تعمیرکار
• کارتابل تأیید هزینه = موافقت مشتری با مبلغ
• کارتابل مشتری = پرتال خود مشتری برای پیگیری و پرداخت

فایده برای صاحب تعمیرگاه

• نظم دسترسی‌ها و امنیت بالاتر
• شروع سریع کارمند جدید با SMS ورود
• جدا کردن نقش‌ها: پذیرش، تعمیر، حسابداری، مدیریت
• قطع فوری دسترسی نیروی خارج‌شده
• آماده‌سازی درست تعمیرکار همراه با تخصص و کمیسیون
• زیرساخت استاندارد برای رشد تیم بدون شلوغی اکانت‌ها

یک جمله خلاصه برای دمو:

«کارتابل کارمند، میز تعریف نیروی انسانی سیستم است؛ مشخص می‌کنید چه کسی وارد شود، وظیفه‌اش چیست، با پیامک یا رمز لاگین کند، و دقیقاً به کدام بخش‌های کارتابل دسترسی داشته باشد.»
TXT,
                'aparat_url' => '',
                'is_active' => true,
                'sort' => 3,
            ],
            [
                'slug' => 'trainee',
                'title' => 'کارتابل کارآموز',
                'short' => 'ثبت دوره، پرتال محدود، دفتر روز و SMS تأیید',
                'body' => <<<'TXT'
کارتابل کارآموز چیست؟

کارتابل کارآموز، میز ثبت‌نام و مدیریت دوره کارآموزی داخل سیستم سرزمین هارد است. از این بخش مدیر کارآموز را ثبت می‌کند، بازه شروع و پایان دوره را مشخص می‌کند، پرتال ورود را فعال می‌کند، دسترسی‌ها (مخصوصاً دفتر روزانه) را تنظیم می‌کند و پیامک تأیید دوره می‌فرستد.

هدف برای صاحب تعمیرگاه: کارآموز مثل کارمند رسمی وارد سیستم می‌شود، ولی با دسترسی محدود و کنترل‌شده؛ دوره تاریخ دارد، ورود قابل قطع است، و کار روزانه‌اش در دفتر روز ثبت می‌شود.

مشکل رایجی که این کارتابل حل می‌کند

• کارآموز بدون حساب مشخص و با اکانت مشترک کار می‌کند
• معلوم نیست دوره کارآموزی از کی تا کی است
• کارآموز به بخش‌های حساس مثل حسابداری یا تنظیمات دسترسی دارد
• پایان دوره رسیده ولی هنوز می‌تواند وارد سیستم شود
• هیچ ثبت منظمی از کارهای روزانه کارآموز وجود ندارد

کارتابل کارآموز این‌ها را منظم می‌کند: ثبت دوره، پرتال ورود، دسترسی محدود، SMS تأیید، و پیگیری وضعیت دوره.

جریان کامل کار (از ثبت تا ورود و دفتر روز)

1. ثبت کارآموز جدید: نام، موبایل، ایمیل/کد ملی اختیاری، بخش/واحد و یادداشت.
2. تعیین بازه کارآموزی: تاریخ شروع و پایان (شمسی) — همین تاریخ‌ها در پیامک تأیید هم می‌رود.
3. فعال‌سازی پرتال ورود: مدیر مشخص می‌کند کارآموز بتواند وارد سیستم شود یا نه.
4. تنظیم نحوه ورود: ورود با موبایل/پیامک، ورود با رمز، یا هر دو.
5. انتخاب دسترسی‌ها: معمولاً میز کار، دفتر روزانه، اعلان‌ها و پروفایل؛ در صورت نیاز مشاهده قبض، ارجاع، انبار و … هم قابل فعال‌سازی است.
6. ارسال SMS تأیید کارآموزی: پیامک خوش‌آمد با نام فروشگاه و بازه دوره برای کارآموز فرستاده می‌شود.
7. ورود کارآموز به پرتال: کارآموز وارد پرتال خودش می‌شود و خدمات انجام‌شده را در دفتر روز ثبت می‌کند.
8. پایان یا قطع دسترسی: با اتمام تاریخ، غیرفعال‌سازی یا حذف، ورود قطع می‌شود.

داخل صفحه کارتابل چه چیزهایی می‌بینید؟

• آمار: کل، در حال کارآموزی، آینده، پرتال فعال، پایان/غیرفعال
• کارت هر کارآموز: نام، بخش، موبایل، وضعیت دوره، نوع ورود، دسترسی دفتر روز
• بازه تاریخ شروع تا پایان
• دکمه‌ها: دسترسی/ویرایش، SMS خوش‌آمد، حذف
• میانبرها: کارآموز جدید، کارمند جدید، متن SMS

پرتال کارآموز چه امکاناتی دارد؟

• نمایش دوره کارآموزی و بخش
• آمار امروز: تعداد ثبت‌ها، تعداد خدمات، خدمات فعال
• ثبت خدمت انجام‌شده در دفتر روز (خدمت، تعداد، مدت، توضیح)
• مشاهده ثبت‌های امروز
• رفتن به دفتر روز کامل و پروفایل (در صورت دسترسی)

چه کسانی با آن کار می‌کنند؟

• مدیر: ثبت کارآموز، تعیین دوره، فعال‌سازی پرتال، دسترسی‌ها، ارسال SMS
• کارآموز: ورود به پرتال و ثبت کارهای روزانه
• کارمند/پذیرش/تعمیرکار: این کارتابل را مدیریت نمی‌کنند؛ کارتابل جداگانه خودشان را دارند

وضعیت‌های دوره کارآموز

• در حال کارآموزی: داخل بازه تاریخ و فعال
• آینده: هنوز شروع نشده
• پایان‌یافته: تاریخ تمام شده
• غیرفعال: توسط مدیر قطع شده

قفل‌ها و نکته‌های مهم فروش

• فقط مدیر (دسترسی کارمندان) این کارتابل را می‌بیند
• نام، موبایل و تاریخ شروع/پایان الزامی است
• بدون فعال‌سازی پرتال، کارآموز ورود ندارد
• بدون دسترسی «دفتر روزانه»، ثبت خدمت در پرتال ممکن نیست
• خدمات قابل ثبت را مدیر از «تنظیمات دفتر روز» تعریف می‌کند
• حذف کارآموز، ورود حساب مرتبط را قطع می‌کند
• این بخش با کارتابل کارمند هم‌خانواده است ولی مخصوص دوره کارآموزی است

تفاوت با بقیه کارتابل‌ها

• کارتابل کارآموز = ثبت دوره و پرتال محدود کارآموز
• کارتابل کارمند = مدیریت نیروهای رسمی و دسترسی کامل‌تر
• کارتابل ارجاع = جابه‌جایی دستگاه تعمیر
• کارتابل تأیید هزینه = موافقت مشتری با مبلغ
• کارتابل مشتری = پرتال خود مشتری

فایده برای صاحب تعمیرگاه

• ثبت رسمی و شفاف دوره کارآموزی
• کنترل دقیق دسترسی کارآموز
• پیگیری کار روزانه با دفتر روز
• ارسال خودکار/دستی پیامک تأیید دوره
• قطع آسان دسترسی در پایان دوره
• جدا نگه داشتن کارآموز از بخش‌های حساس سیستم

یک جمله خلاصه برای دمو:

«کارتابل کارآموز، میز ثبت دوره و پرتال محدود کارآموز است؛ بازه شروع و پایان را مشخص می‌کنید، ورود و دسترسی را فعال می‌کنید، پیامک تأیید می‌فرستید، و کارآموز کارهای روزانه‌اش را در دفتر روز ثبت می‌کند.»
TXT,
                'aparat_url' => '',
                'is_active' => true,
                'sort' => 4,
            ],
        ];
    }

    /**
     * @return list<array{slug:string,title:string,short:string,body:string,aparat_url:string,is_active:bool,sort:int}>
     */
    public static function repairShopGuides(bool $onlyActive = true): array
    {
        $saved = static::settings()['repair_guides'] ?? [];
        if (! is_array($saved)) {
            $saved = [];
        }
        $bySlug = [];
        foreach ($saved as $row) {
            if (! is_array($row)) {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug !== '') {
                $bySlug[$slug] = $row;
            }
        }

        $out = [];
        foreach (static::repairShopGuideDefaults() as $def) {
            $slug = $def['slug'];
            $ov = $bySlug[$slug] ?? [];
            $merged = array_merge($def, is_array($ov) ? $ov : []);
            $merged['slug'] = $slug;
            $merged['title'] = mb_substr(trim((string) ($merged['title'] ?? $def['title'])), 0, 120) ?: $def['title'];
            $merged['short'] = mb_substr(trim((string) ($merged['short'] ?? $def['short'])), 0, 255);
            $body = trim((string) ($merged['body'] ?? $def['body']));
            // اگر هنوز متن کوتاه/پلیس‌هولدر ادمین است، متن پیش‌فرض غنی‌تر را نشان بده
            if ($body === '' || static::isRepairGuideBodyStub($body)) {
                $body = $def['body'];
            }
            if ($merged['short'] === '' || static::isRepairGuideShortStub((string) $merged['short'], $slug)) {
                $merged['short'] = $def['short'];
            }
            $merged['body'] = $body;
            $merged['aparat_url'] = mb_substr(trim((string) ($merged['aparat_url'] ?? '')), 0, 500);
            $merged['is_active'] = array_key_exists('is_active', $merged)
                ? (bool) $merged['is_active']
                : true;
            $merged['sort'] = (int) ($merged['sort'] ?? $def['sort']);
            if ($onlyActive && ! $merged['is_active']) {
                continue;
            }
            $out[] = $merged;
        }

        usort($out, static fn ($a, $b) => ($a['sort'] <=> $b['sort']) ?: strcmp($a['slug'], $b['slug']));

        return $out;
    }

    public static function repairShopGuide(string $slug): ?array
    {
        foreach (static::repairShopGuides(false) as $g) {
            if ($g['slug'] === $slug) {
                return $g;
            }
        }

        return null;
    }

    /**
     * فهرست متنی «منوی کارکنان» — هر آیتم به صفحه آموزش لینک می‌شود
     *
     * @return list<array{num:string,slug:string,title:string,note?:string,guide?:string,short?:string,children?:list<array{slug:string,title:string,guide?:string,short?:string}>}>
     */
    public static function staffMenuTree(): array
    {
        return [
            [
                'num' => '۱',
                'slug' => 'desk',
                'title' => 'میز کار',
                'note' => 'برای کارآموز: پرتال کارآموز',
                'short' => 'ورود روزانه نیرو به کارتابل و میانبرهای کاری',
                'children' => [
                    ['slug' => 'trainee-portal', 'title' => 'پرتال کارآموز (پیش‌نمایش)', 'guide' => 'trainee', 'short' => 'ورود محدود کارآموز و ثبت دفتر روز'],
                ],
            ],
            [
                'num' => '۲',
                'slug' => 'reception',
                'title' => 'پذیرش',
                'short' => 'ثبت قبض، جستجو، لیست و تحویل گروهی',
                'children' => [
                    ['slug' => 'reception-new', 'title' => 'پذیرش جدید', 'short' => 'ساخت قبض پذیرش دستگاه'],
                    ['slug' => 'receipt-search', 'title' => 'جستجوی قبض', 'short' => 'پیدا کردن قبض با سریال، موبایل یا شماره'],
                    ['slug' => 'receipt-list', 'title' => 'لیست قبض‌ها', 'short' => 'مرور و فیلتر همه قبض‌های پذیرش'],
                    ['slug' => 'group-delivery', 'title' => 'تحویل گروهی', 'short' => 'تحویل چند دستگاه با هم'],
                ],
            ],
            [
                'num' => '۳',
                'slug' => 'referral-section',
                'title' => 'ارجاع / کارتابل تعمیر',
                'short' => 'جابه‌جایی دستگاه بین پذیرش و تعمیرکار',
                'children' => [
                    ['slug' => 'referral', 'title' => 'کارتابل ارجاع', 'guide' => 'referral', 'short' => 'تأیید دریافت، دست تعمیر، بازگشت'],
                    ['slug' => 'referral-report', 'title' => 'گزارش ارجاع / محل', 'short' => 'محل فعلی دستگاه و تاریخچه ارجاع'],
                ],
            ],
            [
                'num' => '۴',
                'slug' => 'notifications',
                'title' => 'اعلان‌ها',
                'short' => 'اعلان‌های داخلی کارتابل برای نیروها',
            ],
            [
                'num' => '۵',
                'slug' => 'daybook',
                'title' => 'دفتر روز',
                'short' => 'ثبت خدمات روزانه و گزارش کار روز',
                'children' => [
                    ['slug' => 'daybook-today', 'title' => 'ثبت امروز', 'short' => 'ثبت خدمت انجام‌شده در همان روز'],
                    ['slug' => 'daybook-report', 'title' => 'گزارش همه', 'short' => 'مرور ثبت‌های دفتر روز'],
                ],
            ],
            [
                'num' => '۶',
                'slug' => 'cost-section',
                'title' => 'تأیید هزینه',
                'short' => 'لینک تأیید مبلغ برای مشتری و خدمات مشمول',
                'children' => [
                    ['slug' => 'cost-approval', 'title' => 'کارتابل تأییدها', 'guide' => 'cost-approval', 'short' => 'ارسال لینک و پیگیری تأیید/رد مشتری'],
                    ['slug' => 'cost-services', 'title' => 'خدمات مشمول', 'short' => 'تعیین خدماتی که تأیید مشتری لازم دارند'],
                ],
            ],
            [
                'num' => '۷',
                'slug' => 'customers',
                'title' => 'مشتریان',
                'short' => 'فهرست و ثبت مشتری تعمیرگاه',
                'children' => [
                    ['slug' => 'customer-list', 'title' => 'فهرست مشتریان', 'short' => 'جستجو و مدیریت مشتریان'],
                    ['slug' => 'customer-new', 'title' => 'مشتری جدید', 'short' => 'ثبت مشتری با موبایل و مشخصات'],
                ],
            ],
            [
                'num' => '۸',
                'slug' => 'warehouse',
                'title' => 'انبار',
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
                'num' => '۹',
                'slug' => 'employees',
                'title' => 'کارمندان',
                'short' => 'نیرو، کارآموز، دسترسی و کمیسیون',
                'children' => [
                    ['slug' => 'staff', 'title' => 'کارتابل کارمند', 'guide' => 'staff', 'short' => 'تعریف نیرو، وظیفه و دسترسی'],
                    ['slug' => 'staff-new', 'title' => 'کارمند جدید', 'short' => 'ساخت حساب کارمند جدید'],
                    ['slug' => 'trainee', 'title' => 'کارتابل کارآموز', 'guide' => 'trainee', 'short' => 'ثبت دوره و پرتال کارآموز'],
                    ['slug' => 'trainee-new', 'title' => 'کارآموز جدید', 'short' => 'ثبت کارآموز و بازه دوره'],
                    ['slug' => 'trainee-portal-preview', 'title' => 'پرتال کارآموز (پیش‌نمایش)', 'guide' => 'trainee', 'short' => 'پیش‌نمایش ورود کارآموز'],
                    ['slug' => 'welcome-sms', 'title' => 'متن SMS خوش‌آمد', 'short' => 'قالب پیامک ورود نیرو'],
                    ['slug' => 'tech-commission', 'title' => 'تخصص و کمیسیون تعمیرکار', 'short' => 'تخصص و درصد کمیسیون'],
                    ['slug' => 'tech-new-price', 'title' => 'تعمیرکار جدید (قیمت)', 'short' => 'تعریف تعمیرکار با نرخ/قیمت'],
                ],
            ],
            [
                'num' => '۱۰',
                'slug' => 'sms',
                'title' => 'پیامک‌ها',
                'short' => 'گزارش و قالب پیامک قبض‌ها',
                'children' => [
                    ['slug' => 'sms-receipt-report', 'title' => 'گزارش پیامک قبض‌ها', 'short' => 'پیگیری پیامک‌های ارسال‌شده'],
                    ['slug' => 'sms-templates', 'title' => 'تعریف وضعیت / قالب', 'short' => 'قالب پیامک وضعیت‌ها'],
                ],
            ],
            [
                'num' => '۱۱',
                'slug' => 'accounting',
                'title' => 'حسابداری',
                'short' => 'اسناد، سرفصل، معین و بدهکاران',
                'children' => [
                    ['slug' => 'accounting-desk', 'title' => 'میز حسابداری', 'short' => 'نمای کلی حسابداری'],
                    ['slug' => 'journal', 'title' => 'اسناد روزنامه', 'short' => 'اسناد حسابداری روز'],
                    ['slug' => 'chart-of-accounts', 'title' => 'سرفصل حساب‌ها', 'short' => 'تعریف سرفصل‌ها'],
                    ['slug' => 'ledger', 'title' => 'دفتر معین', 'short' => 'دفتر معین حساب‌ها'],
                    ['slug' => 'trial-balance', 'title' => 'تراز آزمایشی', 'short' => 'تراز آزمایشی'],
                    ['slug' => 'debtors', 'title' => 'بدهکاران', 'short' => 'لیست بدهکاران'],
                    ['slug' => 'manual-voucher', 'title' => 'سند دستی', 'short' => 'ثبت سند دستی'],
                ],
            ],
            [
                'num' => '۱۲',
                'slug' => 'reports',
                'title' => 'گزارش‌ها',
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
                'num' => '۱۳',
                'slug' => 'system-tools',
                'title' => 'ابزارهای سیستم',
                'short' => 'نگهداری و پشتیبان‌گیری',
                'children' => [
                    ['slug' => 'backup', 'title' => 'نگهداری و بکاپ', 'short' => 'پشتیبان و نگهداری سیستم'],
                ],
            ],
            [
                'num' => '۱۴',
                'slug' => 'settings',
                'title' => 'تنظیمات',
                'short' => 'تنظیمات سیستم، دفتر روز و پروفایل',
                'children' => [
                    ['slug' => 'settings-system', 'title' => 'تنظیمات سیستم', 'short' => 'تنظیمات کلی سیستم'],
                    ['slug' => 'settings-daybook', 'title' => 'تنظیمات دفتر روز', 'short' => 'خدمات و تنظیمات دفتر روز'],
                    ['slug' => 'settings-profile', 'title' => 'پروفایل من', 'short' => 'پروفایل کاربر واردشده'],
                ],
            ],
        ];
    }

    /** سازگاری با نام قبلی */
    public static function staffMenuSample(): array
    {
        return static::staffMenuTree();
    }

    /**
     * فهرست متنی «منوی کارتابل مشتری / پرتال»
     *
     * @return list<array{num:string,slug:string,title:string,short?:string,guide?:string,children?:list<array{slug:string,title:string,guide?:string,short?:string}>}>
     */
    public static function customerMenuTree(): array
    {
        return [
            [
                'num' => '۱',
                'slug' => 'customer-track',
                'title' => 'پیگیری سفارش / وضعیت قبض',
                'short' => 'مشتری وضعیت تعمیر را از پرتال می‌بیند',
            ],
            [
                'num' => '۲',
                'slug' => 'customer-cost-link',
                'title' => 'تأیید هزینه (لینک مشتری)',
                'short' => 'مشاهده مبلغ و تأیید یا رد هزینه',
                'guide' => 'cost-approval',
            ],
            [
                'num' => '۳',
                'slug' => 'customer-pay',
                'title' => 'پرداخت',
                'short' => 'پرداخت آنلاین یا ثبت فیش از سمت مشتری',
            ],
            [
                'num' => '۴',
                'slug' => 'customer-warranty',
                'title' => 'گارانتی / وضعیت گارانتی',
                'short' => 'پیگیری گارانتی دستگاه',
            ],
            [
                'num' => '۵',
                'slug' => 'customer-messages',
                'title' => 'پیام‌ها',
                'short' => 'پیام بین مشتری و تعمیرگاه',
            ],
            [
                'num' => '۶',
                'slug' => 'customer-profile',
                'title' => 'پروفایل مشتری',
                'short' => 'مشخصات و موبایل مشتری در پرتال',
            ],
        ];
    }

    /**
     * پیدا کردن یک آیتم منو (کارکنان یا مشتری) با slug
     *
     * @return array{menu:string,slug:string,title:string,short:string,guide?:string,note?:string,children?:list<array{slug:string,title:string,guide?:string,short?:string}>,parent?:array{slug:string,title:string}}|null
     */
    public static function findMenuItem(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        foreach (['staff' => static::staffMenuTree(), 'customer' => static::customerMenuTree()] as $menu => $tree) {
            foreach ($tree as $section) {
                if (($section['slug'] ?? '') === $slug) {
                    return array_merge($section, [
                        'menu' => $menu,
                        'short' => (string) ($section['short'] ?? $section['title']),
                    ]);
                }
                foreach ($section['children'] ?? [] as $child) {
                    if (($child['slug'] ?? '') === $slug) {
                        return array_merge($child, [
                            'menu' => $menu,
                            'short' => (string) ($child['short'] ?? $child['title']),
                            'parent' => [
                                'slug' => (string) $section['slug'],
                                'title' => (string) $section['title'],
                            ],
                        ]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * ساخت محتوای صفحه آموزش برای آیتم منو (اگر راهنمای کامل جدا نداشته باشد)
     *
     * @param  array<string,mixed>  $item
     * @return array{title:string,short:string,body:string,aparat_url:string,is_ready:bool,children:list<array{slug:string,title:string,guide?:string,short?:string}>,menu:string,parent?:array{slug:string,title:string}}
     */
    public static function buildMenuItemPage(array $item): array
    {
        $title = (string) ($item['title'] ?? 'آموزش منو');
        $short = (string) ($item['short'] ?? $title);
        $children = is_array($item['children'] ?? null) ? $item['children'] : [];
        $menu = (string) ($item['menu'] ?? 'staff');
        $menuLabel = $menu === 'customer' ? 'منوی کارتابل مشتری' : 'منوی کارکنان';

        if ($children !== []) {
            $lines = [
                $title.' چیست؟',
                '',
                $short.'. این بخش از '.$menuLabel.' شامل زیرمنوهای زیر است؛ روی هر کدام بزنید تا توضیح و ویدیوی آموزشی همان آیتم را ببینید.',
                '',
                'زیرمنوهای این بخش',
                '',
            ];
            foreach ($children as $c) {
                $lines[] = '• '.($c['title'] ?? '');
            }
            $lines[] = '';
            $lines[] = 'متن کامل و لینک آپارات هر زیرمنو به‌تدریج تکمیل می‌شود؛ آیتم‌های آماده‌شده از قبل صفحه کامل دارند.';
            $body = implode("\n", $lines);
        } else {
            $body = implode("\n", [
                $title.' چیست؟',
                '',
                $short.'. این آیتم بخشی از '.$menuLabel.' در سایت مدیریت تعمیرکاران است.',
                '',
                'هدف این صفحه',
                '',
                'خریدار و تیم فروش دقیقاً ببینند هر منوی سیستم چه کاری می‌کند؛ ویدیوی آپارات و متن کامل آموزشی از ادمین همین بخش قابل تکمیل است.',
                '',
                'یک جمله خلاصه برای دمو:',
                '',
                '«'.$title.' یکی از آیتم‌های '.$menuLabel.' است؛ از فهرست منو باز می‌شود و آموزش متنی + ویدیو برای همان بخش نمایش داده می‌شود.»',
            ]);
        }

        return [
            'title' => $title,
            'short' => $short,
            'body' => $body,
            'aparat_url' => '',
            'is_ready' => false,
            'children' => $children,
            'menu' => $menu,
            'parent' => $item['parent'] ?? null,
        ];
    }

    /** آیا متن ذخیره‌شده هنوز پلیس‌هولدر کوتاه است؟ */
    public static function isRepairGuideBodyStub(string $body): bool
    {
        $markers = [
            'توضیحات کامل‌تر و ویدیوی آموزشی را می‌توانید از تنظیمات ادمین',
            'متن کامل و لینک آپارات را از ادمین تنظیم کنید',
            'کارتابل تأیید هزینه برای بررسی و تأیید هزینه‌های جراحی، بازیابی و موارد مرتبط است',
            'توضیح کامل‌تر را در ادمین بنویسید و لینک آپارات را اضافه کنید',
            'کارتابل کارمند محل مدیریت کارمندان، نقش‌ها و دسترسی‌های سیستم است',
            'جزئیات و ویدیو را از تنظیمات ادمین تکمیل کنید',
            'کارتابل کارآموز برای مدیریت کارآموزان و پرتال ورود آن‌هاست',
            'کارتابل ارجاع هسته گردش دستگاه بین پذیرش و تعمیرکار است',
        ];
        foreach ($markers as $m) {
            if (mb_strpos($body, $m) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function isRepairGuideShortStub(string $short, string $slug): bool
    {
        $legacy = [
            'referral' => 'دریافت/بازگشت دستگاه و دست تعمیر',
            'cost-approval' => 'تأیید جراحی/بازیابی و لینک‌ها',
            'staff' => 'کارمندان، نقش و دسترسی',
            'trainee' => 'کارآموزان و پرتال ورود',
        ];

        return isset($legacy[$slug]) && $short === $legacy[$slug];
    }

    /**
     * پارس متن راهنما به بلوک‌های قابل رندر (عنوان، پاراگراف، لیست، نقل‌قول)
     *
     * @return list<array{type:string,text?:string,items?:list<string>}>
     */
    public static function repairGuideBodyBlocks(string $body): array
    {
        $body = str_replace(["\r\n", "\r"], "\n", trim($body));
        if ($body === '') {
            return [];
        }

        $chunks = preg_split("/\n{2,}/u", $body) ?: [];
        $blocks = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $lines = preg_split("/\n/u", $chunk) ?: [];
            $lines = array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== ''));
            if ($lines === []) {
                continue;
            }

            $isBullet = static fn (string $l): bool => (bool) preg_match('/^[•\-]\s*/u', $l);
            $isNumbered = static fn (string $l): bool => (bool) preg_match('/^\d+[\.\)]\s+/u', $l);

            if (count(array_filter($lines, $isBullet)) === count($lines)) {
                $blocks[] = [
                    'type' => 'ul',
                    'items' => array_map(static fn ($l) => preg_replace('/^[•\-]\s*/u', '', $l) ?? $l, $lines),
                ];
                continue;
            }

            if (count(array_filter($lines, $isNumbered)) === count($lines)) {
                $blocks[] = [
                    'type' => 'ol',
                    'items' => array_map(static fn ($l) => preg_replace('/^\d+[\.\)]\s+/u', '', $l) ?? $l, $lines),
                ];
                continue;
            }

            $listStart = null;
            foreach ($lines as $i => $l) {
                if ($isBullet($l) || $isNumbered($l)) {
                    $listStart = $i;
                    break;
                }
            }
            if ($listStart !== null && $listStart > 0) {
                $intro = implode(' ', array_slice($lines, 0, $listStart));
                if ($intro !== '') {
                    if (static::looksLikeGuideHeading($intro)) {
                        $blocks[] = ['type' => 'h2', 'text' => $intro];
                    } else {
                        $blocks[] = ['type' => 'p', 'text' => $intro];
                    }
                }
                $rest = array_slice($lines, $listStart);
                $ol = $isNumbered($rest[0]);
                $blocks[] = [
                    'type' => $ol ? 'ol' : 'ul',
                    'items' => array_map(
                        static fn ($l) => $ol
                            ? (preg_replace('/^\d+[\.\)]\s+/u', '', $l) ?? $l)
                            : (preg_replace('/^[•\-]\s*/u', '', $l) ?? $l),
                        $rest
                    ),
                ];
                continue;
            }

            if (count($lines) === 1) {
                $line = $lines[0];
                if (preg_match('/^[«"“]/u', $line)) {
                    $blocks[] = ['type' => 'quote', 'text' => $line];
                    continue;
                }
                if (static::looksLikeGuideHeading($line)) {
                    $blocks[] = ['type' => 'h2', 'text' => $line];
                    continue;
                }
            }

            $blocks[] = ['type' => 'p', 'text' => implode("\n", $lines)];
        }

        return $blocks;
    }

    protected static function looksLikeGuideHeading(string $line): bool
    {
        $line = trim($line);
        if ($line === '' || mb_strlen($line) > 90) {
            return false;
        }
        if (preg_match('/[؟?]$/u', $line)) {
            return true;
        }
        if (preg_match('/^[«"“]/u', $line)) {
            return false;
        }
        // جملهٔ مقدماتی فهرست (مثل «سیستم جلوی این‌ها را می‌گیرد:») عنوان نیست
        if (preg_match('/[:：]$/u', $line)) {
            return false;
        }
        // عنوان‌های کوتاه بدون نقطهٔ پایانی جمله
        if (! preg_match('/[۔.]$/u', $line) && ! str_contains($line, '؛')) {
            return true;
        }

        return false;
    }

    /** تبدیل لینک آپارات به آدرس embed */
    public static function aparatEmbedUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#aparat\.com/video/video/embed/videohash/([A-Za-z0-9]+)#i', $url, $m)) {
            return 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame';
        }
        if (preg_match('#aparat\.com/v/([A-Za-z0-9]+)#i', $url, $m)) {
            return 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame';
        }
        if (preg_match('#aparat\.com/video/([A-Za-z0-9]+)#i', $url, $m)) {
            return 'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame';
        }

        return '';
    }

    /**
     * @param  array<string,mixed>  $input
     * @return list<array{slug:string,title:string,short:string,body:string,aparat_url:string,is_active:bool,sort:int}>
     */
    public static function sanitizeRepairGuidesInput(array $input): array
    {
        $allowed = [];
        foreach (static::repairShopGuideDefaults() as $def) {
            $allowed[$def['slug']] = $def;
        }
        $out = [];
        foreach ($allowed as $slug => $def) {
            $row = $input[$slug] ?? null;
            if (! is_array($row)) {
                // also accept list form
                foreach ($input as $maybe) {
                    if (is_array($maybe) && ($maybe['slug'] ?? '') === $slug) {
                        $row = $maybe;
                        break;
                    }
                }
            }
            if (! is_array($row)) {
                $row = [];
            }
            $enabledRaw = $row['is_active'] ?? 1;
            if (is_array($enabledRaw)) {
                $enabledRaw = end($enabledRaw);
            }
            $out[] = [
                'slug' => $slug,
                'title' => mb_substr(trim((string) ($row['title'] ?? $def['title'])), 0, 120) ?: $def['title'],
                'short' => mb_substr(trim((string) ($row['short'] ?? $def['short'])), 0, 255),
                'body' => mb_substr(trim((string) ($row['body'] ?? $def['body'])), 0, 20000),
                'aparat_url' => mb_substr(trim((string) ($row['aparat_url'] ?? '')), 0, 500),
                'is_active' => in_array((string) $enabledRaw, ['1', 'true', 'on', 'yes'], true),
                'sort' => max(1, min(99, (int) ($row['sort'] ?? $def['sort']))),
            ];
        }

        return $out;
    }

    /**
     * کاتالوگ فروش انواع سایت — منوی متنی + صفحات پیشرفته
     *
     * @return array<int, array{slug:string,title:string,short:string,tagline:string,features:array<int,string>}>
     */
    public static function websiteSalesCatalog(): array
    {
        return [
            [
                'slug' => 'repair-shop',
                'title' => 'سایت مدیریت تعمیرکاران',
                'short' => 'نرم‌افزار تعمیرگاه + آموزش + سئو',
                'tagline' => 'مدیریت تعمیرکاران چندلایه: پروفایل و کمیسیون، گردش ارجاع دستگاه، گزارش عملکرد و اتصال به قبض — نه فقط یک لیست نام.',
                'features' => [],
                'detail_intro' => 'مدیریت تعمیرکاران در این سیستم چند لایه است؛ فقط لیست نام نیست.',
                'detail_summary' => 'مدیریت تعمیرکاران = پروفایل + کمیسیون + ورود، به‌اضافه گردش کامل ارجاع/تأیید/گزارش‌کار/بازگشت، و گزارش عملکرد با کمیسیون و موجودی دست تعمیر.',
                'has_guides' => true,
                'guides_heading' => '۴ کارتابل اصلی مدیریت',
                'guides_sub' => 'روی هر کارتابل بزنید تا توضیح کامل و ویدیوی آموزشی را ببینید. لینک آپارات از ادمین تنظیم می‌شود.',
                'sections' => [
                    [
                        'title' => '۱) ثبت و پروفایل تعمیرکار',
                        'items' => [
                            'ثبت نام، موبایل، تخصص (هارد، بازیابی، لپ‌تاپ…)',
                            'درصد کمیسیون (۰ تا ۱۰۰)',
                            'فعال / غیرفعال',
                            'اختیاری: ساخت حساب ورود (ایمیل + رمز، نقش technician)',
                            'مدیریت از منوی تخصص و کمیسیون تعمیرکار',
                            'مدیریت از فرم کارمندان وقتی نقش = تعمیرکار',
                        ],
                    ],
                    [
                        'title' => '۲) کارتابل و گردش دستگاه (هسته اصلی)',
                        'lead' => 'زنجیره اجباری — بدون گزارش کار و تأیید ارجاع، هزینه و تحویل قفل است.',
                        'steps' => [
                            'پذیرش → ارجاع به تعمیرکار',
                            'تعمیرکار تأیید دریافت می‌زند',
                            'دستگاه می‌رود حالت دست تعمیر (with_technician)',
                            'تعمیرکار گزارش کار ثبت می‌کند (اجباری)',
                            'تعمیرکار ارجاع بازگشت به پذیرش می‌زند',
                            'پذیرش/حسابدار تأیید بازگشت می‌کند',
                            'بعد از آن اعلام هزینه / تحویل آزاد می‌شود',
                        ],
                    ],
                    [
                        'title' => '۳) کارهایی که خود تعمیرکار می‌تواند بکند',
                        'lead' => 'با دسترسی پیش‌فرض نقش تعمیرکار:',
                        'items' => [
                            'میز کار، کارتابل ارجاع، اعلان‌ها، دفتر روز، قطعات',
                            'دیدن قبض‌های مربوط / دست خودش',
                            'ثبت گزارش کار روی دستگاهی که نزد اوست',
                            'درخواست بازگشت دستگاه',
                            'گزارش محل دستگاه و تا حدی گزارش عملکرد',
                        ],
                    ],
                    [
                        'title' => '۴) گزارش عملکرد تعمیرکاران',
                        'items' => [
                            'لیست همه تعمیرکاران با فیلتر بازه تاریخ و جستجو (نام/تخصص/موبایل)',
                            'برای هر نفر: تعداد کار، تحویل‌شده، دست تعمیر الان، جمع اجرت، کمیسیون٪، مبلغ کمیسیون',
                            'نمودار تعداد کار و اجرت',
                            'پرونده تکی: لیست کارهای دوره، دستگاه‌های الان دست او، قطعات مصرف‌شده',
                            'آمار ارجاع تأیید/رد، میانگین روز تعمیر، نمودار وضعیت و پذیرش روزانه',
                        ],
                    ],
                    [
                        'title' => '۵) اتصال به قبض و فاکتور',
                        'items' => [
                            'انتساب تعمیرکار روی قبض',
                            'یادداشت تعمیرکار',
                            'نمایش نام تعمیرکار روی فاکتور چاپ (قابل خاموش/روشن در تنظیمات)',
                            'گزارش ارجاع / محل دستگاه (custody) برای پیگیری «دست کیست»',
                        ],
                    ],
                    [
                        'title' => '۶) آنچه ندارد / محدودیت',
                        'items' => [
                            'حذف کامل تعمیرکار از منوی اصلی نیست (ویرایش/غیرفعال؛ با حذف کارمند، پروفایل برای سابقه می‌ماند ولی لاگین قطع می‌شود)',
                            'کمیسیون فقط روی اجرت کارهای تحویل‌شده حساب می‌شود، نه پرداخت حقوق خودکار بانکی',
                            'مدیریت جداگانه شیفت/مرخصی/حقوق پایه جدا از کمیسیون در این ماژول دیده نمی‌شود',
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'online-store',
                'title' => 'سایت فروشگاهی',
                'short' => 'فروش آنلاین کالا',
                'tagline' => 'فروشگاه اینترنتی با سبد خرید، درگاه پرداخت و مدیریت محصولات',
                'features' => [
                    'کاتالوگ و دسته‌بندی محصول',
                    'سبد خرید و پرداخت آنلاین',
                    'مدیریت موجودی و سفارش',
                    'کد تخفیف و کمپین',
                    'سئوی صفحات محصول',
                ],
            ],
            [
                'slug' => 'corporate',
                'title' => 'سایت شرکتی',
                'short' => 'معرفی شرکت و خدمات',
                'tagline' => 'ویترین حرفه‌ای برند، خدمات و اعتمادسازی برای مشتریان سازمانی',
                'features' => [
                    'صفحات درباره ما و خدمات',
                    'نمونه کار و پروژه‌ها',
                    'فرم استعلام و تماس',
                    'بلاگ و اخبار شرکت',
                    'بهینه‌سازی سئو سازمانی',
                ],
            ],
            [
                'slug' => 'booking',
                'title' => 'سایت خدماتی / نوبت‌دهی',
                'short' => 'رزرو آنلاین خدمات',
                'tagline' => 'نوبت‌دهی آنلاین برای کلینیک، آموزشگاه، خدمات حضوری و مشابه',
                'features' => [
                    'تقویم نوبت و ظرفیت',
                    'ثبت‌نام و یادآوری پیامکی',
                    'پروفایل خدمات و قیمت',
                    'پنل اپراتور نوبت',
                    'صفحات آموزشی سئو‌شده',
                ],
            ],
        ];
    }

    /** منوی متنی «طراحی و فروش سایت» را می‌سازد/به‌روز می‌کند (بدون تبدیل به مگا) */
    public static function syncWebsiteSalesMenu(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                return;
            }

            $parent = MegaMenuItem::query()
                ->whereNull('parent_id')
                ->where(function ($q) {
                    $q->where('css_class', 'like', '%mm-web-sales%')
                        ->orWhere('title', 'طراحی و فروش سایت')
                        ->orWhere('title', 'طراحی سایت');
                })
                ->orderByDesc('id')
                ->first();

            $parentAttrs = [
                'title' => 'طراحی و فروش سایت',
                'type' => 'link',
                'url' => '/sites',
                'is_mega' => false,
                'is_active' => true,
                'open_in_new' => false,
                'description' => 'انواع سایت آماده برای فروش',
                'css_class' => 'mm-web-sales',
                'animation' => 'fade',
                'effect' => 'shadow',
                'panel_width' => 'normal',
                'updated_at' => now(),
            ];

            if (! $parent) {
                $maxSort = (int) MegaMenuItem::query()->whereNull('parent_id')->max('sort_order');
                $parent = MegaMenuItem::query()->create(array_merge($parentAttrs, [
                    'sort_order' => $maxSort + 1,
                    'created_at' => now(),
                ]));
            } else {
                $parent->fill($parentAttrs);
                $parent->save();
            }

            $keepIds = [];
            foreach (static::websiteSalesCatalog() as $i => $item) {
                $child = MegaMenuItem::query()
                    ->where('parent_id', $parent->id)
                    ->where(function ($q) use ($item) {
                        $q->where('url', '/sites/'.$item['slug'])
                            ->orWhere('title', $item['title']);
                    })
                    ->first();

                $attrs = [
                    'parent_id' => $parent->id,
                    'title' => $item['title'],
                    'type' => 'link',
                    'url' => '/sites/'.$item['slug'],
                    'description' => $item['short'],
                    'is_mega' => false,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                    'css_class' => 'mm-web-sales-item',
                    'updated_at' => now(),
                ];

                if (! $child) {
                    $child = MegaMenuItem::query()->create(array_merge($attrs, ['created_at' => now()]));
                } else {
                    $child->fill($attrs);
                    $child->save();
                }
                $keepIds[] = (int) $child->id;
            }

            // زیر‌آیتم‌های قدیمی همین والد که دیگر در کاتالوگ نیستند (placeholder و …) را غیرفعال کن
            MegaMenuItem::query()
                ->where('parent_id', $parent->id)
                ->whereNotIn('id', $keepIds)
                ->update(['is_active' => false, 'updated_at' => now()]);
        } catch (\Throwable) {
            //
        }
    }

    public const RECEIPT_PORTAL_URL = 'https://support.hdd-land.ir';

    /** لینک قدیمی منو /orders/track را نگه می‌داریم؛ فقط مسیر خالی را درست می‌کنیم */
    public static function fixLegacyTrackUrls(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                return;
            }
            // اگر آیتم پیگیری با URL اشتباه/خالی بود — قبض به پرتال جدا می‌رود
            \Illuminate\Support\Facades\DB::table('mega_menu_items')
                ->where('title', 'like', '%پیگیری%')
                ->where('title', 'not like', '%قبض%')
                ->where(function ($q) {
                    $q->whereNull('url')
                        ->orWhere('url', '')
                        ->orWhere('url', '/order/track')
                        ->orWhere('url', '/track-order')
                        ->orWhere('url', 'track-order');
                })
                ->update(['url' => '/orders/track', 'updated_at' => now()]);
        } catch (\Throwable) {
            //
        }
    }

    /** منوی «پیگیری قبض» به سایت قبض support.hdd-land.ir */
    public static function syncReceiptPortalUrl(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                return;
            }
            $url = self::RECEIPT_PORTAL_URL;
            \Illuminate\Support\Facades\DB::table('mega_menu_items')
                ->where('title', 'like', '%قبض%')
                ->where(function ($q) use ($url) {
                    $q->whereNull('url')
                        ->orWhere('url', '!=', $url);
                })
                ->update(['url' => $url, 'updated_at' => now()]);
        } catch (\Throwable) {
            //
        }
    }

    public static function ensureSchema(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                Schema::create('mega_menu_items', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('parent_id')->nullable()->index();
                    $table->string('title');
                    $table->string('type', 30)->default('link');
                    $table->string('url')->nullable();
                    $table->unsignedBigInteger('category_id')->nullable();
                    $table->string('badge')->nullable();
                    $table->string('icon', 80)->nullable();
                    $table->unsignedTinyInteger('columns')->default(3);
                    $table->text('html')->nullable();
                    $table->boolean('is_mega')->default(false);
                    $table->boolean('open_in_new')->default(false);
                    $table->boolean('is_active')->default(true);
                    $table->integer('sort_order')->default(0);
                    $table->timestamps();
                });
            }

            foreach ([
                'image_url' => fn ($t) => $t->string('image_url', 500)->nullable(),
                'bg_image_url' => fn ($t) => $t->string('bg_image_url', 500)->nullable(),
                'description' => fn ($t) => $t->string('description', 255)->nullable(),
                'animation' => fn ($t) => $t->string('animation', 30)->default('fade'),
                'effect' => fn ($t) => $t->string('effect', 30)->default('shadow'),
                'panel_width' => fn ($t) => $t->string('panel_width', 20)->default('wide'),
                'show_search' => fn ($t) => $t->boolean('show_search')->default(false),
                'search_placeholder' => fn ($t) => $t->string('search_placeholder', 120)->nullable(),
                'is_tabbed' => fn ($t) => $t->boolean('is_tabbed')->default(false),
                'tab_label' => fn ($t) => $t->string('tab_label', 80)->nullable(),
                'form_type' => fn ($t) => $t->string('form_type', 30)->default('none'),
                'form_html' => fn ($t) => $t->text('form_html')->nullable(),
                'accent_color' => fn ($t) => $t->string('accent_color', 20)->nullable(),
                'css_class' => fn ($t) => $t->string('css_class', 80)->nullable(),
                'icon_image_url' => fn ($t) => $t->string('icon_image_url', 500)->nullable(),
                'font_family' => fn ($t) => $t->string('font_family', 80)->nullable(),
                'title_color' => fn ($t) => $t->string('title_color', 20)->nullable(),
                'link_color' => fn ($t) => $t->string('link_color', 20)->nullable(),
                'hover_color' => fn ($t) => $t->string('hover_color', 20)->nullable(),
                'text_color' => fn ($t) => $t->string('text_color', 20)->nullable(),
                'panel_bg_color' => fn ($t) => $t->string('panel_bg_color', 20)->nullable(),
                'panel_radius' => fn ($t) => $t->unsignedTinyInteger('panel_radius')->default(18),
                'icon_size' => fn ($t) => $t->unsignedTinyInteger('icon_size')->default(18),
                'open_mode' => fn ($t) => $t->string('open_mode', 20)->default('hover'),
                'panel_align' => fn ($t) => $t->string('panel_align', 20)->default('right'),
            ] as $column => $callback) {
                if (! Schema::hasColumn('mega_menu_items', $column)) {
                    Schema::table('mega_menu_items', function ($table) use ($callback) {
                        $callback($table);
                    });
                }
            }
        } catch (\Throwable) {
            //
        }
    }

    public static function seedDefaultsIfEmpty(): void
    {
        static::ensureSchema();
        if (! Schema::hasTable('mega_menu_items')) {
            return;
        }
        if (MegaMenuItem::query()->exists()) {
            return;
        }

        MegaMenuItem::query()->create([
            'title' => 'خانه', 'type' => 'link', 'url' => '/', 'icon' => '🏠', 'sort_order' => 1, 'is_active' => true,
        ]);
        MegaMenuItem::query()->create([
            'title' => 'محصولات', 'type' => 'link', 'url' => '/products', 'icon' => '💾', 'sort_order' => 2, 'is_active' => true,
        ]);

        $mega = MegaMenuItem::query()->create([
            'title' => 'دسته‌بندی‌ها',
            'type' => 'link',
            'url' => '/products',
            'icon' => '🗂️',
            'is_mega' => true,
            'columns' => 3,
            'animation' => 'slide',
            'effect' => 'shadow',
            'panel_width' => 'wide',
            'show_search' => true,
            'search_placeholder' => 'جستجوی محصول در منو...',
            'is_tabbed' => false,
            'sort_order' => 3,
            'is_active' => true,
            'accent_color' => '#e23d12',
        ]);

        if (Schema::hasTable('categories')) {
            $cats = DB::table('categories')->whereNull('parent_id')->where('is_active', 1)->orderBy('sort_order')->limit(6)->get();
            $i = 1;
            foreach ($cats as $cat) {
                MegaMenuItem::query()->create([
                    'parent_id' => $mega->id,
                    'title' => $cat->name,
                    'type' => 'category',
                    'category_id' => $cat->id,
                    'url' => '/category/'.$cat->slug,
                    'icon' => '▸',
                    'sort_order' => $i++,
                    'is_active' => true,
                ]);
            }
        }

        MegaMenuItem::query()->create([
            'parent_id' => $mega->id,
            'title' => 'پرومو ویژه',
            'type' => 'promo',
            'url' => '/products',
            'description' => 'پیشنهادهای امروز ذخیره‌سازی',
            'badge' => 'ویژه',
            'sort_order' => 90,
            'is_active' => true,
            'html' => '',
        ]);

        MegaMenuItem::query()->create([
            'title' => 'پیگیری سفارش', 'type' => 'link', 'url' => '/orders/track', 'icon' => '📦', 'sort_order' => 4, 'is_active' => true,
        ]);
        MegaMenuItem::query()->create([
            'title' => 'تماس', 'type' => 'link', 'url' => '/contact', 'icon' => '☎', 'sort_order' => 5, 'is_active' => true,
        ]);
    }

    /** @return array<string, string> */
    public static function fonts(): array
    {
        return [
            'Vazirmatn' => 'وزیرمتن (پیش‌فرض سایت)',
            'Estedad' => 'استعداد',
            'IRANSansX' => 'ایران‌سنس X',
            'Noto Sans Arabic' => 'Noto Sans Arabic',
            'Cairo' => 'Cairo',
            'Tajawal' => 'Tajawal',
            'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic',
            'Tahoma' => 'Tahoma',
            '' => 'پیش‌فرض قالب سایت',
        ];
    }

    /** @return array<string, string> */
    public static function panelWidthModes(): array
    {
        return [
            'shell' => 'داخل کادر سایت (تمام عرض نوار منو)',
            'item' => 'زیر همان آیتم منو',
        ];
    }

    /** @return array<int, string> */
    public static function iconPresets(): array
    {
        return ['🏠', '💾', '🗂️', '🔥', '⭐', '🛒', '📦', '☎', '💡', '🎯', '🛠', '💻', '📱', '🔧', '🎁', '⚡', '🆕', '✔'];
    }

    /** @return array<string, string> */
    public static function openModes(): array
    {
        return [
            'hover' => 'با هاور موس',
            'click' => 'با کلیک',
        ];
    }

    /** @return array<string, string> */
    public static function panelAligns(): array
    {
        return [
            'right' => 'راست (RTL)',
            'left' => 'چپ',
            'center' => 'وسط',
        ];
    }

    /** @return array<string, string> */
    public static function animations(): array
    {
        return [
            'none' => 'بدون انیمیشن',
            'fade' => 'محو شدن',
            'slide' => 'کشویی از بالا',
            'slide-up' => 'کشویی از پایین',
            'zoom' => 'زوم',
            'flip' => 'فلیپ',
            'bounce' => 'پرش نرم',
        ];
    }

    /** @return array<string, string> */
    public static function effects(): array
    {
        return [
            'none' => 'ساده',
            'shadow' => 'سایه',
            'glass' => 'شیشه‌ای',
            'border' => 'حاشیه برجسته',
            'lift' => 'بلند شدن',
            'glow' => 'درخشش برند',
        ];
    }
}
