<?php

namespace App\Http\Middleware;

use App\Services\AttendanceService;
use App\Support\AttendanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تا تکمیل سلفی + GPS موبایل در اولین ورود، منوی کار قفل است.
 */
class EnsureAttendanceOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! AttendanceSettings::requireOnboarding()) {
            return $next($request);
        }

        // سیستم سراسری یا دسترسی فردی غیرفعال → قفل آنبوردینگ اعمال نشود
        if (! AttendanceSettings::enabled()) {
            return $next($request);
        }

        // ادمین سیستم را قفل نمی‌کنیم (تنظیمات شرکت را خودش می‌چیند)
        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->canAccess('attendance')) {
            return $next($request);
        }

        $attendance = app(AttendanceService::class);
        if (! $attendance->isAccessAllowed($user)) {
            return $next($request);
        }

        if ($attendance->hasCompletedOnboarding($user)) {
            return $next($request);
        }

        if ($request->routeIs(
            'attendance.onboard',
            'attendance.onboard.store',
            'logout',
            'profile.edit',
            'profile.update',
            'profile.password'
        )) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => false,
                'message' => 'ابتدا ثبت اولیه حضور (سلفی و GPS موبایل) را کامل کنید.',
                'redirect' => route('attendance.onboard'),
            ], 423);
        }

        return redirect()
            ->route('attendance.onboard')
            ->with('error', 'برای باز شدن منوی کار، ابتدا سلفی و GPS موبایل را یک‌بار ثبت کنید.');
    }
}
