<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optionally force scheme/host to APP_URL.
 *
 * Cross-domain redirects are OFF by default so a customer install never
 * bounces to the seller domain when a stale bootstrap/cache/config.php
 * still contains support.hdd-land.ir.
 *
 * Set FORCE_CANONICAL_HOST=true on the seller site if you want host locking.
 */
class ForceCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '' || ! str_starts_with($appUrl, 'http')) {
            return $next($request);
        }

        $parts = parse_url($appUrl);
        $wantScheme = $parts['scheme'] ?? 'https';
        $wantHost = $parts['host'] ?? null;
        if (! $wantHost) {
            return $next($request);
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();

        $forceHost = filter_var(env('FORCE_CANONICAL_HOST', false), FILTER_VALIDATE_BOOL);
        // Never bounce a live host to a different domain unless explicitly enabled.
        if (! $forceHost && strcasecmp($host, $wantHost) !== 0) {
            return $next($request);
        }

        if ($scheme === $wantScheme && strcasecmp($host, $wantHost) === 0) {
            return $next($request);
        }

        // Same host: only upgrade http→https (or match APP_URL scheme).
        if (strcasecmp($host, $wantHost) === 0 && $scheme !== $wantScheme) {
            $target = $wantScheme.'://'.$host.$request->getRequestUri();
            $status = $request->isMethodSafe() ? 301 : 308;

            return redirect()->away($target, $status);
        }

        if ($forceHost) {
            $target = $wantScheme.'://'.$wantHost.$request->getRequestUri();
            $status = $request->isMethodSafe() ? 301 : 308;

            return redirect()->away($target, $status);
        }

        return $next($request);
    }
}
