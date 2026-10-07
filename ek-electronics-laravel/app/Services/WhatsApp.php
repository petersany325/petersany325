<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsApp
{
    public static function number(): string
    {
        return preg_replace('/\D+/', '', Setting::getValue('whatsapp_number', '27105002140') ?? '27105002140');
    }

    public static function displayName(): string
    {
        return Setting::getValue('whatsapp_display_name', 'EK Operations') ?: 'EK Operations';
    }

    public static function link(string $text, ?string $to = null): string
    {
        $phone = preg_replace('/\D+/', '', $to ?: self::number());
        $signature = Setting::getValue('whatsapp_signature', '');
        if ($signature && ! str_contains($text, trim($signature))) {
            $text .= $signature;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    public static function apiEnabled(): bool
    {
        return Setting::bool('whatsapp_api_enabled', false)
            && Setting::getValue('whatsapp_api_provider', 'wa_me') !== 'wa_me';
    }

    /** @return array{ok: bool, message: string} */
    public static function testApiConnection(): array
    {
        if (! Setting::bool('whatsapp_api_enabled', false)) {
            return ['ok' => false, 'message' => 'API is not activated. Turn on “Activate WhatsApp Cloud / Business API” and save.'];
        }

        $provider = Setting::getValue('whatsapp_api_provider', 'wa_me');
        if ($provider === 'wa_me') {
            return ['ok' => false, 'message' => 'Provider is set to wa.me links only. Choose Meta or Twilio to test API.'];
        }

        $token = Setting::getValue('whatsapp_api_access_token');
        $phoneId = Setting::getValue('whatsapp_phone_number_id');
        if (! filled($token) || ! filled($phoneId)) {
            return ['ok' => false, 'message' => 'Access token and Phone number ID / Account SID are required.'];
        }

        try {
            if ($provider === 'meta' || $provider === 'custom') {
                $version = Setting::getValue('whatsapp_api_version', 'v21.0') ?: 'v21.0';
                $base = rtrim(Setting::getValue('whatsapp_api_base_url', 'https://graph.facebook.com') ?: 'https://graph.facebook.com', '/');
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(20)
                    ->get("{$base}/{$version}/{$phoneId}", ['fields' => 'display_phone_number,verified_name,quality_rating']);

                if ($response->successful()) {
                    $name = $response->json('verified_name') ?: $response->json('display_phone_number') ?: 'OK';

                    return ['ok' => true, 'message' => 'Connected to Meta Cloud API ('.$name.'). Mode: '.Setting::getValue('whatsapp_api_mode', 'sandbox')];
                }

                return ['ok' => false, 'message' => 'Meta API error: '.$response->status().' '.$response->body()];
            }

            if ($provider === 'twilio') {
                $sid = $phoneId;
                $response = Http::withBasicAuth($sid, (string) $token)
                    ->timeout(20)
                    ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");
                if ($response->successful()) {
                    return ['ok' => true, 'message' => 'Connected to Twilio account '.$sid];
                }

                return ['ok' => false, 'message' => 'Twilio error: '.$response->status().' '.$response->body()];
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => false, 'message' => 'Unknown provider.'];
    }

    public static function logOutbound(string $body, ?string $to = null, ?string $template = null, ?string $relatedType = null, ?int $relatedId = null): WhatsappMessage
    {
        $signature = Setting::getValue('whatsapp_signature', '');
        if ($signature && ! str_contains($body, trim((string) $signature))) {
            $body .= $signature;
        }

        $message = WhatsappMessage::query()->create([
            'direction' => 'out',
            'to_phone' => $to ?: self::number(),
            'from_label' => self::displayName(),
            'template' => $template,
            'body' => $body,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'sent_at' => now(),
        ]);

        if (self::apiEnabled() && self::shouldSendViaApi($template)) {
            $sent = self::sendViaApi($message->to_phone, $body);
            if (! $sent && Setting::bool('whatsapp_api_fallback_wame', true)) {
                // keep log; storefront/controllers may still open wa.me
            }
        }

        return $message;
    }

    public static function logInbound(string $body, string $fromPhone, ?string $fromLabel = null): WhatsappMessage
    {
        return WhatsappMessage::query()->create([
            'direction' => 'in',
            'to_phone' => self::number(),
            'from_label' => $fromLabel ?: $fromPhone,
            'template' => 'inbound',
            'body' => $body,
            'sent_at' => now(),
        ]);
    }

    protected static function shouldSendViaApi(?string $template): bool
    {
        return match ($template) {
            'order' => Setting::bool('whatsapp_api_send_orders', true),
            'ticket', 'ticket_reply' => Setting::bool('whatsapp_api_send_tickets', true),
            'contact' => Setting::bool('whatsapp_api_send_contact', false),
            default => true,
        };
    }

    public static function sendViaApi(string $to, string $body): bool
    {
        $provider = Setting::getValue('whatsapp_api_provider', 'wa_me');
        $to = preg_replace('/\D+/', '', $to) ?: self::number();

        try {
            if ($provider === 'meta' || $provider === 'custom') {
                $token = Setting::getValue('whatsapp_api_access_token');
                $phoneId = Setting::getValue('whatsapp_phone_number_id');
                $version = Setting::getValue('whatsapp_api_version', 'v21.0') ?: 'v21.0';
                $base = rtrim(Setting::getValue('whatsapp_api_base_url', 'https://graph.facebook.com') ?: 'https://graph.facebook.com', '/');
                if (! filled($token) || ! filled($phoneId)) {
                    return false;
                }

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(25)
                    ->post("{$base}/{$version}/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'text',
                        'text' => ['preview_url' => false, 'body' => $body],
                    ]);

                return $response->successful();
            }

            if ($provider === 'twilio') {
                $sid = Setting::getValue('whatsapp_phone_number_id');
                $token = Setting::getValue('whatsapp_api_access_token');
                $from = Setting::getValue('whatsapp_business_account_id');
                if (! filled($sid) || ! filled($token) || ! filled($from)) {
                    return false;
                }
                $response = Http::withBasicAuth($sid, (string) $token)
                    ->asForm()
                    ->timeout(25)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'From' => str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from,
                        'To' => 'whatsapp:+'.$to,
                        'Body' => $body,
                    ]);

                return $response->successful();
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    /** @return list<string> */
    public static function quickReplies(): array
    {
        $raw = Setting::getValue('whatsapp_quick_replies', "Stock check\nCourier quote\nData recovery\nTalk to sales");

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $raw) ?: [])));
    }
}
