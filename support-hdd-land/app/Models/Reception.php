<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reception extends Model
{
    protected $fillable = [
        'ticket_no', 'receipt_no', 'batch_code', 'delivery_batch_id', 'account_code', 'admission_type', 'service_type', 'repair_type',
        'customer_id', 'technician_id', 'custody_technician_id', 'fault_type_id', 'created_by',
        'custody',
        'product_name', 'brand', 'model', 'serial_number', 'accessories', 'appearance_notes',
        'delivered_by', 'pickup_name', 'pickup_phone', 'referrer', 'commission', 'photo_path',
        'hdd_capacity', 'warranty_return', 'warranty_type', 'card_number', 'warranty_end_date',
        'reported_fault', 'final_fault', 'technician_notes', 'status',
        'deposit', 'pos_amount', 'admission_fee', 'estimated_cost', 'payment_method',
        'labor_cost', 'parts_cost', 'stages_cost', 'discount', 'discount_reason', 'total_amount', 'paid_amount',
        'settlement_mode', 'settled_at', 'settlement_note',
        'cost_confirmed_at',
        'cost_approval_status', 'customer_cost_approved_at', 'customer_cost_approved_amount',
        'estimated_delivery_at', 'next_visit_at', 'received_at', 'delivered_at',
        'delivery_cancelled_at', 'delivery_cancel_count',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_cancelled_at' => 'datetime',
            'settled_at' => 'datetime',
            'cost_confirmed_at' => 'datetime',
            'customer_cost_approved_at' => 'datetime',
            'estimated_delivery_at' => 'date',
            'next_visit_at' => 'date',
            'warranty_end_date' => 'date',
            'warranty_return' => 'boolean',
        ];
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
        // Padded 4-digit suffix → lexical MAX matches numeric MAX.
        $last = static::query()
            ->where('ticket_no', 'like', $prefix.'-%')
            ->orderByDesc('ticket_no')
            ->value('ticket_no');

        $seq = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function nextReceiptNo(): string
    {
        $prefix = 'T-20N';
        $max = 999; // next => T-20N1000

        // Numeric MAX via SQL — never load every receipt_no into PHP.
        $dbMax = (int) (static::query()
            ->where('receipt_no', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(receipt_no, ?) AS UNSIGNED)) as m', [strlen($prefix) + 1])
            ->value('m') ?? 0);

        return $prefix.(max($max, $dbMax) + 1);
    }
}
