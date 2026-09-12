<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PaymentReceipt extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'در انتظار تأیید',
        self::STATUS_APPROVED => 'تأیید شده',
        self::STATUS_REJECTED => 'رد شده',
    ];

    protected $fillable = [
        'reception_id', 'customer_id', 'amount', 'transfer_date', 'note',
        'image_path', 'original_name', 'status',
        'reviewed_by', 'reviewed_at', 'review_note', 'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'transfer_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function hasImage(): bool
    {
        return filled($this->image_path) && Storage::disk('local')->exists($this->image_path);
    }

    public function deleteImage(): void
    {
        if ($this->image_path && Storage::disk('local')->exists($this->image_path)) {
            Storage::disk('local')->delete($this->image_path);
        }

        $this->forceFill([
            'image_path' => null,
            'original_name' => null,
        ])->save();
    }
}
