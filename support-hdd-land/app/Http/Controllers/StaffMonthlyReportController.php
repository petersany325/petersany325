<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\User;
use App\Services\NiazpardazSmsService;
use App\Services\StaffMonthlyPerformanceService;
use App\Services\StaffNotifier;
use App\Support\ReportSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin-only monthly staff performance report.
 * Read-only aggregates + optional cartable/SMS notify — does not touch tickets/payments.
 */
class StaffMonthlyReportController extends Controller
{
    public function __construct(
        private readonly StaffMonthlyPerformanceService $performance,
        private readonly StaffNotifier $notifier,
        private readonly NiazpardazSmsService $sms,
    ) {}

    public function index(Request $request): View
    {
        $this->assertAdmin();
        [$from, $to] = $this->range($request);
        $report = $this->performance->build($from, $to);

        $activeOnly = $request->boolean('active_only', true);
        $staff = $report['staff'];
        if ($activeOnly) {
            $staff = $staff->filter(fn (array $row) => $row['has_activity'])->values();
        }

        $chartLabels = $staff->pluck('name')->values()->all();
        $chartRevenue = $staff->pluck('revenue')->map(fn ($v) => (int) $v)->values()->all();
        $chartDelivered = $staff->pluck('delivered')->map(fn ($v) => (int) $v)->values()->all();

        return view('reports.staff-monthly', [
            'from' => $report['from'],
            'to' => $report['to'],
            'prevFrom' => $report['prev_from'],
            'prevTo' => $report['prev_to'],
            'monthLabel' => $report['month_label'],
            'prevMonthLabel' => $report['prev_month_label'],
            'shop' => $report['shop'],
            'prevShop' => $report['prev_shop'],
            'shopLine' => $report['shop_line'],
            'staff' => $staff,
            'activeOnly' => $activeOnly,
            'chartLabels' => $chartLabels,
            'chartRevenue' => $chartRevenue,
            'chartDelivered' => $chartDelivered,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $this->assertAdmin();
        [$from, $to] = $this->range($request);
        $report = $this->performance->forUser($user, $from, $to);

        return view('reports.staff-monthly-show', [
            'user' => $user,
            'from' => $report['from'],
            'to' => $report['to'],
            'monthLabel' => $report['month_label'],
            'prevMonthLabel' => $report['prev_month_label'],
            'shopLine' => $report['shop_line'],
            'row' => $report['row'],
        ]);
    }

    public function notify(Request $request, User $user): RedirectResponse
    {
        $this->assertAdmin();
        [$from, $to] = $this->range($request);
        $report = $this->performance->forUser($user, $from, $to);
        $row = $report['row'];
        $sendSms = $request->boolean('send_sms');

        $title = 'گزارش ماهانه عملکرد — '.$report['month_label'];
        $body = (string) ($row['line'] ?? '');
        $link = route('reports.staff-monthly.show', [
            'user' => $user->id,
            'from' => jalali_input($report['from']),
            'to' => jalali_input($report['to']),
            'period' => 'custom',
        ]);

        $this->notifier->notifyMany(
            [$user],
            'staff_monthly_report',
            $title,
            $body,
            $link,
            [
                'from' => $report['from'],
                'to' => $report['to'],
                'metrics' => [
                    'delivered' => $row['delivered'] ?? 0,
                    'revenue' => $row['revenue'] ?? 0,
                    'collected' => $row['collected'] ?? 0,
                    'approx_profit' => $row['approx_profit'] ?? 0,
                    'warranty_returns' => $row['warranty_returns'] ?? 0,
                ],
            ]
        );

        $msg = 'گزارش در کارتابل اعلان‌های «'.$user->name.'» ثبت شد.';

        if ($sendSms) {
            $phone = User::normalizePhone($user->phone);
            if (! $phone) {
                return back()->with('success', $msg)->withErrors([
                    'sms' => 'موبایل کارمند معتبر نیست؛ فقط اعلان داخلی ارسال شد.',
                ]);
            }
            $text = $this->performance->smsText($row, $report['month_label']);
            $result = $this->sms->send($phone, $text);
            try {
                SmsLog::query()->create([
                    'phone' => $phone,
                    'message' => $text,
                    'audience' => 'employee',
                    'status_key' => 'staff_monthly',
                    'ok' => (bool) ($result['ok'] ?? false),
                    'provider_message' => $result['message'] ?? null,
                    'sent_by' => auth()->id(),
                ]);
            } catch (\Throwable) {
            }
            if ($result['ok'] ?? false) {
                $msg .= ' پیامک هم ارسال شد.';
            } else {
                return back()->with('success', $msg)->withErrors([
                    'sms' => 'پیامک ناموفق: '.($result['message'] ?? 'خطای نامشخص'),
                ]);
            }
        }

        return back()->with('success', $msg);
    }

    public function notifyAll(Request $request): RedirectResponse
    {
        $this->assertAdmin();
        [$from, $to] = $this->range($request);
        $report = $this->performance->build($from, $to);
        $sendSms = $request->boolean('send_sms');
        $count = 0;
        $smsOk = 0;
        $smsFail = 0;

        foreach ($report['staff'] as $row) {
            if (! ($row['has_activity'] ?? false)) {
                continue;
            }
            $user = User::query()->find($row['user_id']);
            if (! $user) {
                continue;
            }

            $title = 'گزارش ماهانه عملکرد — '.$report['month_label'];
            $body = (string) ($row['line'] ?? '');
            $link = route('reports.staff-monthly.show', [
                'user' => $user->id,
                'from' => jalali_input($report['from']),
                'to' => jalali_input($report['to']),
                'period' => 'custom',
            ]);

            $this->notifier->notifyMany([$user], 'staff_monthly_report', $title, $body, $link, [
                'from' => $report['from'],
                'to' => $report['to'],
            ]);
            $count++;

            if ($sendSms) {
                $phone = User::normalizePhone($user->phone);
                if (! $phone) {
                    $smsFail++;
                    continue;
                }
                $text = $this->performance->smsText($row, $report['month_label']);
                $result = $this->sms->send($phone, $text);
                try {
                    SmsLog::query()->create([
                        'phone' => $phone,
                        'message' => $text,
                        'audience' => 'employee',
                        'status_key' => 'staff_monthly',
                        'ok' => (bool) ($result['ok'] ?? false),
                        'provider_message' => $result['message'] ?? null,
                        'sent_by' => auth()->id(),
                    ]);
                } catch (\Throwable) {
                }
                if ($result['ok'] ?? false) {
                    $smsOk++;
                } else {
                    $smsFail++;
                }
            }
        }

        $msg = "اعلان ماهانه برای {$count} نفر در کارتابل ثبت شد.";
        if ($sendSms) {
            $msg .= " پیامک موفق: {$smsOk} · ناموفق: {$smsFail}.";
        }

        return back()->with('success', $msg);
    }

    private function assertAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        ReportSettings::syncFromQuery($request);

        return ReportSettings::range();
    }
}
