<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SmsStatusRule extends Model
{
    public const SEND_ALWAYS = 'always';

    public const SEND_NEVER = 'never';

    public const SEND_ASK = 'ask';

    public const SEND_MODES = [
        self::SEND_ALWAYS => 'ارسال شود',
        self::SEND_NEVER => 'ارسال نشود',
        self::SEND_ASK => 'پرسیده شود',
    ];

    protected $fillable = [
        'code', 'title', 'summary', 'status_key', 'stage_type', 'result_type', 'color',
        'description', 'message_template', 'auto_send', 'send_mode', 'coworker_message_template',
        'send_coworker', 'is_active', 'is_hidden', 'on_create', 'on_price', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'auto_send' => 'boolean',
            'send_coworker' => 'boolean',
            'is_active' => 'boolean',
            'is_hidden' => 'boolean',
            'on_create' => 'boolean',
            'on_price' => 'boolean',
        ];
    }

    public function sendMode(): string
    {
        $mode = (string) ($this->send_mode ?: '');
        if (in_array($mode, [self::SEND_ALWAYS, self::SEND_NEVER, self::SEND_ASK], true)) {
            return $mode;
        }

        return $this->auto_send ? self::SEND_ALWAYS : self::SEND_NEVER;
    }

    public function sendModeLabel(): string
    {
        return self::SEND_MODES[$this->sendMode()] ?? $this->sendMode();
    }

    public function shouldAskEmployee(): bool
    {
        return $this->sendMode() === self::SEND_ASK;
    }

    public function shouldAutoSend(): bool
    {
        return $this->sendMode() === self::SEND_ALWAYS;
    }

    public static function normalizeSendMode(mixed $value, ?bool $autoSendFallback = null): string
    {
        $mode = is_string($value) ? strtolower(trim($value)) : '';
        if (in_array($mode, [self::SEND_ALWAYS, self::SEND_NEVER, self::SEND_ASK], true)) {
            return $mode;
        }
        if ($mode === '1' || $mode === 'true' || $mode === 'yes') {
            return self::SEND_ALWAYS;
        }
        if ($mode === '0' || $mode === 'false' || $mode === 'no') {
            return self::SEND_NEVER;
        }
        if ($autoSendFallback === null) {
            return self::SEND_ALWAYS;
        }

        return $autoSendFallback ? self::SEND_ALWAYS : self::SEND_NEVER;
    }

    public const COLORS = [
        'blue' => 'آبی',
        'orange' => 'نارنجی',
        'amber' => 'کهربایی',
        'green' => 'سبز',
        'teal' => 'فیروزه‌ای',
        'red' => 'قرمز',
        'slate' => 'خاکستری',
    ];

    public const STAGE_TYPES = [
        'run' => 'اجرا',
        'suspend' => 'تعلیق',
        'done' => 'اتمام',
    ];

    public const RESULT_TYPES = [
        'active' => 'فعال',
        'success' => 'موفق',
        'fail' => 'ناموفق',
    ];

    public const PLACEHOLDERS = [
        '{customer_name}' => 'نام مشتری',
        '{phone}' => 'موبایل مشتری',
        '{ticket_no}' => 'کد پذیرش',
        '{receipt_no}' => 'شماره قبض',
        '{device}' => 'نوع / مدل دستگاه',
        '{brand}' => 'برند',
        '{model}' => 'مدل',
        '{serial}' => 'سریال',
        '{fault}' => 'نوع خرابی',
        '{status}' => 'عنوان وضعیت',
        '{amount}' => 'مبلغ (تومان)',
        '{price}' => 'مبلغ (تومان)',
        '{shop_name}' => 'نام فروشگاه',
        '{technician}' => 'نام تعمیرکار',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function stageLabel(): string
    {
        return self::STAGE_TYPES[$this->stage_type] ?? $this->stage_type;
    }

    public function resultLabel(): string
    {
        return self::RESULT_TYPES[$this->result_type] ?? $this->result_type;
    }

    public static function activeOrdered()
    {
        // Cache only IDs — serializing Eloquent models into database cache can
        // corrupt into plain strings/arrays and break receptions/show.
        $ids = Cache::remember('sms_status_rules_active_ids_v2', 120, function () {
            return static::query()
                ->where('is_active', true)
                ->where('is_hidden', false)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        });

        if (! is_array($ids) || $ids === []) {
            Cache::forget('sms_status_rules_active_ids_v2');

            return static::query()
                ->where('is_active', true)
                ->where('is_hidden', false)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $rules = static::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $rules->get((int) $id))
            ->filter(fn ($rule) => $rule instanceof self)
            ->values();
    }

    public static function statusMap(): array
    {
        return Cache::remember('sms_status_map_v2', 120, function () {
            $map = Reception::STATUSES;
            foreach (static::activeOrdered() as $rule) {
                if (! $rule instanceof self) {
                    continue;
                }
                $map[$rule->status_key] = $rule->title;
            }

            return $map;
        });
    }

    public static function clearStatusCache(): void
    {
        Cache::forget('sms_status_rules_active_v1');
        Cache::forget('sms_status_rules_active_ids_v2');
        Cache::forget('sms_status_map_v1');
        Cache::forget('sms_status_map_v2');
    }

    public static function findForStatus(string $statusKey): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('is_hidden', false)
            ->where('status_key', $statusKey)
            ->orderByDesc('on_create')
            ->orderBy('sort_order')
            ->first();
    }

    public static function findOnCreate(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('is_hidden', false)
            ->where('on_create', true)
            ->orderBy('sort_order')
            ->first();
    }

    public static function findOnPrice(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('on_price', true)->orWhere('code', 'price_set')->orWhere('status_key', 'price_set');
            })
            ->orderBy('sort_order')
            ->first();
    }

    public static function makeCode(string $title, ?string $statusKey = null): string
    {
        $base = Str::slug($statusKey ?: $title, '_');
        if ($base === '') {
            $base = 'status_'.now()->format('His');
        }
        $code = $base;
        $i = 1;
        while (static::where('code', $code)->exists()) {
            $code = $base.'_'.$i;
            $i++;
        }

        return $code;
    }
}
