<?php

namespace App\Providers;

use App\Http\Controllers\DesktopDeviceController;
use App\Http\Middleware\EnsurePermission;
use App\Observers\DesktopSyncObserver;
use App\Observers\DesktopSyncTombstoneObserver;
use App\Support\DesktopSync\Catalog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url', '');
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        foreach (Catalog::models() as $model) {
            $model::observe(DesktopSyncObserver::class);
        }
        DesktopSyncTombstoneObserver::register();

        RateLimiter::for('desktop-sync', function (Request $request) {
            $id = $request->attributes->get('sync_token_id') ?: ($request->bearerToken() ?: $request->ip());

            return Limit::perMinute(120)->by('desktop-sync:'.$id)->response(function (Request $request, array $headers) {
                return response()->json([
                    'ok' => false,
                    'message' => 'تعداد درخواست بیش از حد است. کمی بعد دوباره تلاش کنید.',
                ], 429, $headers);
            });
        });

        // Installs that already load these routes keep a single registration.
        // An older bootstrap still picks the API and the settings page up from here.
        $this->app->booted(function () {
            if (! Route::has('desktop.sync')) {
                Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));
            } elseif (! Route::has('desktop-sync.ping')) {
                Route::middleware('api')->prefix('api')->group(base_path('routes/desktop-sync-v1.php'));
            }
            if (! Route::has('settings.desktop-sync')) {
                Route::middleware(['web', 'auth', EnsurePermission::class.':settings'])->group(function () {
                    Route::get('settings/desktop-sync', [DesktopDeviceController::class, 'index'])->name('settings.desktop-sync');
                    Route::post('settings/desktop-sync', [DesktopDeviceController::class, 'store'])->name('settings.desktop-sync.store');
                    Route::post('settings/desktop-sync/{device}/revoke', [DesktopDeviceController::class, 'revoke'])->name('settings.desktop-sync.revoke');
                    Route::post('settings/desktop-sync/{device}/reissue', [DesktopDeviceController::class, 'reissue'])->name('settings.desktop-sync.reissue');
                });
            }
        });
    }
}
