<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetup extends Command
{
    protected $signature = 'wolffiles:telegram-setup';
    protected $description = 'Set up Telegram bot webhook and commands';

    public function handle(): int
    {
        $this->info('🤖 Setting up Telegram Bot...');

        // Set webhook
        $this->line('📡 Registering webhook...');
        $result = TelegramBotService::setWebhook();
        if ($result['ok'] ?? false) {
            $this->info('  ✅ Webhook registered: ' . url('/api/telegram/webhook'));
        } else {
            $this->error('  ❌ Webhook failed: ' . ($result['description'] ?? 'Unknown error'));
        }

        // Set commands
        $this->line('📋 Registering commands...');
        $result = TelegramBotService::setCommands();
        if ($result['ok'] ?? false) {
            $this->info('  ✅ Commands registered!');
        } else {
            $this->error('  ❌ Commands failed: ' . ($result['description'] ?? 'Unknown error'));
        }

        $this->newLine();
        $this->info('🐺 Telegram Bot is ready!');
        $this->line('   Open: https://t.me/wolffileseu_bot');

        return self::SUCCESS;
    }
}