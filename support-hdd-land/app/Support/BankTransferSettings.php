<?php

namespace App\Support;

use App\Models\AppSetting;

class BankTransferSettings
{
    public static function all(): array
    {
        return [
            'enabled' => AppSetting::getValue('bank_transfer_enabled', '0') === '1',
            'card_number' => (string) AppSetting::getValue('bank_card_number', ''),
            'card_holder' => (string) AppSetting::getValue('bank_card_holder', ''),
            'bank_name' => (string) AppSetting::getValue('bank_name', ''),
            'iban' => (string) AppSetting::getValue('bank_iban', ''),
            'instructions' => (string) AppSetting::getValue('bank_transfer_instructions', ''),
        ];
    }

    public static function isEnabled(): bool
    {
        $cfg = self::all();

        return $cfg['enabled'] && filled($cfg['card_number']);
    }

    public static function formattedCard(?string $card = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($card ?? self::all()['card_number'])) ?? '';
        if ($digits === '') {
            return '';
        }

        return trim(implode(' ', str_split($digits, 4)));
    }
}
