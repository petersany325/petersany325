<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft license gate for customer installs.
 * Seller's own site (no LICENSE_KEY) is not blocked.
 */
class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) config('license.key'));
        if ($key === '') {
            return $next($request);
        }

        // Allow installer + license API always
        if ($request->is('install.php') || $request->is('install') || $request->is('license/*')) {
            return $next($request);
        }

        $domain = \App\Models\ProductLicense::normalizeDomain($request->getHost());
        $configured = \App\Models\ProductLicense::normalizeDomain((string) config('license.domain'));
        $token = (string) config('license.token');

        if ($configured !== '' && $configured !== $domain) {
            return response()->view('errors.license', [
                'message' => 'لایسنس این نصب برای دامنه دیگری ثبت شده است.',
            ], 403);
        }

        if ($key === '' || $token === '') {
            return response()->view('errors.license', [
                'message' => 'لایسنس نصب نشده است. فایل install.php را اجرا کنید.',
            ], 403);
        }

        return $next($request);
    }
}
