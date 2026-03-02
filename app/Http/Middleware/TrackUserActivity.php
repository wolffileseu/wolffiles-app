<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip bots, assets, livewire
        $path = $request->path();
        if ($this->shouldSkip($path, $request)) {
            return $next($request);
        }

        // Eingeloggte User: DB updaten
        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->last_activity_at || $user->last_activity_at->diffInMinutes(now()) >= 5) {
                $user->update(['last_activity_at' => now()]);
            }
        }

        // Online tracking
        $sessionId = $request->session()->getId() ?: md5($request->ip());
        $keys = Cache::get('online_visitors', []);
        $keys[$sessionId] = now()->timestamp;
        $threshold = now()->subMinutes(15)->timestamp;
        $keys = array_filter($keys, fn($time) => $time >= $threshold);
        Cache::put('online_visitors', $keys, now()->addHours(1));
        Cache::put('online_count', count($keys), now()->addHours(1));

        // All-Time Pageviews
        DB::table('site_stats')->where('key', 'total_pageviews')->increment('value');

        // All-Time Unique Visitors
        $todayKey = 'visited_' . md5($request->ip() . date('Y-m-d'));
        $isUniqueToday = false;
        if (!Cache::has($todayKey)) {
            Cache::put($todayKey, true, now()->endOfDay());
            DB::table('site_stats')->where('key', 'total_visitors')->increment('value');
            $isUniqueToday = true;
        }

        // Analytics erfassen (async via queue oder direkt)
        try {
            $ua = $request->userAgent() ?? '';
            DB::table('site_analytics')->insert([
                'ip' => $this->anonymizeIp($request->ip()),
                'country' => $this->getCountryCached($request->ip()),
                'path' => substr('/' . ltrim($path, '/'), 0, 500),
                'referrer' => $this->getReferer($request),
                'utm_source' => $request->get('utm_source') ? substr($request->get('utm_source'), 0, 100) : null,
                'utm_medium' => $request->get('utm_medium') ? substr($request->get('utm_medium'), 0, 100) : null,
                'utm_campaign' => $request->get('utm_campaign') ? substr($request->get('utm_campaign'), 0, 100) : null,
                'utm_content' => $request->get('utm_content') ? substr($request->get('utm_content'), 0, 100) : null,
                'browser' => $this->parseBrowser($ua),
                'os' => $this->parseOs($ua),
                'device' => $this->parseDevice($ua),
                'locale' => app()->getLocale(),
                'user_id' => auth()->id(),
                'is_unique_today' => $isUniqueToday,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail — Analytics darf nie die Seite blocken
        }

        return $next($request);
    }

    private function getReferer(Request $request): ?string
    {
        // 1. UTM source (z.B. ?utm_source=reddit)
        if ($request->has('utm_source')) {
            return 'utm:' . substr($request->get('utm_source'), 0, 100);
        }

        // 2. Referrer Header
        $ref = $request->header('referer');
        if ($ref) {
            return substr($ref, 0, 500);
        }

        // 3. Android/iOS App Referrer (manchmal in anderen Headers)
        $origin = $request->header('origin');
        if ($origin && !str_contains($origin, 'wolffiles.eu')) {
            return 'origin:' . substr($origin, 0, 200);
        }

        return null;
    }

    private function shouldSkip(string $path, Request $request): bool
    {
        // Skip assets, livewire, API, admin assets
        if (str_starts_with($path, 'livewire/')) return true;
        if (str_starts_with($path, '_debugbar/')) return true;
        if (str_starts_with($path, 'build/')) return true;
        if (str_starts_with($path, 'storage/')) return true;
        if (preg_match('/\.(css|js|jpg|png|gif|ico|svg|woff|woff2|ttf)$/', $path)) return true;
        if ($request->isMethod('POST') && str_contains($path, 'livewire')) return true;
        return false;
    }

    private function anonymizeIp(?string $ip): string
    {
        if (!$ip) return '0.0.0.0';
        // Letztes Oktett entfernen für DSGVO
        if (str_contains($ip, '.')) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        // IPv6
        return preg_replace('/:[^:]+$/', ':0', $ip);
    }



    private function parseBrowser(string $ua): ?string
    {
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Edg/')) return 'Edge';
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Safari')) return 'Safari';
        if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) return 'Opera';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) return 'IE';
        if (str_contains($ua, 'bot') || str_contains($ua, 'Bot') || str_contains($ua, 'crawl')) return 'Bot';
        return substr($ua, 0, 50) ?: null;
    }

    private function parseOs(string $ua): ?string
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac OS')) return 'macOS';
        if (str_contains($ua, 'Linux') && !str_contains($ua, 'Android')) return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'CrOS')) return 'ChromeOS';
        return null;
    }

    private function parseDevice(string $ua): ?string
    {
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android')) return 'Mobile';
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) return 'Tablet';
        if (str_contains($ua, 'bot') || str_contains($ua, 'Bot') || str_contains($ua, 'crawl')) return 'Bot';
        return 'Desktop';
    }
    protected function getCountryCached(?string $ip): ?string
    {
        if (!$ip) return null;
        return cache()->remember("country_{$ip}", 86400, function () use ($ip) {
            return $this->getCountry($ip);
        });
    }

    protected function getCountry(?string $ip): ?string
    {
        if (!$ip) return null;
        try {
            $response = @file_get_contents("https://ipapi.co/{$ip}/country/");
            return $response && strlen($response) === 2 ? strtoupper($response) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
