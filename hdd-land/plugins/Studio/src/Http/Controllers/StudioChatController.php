<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioChat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class StudioChatController
{
    public function ask(Request $request): JsonResponse
    {
        $key = 'studio-chat:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'text' => 'چند پیام پشت سر هم آمد. کمی بعد دوباره بپرسید، یا از دکمه‌های فروش و پشتیبانی استفاده کنید.',
                'links' => [],
                'handoff' => null,
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $message = trim((string) $request->input('message', ''));
        if (mb_strlen($message) > 500) {
            $message = mb_substr($message, 0, 500);
        }

        return response()->json(StudioChat::reply($message));
    }
}
