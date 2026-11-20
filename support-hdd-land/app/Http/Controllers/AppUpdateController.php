<?php

namespace App\Http\Controllers;

use App\Services\AppUpdateService;
use App\Support\ReleasePolicy;
use Illuminate\Http\Request;

/**
 * Customer (and seller) panel: live update check + one-click apply.
 */
class AppUpdateController extends Controller
{
    public function __construct(private AppUpdateService $updates)
    {
    }

    public function index()
    {
        $status = $this->updates->checkForUpdate(true);

        return view('system-tools.updates', [
            'status' => $status,
            'pollSeconds' => (int) config('updates.poll_seconds', 45),
            'isSeller' => trim((string) config('license.key')) === '',
            'lastResult' => session('app_update_result'),
        ]);
    }

    public function check(Request $request)
    {
        $status = $this->updates->checkForUpdate($request->boolean('force'));

        return response()->json($status);
    }

    public function apply(Request $request)
    {
        if (! ReleasePolicy::mayInstallCustomerPackage()) {
            $message = 'سایت اصلی بسته نصبی مشتری را نصب نمی‌کند. تغییرات اول روی همین سایت تست می‌شوند و فقط بعد از درست بودن گزینه‌ها، از «انتشار آپدیت» برای مشتری فرستاده می‌شوند.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return redirect()
                ->route('system-tools.updates')
                ->with('error', $message);
        }

        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'برای نصب آپدیت باید تأیید را علامت بزنید.',
        ]);

        @set_time_limit(600);
        $result = $this->updates->applyLatestUpdate();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
        }

        return redirect()
            ->route('system-tools.updates')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('app_update_result', $result);
    }
}
