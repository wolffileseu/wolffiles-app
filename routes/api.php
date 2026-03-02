<?php

use App\Http\Controllers\Api\FileApiController;
use Illuminate\Support\Facades\Route;

// Public API (for Discord bot etc.)
Route::prefix('v1')->group(function () {
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
    $start = now()->subDays($period)->toDateString();

    return \Illuminate\Support\Facades\DB::table('heatmap_clicks')
        ->selectRaw('x_percent as x, ROUND(y_px / 20) * 20 as y, COUNT(*) as v')
        ->where('created_at', '>=', $start)
        ->where('path', $path)
        ->groupByRaw('ROUND(x_percent, 0), ROUND(y_px / 20) * 20')
        ->orderByDesc('v')
        ->limit(500)
        ->get();
});


// Telegram Bot Webhook
Route::post('/telegram/webhook', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);