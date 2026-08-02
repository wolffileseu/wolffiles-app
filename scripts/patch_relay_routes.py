#!/usr/bin/env python3
"""Append the relay route group to routes/api.php (idempotent)."""

import datetime
import shutil
import sys

PATH = "routes/api.php"
MARKER = "v1/relay"

ROUTES = """
// Relay nodes for the browser (WASM) client -- ticket issuing + agent callbacks
Route::prefix('v1/relay')->group(function () {
    Route::post('/connect',   [\\App\\Http\\Controllers\\Api\\V1\\Relay\\RelayController::class, 'connect'])
        ->middleware('throttle:30,1')
        ->name('relay.api.connect');

    Route::post('/heartbeat', [\\App\\Http\\Controllers\\Api\\V1\\Relay\\RelayController::class, 'heartbeat'])
        ->middleware('throttle:600,1')
        ->name('relay.api.heartbeat');

    Route::post('/session',   [\\App\\Http\\Controllers\\Api\\V1\\Relay\\RelayController::class, 'session'])
        ->middleware('throttle:1200,1')
        ->name('relay.api.session');
});
"""

with open(PATH, "r", encoding="utf-8") as fh:
    src = fh.read()

if MARKER in src:
    print("SKIP: relay routes already present in routes/api.php")
    sys.exit(0)

stamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
backup = "{}.bak.{}".format(PATH, stamp)
shutil.copy2(PATH, backup)
print("backup: {}".format(backup))

new = src.rstrip() + "\n" + ROUTES

with open(PATH, "w", encoding="utf-8") as fh:
    fh.write(new)

print("OK: relay routes appended")
