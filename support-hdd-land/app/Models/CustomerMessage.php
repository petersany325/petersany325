<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMessage extends Model
{
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    protected $fillable = [
        'customer_id', 'reception_id', 'remote_part_preorder_id', 'body', 'priority',
        'direction', 'staff_read_at', 'customer_read_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'staff_read_at' => 'datetime',
            'customer_read_at' => 'datetime',
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

    public function preorder(): BelongsTo
    {
        return $this->belongsTo(RemotePartPreorder::class, 'remote_part_preorder_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isFromShop(): bool
    {
        return ($this->direction ?: self::DIRECTION_INBOUND) === self::DIRECTION_OUTBOUND;
    }

    public function isUnread(): bool
    {
        if ($this->isFromShop()) {
            return $this->customer_read_at === null;
        }

        return $this->staff_read_at === null;
    }

    public function priorityLabel(): string
    {
        return $this->priority === 'urgent' ? 'فوری' : 'عادی';
    }
}
