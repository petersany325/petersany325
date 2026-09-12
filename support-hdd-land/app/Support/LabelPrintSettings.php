<?php

namespace App\Support;

use App\Models\AppSetting;

class LabelPrintSettings
{
    /** Common label roll sizes (mm). */
    public const SIZES = [
        '30x20' => ['w' => 30, 'h' => 20, 'label' => '۳۰×۲۰ mm'],
        '40x25' => ['w' => 40, 'h' => 25, 'label' => '۴۰×۲۵ mm'],
        '40x30' => ['w' => 40, 'h' => 30, 'label' => '۴۰×۳۰ mm'],
        '50x25' => ['w' => 50, 'h' => 25, 'label' => '۵۰×۲۵ mm'],
        '50x30' => ['w' => 50, 'h' => 30, 'label' => '۵۰×۳۰ mm'],
        '50x40' => ['w' => 50, 'h' => 40, 'label' => '۵۰×۴۰ mm'],
        '60x40' => ['w' => 60, 'h' => 40, 'label' => '۶۰×۴۰ mm'],
        '70x40' => ['w' => 70, 'h' => 40, 'label' => '۷۰×۴۰ mm'],
        '80x40' => ['w' => 80, 'h' => 40, 'label' => '۸۰×۴۰ mm'],
        '80x50' => ['w' => 80, 'h' => 50, 'label' => '۸۰×۵۰ mm'],
        '100x50' => ['w' => 100, 'h' => 50, 'label' => '۱۰۰×۵۰ mm'],
        '100x70' => ['w' => 100, 'h' => 70, 'label' => '۱۰۰×۷۰ mm'],
        'A4_sheet' => ['w' => 210, 'h' => 297, 'label' => 'برگه A4 (چند برچسب)'],
        'custom' => ['w' => 0, 'h' => 0, 'label' => 'سفارشی'],
    ];

    /** Symbologies supported by JsBarcode (scanner-friendly). */
    public const SYMBOLOGIES = [
        'CODE128' => 'Code 128 (پیشنهادی)',
        'CODE39' => 'Code 39',
        'EAN13' => 'EAN-13',
        'EAN8' => 'EAN-8',
        'UPC' => 'UPC-A',
        'ITF14' => 'ITF-14',
        'MSI' => 'MSI',
        'pharmacode' => 'Pharmacode',
        'codabar' => 'Codabar',
    ];

    public const LAYOUTS = [
        'barcode_only' => 'فقط بارکد',
        'title_barcode' => 'عنوان + بارکد',
        'title_barcode_text' => 'عنوان + بارکد + متن زیر',
        'serial_device' => 'سریال دستگاه (پذیرش)',
        'part_stock' => 'کالا / قطعه انبار',
        'compact_shelf' => 'قفسه فشرده',
    ];

    public const POSITIONS = [
        'top' => 'بالا',
        'center' => 'وسط',
        'bottom' => 'پایین',
    ];

    /**
     * 30+ named print modes (size × symbology × layout presets)
     * for scanner / label-printer compatibility.
     *
     * @return array<string, array{label:string,size:string,symbology:string,layout:string}>
     */
    public static function printModes(): array
    {
        return [
            'm01' => ['label' => '۱) پذیرش — Code128 — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'CODE128', 'layout' => 'serial_device'],
            'm02' => ['label' => '۲) پذیرش — Code128 — ۶۰×۴۰', 'size' => '60x40', 'symbology' => 'CODE128', 'layout' => 'serial_device'],
            'm03' => ['label' => '۳) پذیرش — Code128 — ۸۰×۵۰', 'size' => '80x50', 'symbology' => 'CODE128', 'layout' => 'serial_device'],
            'm04' => ['label' => '۴) پذیرش — Code39 — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'CODE39', 'layout' => 'serial_device'],
            'm05' => ['label' => '۵) پذیرش — Code39 — ۸۰×۴۰', 'size' => '80x40', 'symbology' => 'CODE39', 'layout' => 'serial_device'],
            'm06' => ['label' => '۶) پذیرش فشرده — ۴۰×۲۵', 'size' => '40x25', 'symbology' => 'CODE128', 'layout' => 'compact_shelf'],
            'm07' => ['label' => '۷) پذیرش بزرگ — ۱۰۰×۵۰', 'size' => '100x50', 'symbology' => 'CODE128', 'layout' => 'title_barcode_text'],
            'm08' => ['label' => '۸) قطعه — Code128 — ۴۰×۳۰', 'size' => '40x30', 'symbology' => 'CODE128', 'layout' => 'part_stock'],
            'm09' => ['label' => '۹) قطعه — Code128 — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'CODE128', 'layout' => 'part_stock'],
            'm10' => ['label' => '۱۰) قطعه — Code128 — ۶۰×۴۰', 'size' => '60x40', 'symbology' => 'CODE128', 'layout' => 'part_stock'],
            'm11' => ['label' => '۱۱) قطعه — Code39 — ۵۰×۴۰', 'size' => '50x40', 'symbology' => 'CODE39', 'layout' => 'part_stock'],
            'm12' => ['label' => '۱۲) قطعه قفسه — ۳۰×۲۰', 'size' => '30x20', 'symbology' => 'CODE128', 'layout' => 'compact_shelf'],
            'm13' => ['label' => '۱۳) قطعه قفسه — ۴۰×۲۵', 'size' => '40x25', 'symbology' => 'CODE128', 'layout' => 'compact_shelf'],
            'm14' => ['label' => '۱۴) فقط بارکد — ۵۰×۲۵', 'size' => '50x25', 'symbology' => 'CODE128', 'layout' => 'barcode_only'],
            'm15' => ['label' => '۱۵) فقط بارکد — ۷۰×۴۰', 'size' => '70x40', 'symbology' => 'CODE128', 'layout' => 'barcode_only'],
            'm16' => ['label' => '۱۶) عنوان+بارکد — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'CODE128', 'layout' => 'title_barcode'],
            'm17' => ['label' => '۱۷) عنوان+بارکد — ۸۰×۵۰', 'size' => '80x50', 'symbology' => 'CODE128', 'layout' => 'title_barcode'],
            'm18' => ['label' => '۱۸) عنوان+متن — ۱۰۰×۷۰', 'size' => '100x70', 'symbology' => 'CODE128', 'layout' => 'title_barcode_text'],
            'm19' => ['label' => '۱۹) EAN-13 کالا — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'EAN13', 'layout' => 'part_stock'],
            'm20' => ['label' => '۲۰) EAN-13 کالا — ۴۰×۳۰', 'size' => '40x30', 'symbology' => 'EAN13', 'layout' => 'barcode_only'],
            'm21' => ['label' => '۲۱) EAN-8 فشرده — ۴۰×۲۵', 'size' => '40x25', 'symbology' => 'EAN8', 'layout' => 'barcode_only'],
            'm22' => ['label' => '۲۲) UPC-A — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'UPC', 'layout' => 'part_stock'],
            'm23' => ['label' => '۲۳) ITF-14 کارتن — ۸۰×۵۰', 'size' => '80x50', 'symbology' => 'ITF14', 'layout' => 'title_barcode'],
            'm24' => ['label' => '۲۴) MSI انبار — ۵۰×۳۰', 'size' => '50x30', 'symbology' => 'MSI', 'layout' => 'part_stock'],
            'm25' => ['label' => '۲۵) Codabar — ۶۰×۴۰', 'size' => '60x40', 'symbology' => 'codabar', 'layout' => 'title_barcode'],
            'm26' => ['label' => '۲۶) Pharmacode — ۵۰×۲۵', 'size' => '50x25', 'symbology' => 'pharmacode', 'layout' => 'barcode_only'],
            'm27' => ['label' => '۲۷) برگه A4 چندتایی — پذیرش', 'size' => 'A4_sheet', 'symbology' => 'CODE128', 'layout' => 'serial_device'],
            'm28' => ['label' => '۲۸) برگه A4 چندتایی — قطعه', 'size' => 'A4_sheet', 'symbology' => 'CODE128', 'layout' => 'part_stock'],
            'm29' => ['label' => '۲۹) پرینتر معمولی A4 — عنوان+متن', 'size' => 'A4_sheet', 'symbology' => 'CODE128', 'layout' => 'title_barcode_text'],
            'm30' => ['label' => '۳۰) رول عریض ۱۰۰×۵۰ — Code39', 'size' => '100x50', 'symbology' => 'CODE39', 'layout' => 'title_barcode_text'],
            'm31' => ['label' => '۳۱) رول عریض ۸۰×۵۰ — فقط بارکد', 'size' => '80x50', 'symbology' => 'CODE128', 'layout' => 'barcode_only'],
            'm32' => ['label' => '۳۲) پذیرش قفسه — ۵۰×۴۰', 'size' => '50x40', 'symbology' => 'CODE128', 'layout' => 'compact_shelf'],
            'm33' => ['label' => '۳۳) قطعه موجودی — ۸۰×۴۰', 'size' => '80x40', 'symbology' => 'CODE128', 'layout' => 'part_stock'],
            'm34' => ['label' => '۳۴) کد کوتاه Code39 — ۳۰×۲۰', 'size' => '30x20', 'symbology' => 'CODE39', 'layout' => 'barcode_only'],
            'm35' => ['label' => '۳۵) سفارشی (از تنظیمات)', 'size' => 'custom', 'symbology' => 'CODE128', 'layout' => 'title_barcode_text'],
        ];
    }

    public static function all(): array
    {
        $sizeKey = AppSetting::getValue('label_size', '50x30') ?: '50x30';
        $customW = (int) AppSetting::getValue('label_custom_w', '50');
        $customH = (int) AppSetting::getValue('label_custom_h', '30');

        $size = self::SIZES[$sizeKey] ?? self::SIZES['50x30'];
        if ($sizeKey === 'custom') {
            $size = ['w' => max(20, $customW), 'h' => max(15, $customH), 'label' => 'سفارشی'];
        }

        return [
            'size_key' => $sizeKey,
            'width_mm' => (int) $size['w'],
            'height_mm' => (int) $size['h'],
            'custom_w' => $customW,
            'custom_h' => $customH,
            'symbology' => AppSetting::getValue('label_symbology', 'CODE128') ?: 'CODE128',
            'layout' => AppSetting::getValue('label_layout', 'title_barcode_text') ?: 'title_barcode_text',
            'mode' => AppSetting::getValue('label_print_mode', 'm02') ?: 'm02',
            'barcode_position' => AppSetting::getValue('label_barcode_position', 'center') ?: 'center',
            'show_shop' => AppSetting::getValue('label_show_shop', '1') === '1',
            'show_text' => AppSetting::getValue('label_show_text', '1') === '1',
            'auto_print' => AppSetting::getValue('label_auto_print', '1') === '1',
            'copies_default' => max(1, (int) AppSetting::getValue('label_copies_default', '1')),
            'printer_name' => (string) AppSetting::getValue('label_printer_name', ''),
            'gap_mm' => max(0, (int) AppSetting::getValue('label_gap_mm', '2')),
            'margin_mm' => max(0, (int) AppSetting::getValue('label_margin_mm', '2')),
            'font_size' => max(7, min(16, (int) AppSetting::getValue('label_font_size', '9'))),
        ];
    }

    public static function resolveMode(?string $modeKey, array $base): array
    {
        $modes = self::printModes();
        if ($modeKey && isset($modes[$modeKey])) {
            $m = $modes[$modeKey];
            $base['mode'] = $modeKey;
            $base['size_key'] = $m['size'];
            $base['symbology'] = $m['symbology'];
            $base['layout'] = $m['layout'];
            $size = self::SIZES[$m['size']] ?? null;
            if ($size && $m['size'] !== 'custom') {
                $base['width_mm'] = (int) $size['w'];
                $base['height_mm'] = (int) $size['h'];
            } elseif ($m['size'] === 'custom') {
                $base['width_mm'] = max(20, (int) $base['custom_w']);
                $base['height_mm'] = max(15, (int) $base['custom_h']);
            }
        }

        return $base;
    }
}
