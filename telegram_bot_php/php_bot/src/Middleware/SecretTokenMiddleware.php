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
        $expected = (string)($cfg['webhook_secret'] ?? '');
        if ($expected === '') {
            return;
        }
        $secretHeader = (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
        if ($secretHeader !== '' && hash_equals($expected, $secretHeader)) {
            return;
        }
        // Missing/mismatched secret: Telegram updates never reach language/menus.
        @file_put_contents(
            dirname(__DIR__, 2) . '/error.log',
            date('c') . ' webhook secret rejected (header '
            . ($secretHeader === '' ? 'missing' : 'mismatch')
            . "). Re-run Set Webhook in admin so secret_token matches config.\n",
            FILE_APPEND
        );
        http_response_code(403);
        exit('Forbidden');
    }
}
