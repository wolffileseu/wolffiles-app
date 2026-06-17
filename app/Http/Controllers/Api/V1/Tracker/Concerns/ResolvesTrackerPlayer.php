<?php

namespace App\Http\Controllers\Api\V1\Tracker\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ResolvesTrackerPlayer
{
    /**
     * Resolve a player by id, following merges and hiding hidden profiles.
     */
    protected function resolvePlayer(int $id): ?object
    {
        $player = DB::table('tracker_players')->where('id', $id)->first();
        if (! $player) {
            return null;
        }

        if (! is_null($player->merged_into)) {
            $target = DB::table('tracker_players')->where('id', $player->merged_into)->first();
            if ($target) {
                $player = $target;
            }
        }

        if (($player->status ?? null) === 'hidden') {
            return null;
        }

        return $player;
    }

    protected function limit(Request $request, int $default, int $max): int
    {
        $n = (int) $request->query('limit', $default);

        return max(1, min($n, $max));
    }
}
