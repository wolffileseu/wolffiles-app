<?php

namespace App\Services;

use Throwable;
use App\Models\Tracker\TrackerBan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FlagDiscordNotifier
{
    protected static function webhookUrl(): ?string
    {
        return config('services.discord.flag_webhook_url') ?: config('services.discord.webhook_url');
    }

    /**
     * Post a public cheat flag to Discord. Uploads the first public screenshot
     * as a real attachment (multipart) so it stays in the post permanently.
     */
    public static function flagPublished(TrackerBan $ban): void
    {
        $url = static::webhookUrl();
        if (!$url) return;

        $ban->loadMissing(['player', 'publicEvidence', 'servers']);
        $player = $ban->player;
        $name   = $player?->name_clean ?: 'Unknown player';

        $servers = $ban->servers->map(fn($s) => $s->hostname_clean ?: $s->hostname ?: ('Server #'.$s->id))->implode(', ');
        $profileUrl = $player ? route('tracker.player.show', $player) : config('app.url');

        $embed = [
            'color' => 0xEF4444, // red
            'title' => '⚠ Player Flagged',
            'description' => '**' . $name . '**',
            'url' => $profileUrl,
            'fields' => array_values(array_filter([
                $ban->public_reason ? ['name' => 'Reason', 'value' => $ban->public_reason, 'inline' => false] : null,
                $servers ? ['name' => 'Server(s)', 'value' => $servers, 'inline' => false] : null,
                ['name' => 'Profile', 'value' => $profileUrl, 'inline' => false],
            ])),
            'footer' => ['text' => '🐺 Wolffiles.eu — Anti-Cheat'],
            'timestamp' => now()->toIso8601String(),
        ];

        // First public screenshot -> upload as attachment
        $shot = $ban->publicEvidence->firstWhere('type', 'screenshot');

        try {
            if ($shot && $shot->file_path && Storage::disk('s3')->exists($shot->file_path)) {
                $bytes = Storage::disk('s3')->get($shot->file_path);
                $filename = 'evidence.' . pathinfo($shot->file_path, PATHINFO_EXTENSION ?: 'png');
                $embed['image'] = ['url' => 'attachment://' . $filename];

                Http::attach('files[0]', $bytes, $filename)->post($url, [
                    'payload_json' => json_encode([
                        'username' => 'Wolffiles.eu',
                        'embeds' => [$embed],
                    ]),
                ]);
            } else {
                Http::post($url, [
                    'username' => 'Wolffiles.eu',
                    'embeds' => [$embed],
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Discord flag webhook failed: ' . $e->getMessage());
        }
    }
}
