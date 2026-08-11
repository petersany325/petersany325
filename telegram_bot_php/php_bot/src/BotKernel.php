<?php
declare(strict_types=1);

namespace HddLand\Bot;

use HddLand\Bot\Handlers\CallbackRouter;
use HddLand\Bot\Handlers\MessageRouter;
use HddLand\Bot\Middleware\EnsureUserMiddleware;
use HddLand\Bot\Middleware\MaintenanceMiddleware;
use HddLand\Bot\Middleware\Pipeline;
use HddLand\Bot\Middleware\SecretTokenMiddleware;

/**
 * Application kernel: secret → parse update → middleware → routers.
 */
final class BotKernel
{
    public static function handleHttp(): void
    {
        if (function_exists('do_action')) {
            do_action('bot_boot');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            header('Content-Type: text/plain; charset=utf-8');
            echo "HDD-Land PHP webhook is online (layered architecture).\n";
            exit;
        }

        SecretTokenMiddleware::assertOrAbort();

        $raw = file_get_contents('php://input');
        $update = json_decode($raw ?: '[]', true);
        if (!$update || !is_array($update)) {
            exit('no update');
        }

        try {
            self::dispatch($update);
        } catch (\Throwable $e) {
            @file_put_contents(
                dirname(__DIR__) . '/error.log',
                date('c') . ' kernel: ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }

        echo 'ok';
    }

    /** @param array<string,mixed> $update */
    public static function dispatch(array $update): void
    {
        $ctx = Context::fromUpdate($update);
        if (!$ctx->isCallback() && !$ctx->isMessage()) {
            return;
        }

        $pipeline = (new Pipeline())
            ->pipe(new EnsureUserMiddleware())
            ->pipe(new MaintenanceMiddleware());

        $pipeline->process($ctx, static function (Context $ctx): void {
            if ($ctx->isCallback()) {
                CallbackRouter::handle($ctx);
            } else {
                MessageRouter::handle($ctx);
            }
        });
    }
}
