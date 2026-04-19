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
    public function player(TrackerPlayer $player, \Illuminate\Http\Request $request): Response
    {
        $variant = max(1, min(4, (int) $request->query('variant', 1)));

        $encoded = Cache::remember(
            "banner:player:{$player->id}:v2:variant{$variant}",
            now()->addSeconds(30),
            fn () => base64_encode((new PlayerBannerRenderer($player, $variant))->render())
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

        // Cache the fully rendered HTML (60s) — saves both the data queries
        // AND the Blade rendering on every iframe load. Cache key includes
        // width so different embed sizes don't collide.
        $cacheKey = "banner:server:{$server->id}:embed:html:w{$width}:v2";
        $html = Cache::remember(
            $cacheKey,
            now()->addSeconds(120),
            function () use ($server, $opts) {
                $data = (new ServerEmbedDataService())->collect($server);
                return view('frontend.tracker.partials.server-embed', [
                    'd'    => $data,
                    'opts' => $opts,
                ])->render();
            }
        );

        return response($html, 200, [
            'Content-Type'    => 'text/html; charset=utf-8',
            'Cache-Control'   => 'public, max-age=30, stale-while-revalidate=300, s-maxage=60',
        ]);
    }

    /**
     * Vertical HTML embed banner for a player (iframe-friendly).
     * Supports ?variant=1-4 (matches PNG banner variants) and ?w=200-600.
     */
    public function playerEmbed(\App\Models\Tracker\TrackerPlayer $player, \Illuminate\Http\Request $request): \Illuminate\Http\Response
    {
        $width   = max(200, min(600, (int) $request->query('w', 240)));
        $variant = max(1, min(4, (int) $request->query('variant', 1)));

        $cacheKey = "banner:player:{$player->id}:embed:html:w{$width}:variant{$variant}:v1";
        $html = Cache::remember(
            $cacheKey,
            now()->addSeconds(120),
            function () use ($player, $width, $variant) {
                $data = (new \App\Services\Banner\PlayerEmbedDataService())->collect($player);
                return view('frontend.tracker.partials.player-embed', [
                    'd'       => $data,
                    'width'   => $width,
                    'variant' => $variant,
                ])->render();
            }
        );

        return response($html, 200, [
            'Content-Type'  => 'text/html; charset=utf-8',
            'Cache-Control' => 'public, max-age=30, stale-while-revalidate=300, s-maxage=60',
        ]);
    }

}
