<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name', 'phone', 'shop_name', 'code', 'domain', 'license_key', 'source',
        'notes', 'is_active', 'customer_id', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public function displayName(): string
    {
        $shop = trim((string) $this->shop_name);
        $name = trim((string) $this->name);
        if ($shop !== '' && $shop !== $name) {
            return $name.' — '.$shop;
        }

        return $name !== '' ? $name : (string) ($this->domain ?: $this->license_key ?: 'همکار');
    }

    /**
     * Ensure a Customer row exists so accounting can settle with the partner shop if needed.
     */
    public function ensureCustomer(): Customer
    {
        if ($this->customer_id) {
            $existing = Customer::query()->find($this->customer_id);
            if ($existing) {
                return $existing;
            }
        }

        $phone = trim((string) $this->phone);
        $customer = null;
        if ($phone !== '') {
            $customer = Customer::findByPhone($phone);
        }
        if (! $customer) {
            $customer = Customer::query()->create([
                'name' => $this->displayName(),
                'phone' => $phone !== '' ? $phone : ('partner-'.($this->id ?: uniqid())),
                'notes' => 'همکار لایسنس‌دار — '.$this->displayName().($this->domain ? ' @ '.$this->domain : ''),
            ]);
        }

        $this->customer_id = $customer->id;
        $this->save();

        return $customer;
    }
}
