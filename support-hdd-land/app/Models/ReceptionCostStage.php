<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceptionCostStage extends Model
{
    protected $fillable = [
        'reception_id', 'stage_key', 'stage_label', 'amount', 'status',
        'sort_order', 'note', 'cost_approval_id', 'approved_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sort_order' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public const STAGES = [
        'purchase_board' => ['label' => 'خرید برد / قطعه', 'mark' => 'خ', 'sort' => 10],
        'recovery' => ['label' => 'بازیابی اطلاعات', 'mark' => 'ب', 'sort' => 20],
        'test' => ['label' => 'تست و کنترل نهایی', 'mark' => 'ت', 'sort' => 30],
        'custom' => ['label' => 'سایر هزینه', 'mark' => 'س', 'sort' => 90],
    ];

    public const STATUSES = [
        'draft' => 'پیش‌نویس',
        'pending_approval' => 'منتظر تأیید مشتری',
        'approved' => 'تأییدشده',
        'rejected' => 'ردشده',
        'waived' => 'بدون نیاز به تأیید',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class);
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(CostApproval::class, 'cost_approval_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function mark(): string
    {
        return self::STAGES[$this->stage_key]['mark'] ?? '•';
    }

    public function isBillable(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval', 'approved', 'waived'], true)
            && (int) $this->amount > 0;
    }
}
