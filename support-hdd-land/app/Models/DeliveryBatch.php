<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryBatch extends Model
{
    protected $fillable = [
        'batch_code', 'pickup_name', 'pickup_phone', 'ticket_count',
        'total_amount', 'note', 'sms_sent', 'created_by', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'sms_sent' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public static function nextCode(): string
    {
        return 'DLV-'.now()->format('ymdHis').'-'.random_int(100, 999);
    }
}
