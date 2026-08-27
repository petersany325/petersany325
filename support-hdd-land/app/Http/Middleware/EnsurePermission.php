<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user || ! $user->canAccess($permission)) {
            abort(403, 'دسترسی به این بخش مجاز نیست.');
        }

        return $next($request);
    }
}
