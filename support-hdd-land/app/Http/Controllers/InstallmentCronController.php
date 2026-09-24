<?php

namespace App\Http\Controllers;

use App\Services\InstallmentReminderService;
use App\Services\InstallmentService;
use App\Support\InstallmentSettings;
use Illuminate\Http\Request;

class InstallmentCronController extends Controller
{
    public function __invoke(Request $request, InstallmentService $installments, InstallmentReminderService $reminders)
    {
        $token = (string) $request->query('token', '');
        $cfg = InstallmentSettings::all();
        if ($token === '' || ! hash_equals($cfg['cron_token'], $token)) {
            abort(403, 'Invalid installment cron token');
        }

        $overdue = $installments->refreshOverdueStatuses();
        $stats = $reminders->runDueReminders();

        return response()->json([
            'ok' => true,
            'overdue_updated' => $overdue,
            'reminders' => $stats,
        ]);
    }
}
