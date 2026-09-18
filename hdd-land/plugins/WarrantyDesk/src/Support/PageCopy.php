<?php

namespace Plugins\WarrantyDesk\src\Support;

use App\Support\SettingsStore;

class PageCopy
{
    public const KEY = 'warranty_register_page';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'kicker' => 'پوشش گارانتی سازمانی',
            'title' => 'ثبت درخواست پوشش گارانتی',
            'lead' => 'اگر فروشگاه، شرکت یا سازمان هستید و می‌خواهید کالاهایتان زیر پوشش گارانتی سرزمین هارد برود، همین‌جا درخواست بدهید. استعلام سریال مشتری جداست.',
            'intro' => 'این صفحه برای متقاضی پوشش است، نه برای خریدار نهایی. بعد از بررسی، هزینه و مدت پیشنهاد می‌شود و با هر تغییر وضعیت پیامک می‌رود.',
            'bullets' => "بررسی پرونده توسط واحد گارانتی\nاعلام هزینه و مدت پوشش\nپذیرش یا رد پیشنهاد از سمت متقاضی\nفعال‌سازی پوشش و پیامک وضعیت",
            'steps' => "فرم را با مشخصات فروشگاه/شرکت و کالا پر کنید\nکد پیگیری دریافت می‌کنید\nکارشناس پرونده را بررسی و قیمت می‌دهد\nبا تأیید شما پوشش فعال می‌شود",
            'cta_label' => 'ارسال درخواست',
            'form_title' => 'فرم درخواست فروشگاه / شرکت / سازمان',
            'note' => 'استعلام گارانتی سریال برای مشتری نهایی از صفحه «استعلام گارانتی» انجام می‌شود. اینجا فقط درخواست پوشش جدید ثبت می‌شود.',
            'success' => 'درخواست ثبت شد. کد پیگیری را نگه دارید؛ وضعیت با پیامک هم اعلام می‌شود.',
            'lookup_title' => 'پیگیری درخواست ثبت‌شده',
            'lookup_hint' => 'کد پیگیری را وارد کنید تا وضعیت پرونده را ببینید.',
            'sms_on_status' => true,
            'packages' => "پوشش ۶ ماه|6|\nپوشش ۱۲ ماه|12|\nپوشش ۱۸ ماه|18|",
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = SettingsStore::get(self::KEY, []);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        return array_merge(self::defaults(), is_array($raw) ? $raw : []);
    }

    public static function save(array $d): void
    {
        $s = self::get();
        $s['sms_on_status'] = ! empty($d['sms_on_status']);
        foreach ([
            'kicker' => 80,
            'title' => 160,
            'lead' => 600,
            'intro' => 900,
            'bullets' => 2000,
            'steps' => 2000,
            'cta_label' => 60,
            'form_title' => 160,
            'note' => 500,
            'success' => 400,
            'lookup_title' => 160,
            'lookup_hint' => 300,
            'packages' => 2000,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? '')), 0, $max);
        }
        SettingsStore::set(self::KEY, $s);
    }

    /** @return list<string> */
    public static function lines(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /** @return list<array{name:string,months:int,price:?int}> */
    public static function packages(?array $copy = null): array
    {
        $copy ??= self::get();
        $out = [];
        foreach (self::lines((string) ($copy['packages'] ?? '')) as $line) {
            $parts = array_map('trim', explode('|', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }
            $out[] = [
                'name' => $name,
                'months' => (int) ($parts[1] ?? 0),
                'price' => isset($parts[2]) && $parts[2] !== '' ? (int) preg_replace('/\D+/', '', $parts[2]) : null,
            ];
        }

        return $out;
    }

    /** @return array<string,string> */
    public static function applicantTypes(): array
    {
        return [
            'shop' => 'فروشگاه',
            'company' => 'شرکت',
            'org' => 'سازمان / اداره',
        ];
    }

    /** @return array<string,string> */
    public static function statuses(): array
    {
        return [
            'submitted' => 'ثبت شده',
            'review' => 'در حال بررسی',
            'quote' => 'اعلام هزینه',
            'accepted' => 'تأیید متقاضی',
            'active' => 'پوشش فعال',
            'rejected' => 'رد شده',
            'cancelled' => 'لغو شده',
        ];
    }
}
