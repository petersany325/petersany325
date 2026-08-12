<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RemotePartPreorder extends Model
{
    public const STATUS_PENDING_ARRIVAL = 'pending_arrival';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_MATCHED = 'matched';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING_ARRIVAL => 'در انتظار رسیدن',
        self::STATUS_ARRIVED => 'بار رسیده · منتظر تطبیق',
        self::STATUS_MATCHED => 'تبدیل به قبض',
        self::STATUS_REJECTED => 'مغایر / رد شده',
        self::STATUS_CANCELLED => 'لغو شده',
    ];

    public const MATCH_OK = 'ok';
    public const MATCH_MISMATCH = 'mismatch';
    public const MATCH_INCOMPLETE = 'incomplete';

    public const MATCH_RESULTS = [
        self::MATCH_OK => 'مطابق است · ساخت قبض',
        self::MATCH_MISMATCH => 'مغایرت دارد',
        self::MATCH_INCOMPLETE => 'ناقص · نیاز به اطلاعات بیشتر',
    ];

    protected $fillable = [
        'code', 'customer_id', 'part_title', 'description', 'tracking_code', 'origin_city',
        'serial_number', 'brand_model', 'status', 'photos', 'admin_note', 'match_result',
        'arrived_at', 'reviewed_by', 'reviewed_at', 'reception_id',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'arrived_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function matchLabel(): string
    {
        return self::MATCH_RESULTS[$this->match_result] ?? (string) $this->match_result;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_ARRIVAL, self::STATUS_ARRIVED], true);
    }

    public function canConvert(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_ARRIVAL, self::STATUS_ARRIVED], true)
            && ! $this->reception_id;
    }

    /** @return list<array{path:string,original_name:?string,label:?string}> */
    public function photoList(): array
    {
        $photos = $this->photos;
        if (! is_array($photos)) {
            return [];
        }

        return array_values(array_filter($photos, fn ($p) => is_array($p) && filled($p['path'] ?? null)));
    }

    public function hasPhoto(string $path): bool
    {
        foreach ($this->photoList() as $photo) {
            if (($photo['path'] ?? '') === $path && Storage::disk('local')->exists($path)) {
                return true;
            }
        }

        return false;
    }

    public static function nextCode(): string
    {
        $year = now('Asia/Tehran')->format('y');
        $prefix = 'PRE-'.$year.'-';
        $last = static::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if (is_string($last) && preg_match('/PRE-\d{2}-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
