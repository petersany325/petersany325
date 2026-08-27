<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

final class AiService
{
    public static function ask(int $chatId, string $question): void
    {
        if ($question === '') {
            send_message($chatId, 'Usage: /ask your question');
            return;
        }
        $key = function_exists('cfg') ? (string)cfg('openai_api_key', '') : (string)(bot_config()['openai_api_key'] ?? '');
        if ($key === '') {
            send_message($chatId, '⚠️ OpenAI API key is not configured. Set it in Admin → Settings → AI / API.');
            return;
        }

        $payload = [
            'model' => function_exists('cfg') ? (string)cfg('ai_model', 'gpt-4o-mini') : 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => function_exists('cfg')
                        ? (string)cfg('ai_system_prompt', 'You are an expert HDD repair assistant for HDD-Land / SeDiv.')
                        : 'You are an expert HDD repair and data recovery assistant for HDD-Land.com / SeDiv. Be concise and professional.',
                ],
                ['role' => 'user', 'content' => $question],
            ],
            'max_tokens' => 800,
        ];
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$key}\r\n",
                'content' => json_encode($payload),
                'timeout' => 60,
            ],
        ]);
        $raw = @file_get_contents('https://api.openai.com/v1/chat/completions', false, $ctx);
        if ($raw === false) {
            send_message($chatId, '❌ AI request failed.');
            return;
        }
        $json = json_decode($raw, true);
        $answer = $json['choices'][0]['message']['content'] ?? 'No response.';
        send_message($chatId, "🤖 <b>AI Answer:</b>\n\n" . htmlspecialchars((string)$answer));
    }
}
