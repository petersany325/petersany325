<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLicense extends Model
{
    protected $fillable = [
        'license_key', 'customer_name', 'customer_phone', 'customer_email', 'domain', 'product',
        'status', 'token', 'activated_at', 'expires_at', 'last_check_at',
        'check_count', 'last_check_ip', 'last_check_version', 'meta', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_check_at' => 'datetime',
            'meta' => 'array',
            'check_count' => 'integer',
        ];
    }

    public static function generateKey(): string
    {
        return strtoupper(
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4).'-'.
            substr(bin2hex(random_bytes(2)), 0, 4)
        );
    }

    public static function normalizeKey(string $key): string
    {
        return strtoupper(trim($key));
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0] ?? $domain;
        $domain = preg_replace('/:\\d+$/', '', $domain) ?? $domain;

        return preg_replace('/^www\\./', '', $domain) ?? $domain;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'unused' => 'استفاده‌نشده',
            'active' => 'فعال',
            'revoked' => 'باطل',
            'expired' => 'منقضی',
            default => (string) $this->status,
        };
    }

    public function isOnline(int $withinDays = 7): bool
    {
        return $this->status === 'active'
            && $this->last_check_at
            && $this->last_check_at->gte(now()->subDays($withinDays));
    }

    public function onlineLabel(int $withinDays = 7): string
    {
        if ($this->status !== 'active') {
            return '—';
        }

        return $this->isOnline($withinDays) ? 'آنلاین' : 'آفلاین / بی‌پاسخ';
    }
}
