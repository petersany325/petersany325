<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceBlacklist extends Model
{
    protected $fillable = [
        'serial_number', 'brand', 'model', 'reason', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        $parts = array_filter([
            $this->serial_number ? 'سریال '.$this->serial_number : null,
            trim(($this->brand ?? '').' '.($this->model ?? '')) ?: null,
        ]);

        return $parts ? implode(' — ', $parts) : 'بدون مشخصه';
    }

    public static function matchActive(?string $serial, ?string $brand = null, ?string $model = null): ?self
    {
        $serial = $serial !== null ? strtoupper(trim($serial)) : null;
        $brand = $brand !== null ? trim($brand) : null;
        $model = $model !== null ? strtoupper(trim($model)) : null;

        if ($serial === '' && $brand === '' && $model === '') {
            return null;
        }

        return static::query()
            ->where('is_active', true)
            ->where(function ($q) use ($serial, $brand, $model) {
                if ($serial) {
                    $q->orWhereRaw('UPPER(TRIM(serial_number)) = ?', [$serial]);
                }
                if ($brand && $model) {
                    $q->orWhere(function ($inner) use ($brand, $model) {
                        $inner->whereRaw('LOWER(TRIM(brand)) = ?', [mb_strtolower($brand)])
                            ->whereRaw('UPPER(TRIM(model)) = ?', [$model]);
                    });
                }
            })
            ->orderByDesc('id')
            ->first();
    }
}
