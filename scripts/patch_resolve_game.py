#!/usr/bin/env python3
"""Make RelayController reject non-ET/RtCW servers and fall back to game_id."""

import datetime
import shutil
import sys

PATH = "app/Http/Controllers/Api/V1/Relay/RelayController.php"

with open(PATH, "r", encoding="utf-8") as fh:
    src = fh.read()

if "unsupported_game" in src:
    print("SKIP: patch already applied")
    sys.exit(0)

# --- 1. import the game model -------------------------------------------
imp_anchor = "use App\\Models\\Tracker\\TrackerServer;"
assert src.count(imp_anchor) == 1, "import anchor not unique"
src = src.replace(
    imp_anchor,
    "use App\\Models\\Tracker\\TrackerGame;\n" + imp_anchor,
)

# --- 2. reject unsupported games in connect() ---------------------------
call_anchor = """        $game = $this->resolveGame($server);

        $session = new RelaySession(["""
assert src.count(call_anchor) == 1, "connect() anchor not unique"
src = src.replace(
    call_anchor,
    """        $game = $this->resolveGame($server);

        if ($game === null) {
            return response()->json([
                'error' => 'unsupported_game',
                'message' => 'This server does not run a game the browser client supports.',
            ], 422);
        }

        $session = new RelaySession([""",
)

# --- 3. replace resolveGame() ------------------------------------------
old_method = """    /**
     * Map a tracked server onto the client build that can play it.
     */
    private function resolveGame(TrackerServer $server): string
    {
        $family = strtolower((string) $server->engine_family);

        return str_contains($family, 'rtcw') ? 'rtcw' : 'et';
    }"""

new_method = """    /**
     * Map a tracked server onto the client build that can play it.
     *
     * Returns null when the server runs something we have no WASM client
     * for (CoD, Quake 3) or is not a playable endpoint at all (ETTV).
     */
    private function resolveGame(TrackerServer $server): ?string
    {
        $family = strtolower((string) $server->engine_family);

        if ($family !== '') {
            foreach (['cod', 'quake3', 'ettv'] as $foreign) {
                if (str_starts_with($family, $foreign)) {
                    return null;
                }
            }

            if (str_starts_with($family, 'rtcw')) {
                return 'rtcw';
            }

            if (str_starts_with($family, 'et_')) {
                return 'et';
            }
        }

        // engine_family is only populated while a server is polled online,
        // so fall back to the game it is registered under.
        $slug = strtolower((string) TrackerGame::query()
            ->where('id', $server->game_id)
            ->value('slug'));

        if ($slug === '') {
            return null;
        }

        if (str_starts_with($slug, 'rtcw')) {
            return 'rtcw';
        }

        if (str_starts_with($slug, 'et')) {
            return 'et';
        }

        return null;
    }"""

assert src.count(old_method) == 1, "resolveGame() anchor not unique"
src = src.replace(old_method, new_method)

stamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
backup = "{}.bak.{}".format(PATH, stamp)
shutil.copy2(PATH, backup)
print("backup: {}".format(backup))

with open(PATH, "w", encoding="utf-8") as fh:
    fh.write(src)

print("OK: resolveGame patched")
