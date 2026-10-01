<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    public const METHODS = [
        'cash' => 'نقد',
        'card' => 'کارت',
        'transfer' => 'کارت‌به‌کارت',
        'check' => 'چک',
    ];

    protected $fillable = [
        'installment_plan_id', 'installment_item_id', 'payment_id',
        'installment_check_id', 'received_by', 'amount', 'method',
        'paid_at', 'note', 'thanks_sms_sent',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'thanks_sms_sent' => 'boolean',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function check(): BelongsTo
    {
        return $this->belongsTo(InstallmentCheck::class, 'installment_check_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
