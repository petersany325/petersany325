<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionPart extends Model
{
    protected $fillable = [
        'reception_id', 'part_id', 'part_name',
        'quantity', 'unit_price', 'total_price', 'used_at',
    ];

    protected function casts(): array
    {
        return ['used_at' => 'date'];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
