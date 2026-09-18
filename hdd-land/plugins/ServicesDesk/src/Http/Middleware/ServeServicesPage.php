<?php

namespace Plugins\ServicesDesk\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\ServicesDesk\src\Support\ServicesCopy;
use Symfony\Component\HttpFoundation\Response;

class ServeServicesPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && trim($request->path(), '/') === 'services') {
            try {
                $copy = ServicesCopy::get();

                return response()->view('services-desk::page', [
                    'copy' => $copy,
                    'cards' => ServicesCopy::cards($copy),
                    'tools' => ServicesCopy::tools($copy),
                ]);
            } catch (\Throwable) {
            }
        }

        return $next($request);
    }
}
