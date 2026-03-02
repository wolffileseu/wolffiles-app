<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    protected string $apiUrl;
    protected array $chatIds;
    protected bool $enabled;

    public function __construct()
    {
        $token = config('services.telegram.bot_token', '');
        $this->chatIds = array_filter(array_map('trim', explode(',', config('services.telegram.chat_id', ''))));
        $this->apiUrl = "https://api.telegram.org/bot{$token}";
        $this->enabled = config('services.telegram.enabled', false) && !empty($token) && !empty($this->chatIds);
    }

    /**
     * Send a text message via Telegram to all configured chat IDs
     */
    public function send(string $message, ?string $parseMode = 'HTML'): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $success = false;

        foreach ($this->chatIds as $chatId) {
            try {
                $response = Http::post("{$this->apiUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                    'disable_web_page_preview' => false,
                ]);

                if ($response->successful()) {
                    $success = true;
                } else {
                    Log::error("Telegram notification failed for chat {$chatId}", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Telegram notification error for chat {$chatId}: " . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Send a photo with caption to all configured chat IDs
     */
    public function sendPhoto(string $photoUrl, string $caption = ''): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $success = false;

        foreach ($this->chatIds as $chatId) {
            try {
                $response = Http::post("{$this->apiUrl}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $photoUrl,
                    'caption' => mb_substr($caption, 0, 1024),
                    'parse_mode' => 'HTML',
                ]);

                if ($response->successful()) {
                    $success = true;
                }
            } catch (\Exception $e) {
                Log::error("Telegram photo error for chat {$chatId}: " . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Check if a specific event type should be sent via Telegram
     */
    public function shouldNotify(string $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $enabledEvents = config('services.telegram.events', []);
        return in_array($event, $enabledEvents) || in_array('all', $enabledEvents);
    }

    // ─── Event Methods ───────────────────────────────────────

    public function notifyFileUploaded($file): void
    {
        if (!$this->shouldNotify('file_uploaded')) return;

        $this->send(
            "📦 <b>New File Uploaded</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "👤 By: " . ($file->user->name ?? 'Unknown') . "\n"
            . "📁 Category: " . ($file->category->name ?? 'N/A') . "\n"
            . "💾 Size: " . ($file->file_size_formatted ?? 'N/A') . "\n\n"
            . "🔗 <a href=\"" . url("/admin/files/{$file->id}/edit") . "\">Review in Admin</a>"
        );
    }

    public function notifyFileApproved($file): void
    {
        if (!$this->shouldNotify('file_approved')) return;

        $this->send(
            "✅ <b>File Approved</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "📁 " . ($file->category->name ?? 'N/A') . "\n"
            . "👤 By: " . ($file->user->name ?? 'Unknown') . "\n\n"
            . "🔗 <a href=\"" . route('files.show', $file) . "\">View on Site</a>"
        );
    }

    public function notifyCommentPosted($comment): void
    {
        if (!$this->shouldNotify('comment_posted')) return;

        $commentable = $comment->commentable;
        $title = $commentable->title ?? $commentable->name ?? 'Unknown';
        $type = class_basename($commentable);

        $this->send(
            "💬 <b>New Comment</b>\n\n"
            . "👤 " . ($comment->user->name ?? $comment->guest_name ?? 'Guest') . "\n"
            . "📄 On: {$title} ({$type})\n"
            . "💭 " . mb_substr(strip_tags($comment->content), 0, 200) . "\n\n"
            . "🔗 <a href=\"" . url("/admin/comments") . "\">View in Admin</a>"
        );
    }

    public function notifyDonation($donation): void
    {
        if (!$this->shouldNotify('donation')) return;

        $this->send(
            "💰 <b>Donation Received!</b>\n\n"
            . "💶 Amount: €" . number_format($donation->amount, 2) . "\n"
            . "👤 From: " . ($donation->donor_name ?? 'Anonymous') . "\n"
            . (!empty($donation->message) ? "💬 \"{$donation->message}\"\n" : "")
            . "\n🐺 Thank you for supporting Wolffiles.eu!"
        );
    }

    public function notifyUserRegistered($user): void
    {
        if (!$this->shouldNotify('user_registered')) return;

        $this->send(
            "👋 <b>New User Registered</b>\n\n"
            . "👤 {$user->name}\n"
            . "📧 {$user->email}\n\n"
            . "🔗 <a href=\"" . url("/admin/users/{$user->id}/edit") . "\">View in Admin</a>"
        );
    }

    public function notifyContactForm($data): void
    {
        if (!$this->shouldNotify('contact_form')) return;

        $this->send(
            "📩 <b>Contact Form Message</b>\n\n"
            . "👤 " . ($data['name'] ?? 'Unknown') . "\n"
            . "📧 " . ($data['email'] ?? 'N/A') . "\n"
            . "📋 Subject: " . ($data['subject'] ?? 'N/A') . "\n\n"
            . "💬 " . mb_substr($data['message'] ?? '', 0, 500)
        );
    }

    public function notifyServerOrder($order): void
    {
        if (!$this->shouldNotify('server_order')) return;

        $this->send(
            "🖥️ <b>New Server Order!</b>\n\n"
            . "👤 " . ($order->user->name ?? 'Unknown') . "\n"
            . "🎮 " . ($order->server_type ?? 'ET Server') . "\n"
            . "🔢 Slots: " . ($order->slots ?? 'N/A') . "\n"
            . "💶 " . ($order->price ?? 'N/A') . "€/month\n\n"
            . "🔗 <a href=\"" . url("/admin/server-orders") . "\">View in Admin</a>"
        );
    }

    public function notifyNewsPosted($post): void
    {
        if (!$this->shouldNotify('news_posted')) return;

        $this->send(
            "📰 <b>News Published</b>\n\n"
            . "📄 <b>{$post->title}</b>\n"
            . "✍️ By: " . ($post->user->name ?? 'Admin') . "\n\n"
            . "🔗 <a href=\"" . route('posts.show', $post) . "\">Read on Site</a>"
        );
    }

    public function notifyMapOfTheWeek($file): void
    {
        if (!$this->shouldNotify('map_of_week')) return;

        $this->send(
            "🗺️ <b>New Map of the Week!</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "📥 Downloads: " . number_format($file->download_count ?? 0) . "\n\n"
            . "🔗 <a href=\"" . route('files.show', $file) . "\">View on Site</a>"
        );
    }

    public function notifyReport($report): void
    {
        if (!$this->shouldNotify('report')) return;

        $this->send(
            "🚩 <b>New Report</b>\n\n"
            . "👤 By: " . ($report->user->name ?? 'Guest') . "\n"
            . "📄 " . ($report->reason ?? 'No reason given') . "\n\n"
            . "🔗 <a href=\"" . url("/admin/reports") . "\">View in Admin</a>"
        );
    }

    /**
     * Test the connection - sends to all configured chat IDs
     */
    public function test(): array
    {
        $allSuccess = true;
        $errors = [];

        foreach ($this->chatIds as $chatId) {
            try {
                $response = Http::post("{$this->apiUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "🐺 <b>Wolffiles.eu Telegram Notifications</b>\n\nConnection test successful! ✅\nChat ID: {$chatId}\n\n" . now()->format('d.m.Y H:i:s'),
                    'parse_mode' => 'HTML',
                ]);

                if (!$response->successful()) {
                    $allSuccess = false;
                    $errors[] = "Chat {$chatId}: " . $response->body();
                }
            } catch (\Exception $e) {
                $allSuccess = false;
                $errors[] = "Chat {$chatId}: " . $e->getMessage();
            }
        }

        if ($allSuccess) {
            return ['success' => true, 'message' => 'Test sent to ' . count($this->chatIds) . ' chat(s)!'];
        }

        return ['success' => false, 'message' => implode('; ', $errors)];
    }
}