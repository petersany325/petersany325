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
    ];

    protected function casts(): array
    {
        return [
            'needs_part' => 'boolean',
        ];
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
