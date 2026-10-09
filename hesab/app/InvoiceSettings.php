<?php
declare(strict_types=1);

final class InvoiceSettings
{
    public static function defaults(): array
    {
        return [
            'inv_company_name' => (string) cfg('app_name', 'حساب'),
            'inv_company_address' => '',
            'inv_company_phone' => '',
            'inv_company_fax' => '',
            'inv_economic_code' => '',
            'inv_national_id' => '',
            'inv_postal_code' => '',
            'inv_logo_url' => '',
            'inv_prefix' => 'INV-',
            'inv_next_number' => '1',
            'inv_tax_percent' => '9',
            'inv_show_tax' => '1',
            'inv_currency' => 'ریال',
            'inv_default_note' => 'از حسن انتخاب شما سپاسگزاریم.',
            'print_paper' => 'A4',
            'print_orientation' => 'portrait',
            'print_show_logo' => '1',
            'print_show_qr' => '0',
            'print_show_signature' => '1',
            'print_show_stamp' => '1',
            'print_margin_mm' => '12',
            'print_header_html' => '',
            'print_footer_html' => 'صفحه {page} — صادر شده از سامانه حسابداری',
            'print_watermark' => '',
            'print_font_size' => '12',
            'print_color_accent' => '#005a9e',
            'print_copies' => '1',
            'print_auto_open' => '1',
        ];
    }

    public static function all(): array
    {
        return SettingsStore::getMany(array_keys(self::defaults()), self::defaults());
    }

    public static function save(array $post): void
    {
        $map = [];
        foreach (self::defaults() as $k => $def) {
            if (str_starts_with($k, 'inv_show_') || str_starts_with($k, 'print_show_') || $k === 'print_auto_open') {
                $map[$k] = isset($post[$k]) ? '1' : '0';
                continue;
            }
            if (array_key_exists($k, $post)) {
                $map[$k] = trim((string) $post[$k]);
            }
        }
        SettingsStore::setMany($map);
    }
}
