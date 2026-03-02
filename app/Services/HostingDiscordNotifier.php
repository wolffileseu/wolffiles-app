<?php

namespace App\Services;

use App\Models\ServerOrder;
use App\Models\ServerInvoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HostingDiscordNotifier
{
    protected static function getWebhookUrl(): ?string
    {
        return config('services.discord.hosting_webhook_url') ?: config('services.discord.webhook_url');
    }

    public static function serverOrdered(ServerOrder $order, ServerInvoice $invoice): void
    {
        $url = static::getWebhookUrl();
        if (!$url) return;

        try {
            Http::post($url, [
                'embeds' => [[
                    'color' => 0x22C55E,
                    'title' => '🖥️ Neuer Server bestellt!',
                    'fields' => [
                        ['name' => '👤 Kunde', 'value' => $order->user->name, 'inline' => true],
                        ['name' => '🎮 Game', 'value' => $order->getGameDisplayName(), 'inline' => true],
                        ['name' => '🔧 Mod', 'value' => $order->getModDisplayName(), 'inline' => true],
                        ['name' => '🏷️ Server', 'value' => $order->server_name, 'inline' => true],
                        ['name' => '👥 Slots', 'value' => (string) $order->slots, 'inline' => true],
                        ['name' => '💰 Preis', 'value' => number_format((float) $invoice->amount, 2) . '€ (' . $order->billing_period . ')', 'inline' => true],
                    ],
                    'footer' => ['text' => '🐺 Wolffiles.eu Server Hosting'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord hosting webhook failed: ' . $e->getMessage());
        }
    }

    public static function serverProvisioned(ServerOrder $order): void
    {
        $url = static::getWebhookUrl();
        if (!$url) return;

        try {
            Http::post($url, [
                'embeds' => [[
                    'color' => 0x3B82F6,
                    'title' => '✅ Server provisioniert!',
                    'fields' => [
                        ['name' => '🏷️ Server', 'value' => $order->server_name, 'inline' => true],
                        ['name' => '🌐 IP', 'value' => $order->ip_address . ':' . $order->port, 'inline' => true],
                        ['name' => '👤 Kunde', 'value' => $order->user->name, 'inline' => true],
                    ],
                    'footer' => ['text' => '🐺 Wolffiles.eu Server Hosting'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord hosting webhook failed: ' . $e->getMessage());
        }
    }

    public static function paymentReceived(ServerOrder $order, float $amount, string $txnId): void
    {
        $url = static::getWebhookUrl();
        if (!$url) return;

        try {
            Http::post($url, [
                'embeds' => [[
                    'color' => 0xF59E0B,
                    'title' => '💰 Hosting-Zahlung erhalten!',
                    'fields' => [
                        ['name' => '👤 Kunde', 'value' => $order->user->name, 'inline' => true],
                        ['name' => '🏷️ Server', 'value' => $order->server_name, 'inline' => true],
                        ['name' => '💶 Betrag', 'value' => number_format($amount, 2) . '€', 'inline' => true],
                        ['name' => '📅 Bezahlt bis', 'value' => $order->paid_until?->format('d.m.Y'), 'inline' => true],
                    ],
                    'footer' => ['text' => '🐺 Wolffiles.eu Server Hosting'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord hosting webhook failed: ' . $e->getMessage());
        }
    }

    public static function serverSuspended(ServerOrder $order): void
    {
        $url = static::getWebhookUrl();
        if (!$url) return;

        try {
            Http::post($url, [
                'embeds' => [[
                    'color' => 0xEF4444,
                    'title' => '⏸️ Server suspendiert (abgelaufen)',
                    'fields' => [
                        ['name' => '🏷️ Server', 'value' => $order->server_name, 'inline' => true],
                        ['name' => '👤 Kunde', 'value' => $order->user->name, 'inline' => true],
                        ['name' => '📅 Abgelaufen', 'value' => $order->paid_until?->format('d.m.Y'), 'inline' => true],
                    ],
                    'footer' => ['text' => '🐺 Wolffiles.eu Server Hosting'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord hosting webhook failed: ' . $e->getMessage());
        }
    }

    public static function serverTerminated(ServerOrder $order): void
    {
        $url = static::getWebhookUrl();
        if (!$url) return;

        try {
            Http::post($url, [
                'embeds' => [[
                    'color' => 0x6B7280,
                    'title' => '❌ Server terminiert',
                    'fields' => [
                        ['name' => '🏷️ Server', 'value' => $order->server_name, 'inline' => true],
                        ['name' => '👤 Kunde', 'value' => $order->user->name, 'inline' => true],
                    ],
                    'footer' => ['text' => '🐺 Wolffiles.eu Server Hosting'],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
        } catch (\Exception $e) {
            Log::warning('Discord hosting webhook failed: ' . $e->getMessage());
        }
    }
}
