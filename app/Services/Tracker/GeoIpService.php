<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    /**
     * Lookup geo information for an IP address.
     * Uses ip-api.com (free, 45 req/min) with 24h cache.
     */
    public static function lookup(string $ip): ?array
    {
        // Skip private/local IPs
        if (self::isPrivateIp($ip)) {
            return null;
        }

        $cacheKey = "geoip:{$ip}";

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,countryCode,city,lat,lon',
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (($data['status'] ?? '') === 'success') {
                        return [
                            'country' => $data['country'] ?? null,
                            'country_code' => $data['countryCode'] ?? null,
                            'city' => $data['city'] ?? null,
                            'latitude' => $data['lat'] ?? null,
                            'longitude' => $data['lon'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("GeoIP lookup failed for {$ip}: {$e->getMessage()}");
            }

            return null;
        });
    }

    /**
     * Batch lookup (respects rate limit: max 45/min for ip-api.com).
     */
    public static function batchLookup(array $ips): array
    {
        $results = [];
        $uncached = [];

        // Check cache first
        foreach ($ips as $ip) {
            $cached = Cache::get("geoip:{$ip}");
            if ($cached !== null) {
                $results[$ip] = $cached;
            } else {
                $uncached[] = $ip;
            }
        }

        // Batch API call for uncached IPs (ip-api.com supports batch)
        if (!empty($uncached)) {
            try {
                $chunks = array_chunk($uncached, 100); // Max 100 per batch call

                foreach ($chunks as $chunk) {
                    $payload = array_map(fn($ip) => ['query' => $ip, 'fields' => 'status,country,countryCode,city,lat,lon'], $chunk);

                    $response = Http::timeout(10)->post('http://ip-api.com/batch', $payload);

                    if ($response->successful()) {
                        foreach ($response->json() as $i => $data) {
                            $ip = $chunk[$i];
                            if (($data['status'] ?? '') === 'success') {
                                $geo = [
                                    'country' => $data['country'] ?? null,
                                    'country_code' => $data['countryCode'] ?? null,
                                    'city' => $data['city'] ?? null,
                                    'latitude' => $data['lat'] ?? null,
                                    'longitude' => $data['lon'] ?? null,
                                ];
                                Cache::put("geoip:{$ip}", $geo, 86400);
                                $results[$ip] = $geo;
                            }
                        }
                    }

                    // Rate limit: sleep between chunks
                    if (count($chunks) > 1) {
                        usleep(500000); // 0.5s
                    }
                }
            } catch (\Exception $e) {
                Log::warning("GeoIP batch lookup failed: {$e->getMessage()}");
            }
        }

        return $results;
    }

    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
