<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'reception_id', 'customer_id', 'received_by',
        'type', 'method', 'amount', 'note', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public const TYPES = [
        'deposit' => 'بیعانه',
        'partial' => 'پرداخت جزئی',
        'final' => 'تسویه نهایی',
        'refund' => 'عودت',
    ];

    public const METHODS = [
        'cash' => 'نقدی',
        'card' => 'کارتخوان',
        'transfer' => 'کارت‌به‌کارت',
        'zarinpal' => 'درگاه اینترنتی (زرین‌پال)',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }
}
