<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayTransaction extends Model
{
    protected $fillable = [
        'gateway', 'reception_id', 'customer_id', 'payment_id',
        'amount', 'currency', 'authority', 'ref_id', 'card_pan',
        'status', 'source', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'paid_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public const STATUSES = [
        'pending' => 'در انتظار',
        'paid' => 'پرداخت‌شده',
        'failed' => 'ناموفق',
        'cancelled' => 'لغو',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
