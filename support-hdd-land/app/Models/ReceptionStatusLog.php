<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionStatusLog extends Model
{
    protected $fillable = [
        'reception_id', 'from_status', 'to_status', 'event_type',
        'title', 'note', 'changed_by', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public const EVENT_TYPES = [
        'status_change' => 'تغییر وضعیت',
        'delivery' => 'تحویل به مشتری',
        'delivery_cancel' => 'لغو تحویل',
        'payment_auto_deliver' => 'تحویل خودکار پس از پرداخت',
        'exit_otp' => 'کد تأیید خروج',
        'cost_stage' => 'مرحله هزینه',
        'ticket_edit' => 'ویرایش قبض',
        'trash' => 'انتقال به سطل زباله',
        'trash_restore' => 'بازیابی از سطل زباله',
        'system' => 'سیستم',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function eventLabel(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }

    public function toStatusLabel(): string
    {
        return Reception::STATUSES[$this->to_status]
            ?? SmsStatusRule::statusMap()[$this->to_status]
            ?? $this->to_status;
    }

    public function fromStatusLabel(): ?string
    {
        if (! $this->from_status) {
            return null;
        }

        return Reception::STATUSES[$this->from_status]
            ?? SmsStatusRule::statusMap()[$this->from_status]
            ?? $this->from_status;
    }

    public function displayTitle(): string
    {
        if (trim((string) $this->title) !== '') {
            return (string) $this->title;
        }

        return $this->eventLabel().': '.$this->toStatusLabel();
    }
}
