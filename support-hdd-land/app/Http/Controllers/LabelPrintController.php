<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Reception;
use App\Support\LabelPrintSettings;
use Illuminate\Http\Request;

class LabelPrintController extends Controller
{
    public function reception(Request $request, Reception $reception)
    {
        $settings = $this->settingsFromRequest($request);
        $copies = max(1, min(200, (int) $request->query('qty', $settings['copies_default'])));

        $code = $this->barcodeValue(
            $settings['symbology'],
            (string) ($reception->serial_number ?: $reception->ticket_no ?: $reception->receipt_no)
        );

        $payload = [
            'type' => 'reception',
            'title' => shop_name(),
            'line1' => $reception->ticket_no,
            'line2' => trim(($reception->brand ?? '').' '.($reception->model ?? '')) ?: ($reception->product_name ?: 'دستگاه'),
            'line3' => $reception->customer?->displayName() ?: '',
            'code' => $code,
            'human' => (string) ($reception->serial_number ?: $reception->ticket_no),
        ];

        return view('labels.print', [
            'settings' => $settings,
            'copies' => $copies,
            'payload' => $payload,
            'modes' => LabelPrintSettings::printModes(),
            'backUrl' => route('receptions.show', $reception),
            'printUrlBase' => route('labels.reception', $reception),
        ]);
    }

    public function part(Request $request, Part $part)
    {
        $settings = $this->settingsFromRequest($request);
        $defaultQty = max(1, (int) $part->stock);
        if ($request->boolean('stock')) {
            $defaultQty = max(1, (int) $part->stock);
        }
        $copies = max(1, min(500, (int) $request->query('qty', $request->boolean('stock') ? max(1, (int) $part->stock) : $settings['copies_default'])));

        $rawCode = (string) ($part->code ?: ('P'.$part->id));
        $code = $this->barcodeValue($settings['symbology'], $rawCode);

        $payload = [
            'type' => 'part',
            'title' => shop_name(),
            'line1' => $part->name,
            'line2' => trim(($part->brand ?? '').' '.($part->model ?? '')) ?: ('کد: '.$rawCode),
            'line3' => 'موجودی: '.number_format((int) $part->stock),
            'code' => $code,
            'human' => $rawCode,
        ];

        return view('labels.print', [
            'settings' => $settings,
            'copies' => $copies,
            'payload' => $payload,
            'modes' => LabelPrintSettings::printModes(),
            'backUrl' => route('parts.show', $part),
            'printUrlBase' => route('labels.part', $part),
            'stockQty' => max(0, (int) $part->stock),
        ]);
    }

    public function preview(Request $request)
    {
        $settings = $this->settingsFromRequest($request);
        $copies = max(1, min(20, (int) $request->query('qty', 1)));
        $sample = (string) $request->query('sample', 'SAMPLE-12345');

        $payload = [
            'type' => 'preview',
            'title' => shop_name(),
            'line1' => 'نمونه برچسب',
            'line2' => 'تست چاپ بارکد',
            'line3' => $settings['printer_name'] ? 'پرینتر: '.$settings['printer_name'] : 'پرینتر پیش‌فرض سیستم',
            'code' => $this->barcodeValue($settings['symbology'], $sample),
            'human' => $sample,
        ];

        return view('labels.print', [
            'settings' => $settings,
            'copies' => $copies,
            'payload' => $payload,
            'modes' => LabelPrintSettings::printModes(),
            'backUrl' => route('settings.index', ['tab' => 'labels']),
            'printUrlBase' => route('labels.preview'),
        ]);
    }

    private function settingsFromRequest(Request $request): array
    {
        $base = LabelPrintSettings::all();
        $mode = $request->query('mode', $base['mode']);
        $base = LabelPrintSettings::resolveMode(is_string($mode) ? $mode : null, $base);

        if ($request->filled('symbology') && isset(LabelPrintSettings::SYMBOLOGIES[$request->query('symbology')])) {
            $base['symbology'] = (string) $request->query('symbology');
        }
        if ($request->filled('layout') && isset(LabelPrintSettings::LAYOUTS[$request->query('layout')])) {
            $base['layout'] = (string) $request->query('layout');
        }
        if ($request->filled('size') && isset(LabelPrintSettings::SIZES[$request->query('size')])) {
            $base['size_key'] = (string) $request->query('size');
            $size = LabelPrintSettings::SIZES[$base['size_key']];
            if ($base['size_key'] !== 'custom') {
                $base['width_mm'] = (int) $size['w'];
                $base['height_mm'] = (int) $size['h'];
            }
        }
        if ($request->filled('pos') && isset(LabelPrintSettings::POSITIONS[$request->query('pos')])) {
            $base['barcode_position'] = (string) $request->query('pos');
        }

        return $base;
    }

    private function barcodeValue(string $symbology, string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            $raw = '0';
        }

        // Keep alphanumeric for CODE128/39; digits for EAN/UPC/ITF/MSI/pharmacode.
        return match ($symbology) {
            'EAN13' => $this->padDigits($raw, 12), // JsBarcode adds check digit
            'EAN8' => $this->padDigits($raw, 7),
            'UPC' => $this->padDigits($raw, 11),
            'ITF14' => $this->padDigits($raw, 13),
            'MSI', 'pharmacode' => preg_replace('/\D+/', '', $raw) ?: '1234',
            'codabar' => preg_match('/^[A-Da-d].*[A-Da-d]$/', $raw) ? strtoupper($raw) : ('A'.preg_replace('/[^0-9\-$:\/.+]/', '', $raw).'A'),
            'CODE39' => strtoupper(preg_replace('/[^0-9A-Za-z\-. $\/+%]/', '', $raw) ?: 'CODE'),
            default => $raw,
        };
    }

    private function padDigits(string $raw, int $len): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?: '0';
        if (strlen($digits) > $len) {
            $digits = substr($digits, -$len);
        }

        return str_pad($digits, $len, '0', STR_PAD_LEFT);
    }
}
