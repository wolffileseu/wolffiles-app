<?php

namespace App\Console\Commands;

use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TestTelegram extends Command
{
    protected $signature = 'wolffiles:test-telegram';
    protected $description = 'Test Telegram notification connection';

    public function handle(): int
    {
        $this->info('🤖 Testing Telegram connection...');

        $service = app(TelegramNotificationService::class);
        $result = $service->test();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            return self::SUCCESS;
        }

        $this->error('❌ ' . $result['message']);
        return self::FAILURE;
    }
}