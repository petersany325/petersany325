<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentItem extends Model
{
    public const STATUSES = [
        'pending' => 'در انتظار',
        'partial' => 'ناقص',
        'paid' => 'پرداخت‌شده',
        'overdue' => 'معوق',
        'cancelled' => 'لغو',
    ];

    protected $fillable = [
        'installment_plan_id', 'sequence', 'due_date', 'amount', 'paid_amount',
        'status', 'notes', 'reminded_before_at', 'reminded_due_at', 'after_reminders',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'integer',
            'paid_amount' => 'integer',
            'reminded_before_at' => 'datetime',
            'reminded_due_at' => 'datetime',
            'after_reminders' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(InstallmentCheck::class);
    }

    public function remainingAmount(): int
    {
        return max(0, (int) $this->amount - (int) $this->paid_amount);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $remaining = $this->remainingAmount();
        if ($remaining <= 0) {
            $status = 'paid';
        } elseif ((int) $this->paid_amount > 0) {
            $status = 'partial';
        } elseif ($this->due_date && $this->due_date->lt(now()->startOfDay())) {
            $status = 'overdue';
        } else {
            $status = 'pending';
        }

        if ($this->status !== $status) {
            $this->forceFill(['status' => $status])->save();
        }
    }
}
