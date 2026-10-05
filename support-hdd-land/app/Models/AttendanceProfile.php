<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceProfile extends Model
{
    public const SELFIE_NONE = 'none';

    public const SELFIE_PENDING = 'pending';

    public const SELFIE_APPROVED = 'approved';

    public const SELFIE_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'reference_photo_path',
        'selfie_status',
        'face_detected',
        'selfie_approved_at',
        'selfie_approved_by',
        'selfie_reject_reason',
        'device_fingerprint',
        'enrolled_at',
        'enrolled_by',
        'onboarding_completed_at',
        'phone_gps_lat',
        'phone_gps_lng',
        'phone_gps_accuracy_m',
        'phone_gps_captured_at',
        'is_active',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'phone_gps_captured_at' => 'datetime',
        'selfie_approved_at' => 'datetime',
        'phone_gps_lat' => 'float',
        'phone_gps_lng' => 'float',
        'phone_gps_accuracy_m' => 'float',
        'face_detected' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrolledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selfie_approved_by');
    }

    public function hasReferencePhoto(): bool
    {
        return filled($this->reference_photo_path);
    }

    public function isSelfiePending(): bool
    {
        return $this->selfie_status === self::SELFIE_PENDING;
    }

    public function isSelfieApproved(): bool
    {
        return $this->selfie_status === self::SELFIE_APPROVED && $this->hasReferencePhoto();
    }

    public function isSelfieRejected(): bool
    {
        return $this->selfie_status === self::SELFIE_REJECTED;
    }

    public function selfieStatusLabel(): string
    {
        return match ($this->selfie_status) {
            self::SELFIE_PENDING => 'در انتظار تأیید ادمین',
            self::SELFIE_APPROVED => 'تأیید شده',
            self::SELFIE_REJECTED => 'رد شده',
            default => 'ثبت نشده',
        };
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null
            && $this->hasReferencePhoto()
            && $this->phone_gps_lat !== null
            && $this->phone_gps_lng !== null;
    }
}
