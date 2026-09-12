<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHandoff extends Model
{
    public const DIR_TO_BENCH = 'to_bench';

    public const DIR_TO_FRONT = 'to_front_desk';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reception_id', 'from_user_id', 'to_user_id', 'to_technician_id',
        'direction', 'serial_snapshot', 'status', 'note', 'response_note', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function toTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'to_technician_id');
    }

    public function directionLabel(): string
    {
        return match ($this->direction) {
            self::DIR_TO_BENCH => 'ارجاع به تعمیرکار',
            self::DIR_TO_FRONT => 'بازگشت به پذیرش',
            default => $this->direction,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'در انتظار تأیید',
            self::STATUS_ACCEPTED => 'تأیید دریافت',
            self::STATUS_REJECTED => 'رد دریافت',
            self::STATUS_CANCELLED => 'لغو شده',
            default => $this->status,
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
