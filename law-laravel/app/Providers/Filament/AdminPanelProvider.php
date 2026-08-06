<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\EditAdminProfile;
use App\Http\Middleware\EnsureInstalled;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->login()
            ->profile(EditAdminProfile::class, isSimple: false)
            ->brandName('Admin CP · آریان')
            ->brandLogo(null)
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#234e75'),
                'gray' => Color::Slate,
                'warning' => Color::hex('#c9a227'),
            ])
            ->font('Vazirmatn')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->collapsedSidebarWidth('4.5rem')
            ->navigationGroups([
                NavigationGroup::make('خانه کنترل پنل')
                    ->icon(Heroicon::OutlinedHome),
                NavigationGroup::make('ورودی‌ها و پیگیری')
                    ->icon(Heroicon::OutlinedInbox)
                    ->collapsed(false),
                NavigationGroup::make('مدیریت محتوا')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsed(false),
                NavigationGroup::make('سیستم و حساب')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsed(false),
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('ویرایش اطلاعات ادمین')
                    ->icon(Heroicon::OutlinedUserCircle),
                MenuItem::make('change-password')
                    ->label('تغییر رمز عبور')
                    ->icon(Heroicon::OutlinedKey)
                    ->url(fn (): string => EditAdminProfile::getUrl()),
                'logout' => MenuItem::make()
                    ->label('خروج از پنل'),
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
                fn (): string => Blade::render('<link rel="stylesheet" href="{{ asset(\'css/vbulletin-admin.css\') }}?v=2" />')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => Blade::render(<<<'HTML'
                    <div style="margin:0.75rem 0.5rem 0.25rem;padding:0.55rem 0.75rem;background:#152a45;color:#fff;font-size:0.75rem;font-weight:800;border:1px solid #0f2138;">
                        منوی مدیریت · شبیه ACP
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
