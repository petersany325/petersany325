<?php

namespace Plugins\ContactDesk\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Plugins\ContactDesk\src\Support\ContactCopy;
use Symfony\Component\HttpFoundation\Response;

class ServeContactPage
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && trim($request->path(), '/') === 'contact') {
            try {
                return response()->view('contact-desk::page', [
                    'copy' => ContactCopy::get(),
                ]);
            } catch (\Throwable) {
            }
        }

        return $next($request);
    }
}
