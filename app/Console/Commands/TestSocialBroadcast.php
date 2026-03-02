<?php

namespace App\Console\Commands;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaService;
use Illuminate\Console\Command;

class TestSocialBroadcast extends Command
{
    protected $signature = 'wolffiles:test-broadcast
        {--event=file_approved : Event type: file_approved, donation, map_of_week}
        {--dry-run : Show what would be sent without actually posting}';

    protected $description = 'Test the Social Media Broadcast System';

    public function handle(): int
    {
        $event = $this->option('event');
        $dryRun = $this->option('dry-run');

        $this->newLine();
        $this->line('  ╔══════════════════════════════════════════════╗');
        $this->line('  ║  🐺 Wolffiles.eu — Social Media Broadcast   ║');
        $this->line('  ║           System Test                        ║');
        $this->line('  ╚══════════════════════════════════════════════╝');
        $this->newLine();

        $channels = SocialMediaChannel::active()->get();

        if ($channels->isEmpty()) {
            $this->error('  No active social media channels found!');
            return self::FAILURE;
        }

        $this->info("  📡 Active Channels:");
        $this->newLine();

        foreach ($channels as $ch) {
            $events = is_array($ch->enabled_events) ? implode(', ', $ch->enabled_events) : $ch->enabled_events;
            $icon = match ($ch->provider) {
                'discord' => '💬',
                'twitter' => '🐦',
                'facebook' => '📘',
                'reddit' => '🤖',
                default => '📌',
            };
            $this->line("    {$icon} {$ch->name} ({$ch->provider}) — Events: {$events}");
        }

        $this->newLine();
        $this->line("  🎯 Testing event: {$event}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('  🏃 DRY RUN — no messages will be sent');
            return self::SUCCESS;
        }

        if (!$this->confirm('  Send test broadcast to all active channels?', true)) {
            $this->line('  Cancelled.');
            return self::SUCCESS;
        }

        $service = app(SocialMediaService::class);

        $results = match ($event) {
            'donation' => $this->testDonation($service),
            'map_of_week' => $this->testMapOfTheWeek($service),
            default => $this->testFileApproved($service),
        };

        $this->newLine();
        $this->info("  📊 Results:");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($results as $channelName => $result) {
            $provider = $result['provider'] ?? 'unknown';
            $ok = $result['success'] ?? false;
            $error = $result['error'] ?? 'unknown error';

            if ($ok) {
                $this->line("    ✅ {$channelName} ({$provider}) — Posted successfully!");
                $success++;
            } else {
                $this->line("    ❌ {$channelName} ({$provider}) — Failed: {$error}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line("  ═══════════════════════════════════════");
        $this->line("    ✅ Success: {$success}  |  ❌ Failed: {$failed}");
        $this->line("  ═══════════════════════════════════════");
        $this->newLine();

        if ($failed === 0 && $success > 0) {
            $this->info('  🎉 All broadcasts sent successfully!');
            $this->line('  🐺 Wolffiles.eu Social Media Broadcast System is LIVE!');
        } elseif ($success > 0) {
            $this->warn("  ⚠️  {$failed} channel(s) failed. Check Admin for details.");
        } else {
            $this->error("  ❌ All channels failed. Check Admin → Social Media Broadcast.");
        }

        $errorChannels = SocialMediaChannel::active()->whereNotNull('last_error')->get();
        if ($errorChannels->isNotEmpty()) {
            $this->newLine();
            $this->line("  📋 Channel Errors:");
            foreach ($errorChannels as $ch) {
                $this->line("    • {$ch->name}: {$ch->last_error}");
            }
        }

        $this->newLine();
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function testFileApproved(SocialMediaService $service): array
    {
        $this->line('  📦 Sending test: New File Approved');

        $file = \App\Models\File::where('status', 'approved')
            ->whereHas('category')
            ->latest()
            ->first();

        if ($file) {
            $this->line("    Using real file: {$file->title}");
            return $service->broadcastFileApproved($file);
        }

        $this->line('    Using sample data');
        return $service->broadcast(SocialMediaChannel::EVENT_FILE_APPROVED, [
            'title' => '🐺 Wolffiles Broadcast Test',
            'description' => 'Social Media Broadcast System is working!',
            'url' => 'https://wolffiles.eu',
            'category' => 'Maps',
            'file_size' => '15.2 MB',
            'uploader' => 'Wolffiles Bot',
        ]);
    }

    private function testDonation(SocialMediaService $service): array
    {
        $this->line('  💰 Sending test: Donation Received');

        return $service->broadcast(SocialMediaChannel::EVENT_DONATION, [
            'amount' => '5.00',
            'donor' => 'Wolffiles Test',
            'message' => 'Test donation — Broadcast System is working! 🐺',
            'yearly_total' => '42.00',
            'yearly_goal' => '600.00',
        ]);
    }

    private function testMapOfTheWeek(SocialMediaService $service): array
    {
        $this->line('  🗺️  Sending test: Map of the Week');

        $file = \App\Models\File::where('is_featured', true)->first()
            ?? \App\Models\File::where('status', 'approved')->whereHas('category')->latest()->first();

        if ($file) {
            $this->line("    Using real file: {$file->title}");
            return $service->broadcastMapOfTheWeek($file);
        }

        return $service->broadcast(SocialMediaChannel::EVENT_MAP_OF_WEEK, [
            'title' => '🐺 Wolffiles Map of the Week Test',
            'description' => 'Broadcast System test!',
            'url' => 'https://wolffiles.eu',
            'author' => 'Wolffiles Bot',
            'download_count' => 1337,
            'rating' => '4.8',
        ]);
    }
}