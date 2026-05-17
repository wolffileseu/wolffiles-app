<?php

use App\Http\Controllers\Frontend\TestserverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Testserver Routes
|--------------------------------------------------------------------------
| Public testing server system - users can spawn a configured ET server
| with a chosen map+mod for X minutes via the web UI.
|
| Feature-Toggle: testserver_settings.feature_enabled
*/

Route::prefix('testserver')->name('testserver.')->group(function () {
    // Übersicht
    Route::get('/', [TestserverController::class, 'index'])
        ->name('index');

    // Launch-Wizard (Map + Mod auswählen)
    Route::get('/launch', [TestserverController::class, 'launch'])
        ->name('launch');

    // Reservation (POST mit JSON-Response)
    Route::post('/reserve', [TestserverController::class, 'reserve'])
        ->name('reserve');

    // Connect-Page für eine konkrete Session
    Route::get('/s/{token}', [TestserverController::class, 'show'])
        ->name('show')
        ->where('token', '[a-f0-9-]{36}');

    // AJAX-Status-Polling
    Route::get('/s/{token}/status', [TestserverController::class, 'status'])
        ->name('status')
        ->where('token', '[a-f0-9-]{36}');

    // Session abbrechen (POST)
    Route::post('/s/{token}/cancel', [TestserverController::class, 'cancel'])
        ->name('cancel')
        ->where('token', '[a-f0-9-]{36}');
});
