<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Tangani request webhook dari Telegram Bot API.
     */
    public function handle(Request $request, TelegramService $telegramService): JsonResponse
    {
        $update = $request->all();

        if (! empty($update['message']) && is_array($update['message'])) {
            $telegramService->handleIncomingMessage($update['message']);
        } elseif (! empty($update['callback_query']['message']) && is_array($update['callback_query']['message'])) {
            $telegramService->handleIncomingMessage($update['callback_query']['message']);
        }

        return response()->json(['ok' => true]);
    }
}
