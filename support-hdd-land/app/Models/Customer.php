<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
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
}
