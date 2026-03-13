<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ApiStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today     = DB::table('api_request_logs')->whereDate('created_at', today())->count();
        $week      = DB::table('api_request_logs')->where('created_at', '>=', now()->subWeek())->count();
        $month     = DB::table('api_request_logs')->where('created_at', '>=', now()->subMonth())->count();
        $avgMs     = (int) DB::table('api_request_logs')->where('created_at', '>=', now()->subDay())->avg('response_ms');

        $topEndpoint = DB::table('api_request_logs')
            ->select('endpoint', DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('endpoint')
            ->orderByDesc('total')
            ->first();

        $topClient = DB::table('api_request_logs')
            ->select('client_type', DB::raw('count(*) as total'))
            ->where('created_at', '>=', now()->subWeek())
            ->groupBy('client_type')
            ->orderByDesc('total')
            ->first();

        return [
            Stat::make('API Calls Today', number_format($today))
                ->description('Requests today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('This Week', number_format($week))
                ->description('Last 7 days')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('This Month', number_format($month))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Avg Response', $avgMs . ' ms')
                ->description('Last 24h average')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($avgMs < 100 ? 'success' : ($avgMs < 300 ? 'warning' : 'danger')),

            Stat::make('Top Endpoint', $topEndpoint ? str_replace('api/v1/', '', $topEndpoint->endpoint) : '—')
                ->description($topEndpoint ? number_format($topEndpoint->total) . ' calls this week' : 'No data')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),

            Stat::make('Top Client', $topClient ? $topClient->client_type : '—')
                ->description($topClient ? number_format($topClient->total) . ' calls this week' : 'No data')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('gray'),
        ];
    }
}
