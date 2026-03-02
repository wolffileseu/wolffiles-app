<?php

// ═══════════════════════════════════════════════════════════════
// Add these routes to routes/web.php
// Place them BEFORE any catch-all routes
// ═══════════════════════════════════════════════════════════════

use App\Http\Controllers\Frontend\CampaignCreatorController;

// Tools Section
Route::prefix('tools')->name('tools.')->group(function () {
    Route::get('/campaign-creator', [CampaignCreatorController::class, 'index'])
        ->name('campaign-creator');
    Route::get('/campaign-creator/search-maps', [CampaignCreatorController::class, 'searchMaps'])
        ->name('campaign-creator.search-maps');
});
