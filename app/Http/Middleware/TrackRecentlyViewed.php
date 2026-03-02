<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackRecentlyViewed
{
    /**
     * Track recently viewed files in session.
     * Stores last 20 file IDs.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Add a file to recently viewed list.
     * Call from FileController::show()
     */
    public static function addFile(int $fileId): void
    {
        $viewed = session('recently_viewed', []);

        // Remove if already exists (to move to front)
        $viewed = array_diff($viewed, [$fileId]);

        // Add to front
        array_unshift($viewed, $fileId);

        // Keep only last 20
        $viewed = array_slice($viewed, 0, 20);

        session(['recently_viewed' => $viewed]);
    }

    /**
     * Get recently viewed file IDs.
     */
    public static function getRecentIds(int $limit = 10): array
    {
        return array_slice(session('recently_viewed', []), 0, $limit);
    }

    /**
     * Get recently viewed files as models.
     */
    public static function getRecentFiles(int $limit = 10)
    {
        $ids = self::getRecentIds($limit);

        if (empty($ids)) {
            return collect();
        }

        return \App\Models\File::whereIn('id', $ids)
            ->where('status', 'approved')
            ->with('screenshots')
            ->get()
            ->sortBy(function ($file) use ($ids) {
                return array_search($file->id, $ids);
            });
    }
}
