<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reception;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only monthly staff performance aggregates for admin reports.
 * Does not modify receptions, payments, or stock.
 */
class StaffMonthlyPerformanceService
{
    /**
     * @return array{
     *   from:string,to:string,prev_from:string,prev_to:string,
     *   month_label:string,prev_month_label:string,
     *   shop:array<string,mixed>,prev_shop:array<string,mixed>,
     *   staff:Collection<int,array<string,mixed>>
     * }
     */
    public function build(string $from, string $to): array
    {
        $fromDay = Carbon::parse($from)->startOfDay();
        $toDay = Carbon::parse($to)->endOfDay();
        $days = max(1, $fromDay->diffInDays($toDay) + 1);
        $prevTo = $fromDay->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $shop = $this->shopMetrics($fromDay->toDateString(), $toDay->toDateString());
        $prevShop = $this->shopMetrics($prevFrom->toDateString(), $prevTo->toDateString());

        $staff = $this->staffRows($fromDay->toDateString(), $toDay->toDateString(), $prevFrom->toDateString(), $prevTo->toDateString());

        return [
            'from' => $fromDay->toDateString(),
            'to' => $toDay->toDateString(),
            'prev_from' => $prevFrom->toDateString(),
            'prev_to' => $prevTo->toDateString(),
            'month_label' => $this->labelRange($fromDay, $toDay),
            'prev_month_label' => $this->labelRange($prevFrom, $prevTo),
            'shop' => $shop,
            'prev_shop' => $prevShop,
            'shop_line' => $this->oneLine('مجموعه', $shop, $prevShop),
            'staff' => $staff,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function forUser(User $user, string $from, string $to): array
    {
        $all = $this->build($from, $to);
        $row = $all['staff']->firstWhere('user_id', $user->id);
        if (! $row) {
            $row = $this->emptyStaffRow($user);
            $row['line'] = $this->oneLine($user->name, $row, $this->emptyMetrics());
        }

        return [
            'from' => $all['from'],
            'to' => $all['to'],
            'prev_from' => $all['prev_from'],
            'prev_to' => $all['prev_to'],
            'month_label' => $all['month_label'],
            'prev_month_label' => $all['prev_month_label'],
            'shop_line' => $all['shop_line'],
            'row' => $row,
        ];
    }

    /**
     * @param  array<string,mixed>  $row
     */
    public function smsText(array $row, string $monthLabel): string
    {
        $shop = trim((string) \App\Models\AppSetting::getValue('invoice_shop_name', (string) config('app.name', 'تعمیرگاه')))
            ?: (string) config('app.name', 'تعمیرگاه');

        return "گزارش ماهانه {$shop}\n"
            ."دوره: {$monthLabel}\n"
            .($row['line'] ?? '')
            ."\nجزئیات در کارتابل اعلان‌ها.";
    }

    /**
     * @return array<string,int|float>
     */
    private function shopMetrics(string $from, string $to): array
    {
        $delivered = Reception::query()
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(total_amount),0) as revenue')
            ->selectRaw('COALESCE(SUM(labor_cost),0) as labor')
            ->selectRaw('COALESCE(SUM(parts_cost),0) as parts')
            ->selectRaw('COALESCE(SUM(stages_cost),0) as stages')
            ->selectRaw('COALESCE(SUM(discount),0) as discount')
            ->selectRaw('COALESCE(SUM(CASE WHEN warranty_return = 1 THEN 1 ELSE 0 END),0) as warranty_returns')
            ->first();

        $cancelled = Reception::query()
            ->where('status', 'cancelled')
            ->whereDate('updated_at', '>=', $from)
            ->whereDate('updated_at', '<=', $to)
            ->count();

        $collected = (int) Payment::query()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->where('type', '!=', 'refund')
            ->sum('amount');

        $refunds = (int) abs((int) Payment::query()
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->where('type', 'refund')
            ->sum('amount'));

        $revenue = (int) ($delivered->revenue ?? 0);
        $parts = (int) ($delivered->parts ?? 0);
        $labor = (int) ($delivered->labor ?? 0);
        $stages = (int) ($delivered->stages ?? 0);
        $discount = (int) ($delivered->discount ?? 0);
        // سود تقریبی مجموعه: درآمد خالص تحویل − بهای قطعات (مراحل در درآمد مانده‌اند)
        $approxProfit = $revenue - $parts;

        return [
            'delivered' => (int) ($delivered->cnt ?? 0),
            'revenue' => $revenue,
            'labor' => $labor,
            'parts' => $parts,
            'stages' => $stages,
            'discount' => $discount,
            'collected' => $collected,
            'refunds' => $refunds,
            'warranty_returns' => (int) ($delivered->warranty_returns ?? 0),
            'cancelled' => $cancelled,
            'approx_profit' => $approxProfit,
        ];
    }

    /**
     * @return Collection<int,array<string,mixed>>
     */
    private function staffRows(string $from, string $to, string $prevFrom, string $prevTo): Collection
    {
        $users = User::query()
            ->with('technician')
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'receptionist', 'technician', 'employee', 'accountant'])
            ->orderBy('name')
            ->get();

        $techIds = $users->map(fn (User $u) => $u->technician?->id)->filter()->values()->all();

        $deliveredNow = $this->deliveredByTechnician($techIds, $from, $to);
        $deliveredPrev = $this->deliveredByTechnician($techIds, $prevFrom, $prevTo);
        $intakeNow = $this->intakeByUser($from, $to);
        $intakePrev = $this->intakeByUser($prevFrom, $prevTo);
        $payNow = $this->paymentsByUser($from, $to);
        $payPrev = $this->paymentsByUser($prevFrom, $prevTo);

        return $users->map(function (User $user) use (
            $deliveredNow, $deliveredPrev, $intakeNow, $intakePrev, $payNow, $payPrev
        ) {
            $techId = $user->technician?->id;
            $cur = $this->emptyMetrics();
            $prev = $this->emptyMetrics();

            if ($techId) {
                $d = $deliveredNow[$techId] ?? null;
                $p = $deliveredPrev[$techId] ?? null;
                if ($d) {
                    $cur = array_merge($cur, $d);
                }
                if ($p) {
                    $prev = array_merge($prev, $p);
                }
                $pct = (float) ($user->technician->commission_percent ?? 0);
                $cur['commission'] = (int) round(((int) $cur['labor']) * $pct / 100);
                $prev['commission'] = (int) round(((int) $prev['labor']) * $pct / 100);
                $cur['commission_percent'] = $pct;
            }

            $cur['intake'] = (int) ($intakeNow[$user->id] ?? 0);
            $prev['intake'] = (int) ($intakePrev[$user->id] ?? 0);
            $cur['collected'] = (int) ($payNow[$user->id]['collected'] ?? 0);
            $cur['refunds'] = (int) ($payNow[$user->id]['refunds'] ?? 0);
            $prev['collected'] = (int) ($payPrev[$user->id]['collected'] ?? 0);
            $prev['refunds'] = (int) ($payPrev[$user->id]['refunds'] ?? 0);

            $cur['approx_profit'] = (int) $cur['revenue'] - (int) $cur['parts'];
            $prev['approx_profit'] = (int) $prev['revenue'] - (int) $prev['parts'];

            $hasActivity = ((int) $cur['delivered'] + (int) $cur['intake'] + (int) $cur['collected']
                + (int) $prev['delivered'] + (int) $prev['intake'] + (int) $prev['collected']) > 0;

            $row = array_merge($cur, [
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'role_label' => $user->roleLabel(),
                'phone' => $user->phone,
                'technician_id' => $techId,
                'technician_name' => $user->technician?->name,
                'has_activity' => $hasActivity,
                'prev' => $prev,
                'delta' => [
                    'delivered' => $this->deltaPct((int) $cur['delivered'], (int) $prev['delivered']),
                    'revenue' => $this->deltaPct((int) $cur['revenue'], (int) $prev['revenue']),
                    'collected' => $this->deltaPct((int) $cur['collected'], (int) $prev['collected']),
                    'approx_profit' => $this->deltaPct((int) $cur['approx_profit'], (int) $prev['approx_profit']),
                    'warranty_returns' => $this->deltaPct((int) $cur['warranty_returns'], (int) $prev['warranty_returns']),
                ],
            ]);
            $row['line'] = $this->oneLine($user->name, $cur, $prev);

            return $row;
        })->filter(fn (array $row) => $row['has_activity'] || $row['technician_id'])
            ->values();
    }

    /**
     * @param  list<int>  $techIds
     * @return array<int,array<string,int>>
     */
    private function deliveredByTechnician(array $techIds, string $from, string $to): array
    {
        if ($techIds === []) {
            return [];
        }

        $rows = Reception::query()
            ->whereIn('technician_id', $techIds)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->groupBy('technician_id')
            ->select('technician_id')
            ->selectRaw('COUNT(*) as delivered')
            ->selectRaw('COALESCE(SUM(total_amount),0) as revenue')
            ->selectRaw('COALESCE(SUM(labor_cost),0) as labor')
            ->selectRaw('COALESCE(SUM(parts_cost),0) as parts')
            ->selectRaw('COALESCE(SUM(stages_cost),0) as stages')
            ->selectRaw('COALESCE(SUM(CASE WHEN warranty_return = 1 THEN 1 ELSE 0 END),0) as warranty_returns')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->technician_id] = [
                'delivered' => (int) $row->delivered,
                'revenue' => (int) $row->revenue,
                'labor' => (int) $row->labor,
                'parts' => (int) $row->parts,
                'stages' => (int) $row->stages,
                'warranty_returns' => (int) $row->warranty_returns,
            ];
        }

        return $out;
    }

    /** @return array<int,int> */
    private function intakeByUser(string $from, string $to): array
    {
        return Reception::query()
            ->whereNotNull('created_by')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', '!=', 'cancelled')
            ->groupBy('created_by')
            ->select('created_by', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'created_by')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /** @return array<int,array{collected:int,refunds:int}> */
    private function paymentsByUser(string $from, string $to): array
    {
        $rows = Payment::query()
            ->whereNotNull('received_by')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->groupBy('received_by')
            ->select('received_by')
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'refund' THEN 0 ELSE amount END),0) as collected")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'refund' THEN ABS(amount) ELSE 0 END),0) as refunds")
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->received_by] = [
                'collected' => (int) $row->collected,
                'refunds' => (int) $row->refunds,
            ];
        }

        return $out;
    }

    /** @return array<string,int|float> */
    private function emptyMetrics(): array
    {
        return [
            'delivered' => 0,
            'revenue' => 0,
            'labor' => 0,
            'parts' => 0,
            'stages' => 0,
            'collected' => 0,
            'refunds' => 0,
            'warranty_returns' => 0,
            'cancelled' => 0,
            'intake' => 0,
            'approx_profit' => 0,
            'commission' => 0,
            'commission_percent' => 0,
        ];
    }

    /** @return array<string,mixed> */
    private function emptyStaffRow(User $user): array
    {
        $cur = $this->emptyMetrics();

        return array_merge($cur, [
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'role_label' => $user->roleLabel(),
            'phone' => $user->phone,
            'technician_id' => $user->technician?->id,
            'technician_name' => $user->technician?->name,
            'has_activity' => false,
            'prev' => $this->emptyMetrics(),
            'delta' => [
                'delivered' => null,
                'revenue' => null,
                'collected' => null,
                'approx_profit' => null,
                'warranty_returns' => null,
            ],
            'line' => '',
        ]);
    }

    /**
     * @param  array<string,mixed>  $cur
     * @param  array<string,mixed>  $prev
     */
    private function oneLine(string $name, array $cur, array $prev): string
    {
        $revDelta = $this->deltaLabel($this->deltaPct((int) ($cur['revenue'] ?? 0), (int) ($prev['revenue'] ?? 0)));
        $profitDelta = $this->deltaLabel($this->deltaPct((int) ($cur['approx_profit'] ?? 0), (int) ($prev['approx_profit'] ?? 0)));

        return sprintf(
            '%s: %d تعمیر تحویل · درآمد %s · وصول %s · سود تقریبی %s · برگشتی/گارانتی %d · نسبت به دوره قبل درآمد %s · سود %s',
            $name,
            (int) ($cur['delivered'] ?? 0),
            number_format((int) ($cur['revenue'] ?? 0)),
            number_format((int) ($cur['collected'] ?? 0)),
            number_format((int) ($cur['approx_profit'] ?? 0)),
            (int) ($cur['warranty_returns'] ?? 0),
            $revDelta,
            $profitDelta
        );
    }

    private function deltaPct(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current === 0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function deltaLabel(?float $pct): string
    {
        if ($pct === null) {
            return 'جدید';
        }
        if ($pct > 0) {
            return '+'.$pct.'٪';
        }
        if ($pct < 0) {
            return $pct.'٪';
        }

        return 'بدون تغییر';
    }

    private function labelRange(Carbon $from, Carbon $to): string
    {
        return jalali_date($from).' تا '.jalali_date($to);
    }
}
