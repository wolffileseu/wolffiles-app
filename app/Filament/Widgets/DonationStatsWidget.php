<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use App\Models\DonationSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $monthlyGoal = (float) DonationSetting::get('monthly_goal', 50);
        $yearlyGoal = $monthlyGoal * 12;
        $yearlyTotal = Donation::completed()->whereYear('created_at', now()->year)->sum('amount');
        $yearlyPercent = $yearlyGoal > 0 ? round(($yearlyTotal / $yearlyGoal) * 100) : 0;
        $thisMonth = Donation::completed()->thisMonth()->sum('amount');

        return [
            Stat::make('Yearly Goal', '€' . number_format($yearlyTotal, 2) . ' / €' . number_format($yearlyGoal, 2))
                ->description($yearlyPercent . '% reached')
                ->icon('heroicon-o-heart')
                ->color($yearlyPercent >= 80 ? 'success' : ($yearlyPercent >= 40 ? 'warning' : 'danger')),
            Stat::make('This Month', '€' . number_format($thisMonth, 2))
                ->description(Donation::completed()->thisMonth()->count() . ' donations')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('All Time', '€' . number_format(Donation::completed()->sum('amount'), 2))
                ->description(Donation::completed()->count() . ' total donations')
                ->icon('heroicon-o-gift')
                ->color('info'),
        ];
    }
}
