<?php

namespace App\Http\Middleware;

use App\Models\DesktopDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureDesktopSyncToken
{
    public function handle(Request $request, Closure $next, string $scope = 'any'): Response
    {
        $plain = trim((string) $request->bearerToken());
        if ($plain === '' || ! Schema::hasTable('desktop_devices')) {
            return $this->deny();
        }

        $digest = hash('sha256', $plain);
        $device = DesktopDevice::query()->where('token_hash', $digest)->first();
        if (! $device || ! hash_equals((string) $device->token_hash, $digest) || $device->isRevoked()) {
            return $this->deny();
        }

        $expires = $device->getAttribute('expires_at');
        if ($expires && Carbon::parse($expires)->isPast()) {
            return $this->deny();
        }

        if ($scope !== 'any' && ! in_array($scope, $device->tokenScopes(), true)) {
            return response()->json([
                'ok' => false,
                'message' => 'این توکن اجازه این کار را ندارد.',
            ], 403);
        }

        $request->attributes->set('desktopDevice', $device);
        $request->attributes->set('sync_token_id', $device->id);
        app()->instance('sync.origin', 'desktop');

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() >= 400) {
            return;
        }
        $device = $request->attributes->get('desktopDevice');
        if (! $device instanceof DesktopDevice) {
            return;
        }

        $fill = ['last_seen_at' => now()];
        if (Schema::hasColumn('desktop_devices', 'last_ip')) {
            $fill['last_ip'] = substr((string) $request->ip(), 0, 64);
        }
        $device->forceFill($fill)->save();
    }

    private function deny(): Response
    {
        return response()->json([
            'ok' => false,
            'message' => 'توکن همگام‌سازی معتبر نیست.',
        ], 401);
    }
}
