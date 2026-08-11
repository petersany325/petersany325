<?php
declare(strict_types=1);

namespace HddLand\Bot;

/**
 * Request-scoped context for one Telegram update.
 */
final class Context
{
    /** @var array<string,mixed> */
    public array $update;
    /** @var array<string,mixed>|null */
    public ?array $message = null;
    /** @var array<string,mixed>|null */
    public ?array $callback = null;
    /** @var array<string,mixed> */
    public array $from = [];

    public int $chatId = 0;
    public int $userId = 0;
    public int $messageId = 0;
    public string $lang = 'en';
    public string $text = '';
    public string $callbackData = '';
    public string $callbackId = '';

    /** @param array<string,mixed> $update */
    public static function fromUpdate(array $update): self
    {
        $ctx = new self();
        $ctx->update = $update;

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $cb = $update['callback_query'];
            $ctx->callback = $cb;
            $ctx->from = isset($cb['from']) && is_array($cb['from']) ? $cb['from'] : [];
            $ctx->callbackId = (string)($cb['id'] ?? '');
            $ctx->callbackData = (string)($cb['data'] ?? '');
            $msg = isset($cb['message']) && is_array($cb['message']) ? $cb['message'] : null;
            $ctx->message = $msg;
            $ctx->chatId = (int)($msg['chat']['id'] ?? 0);
            $ctx->messageId = (int)($msg['message_id'] ?? 0);
        } elseif (isset($update['message']) && is_array($update['message'])) {
            $msg = $update['message'];
            $ctx->message = $msg;
            $ctx->from = isset($msg['from']) && is_array($msg['from']) ? $msg['from'] : [];
            $ctx->chatId = (int)($msg['chat']['id'] ?? 0);
            $ctx->messageId = (int)($msg['message_id'] ?? 0);
            $ctx->text = trim((string)($msg['text'] ?? ''));
        }

        $ctx->userId = (int)($ctx->from['id'] ?? 0);
        return $ctx;
    }

    public function isCallback(): bool
    {
        return $this->callback !== null;
    }

    public function isMessage(): bool
    {
        return $this->message !== null && $this->callback === null;
    }
}
