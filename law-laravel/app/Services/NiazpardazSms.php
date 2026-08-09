<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Setting;
use App\Support\Jalali;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NiazpardazSms
{
    public function enabled(): bool
    {
        return Setting::get('sms_enabled', '0') === '1';
    }

    public function send(string $to, string $message): array
    {
        $to = preg_replace('/\D+/', '', $to) ?: '';
        $message = trim($message);

        if ($to === '' || $message === '') {
            return ['ok' => false, 'error' => 'شماره یا متن خالی است'];
        }

        if (! $this->enabled()) {
            return ['ok' => false, 'error' => 'ارسال پیامک غیرفعال است'];
        }

        $from = trim((string) Setting::get('sms_from', ''));
        $username = trim((string) Setting::get('sms_username', ''));
        $password = (string) Setting::get('sms_password', '');
        $apiKey = trim((string) Setting::get('sms_api_key', ''));

        try {
            if ($apiKey !== '') {
                // Official REST (API Key) — https://api.niazpardaz-sms.com style via panel gateway
                $response = Http::timeout(20)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.niazpardaz.ir/api/v2/RestWebApi/SendSms', [
                        'fromNumber' => $from,
                        'toNumbers' => [$to],
                        'messageContent' => $message,
                        'isFlash' => false,
                    ]);

                // Fallback classic panel endpoint if API host fails
                if (! $response->successful()) {
                    $response = Http::timeout(20)->asForm()->post('https://panel.niazpardaz-sms.com/SMSInOutBox/Send', [
                        'username' => $username,
                        'password' => $password,
                        'from' => $from,
                        'to' => $to,
                        'text' => $message,
                        'apiKey' => $apiKey,
                    ]);
                }
            } else {
                $response = Http::timeout(20)->get('https://panel.niazpardaz-sms.com/SMSInOutBox/SendSms', [
                    'username' => $username,
                    'password' => $password,
                    'from' => $from,
                    'to' => $to,
                    'text' => $message,
                ]);
            }

            $body = $response->body();
            $ok = $response->successful();

            Log::info('niazpardaz.sms', [
                'to' => $to,
                'status' => $response->status(),
                'body' => mb_substr($body, 0, 300),
            ]);

            return [
                'ok' => $ok,
                'status' => $response->status(),
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            Log::warning('niazpardaz.sms_failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function renderTemplate(string $template, Appointment $appointment): string
    {
        $date = $appointment->preferred_date
            ? Jalali::format($appointment->preferred_date, 'Y/m/d')
            : '—';

        $status = match ($appointment->status) {
            'pending' => 'در انتظار',
            'confirmed' => 'تأیید شده',
            'done' => 'انجام شده',
            'cancelled' => 'لغو شده',
            default => (string) $appointment->status,
        };

        $brand = Setting::get('site_name', 'مؤسسه حقوقی');

        return strtr($template, [
            '{name}' => (string) $appointment->name,
            '{phone}' => (string) $appointment->phone,
            '{date}' => $date,
            '{time}' => (string) ($appointment->preferred_time ?: '—'),
            '{topic}' => (string) ($appointment->topic ?: 'مشاوره'),
            '{status}' => $status,
            '{brand}' => (string) $brand,
            '{notes}' => (string) ($appointment->notes ?: ''),
        ]);
    }

    public function notifyAppointmentCreated(Appointment $appointment): void
    {
        if (! $this->enabled() || Setting::get('sms_on_appointment', '1') !== '1') {
            return;
        }

        $tpl = Setting::get(
            'sms_tpl_appointment',
            "{brand}\n{name} عزیز، درخواست نوبت شما ثبت شد.\nموضوع: {topic}\nتاریخ پیشنهادی: {date} ساعت {time}\nبه‌زودی هماهنگ می‌کنیم."
        );
        $this->send((string) $appointment->phone, $this->renderTemplate($tpl, $appointment));

        $adminPhone = trim((string) Setting::get('sms_admin_phone', ''));
        if ($adminPhone !== '' && Setting::get('sms_notify_admin', '1') === '1') {
            $adminTpl = Setting::get(
                'sms_tpl_admin',
                "نوبت جدید\n{name} | {phone}\n{topic}\n{date} {time}"
            );
            $this->send($adminPhone, $this->renderTemplate($adminTpl, $appointment));
        }
    }

    public function notifyAppointmentConfirmed(Appointment $appointment): void
    {
        if (! $this->enabled() || Setting::get('sms_on_confirm', '1') !== '1') {
            return;
        }

        $tpl = Setting::get(
            'sms_tpl_confirm',
            "{brand}\n{name} عزیز، نوبت مشاوره شما تأیید شد.\nتاریخ: {date}\nساعت: {time}\nموضوع: {topic}"
        );
        $this->send((string) $appointment->phone, $this->renderTemplate($tpl, $appointment));
    }
}
