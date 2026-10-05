<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEvent extends Model
{
    public const TYPE_IN = 'check_in';

    public const TYPE_OUT = 'check_out';

    public const METHOD_SELFIE = 'selfie';

    public const METHOD_OTP = 'otp';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FLAGGED = 'flagged';

    protected $fillable = [
        'user_id',
        'type',
        'method',
        'status',
        'latitude',
        'longitude',
        'accuracy_m',
        'distance_m',
        'inside_geofence',
        'photo_path',
        'ip',
        'user_agent',
        'device_fingerprint',
        'otp_verified',
        'flag_reason',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_m' => 'float',
        'inside_geofence' => 'boolean',
        'otp_verified' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_OUT ? 'خروج' : 'ورود';
    }

    public function methodLabel(): string
    {
        return $this->method === self::METHOD_OTP ? 'رمز یک‌بارمصرف' : 'سلفی';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REJECTED => 'رد شده',
            self::STATUS_FLAGGED => 'مشکوک',
            default => 'قبول',
        };
    }
}
