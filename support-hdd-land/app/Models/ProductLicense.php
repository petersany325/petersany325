<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLicense extends Model
{
    protected $fillable = [
        'license_key', 'customer_name', 'customer_phone', 'domain', 'product',
        'status', 'token', 'activated_at', 'expires_at', 'last_check_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_check_at' => 'datetime',
            'meta' => 'array',
        ];
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
}
