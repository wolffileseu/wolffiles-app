<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverview extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = StatisticsService::getContentStats();
        $downloadHistory = StatisticsService::getGlobalDownloadHistory(7);

        return [
            Stat::make('Total Files', number_format($stats['total_files']))
                ->description($stats['uploads_today'] . ' today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Pending Review', $stats['pending_files'])
                ->description('Awaiting moderation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['pending_files'] > 0 ? 'warning' : 'success'),

            Stat::make('Total Downloads', number_format($stats['total_downloads']))
                ->description($stats['downloads_today'] . ' today')
                ->chart(array_values($downloadHistory))
                ->color('primary'),

            Stat::make('Avg. Rating', $stats['avg_rating'] . ' ★')
                ->description('From rated files')
                ->color('warning'),

            Stat::make('Uploads This Week', $stats['uploads_this_week'])
                ->description($stats['uploads_this_month'] . ' this month')
                ->color('info'),

            Stat::make('Downloads This Week', number_format($stats['downloads_this_week']))
                ->color('success'),
        ];
    }
}
