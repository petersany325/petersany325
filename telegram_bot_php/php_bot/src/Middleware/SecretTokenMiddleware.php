<?php
declare(strict_types=1);

namespace HddLand\Bot\Middleware;

use HddLand\Bot\Context;

/**
 * Validates Telegram webhook secret before any handler runs.
 * Used from Kernel entry, not only pipeline.
 */
final class SecretTokenMiddleware
{
    public static function assertOrAbort(): void
    {
        $cfg = bot_config();
        $secretHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        if (!empty($cfg['webhook_secret']) && !hash_equals((string)$cfg['webhook_secret'], (string)$secretHeader)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
