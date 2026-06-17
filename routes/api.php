<?php
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\FileUploadApiController;
use App\Http\Controllers\Api\FileApiController;
use Illuminate\Support\Facades\Route;

// Public API (for Discord bot etc.)
Route::prefix('v1')->middleware('throttle:1200,1')->group(function () {
    Route::get('/files/search', [FileApiController::class, 'search']);
    Route::get('/files/latest', [FileApiController::class, 'latest']);
    Route::get('/files/random', [FileApiController::class, 'random']);
    Route::get('/files/top', [FileApiController::class, 'top']);
    Route::get('/files/trending', [FileApiController::class, 'trending']);
    Route::get('/files/featured', [FileApiController::class, 'featured']);
    Route::get('/files/{file}', [FileApiController::class, 'show']);
    Route::get('/stats', [FileApiController::class, 'stats']);
    Route::get('/wiki/search', [FileApiController::class, 'wikiSearch']);
    Route::get('/tutorials/search', [FileApiController::class, 'tutorialSearch']);
});


Route::post('/heatmap', [\App\Http\Controllers\Api\HeatmapController::class, 'store'])->middleware('throttle:60,1');

Route::get('/heatmap-data', function (\Illuminate\Http\Request $request) {
    $path = $request->get('path', '/');
    $period = (int)$request->get('period', 7);

    // Validate inputs
    if (!is_string($path) || strlen($path) > 500) {
        return response()->json(['error' => 'Invalid path'], 400);
    }
    $period = max(1, min($period, 90));
	
    $start = now()->subDays($period)->toDateString();

    return \Illuminate\Support\Facades\DB::table('heatmap_clicks')
        ->selectRaw('x_percent as x, ROUND(y_px / 20) * 20 as y, COUNT(*) as v')
        ->where('created_at', '>=', $start)
        ->where('path', $path)
        ->groupByRaw('ROUND(x_percent, 0), ROUND(y_px / 20) * 20')
        ->orderByDesc('v')
        ->limit(500)
        ->get();
})->middleware('throttle:30,1');


// ── AUTH (öffentlich) ─────────────────────────────────────────
Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthApiController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.auth.login');

    Route::post('/auth/register', [AuthApiController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('api.auth.register');

    // ── AUTH (eingeloggt) ─────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/me', [AuthApiController::class, 'me'])
            ->name('api.auth.me');

        Route::post('/auth/logout', [AuthApiController::class, 'logout'])
            ->name('api.auth.logout');

        // Upload
        Route::post('/files', [FileUploadApiController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('api.files.store');

        Route::get('/files/my', [FileUploadApiController::class, 'myFiles'])
            ->name('api.files.my');

        // Multipart upload API for the desktop uploader and other Sanctum clients.
        // Mirrors /upload-api/* (which uses session auth for the web frontend).
        Route::prefix('upload-api')->name('api.upload.')->group(function () {
            Route::post('/init',     [\App\Http\Controllers\MultipartUploadController::class, 'init'])->name('init');
            Route::post('/sign',     [\App\Http\Controllers\MultipartUploadController::class, 'sign'])->name('sign');
            Route::post('/complete', [\App\Http\Controllers\MultipartUploadController::class, 'complete'])->name('complete');
            Route::post('/abort',    [\App\Http\Controllers\MultipartUploadController::class, 'abort'])->name('abort');
        });
    });

    // Kategorien (öffentlich)
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryApiController::class, 'index'])
        ->name('api.categories.index');

    // Uploader Tree (Game → Categories für Wolffiles Uploader Desktop App)
    Route::get('/uploader/tree', [\App\Http\Controllers\Api\UploaderTreeController::class, 'tree'])
        ->name('api.uploader.tree');

});


// Telegram Bot Webhook
Route::post('/telegram/webhook', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

// Clan Tool - Version Check (public)
Route::get("/v1/clan/version", [\App\Http\Controllers\Api\V1\ClanController::class, "version"]);

// Clan Tool - Authenticated Routes
Route::middleware(\App\Http\Middleware\ValidateClanApiKey::class)
    ->prefix("v1/clan")
    ->group(function () {
        Route::get("/me",             [\App\Http\Controllers\Api\V1\ClanController::class, "me"]);
        Route::post("/news",          [\App\Http\Controllers\Api\V1\ClanController::class, "postNews"]);
        Route::post("/event",         [\App\Http\Controllers\Api\V1\ClanController::class, "postEvent"]);
        Route::post("/match",         [\App\Http\Controllers\Api\V1\ClanController::class, "postMatch"]);
        Route::post("/recruitment",   [\App\Http\Controllers\Api\V1\ClanController::class, "postRecruitment"]);
    });

// Tracker API (public)
Route::prefix('v1/tracker')->middleware('throttle:1200,1')->group(function () {
    Route::get('/servers',         [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServers'])->name('tracker.api.servers');
    Route::get('/servers/top',     [\App\Http\Controllers\Frontend\TrackerController::class, 'apiTopServers'])->name('tracker.api.top-servers');
    Route::get('/stats',           [\App\Http\Controllers\Frontend\TrackerController::class, 'apiStats'])->name('tracker.api.stats');
    Route::get('/online',          [\App\Http\Controllers\Frontend\TrackerController::class, 'apiOnline'])->name('tracker.api.online');

    // --- Tracker API Phase 1: player combat depth (read-only) ---
    Route::get('/players/{id}/weapons',     [\App\Http\Controllers\Api\V1\Tracker\PlayerStatsController::class, 'weapons'])->name('tracker.api.player-weapons');
    Route::get('/players/{id}/stats',       [\App\Http\Controllers\Api\V1\Tracker\PlayerStatsController::class, 'stats'])->name('tracker.api.player-stats');
    Route::get('/players/{id}/elo-history', [\App\Http\Controllers\Api\V1\Tracker\PlayerStatsController::class, 'eloHistory'])->name('tracker.api.player-elo-history');
    Route::get('/players/{id}/daily',       [\App\Http\Controllers\Api\V1\Tracker\PlayerStatsController::class, 'daily'])->name('tracker.api.player-daily');
    Route::get('/players/{id}/aliases',     [\App\Http\Controllers\Api\V1\Tracker\PlayerStatsController::class, 'aliases'])->name('tracker.api.player-aliases');
    Route::get('/players/search',  [\App\Http\Controllers\Frontend\TrackerController::class, 'apiPlayerSearch'])->name('tracker.api.player-search');
    Route::get('/players/top',     [\App\Http\Controllers\Frontend\TrackerController::class, 'apiTopPlayers'])->name('tracker.api.top-players');
    Route::get('/players/{id}',    [\App\Http\Controllers\Frontend\TrackerController::class, 'apiPlayer'])->name('tracker.api.player');
    Route::get('/maps/{mapName}',  [\App\Http\Controllers\Frontend\TrackerController::class, 'apiMapStats'])->name('tracker.api.map-stats');
    Route::get('/rankings',        [\App\Http\Controllers\Frontend\TrackerExtendedController::class, 'apiRankings'])->name('tracker.api.rankings');

    // Per-server detail endpoints (backed by materialized snapshot tables)
    Route::get('/servers/{id}',              [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServerDetail'])->whereNumber('id')->name('tracker.api.server-detail');
    Route::get('/servers/{id}/rank',         [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServerRank'])->whereNumber('id')->name('tracker.api.server-rank');
    Route::get('/servers/{id}/top-players',  [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServerTopPlayers'])->whereNumber('id')->name('tracker.api.server-top-players');
    Route::get('/servers/{id}/online',       [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServerOnline'])->whereNumber('id')->name('tracker.api.server-online');
    Route::get('/servers/{id}/history',      [\App\Http\Controllers\Frontend\TrackerController::class, 'apiServerHistory'])->whereNumber('id')->name('tracker.api.server-history');
    Route::get('/clans',           [\App\Http\Controllers\Frontend\TrackerExtendedController::class, 'apiClans'])->name('tracker.api.clans');
});

