<?php

namespace App\Support;

use Plugins\Studio\src\Support\StudioSms;

/**
 * SMS hooks: login, ticket, invoice. Never throws — site stays up if SMS is off.
 */
class StudioNotify
{
    public static function login(?object $user): void
    {
        $phone = self::phoneOf($user);
        if ($phone === '') {
            return;
        }
        self::send($phone, 'ورود به فراز نت آمل ثبت شد. اگر شما نبودید به پشتیبانی پیام بدهید.');
    }

    /** @param  array<string, mixed>  $ticket */
    public static function ticketOpened(array $ticket): void
    {
        $phone = StudioSms::normalizePhone((string) ($ticket['phone'] ?? ''));
        if ($phone === '') {
            return;
        }
        $id = (string) ($ticket['id'] ?? '');
        self::send($phone, 'تیکت '.$id.' در فراز نت آمل ثبت شد. پاسخ از کارتابل یا صفحه پشتیبانی می‌آید.');
    }

    /** @param  array<string, mixed>  $invoice */
    public static function invoice(array $invoice): void
    {
        $phone = StudioSms::normalizePhone((string) ($invoice['buyer_phone'] ?? ''));
        $title = (string) ($invoice['title'] ?? 'سفارش');
        $id = (string) ($invoice['id'] ?? '');
        if ($phone !== '') {
            self::send($phone, 'فاکتور '.$id.' برای «'.$title.'» ثبت شد. جزئیات در کارتابل است.');
        }
        $sellerPhone = StudioSms::normalizePhone((string) ($invoice['seller_phone'] ?? ''));
        if ($sellerPhone !== '' && $sellerPhone !== $phone) {
            $share = (int) ($invoice['share'] ?? 0);
            $shareText = $share > 0 ? number_format($share).' تومان' : 'سهم بعد از اعلام قیمت';
            self::send($sellerPhone, 'فروش «'.$title.'» ثبت شد. سهم شما: '.$shareText.' — فاکتور '.$id);
        }
    }

    public static function send(string $phone, string $message): bool
    {
        try {
            if (! class_exists(StudioSms::class)) {
                return false;
            }
            $out = StudioSms::sendSms($phone, $message);

            return ! empty($out['ok']);
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function phoneOf(?object $user): string
    {
        if (! $user) {
            return '';
        }
        try {
            $phone = (string) ($user->phone ?? '');
            if (class_exists(StudioSms::class)) {
                return StudioSms::normalizePhone($phone);
            }

            return $phone;
        } catch (\Throwable) {
            return '';
        }
    }
}
