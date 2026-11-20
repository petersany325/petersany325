<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesktopDevice extends Model
{
    protected $fillable = [
        'name',
        'token_hash',
        'last_seen_at',
        'last_cursor',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array{0: self, 1: string}
     */
    public static function issue(string $name, ?int $userId): array
    {
        $plain = 'hds_'.bin2hex(random_bytes(24));
        $device = static::query()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'created_by' => $userId,
            'last_cursor' => 0,
        ]);

        return [$device, $plain];
    }

    public static function findByToken(string $plain): ?self
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }

        return static::query()->where('token_hash', hash('sha256', $plain))->first();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
