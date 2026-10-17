<?php

namespace App\Http\Middleware;

use App\Support\SiteHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send shop pages to shop.* and studio pages to the apex host.
 * Never runs against support.hdd-land.ir and never writes receipt data.
 */
class SiteHostSplit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SiteHost::isReceiptHost($request)) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');
        if ($path === '/index.php') {
            $path = '/';
        }

        if (SiteHost::isSharedPath($path)) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $suffix = $query ? ('?'.$query) : '';

        if (SiteHost::isStudio($request) && SiteHost::isShopOnlyPath($path)) {
            return redirect()->to(SiteHost::shopUrl($path).$suffix, 301);
        }

        if (SiteHost::isShop($request) && SiteHost::isStudioOnlyPath($path)) {
            return redirect()->to(SiteHost::studioUrl($path).$suffix, 301);
        }

        return $next($request);
    }
}
