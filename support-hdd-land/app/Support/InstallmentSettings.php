<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Str;

class InstallmentSettings
{
    public const KEYS = [
        'enabled' => 'installment_sms_enabled',
        'before_days' => 'installment_sms_before_days',
        'on_due' => 'installment_sms_on_due',
        'after_days' => 'installment_sms_after_days',
        'tpl_before' => 'installment_sms_tpl_before',
        'tpl_due' => 'installment_sms_tpl_due',
        'tpl_after' => 'installment_sms_tpl_after',
        'tpl_thanks' => 'installment_sms_tpl_thanks',
        'cron_token' => 'installment_cron_token',
    ];

    public static function all(): array
    {
        $token = AppSetting::getValue(self::KEYS['cron_token']);
        if (! $token) {
            $token = Str::random(40);
            AppSetting::setValue(self::KEYS['cron_token'], $token);
        }

        return [
            'enabled' => AppSetting::getValue(self::KEYS['enabled'], '1') === '1',
            'before_days' => max(0, (int) AppSetting::getValue(self::KEYS['before_days'], '3')),
            'on_due' => AppSetting::getValue(self::KEYS['on_due'], '1') === '1',
            'after_days' => self::parseDayList(AppSetting::getValue(self::KEYS['after_days'], '1,3,7')),
            'tpl_before' => AppSetting::getValue(self::KEYS['tpl_before'], self::defaultTpl('before')),
            'tpl_due' => AppSetting::getValue(self::KEYS['tpl_due'], self::defaultTpl('due')),
            'tpl_after' => AppSetting::getValue(self::KEYS['tpl_after'], self::defaultTpl('after')),
            'tpl_thanks' => AppSetting::getValue(self::KEYS['tpl_thanks'], self::defaultTpl('thanks')),
            'cron_token' => $token,
        ];
    }

    public static function save(array $data): void
    {
        AppSetting::setValue(self::KEYS['enabled'], ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue(self::KEYS['before_days'], (string) max(0, (int) ($data['before_days'] ?? 3)));
        AppSetting::setValue(self::KEYS['on_due'], ! empty($data['on_due']) ? '1' : '0');
        $after = is_array($data['after_days'] ?? null)
            ? implode(',', $data['after_days'])
            : (string) ($data['after_days'] ?? '1,3,7');
        AppSetting::setValue(self::KEYS['after_days'], implode(',', self::parseDayList($after)));
        foreach (['tpl_before', 'tpl_due', 'tpl_after', 'tpl_thanks'] as $k) {
            if (array_key_exists($k, $data)) {
                AppSetting::setValue(self::KEYS[$k], (string) $data[$k]);
            }
        }
        if (! empty($data['regenerate_cron_token'])) {
            AppSetting::setValue(self::KEYS['cron_token'], Str::random(40));
        }
    }

    /** @return list<int> */
    public static function parseDayList(?string $raw): array
    {
        $parts = preg_split('/[,\s]+/', (string) $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $n = (int) $p;
            if ($n > 0 && $n <= 365) {
                $out[$n] = $n;
            }
        }
        $list = array_values($out);
        sort($list);

        return $list ?: [1, 3, 7];
    }

    public static function defaultTpl(string $kind): string
    {
        return match ($kind) {
            'before' => '{shop} — یادآوری: قسط {seq} مبلغ {amount} تومان تا {due} سررسید می‌شود. مشتری: {customer}',
            'due' => '{shop} — امروز سررسید قسط {seq} مبلغ {amount} تومان است. مشتری: {customer}',
            'after' => '{shop} — قسط {seq} مبلغ {remain} تومان از {due} معوق است ({days} روز). مشتری: {customer}',
            'thanks' => '{shop} — از پرداخت قسط {seq} مبلغ {paid} تومان سپاسگزاریم. مشتری: {customer}',
            default => '',
        };
    }

    public static function render(string $tpl, array $vars): string
    {
        $out = $tpl;
        foreach ($vars as $k => $v) {
            $out = str_replace('{'.$k.'}', (string) $v, $out);
        }

        return trim($out);
    }
}
