<?php

namespace App\Http\Middleware;

use App\Models\DesktopDevice;
use Closure;
use Illuminate\Http\Request;
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
        $revoked = $device && (method_exists($device, 'isRevoked') ? $device->isRevoked() : $device->revoked_at !== null);
        if (! $device || ! hash_equals((string) $device->token_hash, $digest) || $revoked) {
            return $this->deny();
        }

        $expired = method_exists($device, 'isExpired')
            ? $device->isExpired()
            : ($device->expires_at !== null && $device->expires_at->isPast());
        if ($expired) {
            return $this->deny();
        }

        $allowed = ! method_exists($device, 'allows') || $device->allows($scope);
        if ($scope !== 'any' && ! $allowed) {
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
