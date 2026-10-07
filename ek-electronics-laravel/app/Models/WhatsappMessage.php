<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'direction', 'to_phone', 'from_label', 'template', 'body',
        'related_type', 'related_id', 'sent_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
