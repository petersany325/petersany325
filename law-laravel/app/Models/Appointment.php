<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'topic', 'preferred_date',
        'preferred_time', 'notes', 'admin_note', 'status',
        'viewed_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'viewed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function markViewed(): void
    {
        if ($this->status === 'pending') {
            $this->forceFill([
                'status' => 'viewed',
                'viewed_at' => now(),
            ])->save();
        } elseif ($this->viewed_at === null) {
            $this->forceFill(['viewed_at' => now()])->save();
        }
    }

    public function archive(): void
    {
        $this->forceFill([
            'status' => 'archived',
            'archived_at' => now(),
        ])->save();
    }

    public function scopeInbox($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['viewed', 'confirmed']);
    }

    public function scopeArchived($query)
    {
        return $query->whereIn('status', ['archived', 'done', 'cancelled']);
    }
}
