<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLogEntry extends Model
{
    protected $fillable = [
        'user_id', 'work_date', 'daily_log_category_id', 'category_name',
        'title', 'body', 'quantity', 'minutes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'quantity' => 'integer',
            'minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DailyLogCategory::class, 'daily_log_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayTitle(): string
    {
        $title = trim((string) $this->title);
        if ($title !== '') {
            return $title;
        }

        return trim((string) ($this->category_name ?: $this->category?->name ?: 'رویداد'));
    }
}
