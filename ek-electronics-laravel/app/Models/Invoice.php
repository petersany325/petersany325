<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'order_id', 'customer_name', 'customer_phone', 'amount', 'vat_amount',
        'status', 'due_date', 'paid_at', 'whatsapp_sent_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
