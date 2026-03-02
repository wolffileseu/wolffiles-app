<?php

namespace App\Services\SocialMedia\Providers;

use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookProvider implements SocialMediaProvider
{
    protected const GRAPH_API = 'https://graph.facebook.com/v19.0';

    public function getIdentifier(): string
    {
        return 'facebook';
    }

    public function getName(): string
    {
        return 'Facebook';
    }

    public static function getConfigFields(): array
    {
        return [
            'page_id' => [
                'type' => 'text',
                'label' => 'Page ID',
                'required' => true,
                'help' => 'Facebook Page → About → Page ID (or from URL)',
            ],
            'page_access_token' => [
                'type' => 'password',
                'label' => 'Page Access Token (Long-lived)',
                'required' => true,
                'help' => 'Generate via Graph API Explorer with pages_manage_posts permission. Use a long-lived token!',
            ],
        ];
    }

    public function sendFileApproved(SocialMediaChannel $channel, array $data): bool
    {
        $message = $channel->message_template_file
            ? $this->parseTemplate($channel->message_template_file, $data)
            : "📁 New File on Wolffiles.eu!\n\n"
              . "🎮 {$data['title']}\n"
              . "📂 Category: {$data['category']}\n"
              . "📦 Size: {$data['file_size']}\n"
              . "👤 Uploaded by: {$data['uploader']}\n\n"
              . "Download now 👇\n"
              . $data['url'] . "\n\n"
              . "#EnemyTerritory #Wolfenstein #ET #RtCW #Gaming #Wolffiles";

        return $this->postToPage($channel, $message, $data['url']);
    }

    public function sendDonation(SocialMediaChannel $channel, array $data): bool
    {
        $message = $channel->message_template_donation
            ? $this->parseTemplate($channel->message_template_donation, $data)
            : "💝 Thank you {$data['donor']} for your generous donation!\n\n"
              . "Every contribution helps us keep Wolffiles.eu running and the ET/RtCW community alive! 🐺\n\n"
              . "Support us: https://wolffiles.eu/donate\n\n"
              . "#EnemyTerritory #Wolfenstein #Community #Gaming";

        return $this->postToPage($channel, $message, 'https://wolffiles.eu/donate');
    }

    public function sendMapOfTheWeek(SocialMediaChannel $channel, array $data): bool
    {
        $message = $channel->message_template_motw
            ? $this->parseTemplate($channel->message_template_motw, $data)
            : "🏆 Map of the Week!\n\n"
              . "🗺️ {$data['title']}\n"
              . (!empty($data['author']) ? "🎨 Author: {$data['author']}\n" : '')
              . (!empty($data['download_count']) ? "📥 Downloads: " . number_format($data['download_count']) . "\n" : '')
              . "\n" . Str::limit(strip_tags($data['description'] ?? ''), 300) . "\n\n"
              . "Check it out 👇\n"
              . $data['url'] . "\n\n"
              . "#EnemyTerritory #Wolfenstein #ET #MapOfTheWeek #Gaming";

        return $this->postToPage($channel, $message, $data['url']);
    }

    public function testConnection(SocialMediaChannel $channel): array
    {
        $pageId = $channel->getConfigValue('page_id');
        $token = $channel->getConfigValue('page_access_token');

        if (!$pageId || !$token) {
            return ['success' => false, 'message' => 'Page ID and Access Token are required.'];
        }

        try {
            $response = Http::get(self::GRAPH_API . '/' . $pageId, [
                'access_token' => $token,
                'fields' => 'name,id',
            ]);

            if ($response->successful()) {
                $pageName = $response->json('name');
                return ['success' => true, 'message' => "Connected to Facebook Page: {$pageName}!"];
            }

            $error = $response->json('error.message', 'Unknown error');
            return ['success' => false, 'message' => 'Facebook API error: ' . $error];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Post to Facebook Page feed.
     */
    protected function postToPage(SocialMediaChannel $channel, string $message, ?string $link = null): bool
    {
        try {
            $pageId = $channel->getConfigValue('page_id');
            $token = $channel->getConfigValue('page_access_token');

            $payload = [
                'message' => $message,
                'access_token' => $token,
            ];

            if ($link) {
                $payload['link'] = $link;
            }

            $response = Http::post(self::GRAPH_API . '/' . $pageId . '/feed', $payload);

            if ($response->successful()) {
                $channel->markPosted();
                return true;
            }

            $error = $response->json('error.message', $response->body());
            $channel->markFailed('Facebook API: ' . $error);
            return false;
        } catch (\Exception $e) {
            $channel->markFailed($e->getMessage());
            Log::error('Facebook broadcast failed', [
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

    public function sendNewsPosted(SocialMediaChannel $channel, array $data): bool
    {
        $template = $channel->message_template_news;
        if ($template) {
            $message = $this->parseTemplate($template, $data);
        } else {
            $message = "📰 " . ($data['title'] ?? 'News') . "\n\n" . ($data['description'] ?? '') . "\n\nRead more: " . ($data['url'] ?? '');
        }

        return $this->postToPage($channel, $message, $data['url'] ?? null);
    }
}
