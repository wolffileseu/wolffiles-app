<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $bot): Response
    {
        // Verify webhook secret token
        $secretToken = config('services.telegram.webhook_secret');
        if ($secretToken && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secretToken) {
            return response('Unauthorized', 403);
        }

        $update = $request->all();

        if (empty($update)) {
            return response('No data', 200);
        }

        $bot->handleUpdate($update);

        return response('OK', 200);
    }
}