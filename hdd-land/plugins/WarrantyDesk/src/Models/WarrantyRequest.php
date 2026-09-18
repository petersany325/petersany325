<?php

namespace Plugins\WarrantyDesk\src\Models;

use Illuminate\Database\Eloquent\Model;
use Plugins\WarrantyDesk\src\Support\PageCopy;

class WarrantyRequest extends Model
{
    protected $table = 'warranty_coverage_requests';

    protected $fillable = [
        'public_code', 'user_id', 'applicant_type', 'org_name', 'contact_name',
        'mobile', 'phone', 'city', 'product_kind', 'brand_model', 'qty',
        'serials', 'notes', 'status', 'quote_amount', 'quote_months',
        'package_name', 'admin_note', 'sms_log', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'quote_amount' => 'integer',
            'quote_months' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function makeCode(): string
    {
        do {
            $code = 'WR-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (static::query()->where('public_code', $code)->exists());

        return $code;
    }

    public function statusLabel(): string
    {
        return PageCopy::statuses()[$this->status] ?? $this->status;
    }

    public function applicantLabel(): string
    {
        return PageCopy::applicantTypes()[$this->applicant_type] ?? $this->applicant_type;
    }

    public function appendSmsLog(string $line): void
    {
        $prev = trim((string) $this->sms_log);
        $this->sms_log = trim($prev."\n".now()->format('Y-m-d H:i').' '.$line);
    }
}
