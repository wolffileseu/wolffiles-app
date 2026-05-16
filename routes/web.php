<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\FileController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\LuaScriptController;
use App\Http\Controllers\Frontend\PostController;
use App\Http\Controllers\Frontend\CommentController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\StatisticsController;
use App\Http\Controllers\Frontend\ReportController;
use App\Http\Controllers\Frontend\RssFeedController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\NotificationController;
use App\Http\Controllers\Frontend\DmController;
use App\Http\Controllers\Frontend\TrackerController;
use App\Http\Controllers\Frontend\DemoController;
use App\Http\Controllers\Frontend\DonationController;
use App\Http\Controllers\FastDlController;
use App\Http\Controllers\Frontend\ClanFastDlController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Api\EasterEggController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\WikiController;
use App\Http\Controllers\Frontend\TutorialController;
use App\Http\Controllers\Frontend\CampaignCreatorController;

Route::prefix('tools')->name('tools.')->group(function () {
    Route::get('/campaign-creator', [CampaignCreatorController::class, 'index'])->name('campaign-creator');
    Route::get('/campaign-creator/search-maps', [CampaignCreatorController::class, 'searchMaps'])->name('campaign-creator.search-maps');
    Route::get("/nickname-generator", [\App\Http\Controllers\Frontend\NicknameGeneratorController::class, "index"])->name("nickname-generator");
    Route::get("/omni-bot", [\App\Http\Controllers\Frontend\OmnibotController::class, "index"])->name("omnibot");
    Route::get("/omni-bot/download/{map}", [\App\Http\Controllers\Frontend\OmnibotController::class, "download"])->name("omnibot.download");
    Route::get("/omni-bot/download-all", [\App\Http\Controllers\Frontend\OmnibotController::class, "downloadAll"])->name("omnibot.download-all");
});

// Wiki (public)
Route::get('/wiki', [WikiController::class, 'index'])->name('wiki.index');
// Wiki Special Pages — MÜSSEN vor /wiki/{slug} stehen, sonst Catch-all-Konflikt!
Route::get('/wiki/special/recent',          [WikiController::class, 'recentChanges'])->name('wiki.special.recent');
Route::get('/wiki/special/random',          [WikiController::class, 'randomPage'])->name('wiki.special.random');
Route::get('/wiki/special/all',             [WikiController::class, 'allPages'])->name('wiki.special.all');
Route::get('/wiki/special/links-to/{slug}', [WikiController::class, 'whatLinksHere'])->name('wiki.special.whatlinkshere');

// Wiki Talk-Pages
Route::get('/wiki/{slug}/talk',                            [WikiController::class, 'talk'])->name('wiki.talk');
Route::middleware('auth')->group(function () {
    Route::get('/wiki/{slug}/talk/new',                    [WikiController::class, 'newThreadForm'])->name('wiki.talk.new');
    Route::post('/wiki/{slug}/talk',                       [WikiController::class, 'storeThread'])->name('wiki.talk.store');
    Route::post('/wiki/{slug}/talk/{thread}/reply',        [WikiController::class, 'reply'])->name('wiki.talk.reply');
    Route::post('/wiki/{slug}/talk/{thread}/resolve',      [WikiController::class, 'toggleResolve'])->name('wiki.talk.resolve');
    Route::post('/wiki/{slug}/talk/{thread}/pin',          [WikiController::class, 'togglePin'])->name('wiki.talk.pin');
    Route::delete('/wiki/{slug}/talk/{thread}',            [WikiController::class, 'deleteThread'])->name('wiki.talk.delete');
    Route::delete('/wiki/{slug}/talk/msg/{message}',       [WikiController::class, 'deleteMessage'])->name('wiki.talk.delete_msg');
});

Route::get('/wiki/{slug}', [WikiController::class, 'show'])->name('wiki.show');
Route::get('/wiki/{slug}/history', [WikiController::class, 'history'])->name('wiki.history');
Route::get('/wiki/{slug}/diff/{rev1}/{rev2}', [WikiController::class, 'diff'])->name('wiki.diff');
Route::post('/wiki/{slug}/restore/{rev}', [WikiController::class, 'restore'])->middleware('auth')->name('wiki.restore');

// Wiki (auth required)
Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/wiki-create', [WikiController::class, 'create'])->name('wiki.create');
    Route::post('/wiki', [WikiController::class, 'store'])->name('wiki.store');
    Route::get('/wiki/{wikiArticle}/edit', [WikiController::class, 'edit'])->name('wiki.edit');
    Route::put('/wiki/{wikiArticle}', [WikiController::class, 'update'])->name('wiki.update');
});

// Tutorials (public)
Route::get('/tutorials', [TutorialController::class, 'index'])->name('tutorials.index');
Route::get('/tutorials/{slug}', [TutorialController::class, 'show'])->name('tutorials.show');

// Tutorials (auth required)
Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/tutorial-create', [TutorialController::class, 'create'])->name('tutorials.create');
    Route::post('/tutorials', [TutorialController::class, 'store'])->name('tutorials.store');
    Route::post('/tutorials/{tutorial}/vote', [TutorialController::class, 'vote'])->name('tutorials.vote');
});


// Home
Route::get('/', [HomeController::class, 'index'])->name('home');


// ===== Demos =====
Route::get('/demos', [DemoController::class, 'index'])->name('demos.index');
Route::get('/demos/{demo}', [DemoController::class, 'show'])->name('demos.show');
Route::get('/demos/{demo}/viewer', [DemoController::class, 'viewer'])->name('demos.viewer');
Route::get('/demos/{demo}/download', [DemoController::class, 'download'])->name('demos.download');

Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/demo/upload', [DemoController::class, 'upload'])->name('demos.upload');
    Route::post('/demo/upload', [DemoController::class, 'store'])->name('demos.store');
});


// Files
Route::get('/files', [FileController::class, 'index'])->name('files.index');
Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
Route::get('/files/{file}/stream', [\App\Http\Controllers\Frontend\FileController::class, 'stream'])->name('files.stream');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// LUA Scripts
Route::get('/lua', [LuaScriptController::class, 'index'])->name('lua.index');

// LUA Upload (muss VOR {luaScript} stehen!)
Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/lua/upload', [LuaScriptController::class, 'upload'])->name('lua.upload');
    Route::post('/lua/upload', [LuaScriptController::class, 'store'])->name('lua.store');
});

// Erweiterte Suche
Route::get('/search', [SearchController::class, 'index'])->name('search');




Route::get('/lua/{luaScript}', [LuaScriptController::class, 'show'])->name('lua.show');
Route::get('/lua/{luaScript}/download', [LuaScriptController::class, 'download'])->name('lua.download');

// News / Blog
Route::get('/news', [PostController::class, 'index'])->name('posts.index');
Route::get('/news/{post}', [PostController::class, 'show'])->name('posts.show');

// Custom Pages
Route::get('/page/{page}', [PageController::class, 'show'])->name('pages.show');

// Statistics
Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap')->withoutMiddleware(['web']);

// Contact
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// User Profiles (public)
Route::get('/user/{user}', [ProfileController::class, 'show'])->name('profile.show');

// RSS Feed
Route::get('/rss/files', [RssFeedController::class, 'files'])->name('rss.files');

// Easter Egg
Route::post('/easter-egg/complete', [EasterEggController::class, 'complete'])
    ->middleware('auth')
    ->name('easter-egg.complete');

// Discord OAuth
Route::get('/auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord.redirect');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');

// Auth-required routes
Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    // Upload
    Route::get('/upload', [FileController::class, 'upload'])->name('files.upload');
    Route::post('/upload', [FileController::class, 'store'])->name('files.store');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['get', 'post'], '/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

// Criteria Rating
Route::post('/files/{file}/rate-criterion', function (\Illuminate\Http\Request $request, \App\Models\File $file) {
    $request->validate(['criteria_id' => 'required|exists:rating_criteria,id', 'score' => 'required|integer|min:1|max:5']);
    \Illuminate\Support\Facades\DB::table('file_criteria_ratings')->updateOrInsert(
        ['file_id' => $file->id, 'rating_criteria_id' => $request->criteria_id, 'user_id' => auth()->id()],
        ['score' => $request->score, 'updated_at' => now(), 'created_at' => now()]
    );
    return response()->json(['ok' => true]);
})->name('files.rateCriterion');


    // File interactions
    Route::post('/files/{file}/rate', [FileController::class, 'rate'])->name('files.rate');
    Route::post('/files/{file}/favorite', [FileController::class, 'favorite'])->name('files.favorite');

    // Comments
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Reports
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

    Route::post('/polls/{poll}/vote', [\App\Http\Controllers\Frontend\PollController::class, 'vote'])->name('polls.vote');
    // Profile
    Route::get('/my/favorites', [ProfileController::class, 'favorites'])->name('profile.favorites');
    Route::get('/my/uploads', [ProfileController::class, 'myUploads'])->name('profile.uploads');
    Route::get('/my/settings', [ProfileController::class, 'settings'])->name('profile.settings');
    Route::put('/my/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');
    Route::post('/my/settings/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/my/settings/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    Route::delete('/my/settings', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/my/settings/delete-account', [ProfileController::class, 'destroy'])->name('profile.destroy.post');
    Route::post('/my/settings/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications.update');

    // Discord connect/disconnect
    Route::get('/auth/discord/disconnect', [DiscordController::class, 'disconnect'])->name('auth.discord.disconnect');
});

// ===== Clan Fast Download Portal =====
Route::middleware("auth")->prefix("my-fastdl")->group(function () {
    Route::get("/", [ClanFastDlController::class, "index"])->name("clan.fastdl");
    Route::post("/create", [ClanFastDlController::class, "store"])->name("clan.fastdl.store");
    Route::post("/directories", [ClanFastDlController::class, "updateDirectories"])->name("clan.fastdl.directories");
    Route::post("/upload", [ClanFastDlController::class, "upload"])->name("clan.fastdl.upload");
    Route::delete("/file/{file}", [ClanFastDlController::class, "deleteFile"])->name("clan.fastdl.delete");
    // Fallback: verhindert 405 bei direkter GET-Navigation auf die Delete-URL
    Route::get("/file/{file}", fn () => redirect()->route("clan.fastdl"));
});

// ===== Fast Download =====
// These routes respond on dl.wolffiles.eu subdomain
Route::domain('dl.wolffiles.eu')->group(function () {
    Route::get('/', [FastDlController::class, 'index'])->name('fastdl.index');
    Route::get('/{game}', [FastDlController::class, 'listGame'])->name('fastdl.game');
    Route::get('/{game}/{directory}', [FastDlController::class, 'listDirectory'])->name('fastdl.directory');
    Route::get('/{game}/{directory}/{filename}', [FastDlController::class, 'serve'])->name('fastdl.serve');
});

// ===== Donations =====
Route::get("/donate", [DonationController::class, "index"])->name("donate");
Route::post("/donate/paypal-ipn", [DonationController::class, "paypalIpn"])->name("donate.paypal.ipn");

// ===== Tracker =====
Route::get('/tracker', [TrackerController::class, 'index'])->name('tracker.index');
Route::get('/servers', [TrackerController::class, 'servers'])->name('tracker.servers');
Route::get('/servers/{server}', [TrackerController::class, 'serverShow'])->name('tracker.server.show');
Route::get('/players', [TrackerController::class, 'players'])->name('tracker.players');
Route::get('/players/{player}', [TrackerController::class, 'playerShow'])->name('tracker.player.show');

// === PLAYER SERVERS (Commit 2) ===
Route::get('/players/{player}/servers', [TrackerController::class, 'playerServers'])->name('tracker.player.servers');

Route::post('/players/{player}/claim', [TrackerController::class, 'claimPlayer'])->middleware('auth')->name('tracker.player.claim');
Route::post('/players/{player}/unclaim', [TrackerController::class, 'unclaimPlayer'])->middleware('auth')->name('tracker.player.unclaim');

// Auth routes (Laravel Breeze)
require __DIR__ . '/auth.php';
// BSP file proxy for 3D map viewer (avoids CORS issues with S3)
Route::get('/bsp-proxy/{file_id}', function (int $file_id) {
    $file = App\Models\File::findOrFail($file_id);
    if (empty($file->bsp_path)) {
        abort(404);
    }

    $s3 = Storage::disk('s3');
    if (!$s3->exists($file->bsp_path)) {
        abort(404);
    }

    return response()->stream(function () use ($s3, $file) {
        echo $s3->get($file->bsp_path);
    }, 200, [
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'inline',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('bsp.proxy');

// Texture proxy for 3D map viewer (S3 -> game-pool -> placeholder)
Route::get('/tex-proxy/{file_id}/{path}', App\Http\Controllers\TexProxyController::class)
    ->where('path', '.*')->name('tex.proxy');
// Public API Documentation
Route::get('/api-docs', function () {
    return view('frontend.api-docs');
})->name('api-docs');

// ==========================================
// Server Hosting
// ==========================================
Route::prefix('hosting')->name('hosting.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\HostingController::class, 'index'])->name('index');
    Route::get('/configure/{product:slug}', [\App\Http\Controllers\Frontend\HostingController::class, 'configure'])->name('configure');
    Route::post('/calculate-price', [\App\Http\Controllers\Frontend\HostingController::class, 'calculatePrice'])->name('calculate-price');

    Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
        Route::post('/checkout', [\App\Http\Controllers\Frontend\HostingController::class, 'checkout'])->name('checkout');
        Route::get('/payment/{order}', [\App\Http\Controllers\Frontend\HostingController::class, 'payment'])->name('payment');
        Route::get('/payment/{order}/success', [\App\Http\Controllers\Frontend\HostingController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/dashboard', [\App\Http\Controllers\Frontend\HostingController::class, 'dashboard'])->name('dashboard');
        Route::get('/server/{order}', [\App\Http\Controllers\Frontend\HostingController::class, 'serverDetail'])->name('server');
        Route::post('/server/{order}/action', [\App\Http\Controllers\Frontend\HostingController::class, 'serverAction'])->name('server.action');
        Route::post('/server/{order}/command', [\App\Http\Controllers\Frontend\HostingController::class, 'sendCommand'])->name('server.command');
        Route::get('/server/{order}/renew', [\App\Http\Controllers\Frontend\HostingController::class, 'renew'])->name('renew');
    });
});

// PayPal IPN for Hosting (no CSRF)
Route::post('/hosting/paypal/ipn', [\App\Http\Controllers\Frontend\HostingController::class, 'paypalIpn'])
    ->name('hosting.paypal.ipn')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Credits
Route::get('/credits', [\App\Http\Controllers\Frontend\CreditsController::class, 'index'])->name('credits');

// ===== Tracker Extended =====
use App\Http\Controllers\Frontend\TrackerExtendedController;

Route::get('/tracker/map', [TrackerExtendedController::class, 'worldMap'])->name('tracker.map');
Route::get('/tracker/rankings', [TrackerExtendedController::class, 'rankings'])->name('tracker.rankings');
Route::get('/tracker/rankings/servers', [TrackerExtendedController::class, 'serverRankings'])->name('tracker.rankings.servers');

// Global Weapon Mastery Leaderboards
Route::get('/tracker/weapons', [\App\Http\Controllers\Frontend\WeaponLeaderboardController::class, 'index'])
    ->name('tracker.weapons.index');
Route::get('/tracker/weapons/{slug}', [\App\Http\Controllers\Frontend\WeaponLeaderboardController::class, 'show'])
    ->name('tracker.weapons.show');
Route::get('/tracker/rankings/players', [TrackerExtendedController::class, 'playerRankings'])->name('tracker.rankings.players');
Route::get('/tracker/clans', [TrackerExtendedController::class, 'clans'])->name('tracker.clans');
Route::get('/tracker/clans/{clan}', [TrackerExtendedController::class, 'clanShow'])->name('tracker.clan.show');
Route::get('/tracker/compare', [TrackerExtendedController::class, 'playerCompare'])->name('tracker.compare');

// === MATCH BROWSER (Commit 1) ===
Route::get('/tracker/matches', [\App\Http\Controllers\Frontend\TrackerExtendedController::class, 'matchesBrowse'])->name('tracker.matches.browse');
Route::get('/tracker/matches/{match}', [\App\Http\Controllers\Frontend\TrackerExtendedController::class, 'matchShow'])->name('tracker.matches.show');

Route::get('/tracker/scrims', [TrackerExtendedController::class, 'scrims'])->name('tracker.scrims');

Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/tracker/scrims/create', [TrackerExtendedController::class, 'scrimCreate'])->name('tracker.scrims.create');
    Route::post('/tracker/scrims', [TrackerExtendedController::class, 'scrimStore'])->name('tracker.scrims.store');
    Route::post('/servers/{server}/rate', [TrackerExtendedController::class, 'rateServer'])->name('tracker.server.rate');
});


// ===== Tracker Claims =====
use App\Http\Controllers\Frontend\TrackerClaimController;
use App\Http\Controllers\Frontend\TrackerClaimAdminController;

Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    // Player claims
    Route::get('/tracker/players/{player}/claim', [TrackerClaimController::class, 'claimPlayer'])->name('tracker.claim.player');
    Route::post('/tracker/players/{player}/claim', [TrackerClaimController::class, 'storePlayerClaim'])->name('tracker.claim.player.store');

    // Clan claims
    Route::get('/tracker/clans/{clan}/claim', [TrackerClaimController::class, 'claimClan'])->name('tracker.claim.clan');
    Route::post('/tracker/clans/{clan}/claim', [TrackerClaimController::class, 'storeClanClaim'])->name('tracker.claim.clan.store');

    // My claims
    Route::get('/tracker/my-claims', [TrackerClaimController::class, 'myClaims'])->name('tracker.my-claims');
});

// Admin/Moderator claim management (add your own middleware for role check)
Route::middleware(['auth'])->prefix('tracker/admin')->group(function () {
    Route::get('/claims', [TrackerClaimAdminController::class, 'index'])->name('tracker.admin.claims');
    Route::get('/claims/{claim}', [TrackerClaimAdminController::class, 'show'])->name('tracker.admin.claims.show');
    Route::post('/claims/{claim}/approve', [TrackerClaimAdminController::class, 'approve'])->name('tracker.admin.claims.approve');
    Route::post('/claims/{claim}/reject', [TrackerClaimAdminController::class, 'reject'])->name('tracker.admin.claims.reject');
});

// ===== Tracker Server Claims =====
Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
    Route::get('/tracker/servers/{server}/claim', [\App\Http\Controllers\Frontend\TrackerClaimController::class, 'claimServer'])->name('tracker.claim.server');
    Route::post('/tracker/servers/{server}/claim', [\App\Http\Controllers\Frontend\TrackerClaimController::class, 'storeServerClaim'])->name('tracker.claim.server.store');
});

// Server live data API
Route::get('/tracker/servers/{server}/live', [\App\Http\Controllers\Frontend\TrackerController::class, 'serverLiveData'])->name('tracker.server.live');
require __DIR__.'/ettv-routes.php';

// =====================
// FORUM
// =====================
use App\Http\Controllers\ForumController;

Route::prefix('forum')->name('forum.')->group(function () {
    // Öffentlich
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/{category}', [ForumController::class, 'category'])->name('category');
    Route::get('/{category}/{thread}', [ForumController::class, 'thread'])->name('thread');

    // Eingeloggte User
    Route::middleware('auth')->group(function () {
    Route::get('settings/privacy/download-export', [\App\Http\Controllers\PrivacyExportController::class, 'download'])->name('settings.privacy.download');
    Route::get('settings/privacy', \App\Livewire\Settings\Privacy::class)->name('settings.privacy');
        Route::get('/{category}/new-thread/create', [ForumController::class, 'createThread'])->name('create-thread');
        Route::post('/{category}/new-thread/create', [ForumController::class, 'storeThread'])->name('store-thread');
        Route::post('/{category}/{thread}/reply', [ForumController::class, 'storePost'])->name('store-post');
        Route::get('/post/{post}/edit', [ForumController::class, 'editPost'])->name('edit-post');
        Route::put('/post/{post}', [ForumController::class, 'updatePost'])->name('update-post');
        Route::delete('/post/{post}', [ForumController::class, 'deletePost'])->name('delete-post');

        // Moderation (Admin + Moderator)
        Route::post('/thread/{thread}/toggle-pin', [ForumController::class, 'togglePin'])->name('toggle-pin');
        Route::post('/thread/{thread}/toggle-lock', [ForumController::class, 'toggleLock'])->name('toggle-lock');
        Route::post('/thread/{thread}/move', [ForumController::class, 'moveThread'])->name('move-thread');
        Route::delete('/thread/{thread}/delete', [ForumController::class, 'deleteThread'])->name('delete-thread');
    });
});

// Legacy Tracker API redirects — forward old URLs to /api/v1/tracker/
Route::get('/api/tracker/{any}', function ($any) {
    return redirect('/api/v1/tracker/' . $any . (request()->getQueryString() ? '?' . request()->getQueryString() : ''), 301);
})->where('any', '.*');

// Tracker banners (PNG signatures for forums/Discord)
Route::get('/tracker/server/{server}/banner.png', [\App\Http\Controllers\Tracker\BannerController::class, 'server'])
    ->name('tracker.server.banner');

Route::get('/tracker/player/{player}/banner.png', [\App\Http\Controllers\Tracker\BannerController::class, 'player'])
    ->name('tracker.player.banner');

// Vertical HTML embed banner (iframe)
Route::get('/tracker/server/{server}/embed', [\App\Http\Controllers\Tracker\BannerController::class, 'serverEmbed'])
    ->name('tracker.server.embed');
Route::get('/tracker/player/{player}/embed', [\App\Http\Controllers\Tracker\BannerController::class, 'playerEmbed'])
    ->name('tracker.player.embed');


// ============================================================
// Multipart Upload API (for Uppy.io browser-direct uploads to S3)
// ============================================================
Route::middleware('auth')->prefix('upload-api')->name('upload.')->group(function () {
    Route::post('/init',     [\App\Http\Controllers\MultipartUploadController::class, 'init'])->name('init');
    Route::post('/sign',     [\App\Http\Controllers\MultipartUploadController::class, 'sign'])->name('sign');
    Route::post('/complete', [\App\Http\Controllers\MultipartUploadController::class, 'complete'])->name('complete');
    Route::post('/abort',    [\App\Http\Controllers\MultipartUploadController::class, 'abort'])->name('abort');
});

// =====================================================================
// Direct Messages (PM System) -- Phase 4
// =====================================================================
Route::middleware("auth")->prefix("dm")->name("dm.")->group(function () {
    Route::get("/",                        [DmController::class, "inbox"])->name("inbox");
    Route::get("/compose",                 [DmController::class, "compose"])->name("compose");
    Route::post("/",                       [DmController::class, "store"])->name("store");
    Route::get("/settings",                [DmController::class, "settings"])->name("settings");
    Route::post("/settings",               [DmController::class, "updateSettings"])->name("settings.update");
    Route::post("/blocks",                 [DmController::class, "blockUser"])->name("blocks.store");
    Route::delete("/blocks/{block}",       [DmController::class, "unblockUser"])->name("blocks.destroy")->whereNumber("block");
    Route::get("/{conversation}",          [DmController::class, "show"])->name("show")->whereNumber("conversation");
    Route::post("/{conversation}/reply",   [DmController::class, "reply"])->name("reply")->whereNumber("conversation");
    Route::delete("/{conversation}",       [DmController::class, "softDelete"])->name("delete")->whereNumber("conversation");
});

// TEMPORARY DEBUG - REMOVE AFTER USE
Route::get('/__omnibot_trigger/{id}', function (int $id) {
    $start = microtime(true);
    $startCwd = getcwd();
    $file = App\Models\File::findOrFail($id);
    $svc = app(App\Services\OmnibotWaypointService::class);
    $result = $svc->scanFile($file);
    $afterCwd = getcwd();
    return response()->json([
        'file_id' => $file->id,
        'file_name' => $file->file_name,
        'cwd_before' => $startCwd,
        'cwd_after' => $afterCwd,
        'result' => $result,
        'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        'pid' => getmypid(),
    ]);
});
