<?php

namespace Plugins\ThemeBuilder\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Legacy ThemeBuilder / Revolution admin entry.
 * The old studio caused intermittent HTTP 500s when options changed.
 * All routes now safely redirect to the modern Hero Studio.
 */
class ThemeBuilderController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()
            ->to(url('/admin/hero-studio'))
            ->with('success', 'بنرساز قدیمی (Revolution) غیرفعال شد. از استودیو هیرو مدرن استفاده کنید.');
    }

    public function save(Request $request): RedirectResponse
    {
        return redirect()
            ->to(url('/admin/hero-studio'))
            ->with('error', 'ذخیره بنرساز قدیمی غیرفعال است. تنظیمات را در استودیو هیرو مدرن ذخیره کنید.');
    }

    /** Catch-all for any leftover theme-builder AJAX/option endpoints. */
    public function fallback(): RedirectResponse
    {
        return redirect()->to(url('/admin/hero-studio'));
    }
}
