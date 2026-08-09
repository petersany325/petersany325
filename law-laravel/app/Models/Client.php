<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'father_name', 'national_code', 'id_number',
        'phone', 'mobile', 'email', 'address',
        'case_type', 'subject', 'opponent', 'referrer', 'description',
        'fee_agreed', 'fee_paid', 'fee_method',
        'contract_date', 'contract_no',
        'status', 'confirmed_at', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'fee_agreed' => 'integer',
            'fee_paid' => 'integer',
            'contract_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFeeRemainingAttribute(): int
    {
        return max(0, (int) ($this->fee_agreed ?? 0) - (int) ($this->fee_paid ?? 0));
    }

    /** Iranian national code checksum validation */
    public static function isValidNationalCode(?string $code): bool
    {
        $code = preg_replace('/\D+/', '', (string) $code);
        if (strlen($code) !== 10 || preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }
        $r = $sum % 11;
        $check = (int) $code[9];

        return ($r < 2 && $check === $r) || ($r >= 2 && $check === 11 - $r);
    }
}
