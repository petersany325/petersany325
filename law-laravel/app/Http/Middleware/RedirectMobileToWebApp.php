<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileToWebApp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query('desktop') === '1') {
            $response = $next($request);

            return $response->cookie('prefer_desktop', '1', 60 * 24 * 30);
        }

        try {
            $enabled = Setting::get('pwa_enabled', '1') === '1';
            $auto = Setting::get('pwa_auto_mobile', '1') === '1';
        } catch (\Throwable) {
            return $next($request);
        }

        if (! $enabled || ! $auto) {
            return $next($request);
        }

        if ($request->cookie('prefer_desktop') === '1') {
            return $next($request);
        }

        if ($request->query('source') === 'pwa') {
            return $next($request);
        }

        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return $next($request);
        }

        $ua = strtolower((string) $request->userAgent());
        $isBot = str_contains($ua, 'bot')
            || str_contains($ua, 'crawl')
            || str_contains($ua, 'slurp')
            || str_contains($ua, 'facebookexternalhit')
            || str_contains($ua, 'preview');
        if ($isBot) {
            return $next($request);
        }

        $isMobile = (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $ua);
        if (! $isMobile) {
            return $next($request);
        }

        if ($request->routeIs('home') || $request->is('/')) {
            $qs = $request->getQueryString();

            return redirect()->to('/app'.($qs ? ('?'.$qs) : ''), 302);
        }

        return $next($request);
    }
}
