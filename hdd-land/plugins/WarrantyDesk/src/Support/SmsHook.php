<?php

namespace Plugins\WarrantyDesk\src\Support;

class SmsHook
{
    public static function notifyStatus(string $mobile, string $code, string $statusLabel, string $extra = ''): bool
    {
        $mobile = preg_replace('/\D+/', '', $mobile) ?: '';
        if (strlen($mobile) < 10) {
            return false;
        }

        $msg = 'سرزمین هارد: درخواست گارانتی '.$code.' — وضعیت: '.$statusLabel;
        if (trim($extra) !== '') {
            $msg .= ' — '.$extra;
        }

        return self::send($mobile, $msg);
    }

    public static function send(string $mobile, string $message): bool
    {
        $classes = [
            'Plugins\\AuthCustomers\\src\\Support\\SmsGateway',
            'Plugins\\AuthCustomers\\SmsGateway',
            'App\\Support\\SmsGateway',
        ];

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                continue;
            }
            try {
                if (method_exists($class, 'send')) {
                    $class::send($mobile, $message);

                    return true;
                }
                if (method_exists($class, 'sendSms')) {
                    $class::sendSms($mobile, $message);

                    return true;
                }
                if (method_exists($class, 'dispatch')) {
                    $class::dispatch($mobile, $message);

                    return true;
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }
}
