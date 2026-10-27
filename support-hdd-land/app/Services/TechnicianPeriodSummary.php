<?php

namespace App\Services;

use App\Models\Reception;
use Illuminate\Support\Collection;

/**
 * Read-only split for a technician in a report period.
 *
 * کار = قبض‌های قیمت‌خورده‌ای که در بازه خروج شده‌اند + آن‌هایی که هنوز خروج نشده‌اند.
 * خرجی = قطعهٔ همان کارها (خروج‌شده و داخل کارگاه).
 * مبلغ خروجی = جمع قبض‌هایی که در همین بازه خروج کامل شده‌اند.
 *
 * سود ماه از مبلغ خروجی منهای خرجیِ همان خروج‌هاست؛ خرجیِ داخل کارگاه در سود نمی‌آید.
 */
class TechnicianPeriodSummary
{
    /**
     * @param  list<int|string>  $technicianIds
     * @return array<int, array{
     *   work_count:int, exit_count:int, open_count:int,
     *   expense:int, exit_amount:int, exit_parts:int, open_parts:int, line:string
     * }>
     */
    public function forTechnicians(array $technicianIds, string $from, string $to): array
    {
        $technicianIds = array_values(array_unique(array_filter(array_map('intval', $technicianIds))));
        $blank = $this->blank();
        $out = [];
        foreach ($technicianIds as $id) {
            $out[$id] = $blank;
        }
        if ($technicianIds === []) {
            return $out;
        }

        $exited = Reception::query()
            ->whereIn('technician_id', $technicianIds)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', '>=', $from)
            ->whereDate('delivered_at', '<=', $to)
            ->groupBy('technician_id')
            ->select('technician_id')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(total_amount),0) as amount')
            ->selectRaw('COALESCE(SUM(parts_cost),0) as parts')
            ->get();

        foreach ($exited as $row) {
            $id = (int) $row->technician_id;
            if (! isset($out[$id])) {
                $out[$id] = $blank;
            }
            $out[$id]['exit_count'] = (int) $row->cnt;
            $out[$id]['exit_amount'] = (int) $row->amount;
            $out[$id]['exit_parts'] = (int) $row->parts;
        }

        $openRows = $this->openQuery($technicianIds, $from, $to)
            ->groupBy('technician_id')
            ->select('technician_id')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(parts_cost),0) as parts')
            ->get();

        foreach ($openRows as $row) {
            $id = (int) $row->technician_id;
            if (! isset($out[$id])) {
                $out[$id] = $blank;
            }
            $out[$id]['open_count'] = (int) $row->cnt;
            $out[$id]['open_parts'] = (int) $row->parts;
        }

        foreach ($out as $id => $row) {
            $work = $row['exit_count'] + $row['open_count'];
            $expense = $row['exit_parts'] + $row['open_parts'];
            $out[$id]['work_count'] = $work;
            $out[$id]['expense'] = $expense;
            $out[$id]['line'] = $this->line($work, $expense, $row['exit_amount'], $row['exit_count'], $row['open_count']);
        }

        return $out;
    }

    /**
     * Priced tickets that have not fully exited, using the same rule as the summary.
     *
     * @return Collection<int, Reception>
     */
    public function openReceipts(int $technicianId, string $from, string $to): Collection
    {
        if ($technicianId <= 0) {
            return collect();
        }

        return $this->openQuery([$technicianId], $from, $to)
            ->with('customer')
            ->latest('id')
            ->limit(150)
            ->get();
    }

    public function line(int $work, int $expense, int $exitAmount, int $exitCount, int $openCount): string
    {
        $text = $work.' کار کردی. خرجی '.number_format($expense).'. مبلغ خروجی '.number_format($exitAmount).'.';
        if ($work > 0) {
            $text .= ' '.$exitCount.' خروج، '.$openCount.' هنوز داخل کارگاه.';
        }

        return $text;
    }

    /**
     * @param  list<int>  $technicianIds
     */
    private function openQuery(array $technicianIds, string $from, string $to)
    {
        $query = Reception::query()
            ->whereIn('technician_id', $technicianIds)
            ->where('status', '!=', 'cancelled')
            ->where(function ($w) {
                $w->where('labor_cost', '>', 0)
                    ->orWhere('parts_cost', '>', 0)
                    ->orWhere('total_amount', '>', 0);
            });

        return $query
            ->where(function ($w) use ($to) {
                $w->where('status', '!=', 'delivered')
                    ->orWhereNull('delivered_at')
                    ->orWhereDate('delivered_at', '>', $to);
            })
            ->whereRaw('DATE(COALESCE(cost_confirmed_at, created_at)) >= ?', [$from])
            ->whereRaw('DATE(COALESCE(cost_confirmed_at, created_at)) <= ?', [$to]);
    }

    /**
     * @return array{
     *   work_count:int, exit_count:int, open_count:int,
     *   expense:int, exit_amount:int, exit_parts:int, open_parts:int, line:string
     * }
     */
    private function blank(): array
    {
        return [
            'work_count' => 0,
            'exit_count' => 0,
            'open_count' => 0,
            'expense' => 0,
            'exit_amount' => 0,
            'exit_parts' => 0,
            'open_parts' => 0,
            'line' => $this->line(0, 0, 0, 0, 0),
        ];
    }
}
