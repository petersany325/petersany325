<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'desktop_device_id',
        'resource',
        'sync_key',
        'op',
        'payload',
        'record_updated_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'record_updated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(DesktopDevice::class, 'desktop_device_id');
    }
}
