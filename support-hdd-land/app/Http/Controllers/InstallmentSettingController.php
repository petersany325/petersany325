<?php

namespace App\Http\Controllers;

use App\Services\InstallmentReminderService;
use App\Services\InstallmentService;
use App\Support\InstallmentSettings;
use Illuminate\Http\Request;

class InstallmentSettingController extends Controller
{
    public function edit()
    {
        return view('installments.settings', [
            'settings' => InstallmentSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable'],
            'before_days' => ['nullable', 'integer', 'min:0', 'max:60'],
            'on_due' => ['nullable'],
            'after_days' => ['nullable', 'string', 'max:100'],
            'tpl_before' => ['nullable', 'string', 'max:500'],
            'tpl_due' => ['nullable', 'string', 'max:500'],
            'tpl_after' => ['nullable', 'string', 'max:500'],
            'tpl_thanks' => ['nullable', 'string', 'max:500'],
            'regenerate_cron_token' => ['nullable'],
        ]);

        InstallmentSettings::save([
            'enabled' => $request->boolean('enabled'),
            'before_days' => (int) ($data['before_days'] ?? 3),
            'on_due' => $request->boolean('on_due'),
            'after_days' => $data['after_days'] ?? '1,3,7',
            'tpl_before' => $data['tpl_before'] ?? '',
            'tpl_due' => $data['tpl_due'] ?? '',
            'tpl_after' => $data['tpl_after'] ?? '',
            'tpl_thanks' => $data['tpl_thanks'] ?? '',
            'regenerate_cron_token' => $request->boolean('regenerate_cron_token'),
        ]);

        return back()->with('success', 'تنظیمات پیامک اقساط ذخیره شد.');
    }

    public function runReminders(InstallmentService $installments, InstallmentReminderService $reminders)
    {
        $installments->refreshOverdueStatuses();
        $stats = $reminders->runDueReminders();

        return back()->with('success', 'یادآوری اجرا شد — ارسال: '.($stats['sent'] ?? 0).' / خطا: '.($stats['errors'] ?? 0));
    }
}
