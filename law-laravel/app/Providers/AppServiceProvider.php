<?php

namespace App\Providers;

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

        \Illuminate\Support\Facades\View::composer(['layouts.site', 'site.home'], function ($view) {
            $keys = [
                'site_name', 'site_tagline', 'phone', 'mobile', 'email', 'address', 'hours',
                'about_title', 'about_text', 'hero_lead',
                'footer_about', 'footer_copyright', 'footer_disclaimer',
                'social_instagram', 'social_linkedin', 'social_whatsapp',
                'cta_text', 'show_phone_in_header',
            ];
            $siteSettings = [];
            try {
                foreach ($keys as $key) {
                    $siteSettings[$key] = \App\Models\Setting::get($key, '');
                }
            } catch (\Throwable) {
                $siteSettings = array_fill_keys($keys, '');
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
