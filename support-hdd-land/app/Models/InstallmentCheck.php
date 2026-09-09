<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentCheck extends Model
{
    public const STATUSES = [
        'held' => 'امانی / نزد ما',
        'deposited' => 'واگذار به بانک',
        'cleared' => 'وصول‌شده',
        'bounced' => 'برگشتی',
        'returned' => 'مسترد',
    ];

    protected $fillable = [
        'installment_plan_id', 'installment_item_id', 'check_number',
        'bank_name', 'branch', 'account_no', 'amount', 'due_date',
        'holder_name', 'status', 'is_custody', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
            'is_custody' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InstallmentItem::class, 'installment_item_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'installment_check_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
