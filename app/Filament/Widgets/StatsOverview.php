<?php

namespace App\Filament\Widgets;

use App\Models\File;
use App\Models\Download;
use App\Models\User;
use App\Models\LuaScript;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Files', File::where('status', 'approved')->count())
                ->description('Approved files in database')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success'),
            Stat::make('Pending Review', File::where('status', 'pending')->count() + LuaScript::where('status', 'pending')->count())
                ->description('Files awaiting moderation')
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Total Downloads', number_format(File::sum('download_count')))
                ->description('All time downloads')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary'),
            Stat::make('Registered Users', User::count())
                ->description(User::where('created_at', '>=', now()->subDays(30))->count() . ' new this month')
                ->icon('heroicon-o-users')
                ->color('info'),
        ];
    }
}
