<?php

namespace App\Services\SocialMedia\Providers;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscordProvider implements SocialMediaProvider
{
    public function getIdentifier(): string
    {
        return 'discord';
    }

    public function getName(): string
    {
        return 'Discord';
    }

    public static function getConfigFields(): array
    {
        return [
            'webhook_url' => [
                'type' => 'url',
                'label' => 'Webhook URL',
                'required' => true,
                'placeholder' => 'https://discord.com/api/webhooks/...',
                'help' => 'Discord Channel → Settings → Integrations → Webhooks → Copy URL',
            ],
            'bot_name' => [
                'type' => 'text',
                'label' => 'Bot Name',
                'default' => 'Wolffiles.eu',
                'required' => false,
            ],
            'avatar_url' => [
                'type' => 'url',
                'label' => 'Avatar URL',
                'default' => 'https://wolffiles.eu/images/logo.png',
                'required' => false,
            ],
        ];
    }

    public function sendFileApproved(SocialMediaChannel $channel, array $data): bool
    {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        if (!$webhookUrl) return false;

        $embed = [
            'title' => '📁 ' . ($data['title'] ?? 'New File'),
            'description' => Str::limit(strip_tags($data['description'] ?? ''), 200),
            'url' => $data['url'] ?? '',
            'color' => 0xF59E0B,
            'fields' => array_filter([
                $data['category'] ? ['name' => 'Category', 'value' => $data['category'], 'inline' => true] : null,
                $data['file_size'] ? ['name' => 'Size', 'value' => $data['file_size'], 'inline' => true] : null,
                $data['uploader'] ? ['name' => 'Uploaded by', 'value' => $data['uploader'], 'inline' => true] : null,
            ]),
            'footer' => ['text' => '🐺 Wolffiles.eu — New file available!'],
            'timestamp' => now()->toIso8601String(),
        ];

        if (!empty($data['thumbnail_url'])) {
            $embed['thumbnail'] = ['url' => $data['thumbnail_url']];
        }

        return $this->post($channel, ['embeds' => [$embed]]);
    }

    public function sendDonation(SocialMediaChannel $channel, array $data): bool
    {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        if (!$webhookUrl) return false;

        $fields = [
            ['name' => '💰 Amount', 'value' => '€' . number_format($data['amount'] ?? 0, 2), 'inline' => true],
            ['name' => '👤 Donor', 'value' => $data['donor'] ?? 'Anonymous', 'inline' => true],
        ];

        if (!empty($data['message'])) {
            $fields[] = ['name' => '💬 Message', 'value' => '"' . $data['message'] . '"', 'inline' => false];
        }

        if (isset($data['yearly_total'], $data['yearly_goal'])) {
            $pct = min(100, round(($data['yearly_total'] / $data['yearly_goal']) * 100));
            $filled = (int) ($pct / 5);
            $bar = str_repeat('█', $filled) . str_repeat('░', 20 - $filled);
            $fields[] = [
                'name' => '📊 Yearly Goal',
                'value' => $bar . "\n€" . number_format($data['yearly_total'], 2) . ' / €' . number_format($data['yearly_goal'], 2) . " ({$pct}%)",
                'inline' => false,
            ];
        }

        return $this->post($channel, [
            'content' => '🎉 **Neue Spende erhalten! | New Donation Received!**',
            'embeds' => [[
                'color' => 0xF59E0B,
                'title' => '💝 Thank you for supporting Wolffiles.eu!',
                'url' => 'https://wolffiles.eu/donate',
                'fields' => $fields,
                'thumbnail' => ['url' => 'https://wolffiles.eu/images/logo.png'],
                'footer' => ['text' => '🐺 Wolffiles.eu — Support us at wolffiles.eu/donate'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ]);
    }

    public function sendMapOfTheWeek(SocialMediaChannel $channel, array $data): bool
    {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        if (!$webhookUrl) return false;

        $embed = [
            'title' => '🗺️ Map of the Week: ' . ($data['title'] ?? 'Unknown'),
            'description' => Str::limit(strip_tags($data['description'] ?? ''), 300),
            'url' => $data['url'] ?? '',
            'color' => 0x10B981,
            'fields' => array_filter([
                $data['author'] ? ['name' => 'Author', 'value' => $data['author'], 'inline' => true] : null,
                $data['download_count'] ? ['name' => 'Downloads', 'value' => number_format($data['download_count']), 'inline' => true] : null,
                $data['rating'] ? ['name' => 'Rating', 'value' => '⭐ ' . $data['rating'], 'inline' => true] : null,
            ]),
            'footer' => ['text' => '🐺 Wolffiles.eu — Map of the Week'],
            'timestamp' => now()->toIso8601String(),
        ];

        if (!empty($data['image_url'])) {
            $embed['image'] = ['url' => $data['image_url']];
        }

        return $this->post($channel, [
            'content' => '🏆 **Map of the Week!**',
            'embeds' => [$embed],
        ]);
    }

    public function testConnection(SocialMediaChannel $channel): array
    {
        $webhookUrl = $channel->getConfigValue('webhook_url');
        if (!$webhookUrl) {
            return ['success' => false, 'message' => 'No webhook URL configured.'];
        }

        try {
            $response = Http::post($webhookUrl, [
                'username' => $channel->getConfigValue('bot_name', 'Wolffiles.eu'),
                'avatar_url' => $channel->getConfigValue('avatar_url', 'https://wolffiles.eu/images/logo.png'),
                'content' => '✅ **Test Message** — Social Media Broadcast System is connected!',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Discord webhook is working!'];
            }

            return ['success' => false, 'message' => 'Discord returned: ' . $response->status() . ' — ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Post to Discord webhook.
     */
    protected function post(SocialMediaChannel $channel, array $payload): bool
    {
        try {
            $payload['username'] = $channel->getConfigValue('bot_name', 'Wolffiles.eu');
            $payload['avatar_url'] = $channel->getConfigValue('avatar_url', 'https://wolffiles.eu/images/logo.png');

            $response = Http::post($channel->getConfigValue('webhook_url'), $payload);

            if ($response->successful()) {
                $channel->markPosted();
                return true;
            }

            $channel->markFailed('HTTP ' . $response->status() . ': ' . $response->body());
            return false;
        } catch (\Exception $e) {
            $channel->markFailed($e->getMessage());
            Log::error('Discord broadcast failed', [
                'channel' => $channel->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendNewsPosted(SocialMediaChannel $channel, array $data): bool
    {
        $embed = [
            'title' => '📰 ' . ($data['title'] ?? 'News'),
            'description' => $data['description'] ?? '',
            'url' => $data['url'] ?? '',
            'color' => 0x3B82F6, // blue
            'fields' => [
                ['name' => '✍️ Author', 'value' => $data['author'] ?? 'Wolffiles.eu', 'inline' => true],
                ['name' => '📅 Published', 'value' => $data['published_at'] ?? now()->format('d.m.Y'), 'inline' => true],
            ],
            'footer' => ['text' => '🐺 Wolffiles.eu — News'],
            'timestamp' => now()->toIso8601String(),
        ];

        if (!empty($data['image_url'])) {
            $embed['image'] = ['url' => $data['image_url']];
        }

        return $this->sendWebhook($channel, [
            'content' => '📰 **New Post on Wolffiles.eu!**',
            'embeds' => [$embed],
        ]);
    }

    protected function sendWebhook(\App\Models\SocialMediaChannel $channel, array $payload): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post($channel->webhook_url, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Discord webhook failed: {$e->getMessage()}");
            return false;
        }
    }
}
