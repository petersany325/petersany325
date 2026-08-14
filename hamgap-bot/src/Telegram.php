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

        // Telegram multipart expects reply_markup as JSON string.
        // JSON body expects reply_markup as object/array.
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
}
