<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Reception;
use Illuminate\Support\Collection;

/**
 * Customer accounts receivable (AR) helpers.
 *
 * Standard repair-shop flow:
 * 1) Bill → Dr Receivable 1210 / Cr Revenue (AccountingService::syncReceptionRevenue)
 * 2) Credit delivery (نسیه) → goods leave; unpaid remaining stays as customer debt
 * 3) Collection → Dr Cash / Cr Receivable (postPayment)
 */
class CustomerDebtService
{
    /**
     * Open receivable tickets: remaining > 0, not cancelled.
     * Includes ready (pre-delivery) and delivered credit (post-delivery).
     *
     * @return Collection<int, Reception>
     */
    public function openTickets(Customer $customer, int $limit = 50): Collection
    {
        return $customer->receptions()
            ->with(['customer:id,name,phone'])
            ->withCount('parts')
            ->where('status', '!=', 'cancelled')
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->filter(fn (Reception $r) => $r->remainingAmount() > 0)
            ->values();
    }

    /** Delivered-on-credit tickets still unpaid (true نسیه debt). */
    public function creditTickets(Customer $customer, int $limit = 50): Collection
    {
        return $this->openTickets($customer, $limit)
            ->filter(fn (Reception $r) => $r->status === 'delivered'
                || $r->settlement_mode === ReceptionSettlementService::MODE_CREDIT)
            ->values();
    }

    public function totalOpen(Customer $customer): int
    {
        return (int) $this->openTickets($customer, 200)->sum(fn (Reception $r) => $r->remainingAmount());
    }

    public function totalCredit(Customer $customer): int
    {
        return (int) $this->creditTickets($customer, 200)->sum(fn (Reception $r) => $r->remainingAmount());
    }

    /**
     * @return array{
     *   has_debt:bool,
     *   total:int,
     *   credit_total:int,
     *   ticket_count:int,
     *   tickets:Collection<int, Reception>
     * }
     */
    public function summary(Customer $customer): array
    {
        $tickets = $this->openTickets($customer);
        $total = (int) $tickets->sum(fn (Reception $r) => $r->remainingAmount());
        $creditTotal = (int) $tickets
            ->filter(fn (Reception $r) => $r->status === 'delivered')
            ->sum(fn (Reception $r) => $r->remainingAmount());

        $limit = $customer->hasCreditLimit() ? (int) $customer->credit_limit : null;

        return [
            'has_debt' => $total > 0,
            'total' => $total,
            'credit_total' => $creditTotal,
            'ticket_count' => $tickets->count(),
            'tickets' => $tickets,
            'credit_limit' => $limit,
            'credit_headroom' => $limit === null ? null : max(0, $limit - $total),
            'over_credit_limit' => $limit !== null && $total > $limit,
        ];
    }

    /**
     * پیام خطای سقف اعتبار برای تحویل نسیه، یا null اگر مجاز باشد.
     * مانده قبض فعلی داخل totalOpen حساب شده است.
     */
    public function creditLimitBlockMessage(Customer $customer, ?int $openDebt = null): ?string
    {
        if (! $customer->hasCreditLimit()) {
            return null;
        }

        $limit = (int) $customer->credit_limit;
        $open = $openDebt ?? $this->totalOpen($customer);
        if ($open <= $limit) {
            return null;
        }

        if ($limit <= 0) {
            return 'سقف اعتبار این مشتری صفر است — نسیه مجاز نیست. مانده بدهی: '
                .number_format($open).' تومان.';
        }

        return 'سقف اعتبار مشتری پر شده است. سقف: '
            .number_format($limit)
            .' تومان — مانده بدهی فعلی: '
            .number_format($open)
            .' تومان ('.number_format($open - $limit).' تومان بیش از سقف). نسیه ثبت نمی‌شود.';
    }
}
