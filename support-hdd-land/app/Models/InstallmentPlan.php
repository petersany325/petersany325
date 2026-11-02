<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPlan extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'active' => 'فعال',
        'completed' => 'تسویه',
        'cancelled' => 'لغو',
    ];

    public const SCHEDULE_MODES = [
        'auto' => 'خودکار',
        'manual' => 'دستی',
    ];

    protected $fillable = [
        'customer_id', 'reception_id', 'created_by', 'title',
        'total_amount', 'down_payment', 'installment_count', 'interval_days',
        'schedule_mode', 'start_date', 'status', 'notes',
        'guarantor_name', 'guarantor_phone', 'guarantor_national_code',
        'guarantor_address', 'guarantor_notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'down_payment' => 'integer',
            'installment_count' => 'integer',
            'interval_days' => 'integer',
            'start_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstallmentItem::class)->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(InstallmentCheck::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paidTotal(): int
    {
        return (int) $this->items->sum('paid_amount');
    }

    public function remainingTotal(): int
    {
        return max(0, (int) $this->items->sum(fn (InstallmentItem $i) => $i->remainingAmount()));
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $allPaid = $this->items()->where('status', '!=', 'cancelled')->get()
            ->every(fn (InstallmentItem $i) => $i->remainingAmount() <= 0);

        $this->forceFill([
            'status' => $allPaid ? 'completed' : 'active',
        ])->save();
    }
}
