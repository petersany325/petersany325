<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalInviteBatch extends Model
{
    public const FILTER_ALL = 'all';
    public const FILTER_NEVER_SENT = 'never_sent';
    public const FILTER_FAILED = 'failed';

    public const FILTERS = [
        self::FILTER_NEVER_SENT => 'هنوز لینک نگرفته‌اند (موفق)',
        self::FILTER_FAILED => 'آخرین ارسال ناموفق',
        self::FILTER_ALL => 'همه مشتریان دارای موبایل',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code', 'filter', 'status', 'total', 'sent_ok', 'sent_fail', 'cursor',
        'customer_ids', 'template_snapshot', 'created_by', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_ids' => 'array',
            'finished_at' => 'datetime',
            'total' => 'integer',
            'sent_ok' => 'integer',
            'sent_fail' => 'integer',
            'cursor' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(PortalInviteSend::class, 'batch_id');
    }

    public function filterLabel(): string
    {
        return self::FILTERS[$this->filter] ?? $this->filter;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_CANCELLED], true);
    }

    public function progressPercent(): int
    {
        if ($this->total <= 0) {
            return 100;
        }

        $done = min($this->total, (int) $this->cursor);

        return (int) round(($done / $this->total) * 100);
    }

    public static function nextCode(): string
    {
        return 'INV-'.now('Asia/Tehran')->format('ymd-His');
    }
}
