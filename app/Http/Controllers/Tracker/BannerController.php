<?php

namespace App\Http\Controllers\Tracker;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerServer;
use App\Services\Banner\PlayerBannerRenderer;
use App\Services\Banner\ServerBannerRenderer;
use App\Services\Banner\ServerEmbedDataService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    /**
     * Dynamic PNG banner for a tracker server.
     *
     * Cache is base64-wrapped: Laravel file-cache serializes values and raw
     * PNG binary interacts poorly with downstream middleware (session, cookie
     * encryption). base64 keeps it ASCII-safe.
     */
    public function server(TrackerServer $server): Response
    {
        $encoded = Cache::remember(
            "banner:server:{$server->id}:v1",
            now()->addSeconds(30),
            fn () => base64_encode((new ServerBannerRenderer($server))->render())
        );
        $png = base64_decode($encoded);

        return response($png, 200, [
            'Content-Type'   => 'image/png',
            'Cache-Control'  => 'public, max-age=30, stale-while-revalidate=60',
            'Content-Length' => (string) strlen($png),
        ]);
    }

    /**
     * Dynamic PNG banner for a tracker player (forum signature style).
     */
    public function player(TrackerPlayer $player): Response
    {
        $encoded = Cache::remember(
            "banner:player:{$player->id}:v1",
            now()->addSeconds(30),
            fn () => base64_encode((new PlayerBannerRenderer($player))->render())
        );
        $png = base64_decode($encoded);

        return response($png, 200, [
            'Content-Type'   => 'image/png',
            'Cache-Control'  => 'public, max-age=30, stale-while-revalidate=60',
            'Content-Length' => (string) strlen($png),
        ]);
    }

    /**
     * Vertical HTML embed banner (iframe-friendly).
     * Returns a Blade view rendered with full server + players data.
     * X-Frame-Options is removed so external sites can embed it.
     */
    public function serverEmbed(TrackerServer $server, \Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        $width = max(200, min(600, (int) $request->query('w', 240)));
        $opts = [
            'show_map'       => (bool) $request->query('map', 1),
            'show_current'   => (bool) $request->query('cur', 1),
            'show_top'       => (bool) $request->query('top', 1),
            'width'          => $width,
        ];

        $data = Cache::remember(
            "banner:server:{$server->id}:embed:v1",
            now()->addSeconds(30),
            fn () => (new ServerEmbedDataService())->collect($server)
        );

        $html = view('frontend.tracker.partials.server-embed', [
            'd'    => $data,
            'opts' => $opts,
        ])->render();

        return response($html, 200, [
            'Content-Type'    => 'text/html; charset=utf-8',
            'Cache-Control'   => 'public, max-age=30, stale-while-revalidate=60',
            'X-Frame-Options' => 'ALLOWALL',
        ]);
    }
}
