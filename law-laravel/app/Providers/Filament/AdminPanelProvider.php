<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\EditAdminProfile;
use App\Filament\Auth\Pages\Login;
use App\Http\Middleware\EnsureInstalled;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile(EditAdminProfile::class, isSimple: false)
            ->brandName(fn (): string => (string) (Setting::get('site_name', '') ?: 'مؤسسه حقوقی آریان'))
            ->brandLogo(null)
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#0f766e'),
                'gray' => Color::Slate,
                'warning' => Color::hex('#d4a017'),
                'danger' => Color::hex('#b91c1c'),
            ])
            ->font('Vazirmatn')
            ->darkMode(false)
            ->themeSwitcher(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->sidebarWidth('16.5rem')
            ->collapsedSidebarWidth('4.25rem')
            ->globalSearch(true)
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->globalSearchFieldSuffix('Ctrl+K')
            ->navigationGroups([
                NavigationGroup::make('خانه کنترل پنل')
                    ->icon(Heroicon::OutlinedHome)
                    ->collapsed(false),
                NavigationGroup::make('ورودی‌ها و پیگیری')
                    ->icon(Heroicon::OutlinedInbox)
                    ->collapsed(true),
                NavigationGroup::make('مدیریت محتوا')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsed(true),
                NavigationGroup::make('سیستم و حساب')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsed(true),
            ])
            ->userMenuItems([
                // Closures preserve Filament profile/logout URL + POST behavior.
                // MenuItem::make() alone wipes url/postToUrl and breaks logout.
                'profile' => fn (Action $action): Action => $action
                    ->label('ویرایش اطلاعات ادمین')
                    ->icon(Heroicon::OutlinedUserCircle),
                'change-password' => MenuItem::make()
                    ->label('تغییر رمز عبور')
                    ->icon(Heroicon::OutlinedKey)
                    ->url(fn (): string => EditAdminProfile::getUrl()),
                'logout' => fn (Action $action): Action => $action
                    ->label('خروج از پنل')
                    ->icon(Heroicon::OutlinedArrowLeftEndOnRectangle)
                    ->color('danger'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render(<<<'HTML'
                    <link rel="stylesheet" href="{{ asset('css/vbulletin-admin.css') }}?v=9" />
                    <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}?v=3" />
                HTML)
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                function (): string {
                    if (auth()->check()) {
                        return '';
                    }

                    try {
                        $brand = (string) (Setting::get('site_name', '') ?: 'مؤسسه حقوقی آریان');
                    } catch (\Throwable) {
                        $brand = 'مؤسسه حقوقی آریان';
                    }

                    $initial = mb_substr(preg_replace('/\s+/u', '', $brand) ?: 'آ', 0, 1);

                    return Blade::render(<<<'HTML'
                        <aside class="login-gate-brand" aria-label="لوگوی ورود">
                            <div class="login-gate-brand__top">
                                <div class="login-gate-brand__mark" aria-hidden="true">{{ $initial }}</div>
                                <span>پنل مدیریت</span>
                            </div>
                            <div class="login-gate-brand__hero">
                                <h1 class="brand-name">{{ $brand }}</h1>
                            </div>
                        </aside>
                    HTML, [
                        'brand' => $brand,
                        'initial' => $initial,
                    ]);
                }
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_END,
                function (): string {
                    if (auth()->check()) {
                        return '';
                    }

                    return Blade::render(<<<'HTML'
                        <p class="login-gate-note">
                            ← <a href="{{ url('/') }}">بازگشت به سایت</a>
                        </p>
                    HTML);
                }
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => Blade::render(<<<'HTML'
                    <div class="vb-side-brand">
                        <strong>پنل مدیریت</strong>
                        <span>جستجو: Ctrl+K</span>
                    </div>
                HTML)
            )
            ->middleware([
                EnsureInstalled::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
