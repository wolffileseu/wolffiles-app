<?php

namespace App\Filament\Pages;

use App\Services\TelegramNotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TelegramSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Telegram Notifications';
    protected static ?string $title = 'Telegram Notifications';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.telegram-settings';

    public bool $enabled = true;
    public string $bot_token = '';
    public string $chat_id = '';
    public array $events = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static array $availableEvents = [
        'file_uploaded' => '📦 New File Uploaded',
        'file_approved' => '✅ File Approved',
        'comment_posted' => '💬 New Comment',
        'donation' => '💰 Donation Received',
        'user_registered' => '👋 New User Registration',
        'contact_form' => '📩 Contact Form Message',
        'server_order' => '🖥️ New Server Order',
        'news_posted' => '📰 News Published',
        'map_of_week' => '🗺️ Map of the Week',
        'report' => '🚩 New Report',
    ];

    public function mount(): void
    {
        $this->enabled = config('services.telegram.enabled', false);
        $this->bot_token = config('services.telegram.bot_token', '');
        $this->chat_id = config('services.telegram.chat_id', '');
        $this->events = config('services.telegram.events', []);
    }

    public function save(): void
    {
        // Update .env file
        $this->updateEnv([
            'TELEGRAM_BOT_TOKEN' => $this->bot_token,
            'TELEGRAM_CHAT_ID' => $this->chat_id,
            'TELEGRAM_ENABLED' => $this->enabled ? 'true' : 'false',
            'TELEGRAM_EVENTS' => implode(',', $this->events),
        ]);

        // Clear config cache
        \Artisan::call('config:clear');

        Notification::make()
            ->title('Telegram settings saved!')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        // Temporarily set config for testing
        config([
            'services.telegram.bot_token' => $this->bot_token,
            'services.telegram.chat_id' => $this->chat_id,
            'services.telegram.enabled' => true,
        ]);

        $service = new TelegramNotificationService();
        $result = $service->test();

        if ($result['success']) {
            Notification::make()
                ->title('✅ Test message sent!')
                ->body('Check your Telegram!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('❌ Test failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if (strpos($envContent, "{$key}=") !== false) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}