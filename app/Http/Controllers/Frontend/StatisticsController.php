<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        // General stats
        $totalFiles = File::where('status', 'approved')->count();
        $totalDownloads = (int) File::sum('download_count');
        $totalUsers = User::count();
        $totalMaps = File::where('status', 'approved')
            ->whereHas('category', fn($q) => $q->where('name', 'like', '%Map%'))
            ->count();
        $totalComments = DB::table('comments')->count();
        $totalRatings = DB::table('ratings')->count();

        // Top 10 most downloaded files
        $topDownloaded = File::where('status', 'approved')
            ->where('download_count', '>', 0)
            ->with(['screenshots'])
            ->orderByDesc('download_count')
            ->limit(10)
            ->get();

        // Top 10 highest rated files
        $topRated = File::where('status', 'approved')
            ->where('rating_count', '>=', 1)
            ->with(['screenshots'])
            ->orderByDesc('average_rating')
            ->orderByDesc('rating_count')
            ->limit(10)
            ->get();

        // Top 10 most viewed
        $topViewed = File::where('status', 'approved')
            ->where('view_count', '>', 0)
            ->with(['screenshots'])
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        // Categories grouped by parent (like categories page)
        $categoryGroups = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                  ->withCount(['files as approved_files_count' => fn($q2) => $q2->where('status', 'approved')])
                  ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        // Calculate totals for parent categories
        foreach ($categoryGroups as $group) {
            $group->total_files = $group->children->sum('approved_files_count');
        }

        // Top uploaders
        $topUploaders = User::withCount(['files as approved_uploads_count' => fn($q) => $q->where('status', 'approved')])
            ->having('approved_uploads_count', '>', 0)
            ->orderByDesc('approved_uploads_count')
            ->limit(10)
            ->get();

        // Downloads last 30 days
        $downloadsByDay = DB::table('downloads')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Fill gaps in last 30 days
        $chartLabels = [];
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartLabels[] = now()->subDays($i)->format('d.m');
            $chartData[] = (int) ($downloadsByDay[$date] ?? 0);
        }

        // Uploads last 30 days
        $uploadsByDay = File::where('status', 'approved')
            ->where('published_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(published_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $uploadChartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $uploadChartData[] = (int) ($uploadsByDay[$date] ?? 0);
        }

        // Recent uploads (last 7 days)
        $recentUploads = File::where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Games distribution
        $gamesDistribution = File::where('status', 'approved')
            ->select('game', DB::raw('COUNT(*) as total'))
            ->groupBy('game')
            ->orderByDesc('total')
            ->get();

        return view('frontend.statistics', compact(
            'totalFiles', 'totalDownloads', 'totalUsers', 'totalMaps',
            'totalComments', 'totalRatings',
            'topDownloaded', 'topRated', 'topViewed',
            'categoryGroups', 'topUploaders',
            'chartLabels', 'chartData', 'uploadChartData',
            'recentUploads', 'gamesDistribution'
        ));
    }
}
