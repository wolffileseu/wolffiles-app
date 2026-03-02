<?php

namespace App\Filament\Widgets;

use App\Services\StatisticsService;
use Filament\Widgets\ChartWidget;

class DownloadChart extends ChartWidget
{
    protected static ?string $heading = 'Downloads (Last 30 Days)';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $history = StatisticsService::getGlobalDownloadHistory(30);

        return [
            'datasets' => [
                [
                    'label' => 'Downloads',
                    'data' => array_values($history),
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => array_map(fn ($d) => date('d.m', strtotime($d)), array_keys($history)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
