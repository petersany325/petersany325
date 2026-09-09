<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionWorkReport extends Model
{
    protected $fillable = [
        'reception_id',
        'user_id',
        'technician_id',
        'summary',
        'details',
        'needs_part',
        'result_status',
        'visibility',
    ];

    public const VISIBILITIES = [
        'private' => 'خصوصی (فقط نویسنده)',
        'internal' => 'داخلی (کارکنان)',
        'public' => 'عمومی (مشتری هم می‌بیند)',
    ];

    protected function casts(): array
    {
        return [
            'needs_part' => 'boolean',
        ];
    }

    public function visibilityLabel(): string
    {
        return self::VISIBILITIES[$this->visibility] ?? $this->visibility;
    }

    public function isVisibleTo(?User $user, bool $isCustomer = false): bool
    {
        $vis = $this->visibility ?: 'internal';
        if ($vis === 'public') {
            return true;
        }
        if ($isCustomer) {
            return false;
        }
        if ($vis === 'private') {
            return $user && (int) $user->id === (int) $this->user_id;
        }

        return (bool) $user;
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
