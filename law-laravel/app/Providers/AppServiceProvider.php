<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Observers\AppointmentObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        Appointment::observe(AppointmentObserver::class);

        Blade::directive('jalali', function ($expression) {
            return "<?php echo \\App\\Support\\Jalali::format($expression); ?>";
        });

        Blade::directive('jalaliDateTime', function ($expression) {
            return "<?php echo \\App\\Support\\Jalali::formatDateTime($expression); ?>";
        });

        \Illuminate\Support\Facades\View::composer(['layouts.site', 'layouts.app', 'site.home', 'app.index'], function ($view) {
            $keys = [
                'site_name', 'site_tagline', 'phone', 'mobile', 'email', 'address', 'hours',
                'about_title', 'about_text', 'hero_lead',
                'footer_about', 'footer_copyright', 'footer_disclaimer',
                'social_instagram', 'social_linkedin', 'social_whatsapp',
                'cta_text', 'show_phone_in_header',
                'pwa_enabled', 'pwa_auto_mobile', 'pwa_name', 'pwa_short_name',
                'pwa_description', 'pwa_theme_color', 'pwa_bg_color', 'pwa_start_url',
                'app_banner_size', 'app_banner_height', 'app_banner_position', 'app_banner_show_lead',
                'asset_version',
            ];
            $siteSettings = [];
            try {
                foreach ($keys as $key) {
                    $siteSettings[$key] = \App\Models\Setting::get($key, match ($key) {
                        'pwa_enabled', 'pwa_auto_mobile', 'show_phone_in_header', 'app_banner_show_lead' => '1',
                        'pwa_short_name' => 'آریان',
                        'pwa_theme_color', 'pwa_bg_color' => '#0a1628',
                        'pwa_start_url' => '/app',
                        'app_banner_size' => 'medium',
                        'app_banner_height' => '42',
                        'app_banner_position' => 'center 35%',
                        'asset_version' => '12',
                        default => '',
                    });
                }
            } catch (\Throwable) {
                $siteSettings = array_fill_keys($keys, '');
                $siteSettings['asset_version'] = '12';
            }

            $headerMenus = collect();
            $footerMenus = collect();
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('menus')) {
                    $headerMenus = \App\Models\Menu::query()->active()->location('header')->orderBy('sort_order')->orderBy('id')->get();
                    $footerMenus = \App\Models\Menu::query()->active()->location('footer')->orderBy('sort_order')->orderBy('id')->get();
                }
            } catch (\Throwable) {
                // installer / pre-migrate
            }

            $view->with([
                'settings' => [
                    'site_name' => $siteSettings['site_name'] ?: 'مؤسسه حقوقی آریان',
                    'site_tagline' => $siteSettings['site_tagline'],
                    'phone' => $siteSettings['phone'],
                    'address' => $siteSettings['address'],
                    'hours' => $siteSettings['hours'],
                    'about_title' => $siteSettings['about_title'],
                    'about_text' => $siteSettings['about_text'],
                    'hero_lead' => $siteSettings['hero_lead'],
                ],
                'siteSettings' => $siteSettings,
                'headerMenus' => $headerMenus,
                'footerMenus' => $footerMenus,
            ]);
        });
    }
}
