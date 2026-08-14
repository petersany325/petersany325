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
            $cfile = new CURLFile($filePath, mime_content_type($filePath) ?: 'application/octet-stream', basename($filePath));
            $params[$fileField] = $cfile;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_TIMEOUT => 60,
            ]);
        } else {
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
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('sendPhoto', $params, $path, 'photo');
    }

    public function editMessageCaption(int $chatId, int $messageId, string $caption, array $replyMarkup = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('editMessageCaption', $params);
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
