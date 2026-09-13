<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        $needed = [];
        foreach ($permissions as $permission) {
            foreach (explode('|', $permission) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $needed[] = $part;
                }
            }
        }
        $needed = array_values(array_unique($needed));
        $ok = $user && (
            $needed === []
            || collect($needed)->contains(fn ($p) => $user->canAccess($p))
        );
        if (! $ok) {
            // After login, missing dashboard used to hard-403; send staff to first allowed page.
            if ($user && in_array('dashboard', $needed, true) && count($needed) === 1) {
                $fallback = $user->homeRoute();
                if ($fallback !== 'dashboard') {
                    return redirect()
                        ->route($fallback)
                        ->with('error', 'دسترسی میز کار برای این کاربر فعال نیست؛ به اولین بخش مجاز هدایت شدید.');
                }
            }

            abort(403, 'دسترسی به این بخش مجاز نیست.');
        }

        return $next($request);
    }
}
