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

        return [
            'has_debt' => $total > 0,
            'total' => $total,
            'credit_total' => $creditTotal,
            'ticket_count' => $tickets->count(),
            'tickets' => $tickets,
        ];
    }
}
