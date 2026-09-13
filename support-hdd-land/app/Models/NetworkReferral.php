<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NetworkReferral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_AWAITING = 'awaiting_decision';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'uuid', 'from_license_id', 'to_license_id',
        'from_domain', 'to_domain', 'from_shop_name', 'to_shop_name',
        'origin_receipt_no', 'origin_ticket_no', 'dest_receipt_no',
        'status', 'payload', 'reject_reason', 'pulled_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'pulled_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $row) {
            if (! $row->uuid) {
                $row->uuid = (string) Str::uuid();
            }
        });
    }

    public function fromLicense(): BelongsTo
    {
        return $this->belongsTo(ProductLicense::class, 'from_license_id');
    }

    public function toLicense(): BelongsTo
    {
        return $this->belongsTo(ProductLicense::class, 'to_license_id');
    }
}
