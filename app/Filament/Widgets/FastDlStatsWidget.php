<?php

namespace App\Filament\Widgets;

use App\Models\FastDl\FastDlFile;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FastDlStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $totalFiles = FastDlFile::where('is_active', true)->count();
        $totalSize = FastDlFile::where('is_active', true)->sum('file_size');
        $totalDownloads = FastDlFile::sum('download_count');

        $todayDls = DB::table('fastdl_downloads')
            ->whereDate('created_at', today())
            ->count();

        $weekDls = DB::table('fastdl_downloads')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        $monthDls = DB::table('fastdl_downloads')
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        // Estimated bandwidth (average PK3 size * downloads)
        $avgSize = FastDlFile::where('is_active', true)->avg('file_size') ?: 0;
        $monthBandwidth = $monthDls * $avgSize;

        return [
            Stat::make('FastDL Files', number_format($totalFiles))
                ->description(self::humanSize($totalSize) . ' total')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info'),
            Stat::make('Downloads Today', number_format($todayDls))
                ->description('This week: ' . number_format($weekDls))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
            Stat::make('Monthly Downloads', number_format($monthDls))
                ->description('~' . self::humanSize($monthBandwidth) . ' bandwidth')
                ->icon('heroicon-o-signal')
                ->color('warning'),
        ];
    }

    private static function humanSize(float $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
