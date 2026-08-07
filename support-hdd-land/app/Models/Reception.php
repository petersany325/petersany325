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
        'labor_cost', 'parts_cost', 'discount', 'total_amount', 'paid_amount', 'cost_confirmed_at',
        'estimated_delivery_at', 'next_visit_at', 'received_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cost_confirmed_at' => 'datetime',
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

    public function customerMessages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
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

    public function statusLabel(): string
    {
        return SmsStatusRule::statusMap()[$this->status]
            ?? (self::STATUSES[$this->status] ?? $this->status);
    }

    public static function availableStatuses(): array
    {
        return SmsStatusRule::statusMap();
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
            || ((int) $this->parts_cost) > 0;
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
        $total = $partsCost + (int) $this->labor_cost + (int) $this->admission_fee - (int) $this->discount;
        $paid = (int) $this->payments()->sum('amount');

        $this->forceFill([
            'parts_cost' => $partsCost,
            'total_amount' => max(0, $total),
            'paid_amount' => $paid,
        ])->save();
    }

    public static function nextTicketNo(): string
    {
        $prefix = 'SH-'.now()->format('ymd');
        $last = static::where('ticket_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('ticket_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function nextReceiptNo(): string
    {
        $last = static::orderByDesc('id')->value('receipt_no');
        $num = $last && ctype_digit((string) $last) ? ((int) $last + 1) : 10001;

        return (string) $num;
    }
}
