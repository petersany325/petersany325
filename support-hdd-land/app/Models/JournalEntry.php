<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_no', 'entry_date', 'description', 'source_type', 'source_id',
        'reception_id', 'customer_id', 'created_by', 'total_amount',
        'deleted_by', 'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public static function nextEntryNo(): string
    {
        $prefix = 'JE-'.now()->format('ymd');
        $last = static::where('entry_no', 'like', $prefix.'%')->orderByDesc('id')->value('entry_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
