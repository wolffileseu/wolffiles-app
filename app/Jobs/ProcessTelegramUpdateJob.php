<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Verarbeitet ein Telegram-Webhook-Update asynchron.
 * Idempotenz via update_id - retried updates werden ignoriert.
 */
class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    public function __construct(public readonly array $update)
    {
        $this->onQueue("default");
    }

    public function handle(TelegramBotService $bot): void
    {
        $updateId = $this->update["update_id"] ?? null;

        if ($updateId !== null) {
            $key = "telegram:update:" . $updateId;
            if (!Cache::add($key, 1, now()->addDay())) {
                Log::info("Telegram update bereits verarbeitet, skip", ["update_id" => $updateId]);
                return;
            }
        }

        $bot->handleUpdate($this->update);
    }
}
