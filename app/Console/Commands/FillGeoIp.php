<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FillGeoIp extends Command
{
    protected $signature = 'analytics:fill-geoip {--limit=100}';
    protected $description = 'Fill missing GeoIP data for analytics';

    public function handle()
    {
        // 1. Resolve pending IPs
        $pending = Cache::get('geo_pending', []);
        $resolved = 0;

        foreach (array_keys($pending) as $ip) {
            $cacheKey = 'geo_' . md5($ip);
            if (Cache::has($cacheKey)) continue;

            try {
                $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false,
                    stream_context_create(['http' => ['timeout' => 2]]));
                if ($response) {
                    $data = json_decode($response, true);
                    $country = $data['countryCode'] ?? null;
                    if ($country) {
                        Cache::put($cacheKey, $country, now()->addWeek());
                        
                        // Update alle Einträge mit dieser anonymisierten IP
                        $anonIp = preg_replace('/\.\d+$/', '.0', $ip);
                        DB::table('site_analytics')
                            ->where('ip', $anonIp)
                            ->whereNull('country')
                            ->update(['country' => $country]);
                        $resolved++;
                    }
                }
            } catch (\Exception $e) {}

            usleep(200000); // 200ms — ip-api rate limit (45/min)
        }

        // Cache leeren
        Cache::forget('geo_pending');
        
        $remaining = DB::table('site_analytics')->whereNull('country')->count();
        $this->info("Resolved {$resolved} IPs. {$remaining} still missing.");
    }
}
