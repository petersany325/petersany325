<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reception extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_no', 'receipt_no', 'batch_code', 'delivery_batch_id', 'account_code', 'admission_type', 'service_type', 'repair_type',
        'customer_id', 'technician_id', 'custody_technician_id', 'fault_type_id', 'created_by',
        'custody',
        'product_name', 'brand', 'model', 'serial_number', 'accessories', 'appearance_notes',
        'delivered_by', 'pickup_name', 'pickup_phone', 'referrer', 'commission', 'photo_path',
        'hdd_capacity', 'capacity_changed', 'hdd_capacity_after',
        'warranty_return', 'warranty_type', 'card_number', 'warranty_end_date',
        'reported_fault', 'final_fault', 'technician_notes', 'status',
        'deposit', 'pos_amount', 'admission_fee', 'estimated_cost', 'payment_method',
        'labor_cost', 'parts_cost', 'stages_cost', 'discount', 'discount_reason', 'total_amount', 'paid_amount',
        'settlement_mode', 'settled_at', 'settlement_note',
        'exit_otp_required', 'exit_otp_verified_at', 'exit_otp_bypass_reason',
        'cost_confirmed_at',
        'cost_approval_status', 'customer_cost_approved_at', 'customer_cost_approved_amount',
        'estimated_delivery_at', 'next_visit_at', 'received_at', 'delivered_at',
        'delivery_cancelled_at', 'delivery_cancel_count',
        'deleted_by', 'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_cancelled_at' => 'datetime',
            'settled_at' => 'datetime',
            'exit_otp_verified_at' => 'datetime',
            'exit_otp_required' => 'boolean',
            'cost_confirmed_at' => 'datetime',
            'customer_cost_approved_at' => 'datetime',
            'estimated_delivery_at' => 'date',
            'next_visit_at' => 'date',
            'warranty_end_date' => 'date',
            'warranty_return' => 'boolean',
            'capacity_changed' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public const STATUSES = [
        'received' => 'پذیرش‌شده',
        'repairing' => 'در حال تعمیر',
        'waiting_part' => 'منتظر قطعه',
        'ready' => 'آماده تحویل',
        'unrepairable' => 'غیرقابل تعمیر',
        'delivered' => 'تحویل‌شده',
        'cancelled' => 'لغو شده',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function custodyTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'custody_technician_id');
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(DeviceHandoff::class);
    }

    public function workReports(): HasMany
    {
        return $this->hasMany(ReceptionWorkReport::class)->latest('id');
    }

    public function latestWorkReport()
    {
        return $this->hasOne(ReceptionWorkReport::class)->latestOfMany();
    }

    public function customerMessages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public function costApprovals(): HasMany
    {
        return $this->hasMany(CostApproval::class)->latest('id');
    }

    public function latestCostApproval()
    {
        return $this->hasOne(CostApproval::class)->latestOfMany();
    }

    public function custodyLabel(): string
    {
        return match ($this->custody ?? 'front_desk') {
            'with_technician' => 'نزد تعمیرکار'.($this->custodyTechnician?->name ? ' ('.$this->custodyTechnician->name.')' : ''),
            'returning' => 'در حال بازگشت به پذیرش',
            default => 'نزد پذیرش / منشی',
        };
    }

    public function faultType(): BelongsTo
    {
        return $this->belongsTo(FaultType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(ReceptionPart::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReceptionStatusLog::class)->latest('id');
    }

    public function exitOtps(): HasMany
    {
        return $this->hasMany(ReceptionExitOtp::class)->latest('id');
    }

    public function needsExitOtp(): bool
    {
        return (bool) $this->exit_otp_required && ! $this->exit_otp_verified_at;
    }

    public function costStages(): HasMany
    {
        return $this->hasMany(ReceptionCostStage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function canEditParts(): bool
    {
        return ! $this->isDelivered();
    }

    public function statusLabel(): string
    {
        return SmsStatusRule::statusMap()[$this->status]
            ?? (self::STATUSES[$this->status] ?? $this->status);
    }

    public static function availableStatuses(): array
    {
        return SmsStatusRule::statusMap();
    }

    public function grossCost(): int
    {
        return max(0,
            (int) $this->labor_cost
            + (int) $this->parts_cost
            + (int) $this->stages_cost
            + (int) $this->admission_fee
        );
    }

    public function remainingAmount(): int
    {
        return max(0, (int) $this->total_amount - (int) $this->paid_amount);
    }

    /** Effective capacity after repair when it changed; otherwise intake capacity. */
    public function effectiveHddCapacity(): ?string
    {
        if ($this->capacity_changed && filled($this->hdd_capacity_after)) {
            return (string) $this->hdd_capacity_after;
        }

        return $this->hdd_capacity ? (string) $this->hdd_capacity : null;
    }

    /** Human summary for reports / portal: "1TB → 500GB" or original. */
    public function capacityLabel(): string
    {
        $before = $this->hdd_capacity ?: null;
        if ($this->capacity_changed && filled($this->hdd_capacity_after)) {
            $from = $before ?: 'نامشخص';

            return $from.' → '.$this->hdd_capacity_after.' (تغییر فضا)';
        }

        return $before ?: '—';
    }

    /**
     * Operational finance status for staff UI (independent of workflow status badge).
     *
     * settled | credit_settled | credit_open | credit_partial |
     * unpaid | partial | waived | no_charge | cancelled
     */
    public function financeStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $remain = $this->remainingAmount();
        $paid = (int) $this->paid_amount;
        $total = (int) $this->total_amount;
        $wasWaive = $this->settlement_mode === \App\Services\ReceptionSettlementService::MODE_WAIVE;

        if ($remain <= 0) {
            if ($total <= 0 && $paid <= 0) {
                return $wasWaive ? 'waived' : 'no_charge';
            }
            if ($wasWaive) {
                return 'waived';
            }
            if ($this->settlement_mode === \App\Services\ReceptionSettlementService::MODE_CREDIT) {
                return 'credit_settled';
            }

            return 'settled';
        }

        if ($this->isDelivered() || $this->settlement_mode === \App\Services\ReceptionSettlementService::MODE_CREDIT) {
            return $paid > 0 ? 'credit_partial' : 'credit_open';
        }

        return $paid > 0 ? 'partial' : 'unpaid';
    }

    public function financeStatusLabel(): string
    {
        return match ($this->financeStatus()) {
            'settled' => 'تسویه‌شده',
            'credit_settled' => 'نسیه تسویه‌شده',
            'credit_open' => 'نسیه — بدهی باز',
            'credit_partial' => 'نسیه — پرداخت جزئی',
            'unpaid' => 'پرداخت‌نشده',
            'partial' => 'پرداخت جزئی',
            'waived' => 'بخشش‌شده',
            'no_charge' => 'بدون مبلغ',
            'cancelled' => 'لغو',
            default => '—',
        };
    }

    /** Delivered (or credit) ticket that still has receivable remaining — can collect cash later. */
    public function canCollectDebt(): bool
    {
        return $this->status !== 'cancelled'
            && $this->remainingAmount() > 0
            && ($this->isDelivered()
                || $this->settlement_mode === \App\Services\ReceptionSettlementService::MODE_CREDIT);
    }

    public function hasCostSet(): bool
    {
        if ($this->cost_confirmed_at) {
            return true;
        }

        return ((int) $this->total_amount) > 0
            || ((int) $this->labor_cost) > 0
            || ((int) $this->parts_cost) > 0
            || ((int) $this->stages_cost) > 0;
    }

    public function confirmCost(): void
    {
        $this->forceFill(['cost_confirmed_at' => now()])->save();
    }

    public function deliveryBatch(): BelongsTo
    {
        return $this->belongsTo(DeliveryBatch::class);
    }

    public function recalculateTotals(): void
    {
        $partsCost = (int) $this->parts()->sum('total_price');
        $stagesCost = (int) $this->costStages()
            ->whereIn('status', ['draft', 'pending_approval', 'approved', 'waived'])
            ->sum('amount');
        $total = $partsCost + $stagesCost + (int) $this->labor_cost + (int) $this->admission_fee - (int) $this->discount;
        $paid = (int) $this->payments()->sum('amount');

        $this->forceFill([
            'parts_cost' => $partsCost,
            'stages_cost' => $stagesCost,
            'total_amount' => max(0, $total),
            'paid_amount' => $paid,
        ])->save();
    }

    public static function nextTicketNo(): string
    {
        $prefix = 'SH-'.now()->format('ymd');
        // Include soft-deleted rows: unique index still holds them, so skipping
        // trash caused Duplicate entry SH-yymmdd-0001 and HTTP 500 on create.
        // "SH-260809-0001" → numeric suffix starts at strlen(prefix)+2 (1-based SQL).
        $suffixStart = strlen($prefix) + 2;
        $dbMax = (int) (static::withTrashed()
            ->where('ticket_no', 'like', $prefix.'-%')
            ->selectRaw('MAX(CAST(SUBSTRING(ticket_no, ?) AS UNSIGNED)) as m', [$suffixStart])
            ->value('m') ?? 0);

        return $prefix.'-'.str_pad((string) ($dbMax + 1), 4, '0', STR_PAD_LEFT);
    }

    public static function nextReceiptNo(): string
    {
        $prefix = 'T-20N';
        $max = 999; // next => T-20N1000

        // Include soft-deleted rows (same unique-index trap as ticket_no).
        $dbMax = (int) (static::withTrashed()
            ->where('receipt_no', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(receipt_no, ?) AS UNSIGNED)) as m', [strlen($prefix) + 1])
            ->value('m') ?? 0);

        return $prefix.(max($max, $dbMax) + 1);
    }
}
