<?php

namespace App\Services\SocialMedia;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\Providers\DiscordProvider;
use App\Services\SocialMedia\Providers\FacebookProvider;
use App\Services\SocialMedia\Providers\RedditProvider;
use App\Services\SocialMedia\Providers\TwitterProvider;
use Illuminate\Support\Facades\Log;

class SocialMediaService
{
    /**
     * Registered providers.
     * Add new providers here to make them available system-wide.
     */
    protected array $providers = [
        'discord' => DiscordProvider::class,
        'reddit' => RedditProvider::class,
        'twitter' => TwitterProvider::class,
        'facebook' => FacebookProvider::class,
    ];

    /**
     * Broadcast an event to all active channels that handle it.
     */
    public function broadcast(string $event, array $data): array
    {
        $results = [];

        $channels = SocialMediaChannel::forEvent($event)
            ->orderBy('sort_order')
            ->get();

        foreach ($channels as $channel) {
            $provider = $this->resolveProvider($channel->provider);
            if (!$provider) {
                Log::warning("No provider found for: {$channel->provider}");
                $results[$channel->name] = ['success' => false, 'error' => 'Unknown provider'];
                continue;
            }

            try {
                $success = match ($event) {
                    SocialMediaChannel::EVENT_FILE_APPROVED => $provider->sendFileApproved($channel, $data),
                    SocialMediaChannel::EVENT_DONATION => $provider->sendDonation($channel, $data),
                    SocialMediaChannel::EVENT_MAP_OF_WEEK => $provider->sendMapOfTheWeek($channel, $data),
                    SocialMediaChannel::EVENT_NEWS_POSTED => $provider->sendNewsPosted($channel, $data),
                    default => false,
                };

                $results[$channel->name] = [
                    'success' => $success,
                    'provider' => $channel->provider,
                ];
            } catch (\Exception $e) {
                Log::error("Social media broadcast failed for {$channel->name}", [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);

                $results[$channel->name] = [
                    'success' => false,
                    'provider' => $channel->provider,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Broadcast a new file approval.
     */
    public function broadcastFileApproved(mixed $file): array
    {
        $data = [
            'title' => $file->title,
            'description' => $file->description ?? '',
            'url' => route('files.show', $file),
            'category' => $file->category->name ?? 'Unknown',
            'file_size' => $file->file_size_formatted ?? '-',
            'uploader' => $file->user->name ?? 'Unknown',
            'thumbnail_url' => $file->screenshots?->first()?->url,
            'image_url' => $file->screenshots?->first()?->url,
        ];

        return $this->broadcast(SocialMediaChannel::EVENT_FILE_APPROVED, $data);
    }

    /**
     * Broadcast a new donation.
     */
    public function broadcastDonation(mixed $donation): array
    {
        // Calculate yearly progress
        $yearlyTotal = \App\Models\Donation::whereYear('created_at', now()->year)->sum('amount');
        $yearlyGoal = config('wolffiles.yearly_donation_goal', 500);

        $data = [
            'amount' => number_format($donation->amount, 2),
            'donor' => $donation->donor_name ?? 'Anonymous',
            'message' => $donation->message ?? '',
            'yearly_total' => $yearlyTotal,
            'yearly_goal' => $yearlyGoal,
        ];

        return $this->broadcast(SocialMediaChannel::EVENT_DONATION, $data);
    }

    /**
     * Broadcast Map of the Week.
     */
    public function broadcastMapOfTheWeek(mixed $file): array
    {
        $data = [
            'title' => $file->title,
            'description' => $file->description ?? '',
            'url' => route('files.show', $file),
            'author' => $file->author ?? $file->user->name ?? 'Unknown',
            'download_count' => $file->download_count ?? 0,
            'rating' => $file->average_rating ?? null,
            'image_url' => $file->screenshots?->first()?->url,
            'thumbnail_url' => $file->screenshots?->first()?->url,
        ];

        return $this->broadcast(SocialMediaChannel::EVENT_MAP_OF_WEEK, $data);
    }

    /**
     * Test a specific channel's connection.
     */
    public function testChannel(SocialMediaChannel $channel): array
    {
        $provider = $this->resolveProvider($channel->provider);
        if (!$provider) {
            return ['success' => false, 'message' => 'Unknown provider: ' . $channel->provider];
        }

        return $provider->testConnection($channel);
    }

    /**
     * Get all registered providers.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get provider config fields for a given provider type.
     */
    public function getProviderConfigFields(string $providerType): array
    {
        $class = $this->providers[$providerType] ?? null;
        if (!$class) return [];

        return $class::getConfigFields();
    }

    /**
     * Resolve a provider instance by its identifier.
     */
    protected function resolveProvider(string $identifier): ?SocialMediaProvider
    {
        $class = $this->providers[$identifier] ?? null;
        if (!$class) return null;

        return app($class);
    }

    /**
     * Register a custom provider.
     */
    public function registerProvider(string $identifier, string $class): void
    {
        $this->providers[$identifier] = $class;
    }

    public function broadcastNewsPosted(mixed $post): array
    {
        $data = [
            'title' => $post->title ?? 'News',
            'description' => \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 200),
            'url' => route('posts.show', $post),
            'author' => $post->user->name ?? 'Wolffiles.eu',
            'image_url' => $post->featured_image ? \Illuminate\Support\Facades\Storage::disk('s3')->url($post->featured_image) : null,
            'published_at' => $post->published_at?->format('d.m.Y H:i') ?? now()->format('d.m.Y H:i'),
        ];

        return $this->broadcast(SocialMediaChannel::EVENT_NEWS_POSTED, $data);
    }
}
