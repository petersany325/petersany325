<?php
declare(strict_types=1);

final class Telegram
{
    public function __construct(private string $token)
    {
    }

    public function api(string $method, array $params = [], ?string $filePath = null, string $fileField = 'photo'): array
    {
        $url = 'https://api.telegram.org/bot' . $this->token . '/' . $method;

        if ($filePath !== null) {
            if (isset($params['reply_markup']) && is_array($params['reply_markup'])) {
                $params['reply_markup'] = json_encode($params['reply_markup'], JSON_UNESCAPED_UNICODE);
            }
            $cfile = new CURLFile(
                $filePath,
                mime_content_type($filePath) ?: 'application/octet-stream',
                basename($filePath)
            );
            $params[$fileField] = $cfile;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_TIMEOUT => 60,
            ]);
        } else {
            if (isset($params['reply_markup']) && is_string($params['reply_markup'])) {
                $decoded = json_decode($params['reply_markup'], true);
                if (is_array($decoded)) {
                    $params['reply_markup'] = $decoded;
                }
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => 30,
            ]);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Telegram curl error: ' . $err);
        }
        curl_close($ch);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid Telegram response');
        }
        return $data;
    }

    public function sendMessage(int $chatId, string $text, array $extra = []): array
    {
        return $this->api('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], $extra));
    }

    /** True when Telegram accepted the send (ok=true). */
    public function messageDelivered(array $apiResponse): bool
    {
        return !empty($apiResponse['ok']);
    }

    public function trySendMessage(int $chatId, string $text, array $extra = []): bool
    {
        try {
            return $this->messageDelivered($this->sendMessage($chatId, $text, $extra));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function sendPhoto(int $chatId, string $path, string $caption, array $replyMarkup = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }
        return $this->api('sendPhoto', $params, $path, 'photo');
    }

    public function sendPhotoFileId(int $chatId, string $fileId, string $caption, array $replyMarkup = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $fileId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }
        return $this->api('sendPhoto', $params);
    }

    public function answerInlineQuery(
        string $inlineQueryId,
        array $results,
        int $cacheTime = 1,
        bool $isPersonal = true,
        string $nextOffset = '',
        ?array $button = null
    ): array {
        $params = [
            'inline_query_id' => $inlineQueryId,
            'results' => $results,
            'cache_time' => $cacheTime,
            'is_personal' => $isPersonal,
            'next_offset' => $nextOffset,
        ];
        if ($button) {
            $params['button'] = $button;
        }
        return $this->api('answerInlineQuery', $params);
    }

    public function getMe(): array
    {
        return $this->api('getMe');
    }

    public function answerCallback(string $id, string $text = '', bool $alert = false): array
    {
        return $this->api('answerCallbackQuery', [
            'callback_query_id' => $id,
            'text' => $text,
            'show_alert' => $alert,
        ]);
    }

    public function copyMessage(int $toChatId, int $fromChatId, int $messageId): array
    {
        return $this->api('copyMessage', [
            'chat_id' => $toChatId,
            'from_chat_id' => $fromChatId,
            'message_id' => $messageId,
        ]);
    }

    public function deleteMessage(int $chatId, int $messageId): array
    {
        return $this->api('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /** Batch-delete bot messages in a private chat (Telegram limit: 100). */
    public function deleteMessages(int $chatId, array $messageIds): array
    {
        $ids = [];
        foreach ($messageIds as $id) {
            if (is_numeric($id) && (int)$id > 0) {
                $ids[] = (int)$id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) {
            return ['ok' => true, 'result' => true];
        }
        // Telegram accepts up to 100 ids per call
        $last = ['ok' => true, 'result' => true];
        foreach (array_chunk($ids, 100) as $chunk) {
            $last = $this->api('deleteMessages', [
                'chat_id' => $chatId,
                'message_ids' => $chunk,
            ]);
        }
        return $last;
    }

    /** Remove inline buttons without deleting the message (fallback). */
    public function clearInlineKeyboard(int $chatId, int $messageId): array
    {
        return $this->api('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ]);
    }

    public static function messageIdFrom(array $apiResponse): ?int
    {
        $id = $apiResponse['result']['message_id'] ?? null;
        return is_numeric($id) ? (int)$id : null;
    }
}
