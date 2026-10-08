<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** @param  string  ...$roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isActiveUser()) {
            return redirect()->route('login')->with('error', 'Please sign in.');
        }

        if ($roles === []) {
            return $next($request);
        }

        $ok = in_array($user->role, $roles, true)
            || ($user->isAdmin() && in_array('admin', $roles, true))
            || ($user->isStaff() && in_array('staff', $roles, true));

        if (! $ok) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
