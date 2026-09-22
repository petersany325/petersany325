<?php

namespace App\Providers;

use App\Http\Controllers\AccountingController;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ensure ticket_label() exists even if helpers.php opcache is stale after overlay.
        if (! function_exists('ticket_label')) {
            require_once app_path('helpers_ticket.php');
        }
    }

    public function boot(): void
    {
        $appUrl = (string) config('app.url', '');
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl(rtrim($appUrl, '/'));
        }

        // Safety net: ensure debt-ticket search route exists even if an overlay
        // missed updating routes/web.php on a host with sticky route cache.
        $this->app->booted(function () {
            if (Route::has('accounting.manual.tickets')) {
                return;
            }
            Route::middleware(['web', 'auth', EnsurePermission::class.':reports.accounting'])
                ->get('/accounting/manual/tickets', [AccountingController::class, 'searchDebtTickets'])
                ->name('accounting.manual.tickets');
        });
    }
}
