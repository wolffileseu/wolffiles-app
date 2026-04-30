<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTelegramUpdateJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $secretToken = config("services.telegram.webhook_secret");
        if ($secretToken && $request->header("X-Telegram-Bot-Api-Secret-Token") !== $secretToken) {
            return response("Unauthorized", 403);
        }

        $update = $request->all();

        if (empty($update)) {
            return response("No data", 200);
        }

        // Sofort 200 - Verarbeitung async, kein Telegram-Retry mehr
        ProcessTelegramUpdateJob::dispatch($update);

        return response("OK", 200);
    }
}
