<?php
declare(strict_types=1);

namespace HddLand\Bot\AdminBot;

/**
 * HTTP kernel for @SedivSupport_bot (English Admin Console).
 */
final class AdminBotKernel
{
    public static function handleHttp(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            header('Content-Type: text/plain; charset=utf-8');
            $token = function_exists('admin_bot_token') ? admin_bot_token() : '';
            echo "SeDiv Admin Console bot webhook is online.\n";
            echo 'Token: ' . ($token !== '' ? 'configured' : 'MISSING') . "\n";
            exit;
        }

        $expected = trim((string)cfg('admin_bot_webhook_secret', ''));
        if ($expected !== '') {
            $got = (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
            if (!hash_equals($expected, $got)) {
                http_response_code(403);
                echo 'forbidden';
                return;
            }
        }

        if (admin_bot_token() === '') {
            http_response_code(500);
            echo 'admin_bot_token missing';
            return;
        }

        // Load credential verifier without depending on web session redirect paths
        if (!function_exists('verify_admin_credentials')) {
            $auth = dirname(__DIR__, 2) . '/admin/auth.php';
            if (is_file($auth)) {
                require_once $auth;
            }
        }

        $raw = file_get_contents('php://input');
        $update = json_decode($raw ?: '[]', true);
        if (!$update || !is_array($update)) {
            echo 'no update';
            return;
        }

        try {
            AdminRouter::handleUpdate($update);
        } catch (\Throwable $e) {
            @file_put_contents(
                dirname(__DIR__, 2) . '/error.log',
                date('c') . ' admin_bot: ' . $e->getMessage() . "\n",
                FILE_APPEND
            );
        }
        echo 'ok';
    }
}
