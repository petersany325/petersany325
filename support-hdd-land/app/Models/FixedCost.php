<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedCost extends Model
{
    protected $fillable = [
        'title', 'category', 'amount', 'day_of_month', 'start_date', 'end_date',
        'pay_method', 'bank_account_id', 'expense_account_code',
        'is_active', 'note', 'last_posted_period', 'last_posted_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'last_posted_at' => 'datetime',
            'amount' => 'integer',
            'day_of_month' => 'integer',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payMethodLabel(): string
    {
        return match ($this->pay_method) {
            'cash' => 'نقد',
            'card' => 'کارتخوان',
            'transfer' => 'کارت‌به‌کارت',
            default => $this->pay_method,
        };
    }

    public function isDueInPeriod(string $periodYm): bool
    {
        if (! $this->is_active || (int) $this->amount <= 0) {
            return false;
        }
        if ($this->last_posted_period === $periodYm) {
            return false;
        }
        [$y, $m] = array_map('intval', explode('-', $periodYm));
        $periodStart = sprintf('%04d-%02d-01', $y, $m);
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        if ($this->start_date && $this->start_date->toDateString() > $periodEnd) {
            return false;
        }
        if ($this->end_date && $this->end_date->toDateString() < $periodStart) {
            return false;
        }

        return true;
    }
}
