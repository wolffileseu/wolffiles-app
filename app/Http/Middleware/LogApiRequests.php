<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (int) ((microtime(true) - $start) * 1000);

        // Async logging – nie den Request verlangsamen
        try {
            $ua = $request->userAgent() ?? '';
            $client = match(true) {
                str_contains($ua, 'Discordbot') || str_contains($ua, 'discord') => 'discord_bot',
                str_contains($ua, 'TelegramBot')                                => 'telegram_bot',
                str_contains($ua, 'curl')                                       => 'curl',
                str_contains($ua, 'python-requests') || str_contains($ua, 'Python') => 'python',
                str_contains($ua, 'Mozilla') || str_contains($ua, 'Chrome')    => 'browser',
                $ua === ''                                                       => 'unknown',
                default                                                         => 'other',
            };

            // IP anonymisieren (GDPR) – nur Hash speichern
            $ipHash = hash('sha256', $request->ip() . config('app.key'));

            // Query String kürzen + API Key rausfiltern falls vorhanden
            $qs = $request->getQueryString();
            if ($qs && strlen($qs) > 500) {
                $qs = substr($qs, 0, 500);
            }

            DB::table('api_request_logs')->insert([
                'endpoint'     => '/' . ltrim($request->path(), '/'),
                'method'       => $request->method(),
                'ip_hash'      => $ipHash,
                'user_agent'   => substr($ua, 0, 255),
                'client_type'  => $client,
                'response_ms'  => min($ms, 65535),
                'status_code'  => $response->getStatusCode(),
                'query_string' => $qs,
                'created_at'   => now(),
            ]);
        } catch (\Throwable) {
            // Logging darf nie den API Request brechen
        }

        return $response;
    }
}
