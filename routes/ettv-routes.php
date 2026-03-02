<?php

use App\Http\Controllers\EttvController;
use App\Http\Controllers\EventController;

Route::prefix('ettv')->name('ettv.')->group(function () {
    Route::get('/', [EttvController::class, 'index'])->name('index');
    Route::post('/watch/{demo}', [EttvController::class, 'watchDemo'])->middleware('auth')->name('watch');
});

Route::prefix('events')->name('events.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/create', [EventController::class, 'create'])->middleware('auth')->name('create');
    Route::post('/', [EventController::class, 'store'])->middleware('auth')->name('store');
    Route::get('/{event:slug}', [EventController::class, 'show'])->name('show');
});
