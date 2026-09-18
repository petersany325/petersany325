<?php

namespace Plugins\AboutDesk\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\AboutDesk\src\Support\AboutCopy;
use Symfony\Component\HttpFoundation\Response;

class ServeAboutPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && trim($request->path(), '/') === 'about') {
            try {
                return response()->view('about-desk::page', [
                    'copy' => AboutCopy::get(),
                    'tools' => AboutCopy::tools(),
                    'paragraphs' => AboutCopy::paragraphs(),
                ]);
            } catch (\Throwable) {
            }
        }

        return $next($request);
    }
}
