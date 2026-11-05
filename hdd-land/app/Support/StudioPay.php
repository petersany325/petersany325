<?php

namespace App\Support;

/**
 * Payment placeholders for Zarinpal and Bank Melli.
 * Keys stay empty until Peter sets them. Links go to real /pay pages.
 */
class StudioPay
{
    public const KEY = 'studio_pay_gateways';

    /** @return array<string, array<string, mixed>> */
    public static function gateways(): array
    {
        $raw = [];
        try {
            $raw = SettingsStore::get(self::KEY, []);
        } catch (\Throwable) {
            $raw = [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'zarinpal' => [
                'id' => 'zarinpal',
                'label' => 'زرین‌پال',
                'merchant' => trim((string) ($raw['zarinpal']['merchant'] ?? '')),
                'ready' => trim((string) ($raw['zarinpal']['merchant'] ?? '')) !== '',
                'start_url' => '/pay/zarinpal',
            ],
            'melli' => [
                'id' => 'melli',
                'label' => 'بانک ملی',
                'terminal' => trim((string) ($raw['melli']['terminal'] ?? '')),
                'ready' => trim((string) ($raw['melli']['terminal'] ?? '')) !== '',
                'start_url' => '/pay/melli',
            ],
        ];
    }

    public static function isReady(string $id): bool
    {
        $all = self::gateways();

        return ! empty($all[$id]['ready']);
    }
}
