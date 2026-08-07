<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'doc_no', 'part_id', 'reception_id', 'user_id', 'type', 'doc_type',
        'quantity', 'unit_cost', 'total_cost', 'stock_after', 'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    public const TYPES = [
        'in' => 'ورود',
        'out' => 'خروج',
        'adjust' => 'تعدیل',
    ];

    public const DOC_TYPES = [
        'opening' => 'موجودی اول دوره',
        'purchase' => 'رسید خرید',
        'receipt' => 'رسید ورود',
        'issue' => 'حواله خروج',
        'consumption' => 'مصرف روی قبض',
        'adjust' => 'تعدیل موجودی',
        'return' => 'برگشت به انبار',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function docTypeLabel(): string
    {
        return self::DOC_TYPES[$this->doc_type] ?? ($this->doc_type ?: $this->typeLabel());
    }

    public static function nextDocNo(string $prefix): string
    {
        $day = now()->format('ymd');
        $full = strtoupper($prefix).'-'.$day.'-';
        $last = static::query()
            ->where('doc_no', 'like', $full.'%')
            ->orderByDesc('id')
            ->value('doc_no');
        $seq = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $full.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
