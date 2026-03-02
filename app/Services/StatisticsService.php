<?php

namespace App\Services;

use App\Models\File;
use App\Models\DownloadStat;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * #27 Record a download for daily statistics.
     */
    public static function recordDownload(int $fileId): void
    {
        $today = now()->toDateString();

        DB::table('download_stats')->updateOrInsert(
            ['file_id' => $fileId, 'date' => $today],
            ['count' => DB::raw('count + 1'), 'updated_at' => now()]
        );
    }

    /**
     * #27 Get download history for a file (last N days).
     */
    public static function getDownloadHistory(int $fileId, int $days = 30): array
    {
        $stats = DB::table('download_stats')
            ->where('file_id', $fileId)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill gaps
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result[$date] = $stats[$date] ?? 0;
        }

        return $result;
    }

    /**
     * #27 Get top downloaded files in a time range.
     */
    public static function getTopDownloaded(int $days = 7, int $limit = 10): \Illuminate\Support\Collection
    {
        return DB::table('download_stats')
            ->select('file_id', DB::raw('SUM(count) as total_downloads'))
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->groupBy('file_id')
            ->orderByDesc('total_downloads')
            ->limit($limit)
            ->get()
            ->map(function ($stat) {
                $file = File::with('screenshots')->find($stat->file_id);
                if ($file) {
                    $file->period_downloads = $stat->total_downloads;
                }
                return $file;
            })
            ->filter();
    }

    /**
     * #27 Get global download stats per day.
     */
    public static function getGlobalDownloadHistory(int $days = 30): array
    {
        $stats = DB::table('download_stats')
            ->select('date', DB::raw('SUM(count) as total'))
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result[$date] = (int)($stats[$date] ?? 0);
        }

        return $result;
    }

    /**
     * #31 Calculate trending scores.
     * Score = (recent_downloads * 2 + recent_views) / age_in_hours^0.5
     */
    public static function calculateTrendingScores(): int
    {
        // Pre-fetch all recent download counts in one query
        $recentDownloads = DB::table('download_stats')
            ->where('date', '>=', now()->subDays(3)->toDateString())
            ->select('file_id', DB::raw('SUM(count) as total'))
            ->groupBy('file_id')
            ->pluck('total', 'file_id')
            ->toArray();

        $updated = 0;

        // Reset scores for files older than 30 days
        File::where('status', 'approved')
            ->where('created_at', '<', now()->subDays(30))
            ->where('trending_score', '>', 0)
            ->update(['trending_score' => 0]);

        // Process in chunks to avoid memory issues
        File::where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('id', 'created_at', 'view_count')
            ->chunk(200, function ($files) use ($recentDownloads, &$updated) {
                foreach ($files as $file) {
                    $downloads = $recentDownloads[$file->id] ?? 0;
                    $ageHours = max(1, $file->created_at->diffInHours(now()));
                    $score = ($downloads * 2 + ($file->view_count ?? 0) * 0.1) / pow($ageHours, 0.5);

                    DB::table('files')->where('id', $file->id)
                        ->update(['trending_score' => round($score, 4)]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * #31 Get trending files.
     */
    public static function getTrending(int $limit = 10): \Illuminate\Support\Collection
    {
        return File::where('status', 'approved')
            ->where('trending_score', '>', 0)
            ->with(['screenshots', 'category', 'user'])
            ->orderByDesc('trending_score')
            ->limit($limit)
            ->get();
    }

    /**
     * #29 Get content statistics for admin.
     */
    public static function getContentStats(): array
    {
        return [
            'total_files' => File::count(),
            'approved_files' => File::where('status', 'approved')->count(),
            'pending_files' => File::where('status', 'pending')->count(),
            'rejected_files' => File::where('status', 'rejected')->count(),
            'total_downloads' => File::sum('download_count'),
            'total_views' => File::sum('view_count'),
            'avg_rating' => round(File::where('rating_count', '>', 0)->avg('average_rating') ?? 0, 2),
            'uploads_today' => File::whereDate('created_at', today())->count(),
            'uploads_this_week' => File::where('created_at', '>=', now()->startOfWeek())->count(),
            'uploads_this_month' => File::where('created_at', '>=', now()->startOfMonth())->count(),
            'downloads_today' => DB::table('download_stats')->where('date', today()->toDateString())->sum('count'),
            'downloads_this_week' => DB::table('download_stats')->where('date', '>=', now()->startOfWeek()->toDateString())->sum('count'),
            'top_categories' => DB::table('files')
                ->join('categories', 'files.category_id', '=', 'categories.id')
                ->where('files.status', 'approved')
                ->select('categories.name', DB::raw('COUNT(*) as count'))
                ->groupBy('categories.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
    }
}
