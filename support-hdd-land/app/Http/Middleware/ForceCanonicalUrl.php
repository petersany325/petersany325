<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        if ($scheme === $wantScheme && strcasecmp($host, $wantHost) === 0) {
            return $next($request);
        }

        $target = $wantScheme.'://'.$wantHost.$request->getRequestUri();
        $status = $request->isMethodSafe() ? 301 : 308;

        return redirect()->away($target, $status);
    }
}
