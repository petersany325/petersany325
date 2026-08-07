<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'reception_id', 'customer_id', 'sms_status_rule_id', 'sent_by',
        'phone', 'status_key', 'audience', 'message', 'ok', 'provider_message',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
        ];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SmsStatusRule::class, 'sms_status_rule_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
