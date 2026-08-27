<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLogCheck extends Model
{
    protected $fillable = [
        'user_id', 'work_date', 'status', 'note', 'checked_by', 'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'issue' => 'نیاز به پیگیری',
            default => 'بررسی شد',
        };
    }
}
