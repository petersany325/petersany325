<?php

namespace Plugins\Studio\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\Studio\src\Support\StudioSms;

class StudioSmsController extends Controller
{
    public function edit(): View
    {
        return view('studio::admin.sms', [
            'sms' => StudioSms::adminStatus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $username = trim((string) $request->input('username', ''));
        $from = trim((string) $request->input('from', ''));
        if ($username === '' || $from === '') {
            return back()->withInput()->with('error', 'نام کاربری پنل و شماره خط را بنویسید.');
        }

        StudioSms::saveAdminConfig([
            'username' => $username,
            'password' => (string) $request->input('password', ''),
            'from' => $from,
            'api_key' => trim((string) $request->input('api_key', '')),
        ]);

        return back()->with('success', 'پنل نیازپرداز به ورود سایت وصل شد. از این به بعد کد ورود کاربر و کارمند از همین پنل پیامک می‌شود.');
    }
}
