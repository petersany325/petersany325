<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\WhatsappMessage;

class WhatsApp
{
    public static function number(): string
    {
        return preg_replace('/\D+/', '', Setting::getValue('whatsapp_number', '27105002140') ?? '27105002140');
    }

    public static function link(string $text, ?string $to = null): string
    {
        $phone = preg_replace('/\D+/', '', $to ?: self::number());

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }

    public static function logOutbound(string $body, ?string $to = null, ?string $template = null, ?string $relatedType = null, ?int $relatedId = null): WhatsappMessage
    {
        return WhatsappMessage::query()->create([
            'direction' => 'out',
            'to_phone' => $to ?: self::number(),
            'from_label' => 'EK Operations',
            'template' => $template,
            'body' => $body,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'sent_at' => now(),
        ]);
    }
}
