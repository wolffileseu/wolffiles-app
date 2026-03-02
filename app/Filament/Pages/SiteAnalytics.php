<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SiteAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.pages.site-analytics';

    public string $period = '7';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function getStartDate(): string
    {
        return now()->subDays((int)$this->period)->toDateString();
    }

    public function getTotals(): array
    {
        $start = $this->getStartDate();
        return [
            'pageviews' => DB::table('site_analytics')->where('created_at', '>=', $start)->count(),
            'unique_visitors' => DB::table('site_analytics')->where('created_at', '>=', $start)->where('is_unique_today', true)->count(),
            'today_pageviews' => DB::table('site_analytics')->where('created_at', '>=', today())->count(),
            'today_visitors' => DB::table('site_analytics')->where('created_at', '>=', today())->where('is_unique_today', true)->count(),
            'online' => Cache::get('online_count', 0),
            'all_time_visitors' => DB::table('site_stats')->where('key', 'total_visitors')->value('value') ?? 0,
            'all_time_pageviews' => DB::table('site_stats')->where('key', 'total_pageviews')->value('value') ?? 0,
        ];
    }

    public function getDailyChart(): array
    {
        $start = $this->getStartDate();
        $days = DB::table('site_analytics')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views, SUM(is_unique_today) as visitors')
            ->where('created_at', '>=', $start)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $days->pluck('date')->map(fn($d) => date('d.m', strtotime($d)))->toArray(),
            'views' => $days->pluck('views')->toArray(),
            'visitors' => $days->pluck('visitors')->toArray(),
        ];
    }

    public function getTopPages(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT ip) as unique_views')
            ->where('created_at', '>=', $start)
            ->where('path', 'not like', '/admin%')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getTopReferrers(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('referrer, COUNT(*) as visits')
            ->where('created_at', '>=', $start)
            ->whereNotNull('referrer')
            ->where('referrer', 'not like', '%wolffiles.eu%')
            ->where('referrer', 'not like', 'origin:%')
            ->where('referrer', '!=', '')
            ->groupBy('referrer')
            ->orderByDesc('visits')
            ->limit(15)
            ->get()
            ->map(function ($r) {
                $parsed = parse_url($r->referrer);
                $r->domain = $parsed['host'] ?? $r->referrer;
                return $r;
            })
            ->toArray();
    }

    public function getCountries(): array
    {
        $start = $this->getStartDate();
        // Keine hardcoded Liste nötig — countryFlag() generiert alle automatisch

        return DB::table('site_analytics')
            ->selectRaw('country, COUNT(*) as views, COUNT(DISTINCT ip) as visitors')
            ->where('created_at', '>=', $start)
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('views')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $c->flag = $this->countryFlag($c->country);
                return $c;
            })
            ->toArray();
    }

    public function getBrowsers(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('browser, COUNT(*) as count')
            ->where('created_at', '>=', $start)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function getDevices(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('device, COUNT(*) as count')
            ->where('created_at', '>=', $start)
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getLanguages(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('locale, COUNT(*) as count')
            ->where('created_at', '>=', $start)
            ->whereNotNull('locale')
            ->groupBy('locale')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    public function getCountryMapData(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('country, COUNT(DISTINCT ip) as visitors')
            ->where('created_at', '>=', $start)
            ->whereNotNull('country')
            ->groupBy('country')
            ->pluck('visitors', 'country')
            ->toArray();
    }

    public function getHeatmapPages(): array
    {
        $start = $this->getStartDate();
        return DB::table('heatmap_clicks')
            ->selectRaw('path, COUNT(*) as clicks')
            ->where('created_at', '>=', $start)
            ->groupBy('path')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getHeatmapData(string $path = '/'): array
    {
        $start = $this->getStartDate();
        return DB::table('heatmap_clicks')
            ->selectRaw('x_percent, y_px, COUNT(*) as intensity')
            ->where('created_at', '>=', $start)
            ->where('path', $path)
            ->groupByRaw('ROUND(x_percent, 0), ROUND(y_px / 20) * 20')
            ->orderByDesc('intensity')
            ->limit(500)
            ->get()
            ->map(fn($r) => ['x' => (float)$r->x_percent, 'y' => (int)$r->y_px, 'v' => (int)$r->intensity])
            ->toArray();
    }

    public function getTopClickedElements(): array
    {
        $start = $this->getStartDate();
        return DB::table('heatmap_clicks')
            ->selectRaw('element, path, COUNT(*) as clicks')
            ->where('created_at', '>=', $start)
            ->whereNotNull('element')
            ->groupBy('element', 'path')
            ->orderByDesc('clicks')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function countryFlag(?string $code): string
    {
        if (!$code || strlen($code) !== 2) return "🏳️";
        $code = strtoupper($code);
        // Unicode Regional Indicator Symbols: A=🇦 (U+1F1E6), B=🇧 (U+1F1E7), etc.
        $first = mb_chr(0x1F1E6 + ord($code[0]) - ord("A"));
        $second = mb_chr(0x1F1E6 + ord($code[1]) - ord("A"));
        return $first . $second;
    }

    public function getUtmSources(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('utm_source, COUNT(*) as visits, COUNT(DISTINCT ip) as visitors')
            ->where('created_at', '>=', $start)
            ->whereNotNull('utm_source')
            ->groupBy('utm_source')
            ->orderByDesc('visits')
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function getUtmCampaigns(): array
    {
        $start = $this->getStartDate();
        return DB::table('site_analytics')
            ->selectRaw('utm_campaign, utm_source, COUNT(*) as visits, COUNT(DISTINCT ip) as visitors')
            ->where('created_at', '>=', $start)
            ->whereNotNull('utm_campaign')
            ->groupBy('utm_campaign', 'utm_source')
            ->orderByDesc('visits')
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function getTrafficSources(): array
    {
        $start = $this->getStartDate();
        $total = DB::table('site_analytics')->where('created_at', '>=', $start)->count();
        if ($total === 0) return [];

        $direct = DB::table('site_analytics')
            ->where('created_at', '>=', $start)
            ->whereNull('referrer')
            ->whereNull('utm_source')
            ->count();

        $internal = DB::table('site_analytics')
            ->where('created_at', '>=', $start)
            ->where('referrer', 'like', '%wolffiles.eu%')
            ->count();

        $utm = DB::table('site_analytics')
            ->where('created_at', '>=', $start)
            ->whereNotNull('utm_source')
            ->count();

        $external = $total - $direct - $internal - $utm;

        return [
            ['label' => 'Direct', 'count' => $direct, 'pct' => round(($direct / $total) * 100), 'color' => '#3b82f6'],
            ['label' => 'Internal', 'count' => $internal, 'pct' => round(($internal / $total) * 100), 'color' => '#8b5cf6'],
            ['label' => 'UTM / Campaigns', 'count' => $utm, 'pct' => round(($utm / $total) * 100), 'color' => '#f59e0b'],
            ['label' => 'External', 'count' => max(0, $external), 'pct' => round((max(0, $external) / $total) * 100), 'color' => '#10b981'],
        ];
    }
}
