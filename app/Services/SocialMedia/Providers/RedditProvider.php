<?php

namespace App\Services\SocialMedia\Providers;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RedditProvider implements SocialMediaProvider
{
    protected const TOKEN_CACHE_PREFIX = 'reddit_token_';
    protected const API_BASE = 'https://oauth.reddit.com';

    public function getIdentifier(): string
    {
        return 'reddit';
    }

    public function getName(): string
    {
        return 'Reddit';
    }

    public static function getConfigFields(): array
    {
        return [
            'client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'required' => true,
                'help' => 'Reddit App → https://www.reddit.com/prefs/apps/ → Create "script" app',
            ],
            'client_secret' => [
                'type' => 'password',
                'label' => 'Client Secret',
                'required' => true,
            ],
            'username' => [
                'type' => 'text',
                'label' => 'Reddit Username',
                'required' => true,
            ],
            'password' => [
                'type' => 'password',
                'label' => 'Reddit Password',
                'required' => true,
            ],
            'subreddit' => [
                'type' => 'text',
                'label' => 'Subreddit (without r/)',
                'required' => true,
                'placeholder' => 'enemyterritory',
                'help' => 'The subreddit to post to (you must be a member)',
            ],
            'flair_id_file' => [
                'type' => 'text',
                'label' => 'Flair ID for Files (optional)',
                'required' => false,
            ],
            'flair_id_donation' => [
                'type' => 'text',
                'label' => 'Flair ID for Donations (optional)',
                'required' => false,
            ],
            'flair_id_motw' => [
                'type' => 'text',
                'label' => 'Flair ID for Map of the Week (optional)',
                'required' => false,
            ],
        ];
    }

    public function sendFileApproved(SocialMediaChannel $channel, array $data): bool
    {
        // Use custom template or default
        $title = $channel->message_template_file
            ? $this->parseTemplate($channel->message_template_file, $data)
            : '📁 New File: ' . ($data['title'] ?? 'Unknown') . ' — Wolffiles.eu';

        $body = "**{$data['title']}** has been added to Wolffiles.eu!\n\n";
        if (!empty($data['description'])) {
            $body .= "> " . Str::limit(strip_tags($data['description']), 300) . "\n\n";
        }
        $body .= "**Category:** {$data['category']}\n";
        $body .= "**Size:** {$data['file_size']}\n";
        $body .= "**Uploaded by:** {$data['uploader']}\n\n";
        $body .= "🔗 [Download on Wolffiles.eu]({$data['url']})\n\n";
        $body .= "---\n*🐺 Wolffiles.eu — Your ET & RtCW file repository*";

        return $this->submitPost($channel, $title, $body, $channel->getConfigValue('flair_id_file'));
    }

    public function sendDonation(SocialMediaChannel $channel, array $data): bool
    {
        $title = $channel->message_template_donation
            ? $this->parseTemplate($channel->message_template_donation, $data)
            : '💝 New Donation — Thank you ' . ($data['donor'] ?? 'Anonymous') . '!';

        $body = "**{$data['donor']}** just donated **€{$data['amount']}** to Wolffiles.eu!\n\n";
        if (!empty($data['message'])) {
            $body .= "> \"{$data['message']}\"\n\n";
        }
        if (isset($data['yearly_total'], $data['yearly_goal'])) {
            $pct = min(100, round(($data['yearly_total'] / $data['yearly_goal']) * 100));
            $body .= "**Yearly Goal Progress:** €" . number_format($data['yearly_total'], 2) . " / €" . number_format($data['yearly_goal'], 2) . " ({$pct}%)\n\n";
        }
        $body .= "🔗 [Support Wolffiles.eu](https://wolffiles.eu/donate)\n\n";
        $body .= "---\n*🐺 Wolffiles.eu — Help us keep the ET/RtCW community alive!*";

        return $this->submitPost($channel, $title, $body, $channel->getConfigValue('flair_id_donation'));
    }

    public function sendMapOfTheWeek(SocialMediaChannel $channel, array $data): bool
    {
        $title = $channel->message_template_motw
            ? $this->parseTemplate($channel->message_template_motw, $data)
            : '🏆 Map of the Week: ' . ($data['title'] ?? 'Unknown');

        $body = "# 🗺️ Map of the Week: {$data['title']}\n\n";
        if (!empty($data['description'])) {
            $body .= Str::limit(strip_tags($data['description']), 500) . "\n\n";
        }
        if (!empty($data['author'])) {
            $body .= "**Author:** {$data['author']}\n";
        }
        if (!empty($data['download_count'])) {
            $body .= "**Downloads:** " . number_format($data['download_count']) . "\n";
        }
        if (!empty($data['rating'])) {
            $body .= "**Rating:** ⭐ {$data['rating']}\n";
        }
        $body .= "\n🔗 [Download & Details]({$data['url']})\n\n";
        $body .= "---\n*🐺 Wolffiles.eu — Voted Map of the Week by the community!*";

        return $this->submitPost($channel, $title, $body, $channel->getConfigValue('flair_id_motw'));
    }

    public function testConnection(SocialMediaChannel $channel): array
    {
        try {
            $token = $this->getAccessToken($channel);
            if (!$token) {
                return ['success' => false, 'message' => 'Failed to authenticate with Reddit. Check your credentials.'];
            }

            // Test by getting account info
            $response = Http::withToken($token)
                ->withUserAgent($this->getUserAgent())
                ->get(self::API_BASE . '/api/v1/me');

            if ($response->successful()) {
                $username = $response->json('name');
                return ['success' => true, 'message' => "Connected as u/{$username}! Ready to post to r/{$channel->getConfigValue('subreddit')}."];
            }

            return ['success' => false, 'message' => 'Reddit API returned: ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get Reddit OAuth2 access token (cached).
     */
    protected function getAccessToken(SocialMediaChannel $channel): ?string
    {
        $cacheKey = self::TOKEN_CACHE_PREFIX . $channel->id;

        return Cache::remember($cacheKey, 3500, function () use ($channel) {
            $response = Http::asForm()
                ->withBasicAuth(
                    $channel->getConfigValue('client_id'),
                    $channel->getConfigValue('client_secret')
                )
                ->withUserAgent($this->getUserAgent())
                ->post('https://www.reddit.com/api/v1/access_token', [
                    'grant_type' => 'password',
                    'username' => $channel->getConfigValue('username'),
                    'password' => $channel->getConfigValue('password'),
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Reddit OAuth failed', ['response' => $response->body()]);
            return null;
        });
    }

    /**
     * Submit a self-post to the configured subreddit.
     */
    protected function submitPost(SocialMediaChannel $channel, string $title, string $body, ?string $flairId = null): bool
    {
        try {
            $token = $this->getAccessToken($channel);
            if (!$token) {
                $channel->markFailed('Failed to get Reddit access token');
                return false;
            }

            $payload = [
                'sr' => $channel->getConfigValue('subreddit'),
                'kind' => 'self',
                'title' => Str::limit($title, 300), // Reddit title limit
                'text' => $body,
                'api_type' => 'json',
            ];

            if ($flairId) {
                $payload['flair_id'] = $flairId;
            }

            $response = Http::withToken($token)
                ->withUserAgent($this->getUserAgent())
                ->asForm()
                ->post(self::API_BASE . '/api/submit', $payload);

            if ($response->successful()) {
                $json = $response->json();
                $errors = data_get($json, 'json.errors', []);

                if (empty($errors)) {
                    $channel->markPosted();
                    return true;
                }

                $errorMsg = collect($errors)->flatten()->implode(', ');
                $channel->markFailed('Reddit errors: ' . $errorMsg);
                return false;
            }

            $channel->markFailed('Reddit HTTP ' . $response->status() . ': ' . $response->body());
            return false;
        } catch (\Exception $e) {
            $channel->markFailed($e->getMessage());
            Log::error('Reddit broadcast failed', [
                'channel' => $channel->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
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

    protected function getUserAgent(): string
    {
        return 'Wolffiles.eu/1.0 (by /u/' . config('services.reddit.username', 'wolffiles') . ')';
    }

    public function sendNewsPosted(SocialMediaChannel $channel, array $data): bool
    {
        $title = $channel->message_template_news
            ? $this->parseTemplate($channel->message_template_news, $data)
            : "📰 " . ($data['title'] ?? 'News');

        $body = "## " . ($data['title'] ?? 'News') . "\n\n"
            . ($data['description'] ?? '') . "\n\n"
            . "**Read more:** " . ($data['url'] ?? '') . "\n\n"
            . "---\n*Posted by Wolffiles.eu*";

        return $this->submitPost($channel, $title, $body);
    }
}
