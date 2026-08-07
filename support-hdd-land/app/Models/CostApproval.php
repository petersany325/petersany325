<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostApproval extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'reception_id', 'customer_id', 'created_by', 'version',
        'amount', 'labor_cost', 'parts_cost', 'discount',
        'description', 'terms_text', 'status', 'token_hash', 'approval_code',
        'expires_at', 'sent_at', 'viewed_at', 'decided_at', 'reject_reason',
        'viewer_ip', 'viewer_ua', 'decision_ip', 'decision_ua', 'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'decided_at' => 'datetime',
            'snapshot' => 'array',
            'amount' => 'integer',
            'labor_cost' => 'integer',
            'parts_cost' => 'integer',
            'discount' => 'integer',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_SENT => 'ارسال‌شده',
            self::STATUS_VIEWED => 'مشاهده‌شده',
            self::STATUS_APPROVED => 'تأییدشده',
            self::STATUS_REJECTED => 'ردشده',
            self::STATUS_EXPIRED => 'منقضی',
            self::STATUS_SUPERSEDED => 'جایگزین شده',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED], true)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED], true)
            && $this->expires_at
            && $this->expires_at->isPast();
    }

    public function canDecide(): bool
    {
        return $this->isOpen() && ! $this->isExpired();
    }
}
