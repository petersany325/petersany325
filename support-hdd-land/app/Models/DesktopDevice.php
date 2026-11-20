<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
        if ($encrypted = static::encryptToken($plain)) {
            $device->forceFill(['token_encrypted' => $encrypted])->save();
        }

        return [$device, $plain];
    }

    public function reissue(): string
    {
        $plain = 'hds_'.bin2hex(random_bytes(24));
        $this->forceFill([
            'token_hash' => hash('sha256', $plain),
            'token_encrypted' => static::encryptToken($plain),
            'revoked_at' => null,
        ])->save();

        return $plain;
    }

    public function plainToken(): ?string
    {
        $stored = $this->getAttribute('token_encrypted');
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return null;
        }
    }

    private static function encryptToken(string $plain): ?string
    {
        if (! Schema::hasTable('desktop_devices') || ! Schema::hasColumn('desktop_devices', 'token_encrypted')) {
            return null;
        }

        return Crypt::encryptString($plain);
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
