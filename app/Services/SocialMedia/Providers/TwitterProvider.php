<?php

namespace App\Services\SocialMedia\Providers;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitterProvider implements SocialMediaProvider
{
    protected const API_BASE = 'https://api.twitter.com/2';

    public function getIdentifier(): string
    {
        return 'twitter';
    }

    public function getName(): string
    {
        return 'Twitter / X';
    }

    public static function getConfigFields(): array
    {
        return [
            'api_key' => [
                'type' => 'text',
                'label' => 'API Key (Consumer Key)',
                'required' => true,
                'help' => 'X Developer Portal → App → Keys and Tokens',
            ],
            'api_secret' => [
                'type' => 'password',
                'label' => 'API Secret (Consumer Secret)',
                'required' => true,
            ],
            'access_token' => [
                'type' => 'text',
                'label' => 'Access Token',
                'required' => true,
            ],
            'access_token_secret' => [
                'type' => 'password',
                'label' => 'Access Token Secret',
                'required' => true,
            ],
            'hashtags' => [
                'type' => 'text',
                'label' => 'Hashtags (optional)',
                'required' => false,
                'default' => '#EnemyTerritory #Wolfenstein #ET #RtCW #Gaming',
                'help' => 'Added to end of each tweet',
            ],
        ];
    }

    public function sendFileApproved(SocialMediaChannel $channel, array $data): bool
    {
        $hashtags = $channel->getConfigValue('hashtags', '#EnemyTerritory #Wolfenstein');

        $text = $channel->message_template_file
            ? $this->parseTemplate($channel->message_template_file, $data)
            : "📁 New file on Wolffiles.eu!\n\n"
              . "🎮 {$data['title']}\n"
              . "📂 {$data['category']}\n"
              . "👤 by {$data['uploader']}\n\n"
              . "🔗 {$data['url']}\n\n"
              . $hashtags;

        return $this->tweet($channel, $text);
    }

    public function sendDonation(SocialMediaChannel $channel, array $data): bool
    {
        $hashtags = $channel->getConfigValue('hashtags', '#EnemyTerritory #Wolfenstein');

        $text = $channel->message_template_donation
            ? $this->parseTemplate($channel->message_template_donation, $data)
            : "💝 Thank you {$data['donor']} for supporting Wolffiles.eu!\n\n"
              . "Every donation helps keep the ET/RtCW community alive! 🐺\n\n"
              . "🔗 https://wolffiles.eu/donate\n\n"
              . $hashtags;

        return $this->tweet($channel, $text);
    }

    public function sendMapOfTheWeek(SocialMediaChannel $channel, array $data): bool
    {
        $hashtags = $channel->getConfigValue('hashtags', '#EnemyTerritory #Wolfenstein');

        $text = $channel->message_template_motw
            ? $this->parseTemplate($channel->message_template_motw, $data)
            : "🏆 Map of the Week!\n\n"
              . "🗺️ {$data['title']}\n"
              . (!empty($data['author']) ? "🎨 by {$data['author']}\n" : '')
              . "\n🔗 {$data['url']}\n\n"
              . $hashtags;

        return $this->tweet($channel, Str::limit($text, 280));
    }

    public function testConnection(SocialMediaChannel $channel): array
    {
        try {
            $response = $this->makeRequest($channel, 'GET', self::API_BASE . '/users/me');

            if ($response->successful()) {
                $username = $response->json('data.username');
                return ['success' => true, 'message' => "Connected as @{$username}!"];
            }

            return ['success' => false, 'message' => 'X API returned: ' . $response->status() . ' — ' . $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Post a tweet.
     */
    protected function tweet(SocialMediaChannel $channel, string $text): bool
    {
        try {
            // Enforce 280 char limit
            $text = Str::limit($text, 280, '...');

            $response = $this->makeRequest($channel, 'POST', self::API_BASE . '/tweets', [
                'text' => $text,
            ]);

            if ($response->successful()) {
                $channel->markPosted();
                return true;
            }

            $channel->markFailed('X API HTTP ' . $response->status() . ': ' . $response->body());
            return false;
        } catch (\Exception $e) {
            $channel->markFailed($e->getMessage());
            Log::error('Twitter broadcast failed', [
                'channel' => $channel->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Make an OAuth 1.0a signed request to the X API.
     */
    protected function makeRequest(SocialMediaChannel $channel, string $method, string $url, array $body = []): \Illuminate\Http\Client\Response
    {
        $apiKey = $channel->getConfigValue('api_key');
        $apiSecret = $channel->getConfigValue('api_secret');
        $accessToken = $channel->getConfigValue('access_token');
        $accessTokenSecret = $channel->getConfigValue('access_token_secret');

        // Build OAuth 1.0a signature
        $oauth = [
            'oauth_consumer_key' => $apiKey,
            'oauth_nonce' => Str::random(32),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $accessToken,
            'oauth_version' => '1.0',
        ];

        // For GET requests, include query params in signature base
        $signatureParams = $oauth;
        if ($method === 'GET') {
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryParams);
                $signatureParams = array_merge($signatureParams, $queryParams);
            }
        }

        ksort($signatureParams);
        $paramString = http_build_query($signatureParams, '', '&', PHP_QUERY_RFC3986);

        $baseUrl = strtok($url, '?');
        $signatureBase = strtoupper($method) . '&' . rawurlencode($baseUrl) . '&' . rawurlencode($paramString);
        $signingKey = rawurlencode($apiSecret) . '&' . rawurlencode($accessTokenSecret);

        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $signatureBase, $signingKey, true));

        // Build Authorization header
        $authHeader = 'OAuth ' . implode(', ', array_map(
            fn($k, $v) => rawurlencode($k) . '="' . rawurlencode($v) . '"',
            array_keys($oauth),
            array_values($oauth)
        ));

        $request = Http::withHeaders([
            'Authorization' => $authHeader,
        ]);

        if ($method === 'POST' && !empty($body)) {
            return $request->asJson()->post($url, $body);
        }

        return $request->get($url);
    }

    protected function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{' . $key . '}', (string) $value, $template);
            }
        }
        return $template;
    }

    public function sendNewsPosted(SocialMediaChannel $channel, array $data): bool
    {
        $template = $channel->message_template_news;
        if ($template) {
            $text = $this->parseTemplate($template, $data);
        } else {
            $text = "📰 " . ($data['title'] ?? 'News') . "\n\n" . \Illuminate\Support\Str::limit($data['description'] ?? '', 150) . "\n\n" . ($data['url'] ?? '');
        }

        return $this->tweet($channel, $text);
    }
}
