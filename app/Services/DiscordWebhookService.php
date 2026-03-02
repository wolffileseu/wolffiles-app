<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordWebhookService
{
    /**
     * Send a notification to Discord when a file is approved.
     */
    public static function notifyFileApproved(File $file): void
    {
        $webhookUrl = config('services.discord.webhook_url');

        if (!$webhookUrl) {
            return;
        }

        try {
            $embed = [
                'title' => '📦 ' . $file->title,
                'description' => \Illuminate\Support\Str::limit(strip_tags($file->description ?? ''), 200),
                'url' => route('files.show', $file),
                'color' => 0xF59E0B, // amber
                'fields' => [
                    [
                        'name' => 'Category',
                        'value' => $file->category->name ?? 'Unknown',
                        'inline' => true,
                    ],
                    [
                        'name' => 'Size',
                        'value' => $file->file_size_formatted ?? '-',
                        'inline' => true,
                    ],
                    [
                        'name' => 'Uploaded by',
                        'value' => $file->user->name ?? 'Unknown',
                        'inline' => true,
                    ],
                ],
                'footer' => [
                    'text' => 'Wolffiles.eu — New file available!',
                ],
                'timestamp' => now()->toIso8601String(),
            ];

            // Add thumbnail if available
            if ($file->screenshots->isNotEmpty()) {
                $embed['thumbnail'] = [
                    'url' => $file->screenshots->first()->url,
                ];
            }

            Http::post($webhookUrl, [
                'username' => 'Wolffiles.eu',
                'avatar_url' => config('app.url') . '/images/wolffiles_logo.png',
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            Log::error('Discord webhook failed: ' . $e->getMessage());
        }
    }

    /**
     * Send custom message to Discord.
     */
    public static function sendMessage(string $content): void
    {
        $webhookUrl = config('services.discord.webhook_url');

        if (!$webhookUrl) {
            return;
        }

        try {
            Http::post($webhookUrl, [
                'username' => 'Wolffiles.eu',
                'content' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error('Discord webhook failed: ' . $e->getMessage());
        }
    }
}
