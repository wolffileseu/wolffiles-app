<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

/**
 * @property int $id
 * @property string $name
 * @property string $provider
 * @property bool $is_active
 * @property string|null $message_template_file
 * @property string|null $message_template_donation
 * @property string|null $message_template_motw
 * @property string|null $message_template_news
 * @property string|null $webhook_url
 * @property \Carbon\Carbon|null $last_posted_at
 */
class SocialMediaChannel extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'config',
        'enabled_events',
        'is_active',
        'message_template_file',
        'message_template_donation',
        'message_template_motw',
        'message_template_news',
        'last_posted_at',
        'last_error',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'enabled_events' => 'array',
        'is_active' => 'boolean',
        'last_posted_at' => 'datetime',
    ];

    // Available event types
    public const EVENT_FILE_APPROVED = 'file_approved';
    public const EVENT_DONATION = 'donation';
    public const EVENT_MAP_OF_WEEK = 'map_of_week';
    public const EVENT_NEWS_POSTED = 'news_posted';

    public const EVENTS = [
        self::EVENT_FILE_APPROVED => 'New File Approved',
        self::EVENT_DONATION => 'New Donation',
        self::EVENT_MAP_OF_WEEK => 'Map of the Week',
        self::EVENT_NEWS_POSTED => 'News Posted',
    ];

    // Available providers
    public const PROVIDER_DISCORD = 'discord';
    public const PROVIDER_REDDIT = 'reddit';
    public const PROVIDER_TWITTER = 'twitter';
    public const PROVIDER_FACEBOOK = 'facebook';

    public const PROVIDERS = [
        self::PROVIDER_DISCORD => 'Discord',
        self::PROVIDER_REDDIT => 'Reddit',
        self::PROVIDER_TWITTER => 'Twitter / X',
        self::PROVIDER_FACEBOOK => 'Facebook',
    ];

    /**
     * Check if this channel should handle a given event.
     */
    public function handlesEvent(string $event): bool
    {
        return $this->is_active && in_array($event, $this->enabled_events ?? []);
    }

    /**
     * Get a specific config value.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Scope: only active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: channels that handle a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->active()->whereJsonContains('enabled_events', $event);
    }

    /**
     * Mark as successfully posted.
     */
    public function markPosted(): void
    {
        $this->update([
            'last_posted_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'last_error' => $error,
        ]);
    }
}
