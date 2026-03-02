<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;

class CampaignCreatorController extends Controller
{
    public function index()
    {
        $maps = File::query()
            ->whereNotNull('map_name')
            ->where('map_name', '!=', '')
            ->where('status', 'approved')
            ->select('id', 'title', 'map_name', 'file_name', 'slug', 'game')
            ->orderBy('map_name')
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
            ->sortBy(fn ($map) => ($map['is_stock'] ? '0' : '1') . strtolower($map['mapname']))
            ->values();

        $games = $maps->pluck('game')->unique()->filter()->sort()->values();

        return view('frontend.tools.campaign-creator', [
            'maps'     => $maps,
            'mapCount' => $maps->count(),
            'games'    => $games,
            'seo'      => [
                'title'       => __('messages.cc_seo_title'),
                'description' => __('messages.cc_seo_description', ['count' => $maps->count()]),
                'canonical'   => route('tools.campaign-creator'),
            ],
        ]);
    }

    public function searchMaps(Request $request)
    {
        $query = $request->input('q', '');

        $maps = File::query()
            ->whereNotNull('map_name')
            ->where('map_name', '!=', '')
            ->where('status', 'approved')
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('map_name', 'LIKE', "%{$query}%")
                       ->orWhere('title', 'LIKE', "%{$query}%")
                       ->orWhere('file_name', 'LIKE', "%{$query}%");
                });
            })
            ->select('id', 'title', 'map_name', 'file_name', 'slug', 'game')
            ->orderBy('map_name')
            ->limit(100)
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
        return in_array(strtolower($mapName), [
            'battery', 'oasis', 'fueldump', 'goldrush', 'railgun', 'radar',
            'mp_beach', 'mp_sub', 'beach', 'assault', 'village', 'destruction',
            'chateau', 'depot', 'tram', 'ice', 'base', 'mp_castle', 'mp_depot',
            'mp_village', 'mp_assault', 'mp_dam', 'mp_rocket',
        ]);
    }
}
