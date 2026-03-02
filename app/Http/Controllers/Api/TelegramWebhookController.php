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
        $update = $request->all();

        if (empty($update)) {
            return response('No data', 200);
        }

        $bot->handleUpdate($update);

        return response('OK', 200);
    }
}