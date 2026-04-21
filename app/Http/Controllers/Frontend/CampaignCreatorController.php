<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;

class CampaignCreatorController extends Controller
{
    /**
     * Hardcoded stock maps (ET + RtCW) — always available, no PK3 download needed.
     * Sending these inline (instead of loading all 3,400+ maps from DB) keeps the
     * page payload at ~20KB instead of 505KB.
     * Custom maps are loaded on-demand via searchMaps().
     */
    private const STOCK_MAPS = [
        ['mapname' => 'battery',      'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'oasis',        'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'fueldump',     'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'goldrush',     'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'railgun',      'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'radar',        'pk3' => 'pak0.pk3', 'game' => 'ET'],
        ['mapname' => 'mp_beach',     'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_sub',       'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'beach',        'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'assault',      'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'village',      'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'destruction',  'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'chateau',      'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'depot',        'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'tram',         'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'ice',          'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'base',         'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_castle',    'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_depot',     'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_village',   'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_assault',   'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_dam',       'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
        ['mapname' => 'mp_rocket',    'pk3' => 'pak0.pk3', 'game' => 'RtCW'],
    ];

    public function index()
    {
        $stockMaps = collect(self::STOCK_MAPS)->map(fn ($m) => [
            'id'       => null,
            'mapname'  => $m['mapname'],
            'pk3'      => $m['pk3'],
            'title'    => ucfirst($m['mapname']),
            'slug'     => null,
            'game'     => $m['game'],
            'is_stock' => true,
        ])->values();

        // Fast COUNT(*) — no rows loaded
        $mapCount = File::query()
            ->whereNotNull('map_name')
            ->where('map_name', '!=', '')
            ->where('status', 'approved')
            ->count();

        $games = collect(['ET', 'RtCW', 'ETFortress', 'ET-Domination'])->values();

        return view('frontend.tools.campaign-creator', [
            'maps'     => $stockMaps,
            'mapCount' => $mapCount,
            'games'    => $games,
            'seo'      => [
                'title'       => __('messages.cc_seo_title'),
                'description' => __('messages.cc_seo_description', ['count' => $mapCount]),
                'canonical'   => route('tools.campaign-creator'),
            ],
        ]);
    }

    public function searchMaps(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $maps = File::query()
            ->whereNotNull('map_name')
            ->where('map_name', '!=', '')
            ->where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('map_name', 'LIKE', "%{$query}%")
                  ->orWhere('title', 'LIKE', "%{$query}%")
                  ->orWhere('file_name', 'LIKE', "%{$query}%");
            })
            ->select('id', 'title', 'map_name', 'file_name', 'slug', 'game')
            ->orderBy('map_name')
            ->limit(50)
            ->get()
            ->map(function ($file) {
                $cleanMapName = $this->stripColorCodes($file->map_name);
                return [
                    'id'       => $file->id,
                    'mapname'  => $cleanMapName,
                    'pk3'      => $this->guessPk3Name($file->file_name, $cleanMapName),
                    'title'    => $file->title,
                    'slug'     => $file->slug,
                    'game'     => $file->game ?? 'ET',
                    'is_stock' => $this->isStockMap($cleanMapName),
                ];
            })
            ->filter(fn ($m) => !empty($m['mapname']))
            ->unique('mapname')
            ->values();

        return response()->json($maps);
    }

    private function stripColorCodes(string $text): string
    {
        return trim(preg_replace('/\^[0-9a-zA-Z]/', '', $text));
    }

    private function guessPk3Name(string $fileName, string $mapName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext === 'pk3') return $fileName;
        if ($this->isStockMap($mapName)) return 'pak0.pk3';
        return strtolower($mapName) . '.pk3';
    }

    private function isStockMap(string $mapName): bool
    {
        return in_array(strtolower($mapName), array_column(self::STOCK_MAPS, 'mapname'), true);
    }
}
