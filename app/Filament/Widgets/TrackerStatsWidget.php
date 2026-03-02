<?php

namespace App\Filament\Widgets;

use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerPlayer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrackerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $onlineServers = TrackerServer::where('is_online', true)->count();
        $totalServers = TrackerServer::active()->count();
        $onlinePlayers = TrackerServer::where('is_online', true)->sum('current_players');

        return [
            Stat::make('Servers Online', $onlineServers . ' / ' . $totalServers)
                ->description('Active servers being tracked')
                ->icon('heroicon-o-server-stack')
                ->color('success'),
            Stat::make('Players Online', number_format($onlinePlayers))
                ->description('Currently playing')
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Players Tracked', number_format(TrackerPlayer::count()))
                ->description(TrackerPlayer::where('last_seen_at', '>=', now()->subDay())->count() . ' active today')
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}
