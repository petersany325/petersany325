<?php

namespace App\Http\Middleware;

use App\Models\DesktopDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDesktopDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?: (string) $request->header('X-Desktop-Token', '');
        if (trim($plain) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'توکن نرم‌افزار کامپیوتر لازم است.',
            ], 401);
        }
        if (! Schema::hasTable('desktop_devices')) {
            return response()->json([
                'ok' => false,
                'message' => 'همگام‌سازی هنوز روی سرور نصب نشده است.',
            ], 503);
        }

        $device = DesktopDevice::findByToken($plain);
        if (! $device || $device->isRevoked()) {
            return response()->json([
                'ok' => false,
                'message' => 'توکن نامعتبر یا باطل شده است.',
            ], 401);
        }

        $request->attributes->set('desktopDevice', $device);

        return $next($request);
    }
}
