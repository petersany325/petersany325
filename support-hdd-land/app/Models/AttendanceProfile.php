<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceProfile extends Model
{
    protected $fillable = [
        'user_id',
        'reference_photo_path',
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
        'phone_gps_lat' => 'float',
        'phone_gps_lng' => 'float',
        'phone_gps_accuracy_m' => 'float',
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

    public function hasReferencePhoto(): bool
    {
        return filled($this->reference_photo_path);
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null
            && $this->hasReferencePhoto()
            && $this->phone_gps_lat !== null
            && $this->phone_gps_lng !== null;
    }
}
