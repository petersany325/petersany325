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
        if (function_exists('ensure_license_sample_files')) {
            ensure_license_sample_files();
        }

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

        // Telegram retries timed-out webhooks — ignore duplicate update_id
        $updateId = (int)($update['update_id'] ?? 0);
        if ($updateId > 0 && self::isDuplicateUpdate($updateId)) {
            echo 'ok';
            return;
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

    private static function isDuplicateUpdate(int $updateId): bool
    {
        try {
            $pdo = db();
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS telegram_updates (
                    update_id BIGINT PRIMARY KEY,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
            $st = $pdo->prepare('INSERT IGNORE INTO telegram_updates (update_id) VALUES (?)');
            $st->execute(array($updateId));
            if ($st->rowCount() === 0) {
                return true;
            }
            // keep table small
            if ($updateId % 50 === 0) {
                $pdo->exec('DELETE FROM telegram_updates WHERE update_id < ' . (int)($updateId - 5000));
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }
}
