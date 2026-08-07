<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'national_code', 'job', 'address',
        'referral_source_id', 'notes',
    ];

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

    public function messages(): HasMany
    {
        return $this->hasMany(CustomerMessage::class);
    }

    public static function findByPhone(?string $phone): ?self
    {
        $phone = User::normalizePhone($phone);
        if (! $phone || strlen($phone) < 10) {
            return null;
        }

        $tail = substr($phone, -10);

        return static::query()
            ->where(function (Builder $q) use ($phone, $tail) {
                $q->where('phone', $phone)
                    ->orWhere('phone', ltrim($phone, '0'))
                    ->orWhere('phone', '0'.ltrim($phone, '0'))
                    ->orWhere('phone', '98'.ltrim($phone, '0'))
                    ->orWhere('phone', '+98'.ltrim($phone, '0'));

                if ($tail !== '') {
                    $q->orWhere('phone', 'like', '%'.$tail);
                }
            })
            ->orderByDesc('id')
            ->first();
    }
}
