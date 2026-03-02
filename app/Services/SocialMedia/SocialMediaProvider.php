<?php

namespace App\Services\SocialMedia;

use App\Models\SocialMediaChannel;

interface SocialMediaProvider
{
    /**
     * Get the provider identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get human-readable name.
     */
    public function getName(): string;

    /**
     * Get the configuration fields this provider needs.
     * Returns array of field definitions for the Filament form.
     */
    public static function getConfigFields(): array;

    /**
     * Send a file approved notification.
     */
    public function sendFileApproved(SocialMediaChannel $channel, array $data): bool;

    /**
     * Send a donation notification.
     */
    public function sendDonation(SocialMediaChannel $channel, array $data): bool;

    /**
     * Send a Map of the Week notification.
     */
    public function sendMapOfTheWeek(SocialMediaChannel $channel, array $data): bool;
    public function sendNewsPosted(SocialMediaChannel $channel, array $data): bool;

    /**
     * Test the connection/configuration.
     */
    public function testConnection(SocialMediaChannel $channel): array;
}
