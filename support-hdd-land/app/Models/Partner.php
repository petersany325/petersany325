<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name', 'phone', 'shop_name', 'code', 'notes', 'is_active', 'customer_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

        return $shop !== '' ? ($this->name.' — '.$shop) : (string) $this->name;
    }

    /**
     * Ensure a Customer row exists so accounting/reception can bill the partner shop.
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
                'phone' => $phone !== '' ? $phone : ('partner-'.$this->id),
                'notes' => 'نماینده همکار — '.$this->displayName(),
            ]);
        }

        $this->customer_id = $customer->id;
        $this->save();

        return $customer;
    }
}
