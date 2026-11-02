<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerMessage extends Model
{
    protected $fillable = [
        'customer_id', 'reception_id', 'body', 'priority', 'staff_read_at', 'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'staff_read_at' => 'datetime',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isUnread(): bool
    {
        return $this->staff_read_at === null;
    }

    public function priorityLabel(): string
    {
        return $this->priority === 'urgent' ? 'فوری' : 'عادی';
    }
}
