<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalInviteSend extends Model
{
    protected $fillable = [
        'batch_id', 'customer_id', 'phone', 'message', 'ok',
        'provider_message', 'sent_by', 'sms_log_id',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PortalInviteBatch::class, 'batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
