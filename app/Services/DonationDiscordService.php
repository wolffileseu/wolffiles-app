<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\DonationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DonationDiscordService
{
    /**
     * Sendet (oder re-sendet) einen Donation-Post in den Discord Webhook.
     *
     * @throws \RuntimeException wenn kein Webhook konfiguriert ist
     */
    public function notify(Donation $donation): bool
    {
        $webhookUrl = DonationSetting::get('discord_webhook_url');
        if (!$webhookUrl) {
            throw new \RuntimeException('Discord Webhook URL ist nicht in den Donation Settings hinterlegt.');
        }

        try {
            $name = $donation->is_anonymous ? 'Anonymous' : ($donation->display_name ?: 'Someone');

            $monthlyGoal = (float) DonationSetting::get('monthly_goal', 50);
            $yearlyGoal = $monthlyGoal * 12;
            $yearlyTotal = Donation::completed()->whereYear('created_at', now()->year)->sum('amount');
            $pct = $yearlyGoal > 0 ? min(100, round(($yearlyTotal / $yearlyGoal) * 100)) : 0;
            $barFull = (int) round($pct / 10);
            $barEmpty = 10 - $barFull;
            $progressBar = str_repeat('🟧', $barFull) . str_repeat('⬛', $barEmpty);

            $fields = [
                ['name' => '💰 Amount', 'value' => '**€' . number_format((float) $donation->amount, 2) . '**', 'inline' => true],
                ['name' => '🧑 Donor', 'value' => $name, 'inline' => true],
                ['name' => '💳 Via', 'value' => ucfirst($donation->source), 'inline' => true],
            ];

            if ($donation->message) {
                $fields[] = ['name' => '💬 Message', 'value' => '*"' . $donation->message . '"*', 'inline' => false];
            }

            $fields[] = ['name' => '📊 Yearly Goal', 'value' => $progressBar . "\n€" . number_format($yearlyTotal, 2) . ' / €' . number_format($yearlyGoal, 2) . " ({$pct}%)", 'inline' => false];

            $response = Http::post($webhookUrl, [
                'content' => '🎉 **Neue Spende erhalten! | New Donation Received!**',
                'embeds' => [[
                    'color' => 0xF59E0B,
                    'title' => '💝 Thank you for supporting Wolffiles.eu!',
                    'url' => 'https://wolffiles.eu/donate',
                    'fields' => $fields,
                    'thumbnail' => ['url' => 'https://wolffiles.eu/images/logo.png'],
                    'footer' => [
                        'text' => '🐺 Wolffiles.eu — Support us at wolffiles.eu/donate',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Discord donation webhook failed: ' . $e->getMessage(), ['donation_id' => $donation->id]);
            throw $e;
        }
    }
}
