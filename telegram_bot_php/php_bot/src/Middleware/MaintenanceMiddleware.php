<?php
declare(strict_types=1);

namespace HddLand\Bot\Middleware;

use HddLand\Bot\Context;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    public function handle(Context $ctx, callable $next): void
    {
        if (
            function_exists('cfg')
            && (int)cfg('maintenance_mode', 0) === 1
            && $ctx->userId > 0
            && !is_admin($ctx->userId)
        ) {
            if ($ctx->isCallback() && $ctx->callbackId !== '') {
                answer_callback($ctx->callbackId);
            }
            send_message($ctx->chatId, (string)cfg('maintenance_text', 'Bot is under maintenance.'));
            return;
        }
        $next($ctx);
    }
}
