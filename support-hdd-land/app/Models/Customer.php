<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    public const GENDERS = [
        'male' => 'آقا',
        'female' => 'خانم',
        'other' => 'سایر',
    ];

    protected $fillable = [
        'name', 'alias', 'gender', 'phone', 'national_code', 'job', 'address',
        'referral_source_id', 'notes',
        'is_blacklisted', 'blacklist_reason', 'blacklisted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_blacklisted' => 'boolean',
            'blacklisted_at' => 'datetime',
        ];
    }

    public function displayName(): string
    {
        $alias = trim((string) $this->alias);
        if ($alias !== '') {
            return $this->name.' ('.$alias.')';
        }

        return (string) $this->name;
    }

    public function genderLabel(): string
    {
        return self::GENDERS[$this->gender] ?? '—';
    }

    public function referralSource(): BelongsTo
    {
        return $this->belongsTo(ReferralSource::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public function remotePartPreorders(): HasMany
    {
        return $this->hasMany(RemotePartPreorder::class);
    }

    /** Open receivable balance (operational: sum of remaining on non-cancelled tickets). */
    public function openDebtTotal(): int
    {
        return (int) app(\App\Services\CustomerDebtService::class)->totalOpen($this);
    }

    /**
     * تطبیق هویت با موبایل — فقط قالب‌های دقیق (بدون LIKE فازی).
     */
    public static function findByPhone(?string $phone): ?self
    {
        $phone = User::normalizePhone($phone);
        if (! $phone || strlen($phone) < 10) {
            return null;
        }

        $digits = ltrim($phone, '0');
        $candidates = array_values(array_unique(array_filter([
            $phone,
            $digits,
            '0'.$digits,
            '98'.$digits,
            '+98'.$digits,
        ])));

        return static::query()
            ->whereIn('phone', $candidates)
            ->orderByDesc('id')
            ->first();
    }
}
