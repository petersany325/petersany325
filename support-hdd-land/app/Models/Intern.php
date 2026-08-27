<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intern extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'national_code',
        'start_date', 'end_date', 'department', 'notes',
        'is_active', 'created_by', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasPortalAccess(): bool
    {
        $user = $this->user;

        return $user
            && $user->is_active
            && ($user->can_login_otp || $user->can_login_password);
    }

    public function isCurrent(): bool
    {
        $today = now('Asia/Tehran')->startOfDay();
        if (! $this->is_active) {
            return false;
        }

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'غیرفعال';
        }
        $today = now('Asia/Tehran')->startOfDay();
        if ($this->end_date < $today) {
            return 'پایان‌یافته';
        }
        if ($this->start_date > $today) {
            return 'آینده';
        }

        return 'در حال کارآموزی';
    }
}
