<?php

namespace App\Services;

use App\Models\File;
use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerPlayerSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    protected string $apiUrl;
    protected string $adminChatId;

    public function __construct()
    {
        $token = config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$token}";
        $this->adminChatId = config('services.telegram.chat_id', '');
    }

    /**
     * Process incoming webhook update
     */
    public function handleUpdate(array $update): void
    {
        try {
            // Inline query (search from any chat)
            if (isset($update['inline_query'])) {
                $this->handleInlineQuery($update['inline_query']);
                return;
            }

            // Callback query (button clicks)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

            // Regular message
            if (isset($update['message']['text'])) {
                $this->handleMessage($update['message']);
            }
        } catch (\Exception $e) {
            Log::error('Telegram bot error: ' . $e->getMessage());
        }
    }

    /**
     * Handle text messages / commands
     */
    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text']);
        $isAdmin = (string)($message["from"]["id"] ?? 0) === (string)$this->adminChatId;

        // Parse command
        $parts = explode(' ', $text, 2);
        $command = strtolower($parts[0]);
        $args = $parts[1] ?? '';

        match ($command) {
            '/start' => $this->cmdStart($chatId),
            '/help' => $this->cmdHelp($chatId, $isAdmin),
            '/search', '/s' => $this->cmdSearch($chatId, $args),
            '/latest', '/new' => $this->cmdLatest($chatId),
            '/motw', '/mapoftheweek' => $this->cmdMotw($chatId),
            '/stats' => $this->cmdStats($chatId),
            '/servers', '/server' => $this->cmdServers($chatId, $args),
            '/player' => $this->cmdPlayer($chatId, $args),
            '/top' => $this->cmdTop($chatId),
            '/random' => $this->cmdRandom($chatId),
            '/approve' => $isAdmin ? $this->cmdApprove($chatId, $args) : $this->sendMessage($chatId, '🚫 Admin only.'),
            '/reject' => $isAdmin ? $this->cmdReject($chatId, $args) : $this->sendMessage($chatId, '🚫 Admin only.'),
            '/pending' => $isAdmin ? $this->cmdPending($chatId) : $this->sendMessage($chatId, '🚫 Admin only.'),
            '/ack' => $isAdmin ? $this->cmdAck($chatId) : $this->sendMessage($chatId, ' Admin only.'),
            default => $this->cmdUnknown($chatId, $command),
        };
    }

    // ─── Commands ────────────────────────────────────────────

    protected function cmdStart(int $chatId): void
    {
        $this->sendMessage($chatId,
            "🐺 <b>Welcome to the Wolffiles.eu Bot!</b>\n\n"
            . "I can help you search files, check server stats, and more!\n\n"
            . "Type /help to see all commands.",
            $this->mainMenuKeyboard()
        );
    }

    protected function cmdHelp(int $chatId, bool $isAdmin = false): void
    {
        $help = "🐺 <b>Wolffiles.eu Bot Commands</b>\n\n"
            . "📦 <b>Files</b>\n"
            . "/search <i>query</i> — Search files\n"
            . "/latest — Latest uploads\n"
            . "/top — Most downloaded files\n"
            . "/random — Random file\n"
            . "/motw — Map of the Week\n\n"
            . "🖥️ <b>Servers</b>\n"
            . "/servers — Online game servers\n"
            . "/player <i>name</i> — Player stats\n"
            . "/stats — Platform statistics\n\n"
            . "💡 <b>Tip:</b> Use inline search in any chat:\n"
            . "<code>@wolffileseu_bot beach</code>";

        if ($isAdmin) {
            $help .= "\n\n🔐 <b>Admin Commands</b>\n"
                . "/pending — Pending files\n"
                . "/approve <i>id</i> — Approve a file\n"
                . "/reject <i>id reason</i> — Reject a file";
        }

        $this->sendMessage($chatId, $help);
    }

    protected function cmdSearch(int $chatId, string $query): void
    {
        if (empty($query)) {
            $this->sendMessage($chatId, "🔍 Usage: <code>/search mp_beach</code>");
            return;
        }

        $files = File::where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('original_author', 'like', "%{$query}%");
            })
            ->with('category')
            ->orderByDesc('download_count')
            ->limit(10)
            ->get();

        if ($files->isEmpty()) {
            $this->sendMessage($chatId, "😕 No files found for \"<b>{$query}</b>\".\n\nTry different keywords!");
            return;
        }

        $text = "🔍 <b>Search results for \"{$query}\"</b>\n"
            . "Found {$files->count()} file(s)\n\n";

        $buttons = [];
        foreach ($files as $i => $file) {
            $num = $i + 1;
            $cat = $file->category->name ?? 'N/A';
            $downloads = number_format($file->download_count ?? 0);
            $text .= "{$num}. 📄 <b>{$file->title}</b>\n"
                . "   📁 {$cat} | 📥 {$downloads}\n\n";

            $buttons[] = [
                ['text' => "📄 {$num}. " . Str::limit($file->title, 25), 'url' => route('files.show', $file)],
            ];
        }

        $this->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    protected function cmdLatest(int $chatId): void
    {
        $files = File::where('status', 'approved')
            ->with('category', 'user')
            ->latest('published_at')
            ->limit(5)
            ->get();

        $text = "📦 <b>Latest Uploads</b>\n\n";
        $buttons = [];

        foreach ($files as $i => $file) {
            $num = $i + 1;
            $ago = ($file->published_at ? \Carbon\Carbon::parse($file->published_at)->diffForHumans() : null) ?? 'Unknown';
            $text .= "{$num}. <b>{$file->title}</b>\n"
                . "   📁 " . ($file->category->name ?? 'N/A') . " | ⏰ {$ago}\n\n";

            $buttons[] = [
                ['text' => "📥 " . Str::limit($file->title, 30), 'url' => route('files.show', $file)],
            ];
        }

        $this->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    protected function cmdTop(int $chatId): void
    {
        $files = File::where('status', 'approved')
            ->with('category')
            ->orderByDesc('download_count')
            ->limit(10)
            ->get();

        $text = "🏆 <b>Most Downloaded Files</b>\n\n";
        $buttons = [];

        foreach ($files as $i => $file) {
            $num = $i + 1;
            $downloads = number_format($file->download_count ?? 0);
            $medal = match($num) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => "#{$num}" };
            $text .= "{$medal} <b>{$file->title}</b>\n"
                . "   📥 {$downloads} downloads\n\n";

            $buttons[] = [
                ['text' => "{$medal} " . Str::limit($file->title, 28), 'url' => route('files.show', $file)],
            ];
        }

        $this->sendMessage($chatId, $text, ['inline_keyboard' => array_slice($buttons, 0, 5)]);
    }

    protected function cmdRandom(int $chatId): void
    {
        $file = File::where('status', 'approved')
            ->with('category', 'user')
            ->inRandomOrder()
            ->first();

        if (!$file) {
            $this->sendMessage($chatId, "😕 No files available.");
            return;
        }

        $text = "🎲 <b>Random File</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "📁 " . ($file->category->name ?? 'N/A') . "\n"
            . "👤 " . ($file->original_author ?? $file->user->name ?? 'Unknown') . "\n"
            . "📥 " . number_format($file->download_count ?? 0) . " downloads\n"
            . "💾 " . ($file->file_size_formatted ?? 'N/A') . "\n\n"
            . ($file->description ? Str::limit(strip_tags($file->description), 200) . "\n\n" : "");

        $buttons = ['inline_keyboard' => [
            [
                ['text' => '📥 Download', 'url' => route('files.show', $file)],
                ['text' => '🎲 Another!', 'callback_data' => 'random'],
            ]
        ]];

        // Try to send with screenshot
        $screenshot = $file->screenshots()->first();
        if ($screenshot && $screenshot->path) {
            $imageUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($screenshot->path);
            $this->sendPhoto($chatId, $imageUrl, $text, $buttons);
        } else {
            $this->sendMessage($chatId, $text, $buttons['inline_keyboard'] ? $buttons : null);
        }
    }

    protected function cmdMotw(int $chatId): void
    {
        $file = File::where('is_featured', true)
            ->with('category', 'user')
            ->first();

        if (!$file) {
            $this->sendMessage($chatId, "🗺️ No Map of the Week set currently.");
            return;
        }

        $text = "🗺️ <b>Map of the Week</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "📁 " . ($file->category->name ?? 'N/A') . "\n"
            . "👤 " . ($file->original_author ?? 'Unknown') . "\n"
            . "📥 " . number_format($file->download_count ?? 0) . " downloads\n\n"
            . ($file->description ? Str::limit(strip_tags($file->description), 200) : "");

        $buttons = ['inline_keyboard' => [
            [['text' => '📥 Download', 'url' => route('files.show', $file)]],
        ]];

        $screenshot = $file->screenshots()->first();
        if ($screenshot && $screenshot->path) {
            $imageUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($screenshot->path);
            $this->sendPhoto($chatId, $imageUrl, $text, $buttons);
        } else {
            $this->sendMessage($chatId, $text, $buttons);
        }
    }

    protected function cmdStats(int $chatId): void
    {
        $totalFiles = File::where('status', 'approved')->count();
        $totalDownloads = File::where('status', 'approved')->sum('download_count');
        $totalUsers = \App\Models\User::count();
        $pendingFiles = File::where('status', 'pending')->count();

        // Server stats
        $onlineServers = TrackerServer::active()->online()->count();
        $totalPlayers = TrackerServer::active()->online()->sum('current_players');

        $text = "📊 <b>Wolffiles.eu Statistics</b>\n\n"
            . "📦 Files: <b>" . number_format($totalFiles) . "</b>\n"
            . "📥 Total Downloads: <b>" . number_format($totalDownloads) . "</b>\n"
            . "👤 Users: <b>" . number_format($totalUsers) . "</b>\n"
            . "⏳ Pending Review: <b>{$pendingFiles}</b>\n\n"
            . "🖥️ <b>Game Servers</b>\n"
            . "🟢 Online: <b>{$onlineServers}</b>\n"
            . "🎮 Players: <b>{$totalPlayers}</b>";

        $this->sendMessage($chatId, $text);
    }

    protected function cmdServers(int $chatId, string $args): void
    {
        $query = TrackerServer::active()->online()->with('game');

        if (!empty($args) && strtolower($args) !== 'list') {
            $query->where('hostname', 'like', "%{$args}%");
        }

        $servers = $query->orderByDesc('current_players')->limit(10)->get();

        if ($servers->isEmpty()) {
            $this->sendMessage($chatId, "🖥️ No online servers found.");
            return;
        }

        $text = "🖥️ <b>Online Game Servers</b>\n\n";

        foreach ($servers as $server) {
            $players = $server->current_players ?? 0;
            $maxPlayers = $server->max_players ?? '?';
            $map = $server->current_map ?? 'Unknown';
            $game = $server->game->short_name ?? 'ET';

            $bar = $this->playerBar($players, $maxPlayers);
            $text .= "🎮 <b>{$server->hostname}</b>\n"
                . "   {$bar} {$players}/{$maxPlayers} | 🗺️ {$map}\n"
                . "   🔗 <code>{$server->full_address}</code>\n\n";
        }

        $this->sendMessage($chatId, $text);
    }

    protected function cmdPlayer(int $chatId, string $name): void
    {
        if (empty($name)) {
            $this->sendMessage($chatId, "👤 Usage: <code>/player wahke</code>");
            return;
        }

        $sessions = TrackerPlayerSession::where('player_name', 'like', "%{$name}%")
            ->with('server')
            ->orderByDesc('last_seen')
            ->limit(5)
            ->get();

        if ($sessions->isEmpty()) {
            $this->sendMessage($chatId, "👤 No player found for \"<b>{$name}</b>\".");
            return;
        }

        // Aggregate stats
        $totalTime = $sessions->sum('play_time_seconds');
        $hours = floor($totalTime / 3600);
        $playerName = $sessions->first()->player_name;
        $lastSeen = $sessions->first()->last_seen?->diffForHumans() ?? 'Unknown';

        $text = "👤 <b>Player: {$playerName}</b>\n\n"
            . "⏱️ Total Play Time: <b>{$hours}h</b>\n"
            . "🕐 Last Seen: {$lastSeen}\n\n"
            . "🖥️ <b>Recent Servers:</b>\n";

        foreach ($sessions->unique('server_id') as $session) {
            $serverName = $session->server->hostname ?? 'Unknown';
            $text .= "  • {$serverName}\n";
        }

        $this->sendMessage($chatId, $text);
    }

    // ─── Admin Commands ──────────────────────────────────────

    protected function cmdPending(int $chatId): void
    {
        $files = File::where('status', 'pending')
            ->with('category', 'user')
            ->latest()
            ->limit(10)
            ->get();

        if ($files->isEmpty()) {
            $this->sendMessage($chatId, "✅ No pending files! All clear.");
            return;
        }

        $text = "⏳ <b>Pending Files ({$files->count()})</b>\n\n";
        $buttons = [];

        foreach ($files as $file) {
            $text .= "📄 <b>[{$file->id}]</b> {$file->title}\n"
                . "   👤 " . ($file->user->name ?? 'Unknown') . " | 📁 " . ($file->category->name ?? 'N/A') . "\n\n";

            $buttons[] = [
                ['text' => "✅ Approve #{$file->id}", 'callback_data' => "approve_{$file->id}"],
                ['text' => "❌ Reject #{$file->id}", 'callback_data' => "reject_{$file->id}"],
                ['text' => '👁️ View', 'url' => url("/admin/files/{$file->id}/edit")],
            ];
        }

        $this->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    protected function cmdApprove(int $chatId, string $args): void
    {
        $fileId = (int) trim($args);
        if (!$fileId) {
            $this->sendMessage($chatId, "Usage: <code>/approve 5035</code>");
            return;
        }

        $file = File::find($fileId);
        if (!$file) {
            $this->sendMessage($chatId, "❌ File #{$fileId} not found.");
            return;
        }

        if ($file->status === 'approved') {
            $this->sendMessage($chatId, "⚠️ File #{$fileId} is already approved.");
            return;
        }

        $service = app(\App\Services\FileUploadService::class);
        $service->approve($file, 1); // user_id 1 = admin

        $this->sendMessage($chatId,
            "✅ <b>File Approved!</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "🔗 " . route('files.show', $file)
        );
    }

    protected function cmdReject(int $chatId, string $args): void
    {
        $parts = explode(' ', $args, 2);
        $fileId = (int) ($parts[0] ?? 0);
        $reason = $parts[1] ?? 'Rejected via Telegram';

        if (!$fileId) {
            $this->sendMessage($chatId, "Usage: <code>/reject 5035 reason</code>");
            return;
        }

        $file = File::find($fileId);
        if (!$file) {
            $this->sendMessage($chatId, "❌ File #{$fileId} not found.");
            return;
        }

        $service = app(\App\Services\FileUploadService::class);
        $service->reject($file, 1, $reason);

        $this->sendMessage($chatId,
            "❌ <b>File Rejected</b>\n\n"
            . "📄 <b>{$file->title}</b>\n"
            . "📝 Reason: {$reason}"
        );
    }

    protected function cmdUnknown(int $chatId, string $command): void
    {
        if (str_starts_with($command, '/')) {
            $this->sendMessage($chatId, "❓ Unknown command. Type /help for available commands.");
        }
    }


    protected function cmdAck(int $chatId): void
    {
        \Illuminate\Support\Facades\Cache::put('tracker:alert_acked', true, today()->addDay()->setHour(6)->setMinute(0)->setSecond(0));
        $this->sendMessage($chatId, '✅ <b>Alert quittiert</b> — keine Benachrichtigungen bis morgen 06:00 Uhr.');
    }
    // ─── Inline Query (search from any chat) ─────────────────

    protected function handleInlineQuery(array $query): void
    {
        $queryId = $query['id'];
        $searchText = trim($query['query']);

        if (strlen($searchText) < 2) {
            $this->answerInlineQuery($queryId, []);
            return;
        }

        $files = File::where('status', 'approved')
            ->where(function ($q) use ($searchText) {
                $q->where('title', 'like', "%{$searchText}%")
                  ->orWhere('original_author', 'like', "%{$searchText}%");
            })
            ->with('category')
            ->orderByDesc('download_count')
            ->limit(20)
            ->get();

        $results = [];
        foreach ($files as $file) {
            $cat = $file->category->name ?? 'N/A';
            $downloads = number_format($file->download_count ?? 0);
            $desc = Str::limit(strip_tags($file->description ?? ''), 100);
            $url = route('files.show', $file);

            $results[] = [
                'type' => 'article',
                'id' => (string) $file->id,
                'title' => $file->title,
                'description' => "{$cat} | 📥 {$downloads} | {$desc}",
                'url' => $url,
                'input_message_content' => [
                    'message_text' => "📄 <b>{$file->title}</b>\n📁 {$cat} | 📥 {$downloads}\n🔗 {$url}",
                    'parse_mode' => 'HTML',
                ],
                'reply_markup' => [
                    'inline_keyboard' => [
                        [['text' => '📥 Download', 'url' => $url]],
                    ],
                ],
            ];
        }

        $this->answerInlineQuery($queryId, $results);
    }

    // ─── Callback Query (button clicks) ──────────────────────

    protected function handleCallbackQuery(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'];
        $callbackId = $callback['id'];
        $data = $callback['data'];
        $isAdmin = (string)($callback["from"]["id"] ?? 0) === (string)$this->adminChatId;

        if ($data === 'random') {
            $this->answerCallbackQuery($callbackId, '🎲 Rolling...');
            $this->cmdRandom($chatId);
            return;
        }

        if (str_starts_with($data, 'approve_') && $isAdmin) {
            $fileId = str_replace('approve_', '', $data);
            $this->answerCallbackQuery($callbackId, '✅ Approving...');
            $this->cmdApprove($chatId, $fileId);
            return;
        }

        if ($data === 'cmd_latest') {
            $this->answerCallbackQuery($callbackId, '📦');
            $this->cmdLatest($chatId);
            return;
        }

        if ($data === 'cmd_servers') {
            $this->answerCallbackQuery($callbackId, '🖥️');
            $this->cmdServers($chatId, '');
            return;
        }

        if ($data === 'cmd_stats') {
            $this->answerCallbackQuery($callbackId, '📊');
            $this->cmdStats($chatId);
            return;
        }

        if ($data === 'cmd_motw') {
            $this->answerCallbackQuery($callbackId, '🗺️');
            $this->cmdMotw($chatId);
            return;
        }

        if (str_starts_with($data, 'reject_') && $isAdmin) {
            $fileId = str_replace('reject_', '', $data);
            $this->answerCallbackQuery($callbackId, '❌ Rejecting...');
            $this->cmdReject($chatId, "{$fileId} Rejected via Telegram");
            return;
        }

        $this->answerCallbackQuery($callbackId, '🐺');
    }

    // ─── Helpers ─────────────────────────────────────────────

    protected function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post("{$this->apiUrl}/sendMessage", $payload);
    }

    protected function sendPhoto(int $chatId, string $photoUrl, string $caption, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => mb_substr($caption, 0, 1024),
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $response = Http::post("{$this->apiUrl}/sendPhoto", $payload);

        // Fallback to text if photo fails
        if (!$response->successful()) {
            $this->sendMessage($chatId, $caption, $replyMarkup);
        }
    }

    protected function answerInlineQuery(string $queryId, array $results): void
    {
        Http::post("{$this->apiUrl}/answerInlineQuery", [
            'inline_query_id' => $queryId,
            'results' => json_encode($results),
            'cache_time' => 60,
        ]);
    }

    protected function answerCallbackQuery(string $callbackId, string $text = ''): void
    {
        Http::post("{$this->apiUrl}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text,
        ]);
    }

    protected function mainMenuKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🔍 Search', 'switch_inline_query_current_chat' => ''],
                ['text' => '📦 Latest', 'callback_data' => 'cmd_latest'],
            ],
            [
                ['text' => '🖥️ Servers', 'callback_data' => 'cmd_servers'],
                ['text' => '📊 Stats', 'callback_data' => 'cmd_stats'],
            ],
            [
                ['text' => '🗺️ Map of the Week', 'callback_data' => 'cmd_motw'],
                ['text' => '🎲 Random', 'callback_data' => 'random'],
            ],
            [
                ['text' => '🌐 Visit Wolffiles.eu', 'url' => 'https://wolffiles.eu'],
            ],
        ]];
    }

    protected function playerBar(int $current, int $max): string
    {
        if ($max <= 0) return '▱▱▱▱▱▱▱▱';
        $filled = (int) round(($current / $max) * 8);
        return str_repeat('▰', $filled) . str_repeat('▱', 8 - $filled);
    }

    /**
     * Register webhook with Telegram
     */
    public static function setWebhook(): array
    {
        $token = config('services.telegram.bot_token');
        $url = url('/api/telegram/webhook');

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            'allowed_updates' => json_encode(['message', 'callback_query', 'inline_query']),
        ]);

        return $response->json();
    }

    /**
     * Register bot commands with Telegram
     */
    public static function setCommands(): array
    {
        $token = config('services.telegram.bot_token');

        $commands = [
            ['command' => 'search', 'description' => '🔍 Search files - /search mp_beach'],
            ['command' => 'latest', 'description' => '📦 Latest uploads'],
            ['command' => 'top', 'description' => '🏆 Most downloaded files'],
            ['command' => 'random', 'description' => '🎲 Random file'],
            ['command' => 'motw', 'description' => '🗺️ Map of the Week'],
            ['command' => 'servers', 'description' => '🖥️ Online game servers'],
            ['command' => 'player', 'description' => '👤 Player stats - /player name'],
            ['command' => 'stats', 'description' => '📊 Platform statistics'],
            ['command' => 'help', 'description' => '❓ Show all commands'],
        ];

        $response = Http::post("https://api.telegram.org/bot{$token}/setMyCommands", [
            'commands' => json_encode($commands),
        ]);

        return $response->json();
    }
}