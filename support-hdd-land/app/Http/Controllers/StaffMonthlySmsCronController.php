<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Services\StaffMonthlyPerformanceService;
use App\Services\StaffNotifier;
use App\Support\BackupSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Day-1 cron: SMS previous calendar month performance to active technicians.
 * Auth via the same backup cron token. Idempotent per YYYY-MM period.
 */
class StaffMonthlySmsCronController extends Controller
{
    public function __invoke(
        Request $request,
        StaffMonthlyPerformanceService $performance,
        NiazpardazSmsService $sms,
        StaffNotifier $notifier,
    ) {
        $token = (string) $request->query('token', '');
        $cfg = BackupSettings::all();
        if ($token === '' || ! hash_equals((string) ($cfg['cron_token'] ?? ''), $token)) {
            abort(403, 'Invalid cron token');
        }

        $enabled = AppSetting::getValue('staff_monthly_sms_enabled', '1') !== '0';
        if (! $enabled && ! $request->boolean('force')) {
            return response()->json([
                'ok' => true,
                'skipped' => true,
                'message' => 'Staff monthly SMS disabled',
            ]);
        }

        $now = Carbon::now(config('app.timezone', 'Asia/Tehran'));
        $force = $request->boolean('force');
        if (! $force && (int) $now->day !== 1) {
            return response()->json([
                'ok' => true,
                'skipped' => true,
                'message' => 'Only runs on day 1 of month (use force=1 to override)',
                'today' => $now->toDateString(),
            ]);
        }

        $prev = $now->copy()->subMonthNoOverflow();
        $periodKey = $prev->format('Y-m');
        $from = $prev->copy()->startOfMonth()->toDateString();
        $to = $prev->copy()->endOfMonth()->toDateString();

        $last = (string) AppSetting::getValue('staff_monthly_sms_last_period', '');
        if (! $force && $last === $periodKey) {
            return response()->json([
                'ok' => true,
                'skipped' => true,
                'message' => 'Already sent for period '.$periodKey,
            ]);
        }

        $report = $performance->build($from, $to);
        $sent = 0;
        $failed = 0;
        $notified = 0;

        foreach ($report['staff'] as $row) {
            if (empty($row['technician_id'])) {
                continue;
            }
            if (! ($row['has_activity'] ?? false) && (int) ($row['delivered'] ?? 0) === 0) {
                continue;
            }

            $user = User::query()->find($row['user_id'] ?? 0);
            if (! $user || ! $user->is_active) {
                continue;
            }

            $title = 'گزارش ماهانه عملکرد — '.$report['month_label'];
            $body = $performance->technicianSmsText($row, $report['month_label']);
            $link = route('reports.technicians');

            try {
                $notifier->notifyMany([$user], 'staff_monthly_report', $title, $body, $link, [
                    'from' => $from,
                    'to' => $to,
                    'period' => $periodKey,
                ]);
                $notified++;
            } catch (\Throwable) {
            }

            $phone = User::normalizePhone($user->phone);
            if (! $phone) {
                $failed++;
                continue;
            }

            $result = $sms->send($phone, $body);
            try {
                SmsLog::query()->create([
                    'phone' => $phone,
                    'message' => $body,
                    'audience' => 'employee',
                    'status_key' => 'staff_monthly_auto',
                    'ok' => (bool) ($result['ok'] ?? false),
                    'provider_message' => $result['message'] ?? null,
                    'sent_by' => null,
                ]);
            } catch (\Throwable) {
            }

            if ($result['ok'] ?? false) {
                $sent++;
            } else {
                $failed++;
            }
        }

        AppSetting::setValue('staff_monthly_sms_last_period', $periodKey);

        return response()->json([
            'ok' => true,
            'period' => $periodKey,
            'from' => $from,
            'to' => $to,
            'cartable_notified' => $notified,
            'sms_ok' => $sent,
            'sms_fail' => $failed,
        ]);
    }
}
