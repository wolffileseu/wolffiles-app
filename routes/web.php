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
Route::get('/wiki/{slug}', [WikiController::class, 'show'])->name('wiki.show');
Route::get('/wiki/{wikiArticle}/history', [WikiController::class, 'history'])->name('wiki.history');

// Wiki (auth required)
Route::middleware('auth')->group(function () {
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
    Route::get('/demo/upload', [DemoController::class, 'upload'])->name('demos.upload');
    Route::post('/demo/upload', [DemoController::class, 'store'])->name('demos.store');
});


// Files
Route::get('/files', [FileController::class, 'index'])->name('files.index');
Route::get('/files/{file}', [FileController::class, 'show'])->name('files.show');
Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

// LUA Scripts
Route::get('/lua', [LuaScriptController::class, 'index'])->name('lua.index');

// LUA Upload (muss VOR {luaScript} stehen!)
Route::middleware('auth')->group(function () {
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
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

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
Route::get('/api/tracker/servers', [TrackerController::class, 'apiServers'])->name('tracker.api.servers');
Route::get('/api/tracker/stats', [TrackerController::class, 'apiStats'])->name('tracker.api.stats');
Route::get('/api/tracker/servers/top', [TrackerController::class, 'apiTopServers'])->name('tracker.api.top-servers');
Route::get('/api/tracker/players/top', [TrackerController::class, 'apiTopPlayers'])->name('tracker.api.top-players');
Route::get('/api/tracker/players/search', [TrackerController::class, 'apiPlayerSearch'])->name('tracker.api.player-search');
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

// Texture proxy for 3D map viewer (serves map-specific textures from S3)
Route::get('/tex-proxy/{file_id}/{path}', function (int $file_id, string $path) {
    $s3 = Storage::disk('s3');
    $s3Path = "bsp/{$file_id}/assets/{$path}";

    // Try exact path first
    if (!$s3->exists($s3Path)) {
        // Case-insensitive: list ALL files under bsp/{id}/assets/ and match
        $allFiles = $s3->allFiles("bsp/{$file_id}/assets");
        $searchPath = strtolower($path);
        $found = false;
        foreach ($allFiles as $f) {
            $relative = str_replace("bsp/{$file_id}/assets/", '', $f);
            if (strtolower($relative) === $searchPath) {
                $s3Path = $f;
                $found = true;
                break;
            }
        }
        if (!$found) { abort(404); }
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

    return response($s3->get($s3Path), 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*')->name('tex.proxy');
Route::get('/tex-proxy/{file_id}/{path}', function (int $file_id, string $path) {
    $s3 = Storage::disk('s3');
    $s3Path = "bsp/{$file_id}/assets/{$path}";

    // Try exact path first
    if (!$s3->exists($s3Path)) {
        // Case-insensitive: list ALL files under bsp/{id}/assets/ and match
        $allFiles = $s3->allFiles("bsp/{$file_id}/assets");
        $searchPath = strtolower($path);
        $found = false;
        foreach ($allFiles as $f) {
            $relative = str_replace("bsp/{$file_id}/assets/", '', $f);
            if (strtolower($relative) === $searchPath) {
                $s3Path = $f;
                $found = true;
                break;
            }
        }
        if (!$found) { abort(404); }
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

    return response($s3->get($s3Path), 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*')->name('tex.proxy');

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
Route::get('/tracker/clans', [TrackerExtendedController::class, 'clans'])->name('tracker.clans');
Route::get('/tracker/clans/{clan}', [TrackerExtendedController::class, 'clanShow'])->name('tracker.clan.show');
Route::get('/tracker/compare', [TrackerExtendedController::class, 'playerCompare'])->name('tracker.compare');
Route::get('/tracker/scrims', [TrackerExtendedController::class, 'scrims'])->name('tracker.scrims');

Route::middleware('auth')->group(function () {
    Route::get('/tracker/scrims/create', [TrackerExtendedController::class, 'scrimCreate'])->name('tracker.scrims.create');
    Route::post('/tracker/scrims', [TrackerExtendedController::class, 'scrimStore'])->name('tracker.scrims.store');
    Route::post('/servers/{server}/rate', [TrackerExtendedController::class, 'rateServer'])->name('tracker.server.rate');
});

Route::get('/api/tracker/rankings', [TrackerExtendedController::class, 'apiRankings'])->name('tracker.api.rankings');
Route::get('/api/tracker/clans', [TrackerExtendedController::class, 'apiClans'])->name('tracker.api.clans');

// ===== Tracker Claims =====
use App\Http\Controllers\Frontend\TrackerClaimController;
use App\Http\Controllers\Frontend\TrackerClaimAdminController;

Route::middleware('auth')->group(function () {
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
    Route::get('/tracker/servers/{server}/claim', [\App\Http\Controllers\Frontend\TrackerClaimController::class, 'claimServer'])->name('tracker.claim.server');
    Route::post('/tracker/servers/{server}/claim', [\App\Http\Controllers\Frontend\TrackerClaimController::class, 'storeServerClaim'])->name('tracker.claim.server.store');
});

// Server live data API
Route::get('/tracker/servers/{server}/live', [\App\Http\Controllers\Frontend\TrackerController::class, 'serverLiveData'])->name('tracker.server.live');
require __DIR__.'/ettv-routes.php';
