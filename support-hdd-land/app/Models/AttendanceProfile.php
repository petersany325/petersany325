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
        'is_active',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
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
}
