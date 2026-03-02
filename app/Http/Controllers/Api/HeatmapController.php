<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapController extends Controller
{
    public function store(Request $request)
    {
        $clicks = json_decode($request->getContent(), true);
        if (!is_array($clicks) || count($clicks) > 50) {
            return response()->json(['ok' => false], 400);
        }

        $rows = [];
        foreach ($clicks as $click) {
            $rows[] = [
                'path' => substr($click['path'] ?? '/', 0, 500),
                'x_percent' => min(100, max(0, (float)($click['x'] ?? 0))),
                'y_px' => min(50000, max(0, (int)($click['y'] ?? 0))),
                'element' => substr($click['el'] ?? '', 0, 200),
                'viewport_width' => (int)($click['w'] ?? 0),
                'ip' => $request->ip(),
                'created_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('heatmap_clicks')->insert($rows);
        }

        return response()->json(['ok' => true]);
    }
}
