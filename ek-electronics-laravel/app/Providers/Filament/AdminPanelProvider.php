<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverview;
use App\Http\Middleware\EnsureInstalled;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->brandName('EK Operations')
            ->colors([
                // Classic vBulletin blues
                'primary' => Color::hex('#5c7099'),
                'gray' => Color::hex('#6e6e8f'),
                'info' => Color::hex('#869bbf'),
                'success' => Color::hex('#25d366'),
                'warning' => Color::hex('#c9a227'),
                'danger' => Color::hex('#a10f2b'),
            ])
            ->font('Tahoma')
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->sidebarCollapsibleOnDesktop(false)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render('<link rel="stylesheet" href="{{ asset(\'assets/css/filament-vbulletin.css\') }}?v=1">')
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
            ])
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
