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
        return '3.4.1';
    }

    public const SETTINGS_KEY = 'mega_menu_settings';

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        $raw = \App\Models\Setting::getValue(self::SETTINGS_KEY, null);
        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true) ?: [];
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        return array_merge([
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
            'panel_fx' => 'soft',
            'panel_bg' => 'white',
            'org_promo_enabled' => true,
            'org_promo_title' => 'پیشنهاد سازمانی',
            'org_promo_desc' => 'تأمین هارد و SSD برای کسب‌وکارها با گارانتی شفاف',
            'org_promo_button' => 'مشاهده',
            'org_promo_url' => '/products',
            'org_promo_image' => '/images/home/mega-promo.jpg',
        ], is_array($decoded) ? $decoded : []);
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

        $payload = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \RuntimeException('رمزگذاری تنظیمات مگامنو ناموفق بود.');
        }
        // ستون value از نوع text است؛ JSON رشته‌ای پایدارتر از پاس‌دادن آرایه است
        \App\Models\Setting::setValue(self::SETTINGS_KEY, $payload);

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
        if (! Cache::get('mega_menu_schema_341')) {
            static::ensureSchema();
            static::fixLegacyTrackUrls();
            Cache::put('mega_menu_schema_341', true, now()->addDay());
        }
        parent::boot();
    }

    /** لینک قدیمی منو /orders/track را نگه می‌داریم؛ فقط مسیر خالی را درست می‌کنیم */
    public static function fixLegacyTrackUrls(): void
    {
        try {
            if (! Schema::hasTable('mega_menu_items')) {
                return;
            }
            // اگر آیتم پیگیری با URL اشتباه/خالی بود
            \Illuminate\Support\Facades\DB::table('mega_menu_items')
                ->where('title', 'like', '%پیگیری%')
                ->where(function ($q) {
                    $q->whereNull('url')->orWhere('url', '')->orWhere('url', '/order/track');
                })
                ->update(['url' => '/orders/track', 'updated_at' => now()]);
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
            'title' => 'پیگیری سفارش', 'type' => 'link', 'url' => '/track-order', 'icon' => '📦', 'sort_order' => 4, 'is_active' => true,
        ]);
        MegaMenuItem::query()->create([
            'title' => 'تماس', 'type' => 'link', 'url' => '/contact', 'icon' => '☎', 'sort_order' => 5, 'is_active' => true,
        ]);
    }

    /** @return array<string, string> */
    public static function fonts(): array
    {
        return [
            '' => 'پیش‌فرض سایت (Vazirmatn)',
            'Vazirmatn' => 'وزیرمتن',
            'Noto Sans Arabic' => 'Noto Sans Arabic',
            'Cairo' => 'Cairo',
            'Tajawal' => 'Tajawal',
            'IBM Plex Sans Arabic' => 'IBM Plex Sans Arabic',
            'Tahoma' => 'Tahoma',
            'Arial' => 'Arial',
            'Tahoma, Arial, sans-serif' => 'سیستمی',
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
