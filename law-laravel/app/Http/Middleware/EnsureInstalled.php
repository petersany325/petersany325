<?php

namespace App\Http\Middleware;

use App\Services\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installer::isInstalled() && ! $request->is('install', 'install/*')) {
            return redirect()->route('install.show');
        }

        return $next($request);
    }
}
