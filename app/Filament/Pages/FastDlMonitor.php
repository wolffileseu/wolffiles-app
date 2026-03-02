<?php

namespace App\Filament\Pages;

use App\Models\FastDl\FastDlFile;
use App\Models\FastDl\FastDlGame;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class FastDlMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Monitor';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.fastdl-monitor';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_fastdl_monitor');
    }

    public function getStats(): array
    {
        return [
            'total_files' => FastDlFile::where('is_active', true)->count(),
            'total_size' => FastDlFile::where('is_active', true)->sum('file_size'),
            'total_downloads' => FastDlFile::sum('download_count'),
            'today' => DB::table('fastdl_downloads')->whereDate('created_at', today())->count(),
            'week' => DB::table('fastdl_downloads')->where('created_at', '>=', now()->subWeek())->count(),
            'month' => DB::table('fastdl_downloads')->where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    public function getTopFiles(): \Illuminate\Support\Collection
    {
        return FastDlFile::where('download_count', '>', 0)
            ->with('directory.game')
            ->orderByDesc('download_count')
            ->limit(20)
            ->get();
    }

    public function getDailyStats(): \Illuminate\Support\Collection
    {
        return DB::table('fastdl_downloads')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getGameStats(): \Illuminate\Support\Collection
    {
        return FastDlGame::where('is_active', true)
            ->get()
            ->map(function ($game) {
                $game->file_count = FastDlFile::whereHas('directory', fn($q) => $q->where('game_id', $game->id))
                    ->where('is_active', true)->count();
                $game->total_size = FastDlFile::whereHas('directory', fn($q) => $q->where('game_id', $game->id))
                    ->where('is_active', true)->sum('file_size');
                $game->total_dls = FastDlFile::whereHas('directory', fn($q) => $q->where('game_id', $game->id))
                    ->sum('download_count');
                return $game;
            });
    }

    public function getRecentDownloads(): \Illuminate\Support\Collection
    {
        return DB::table('fastdl_downloads')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }
}
