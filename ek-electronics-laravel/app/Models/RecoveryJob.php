<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryJob extends Model
{
    protected $fillable = [
        'number', 'client_name', 'client_phone', 'media', 'stage', 'quote', 'notes', 'whatsapp_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'quote' => 'decimal:2',
            'whatsapp_sent_at' => 'datetime',
        ];
    }
}
