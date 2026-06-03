<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\SeoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index()
    {
        // Neuestes File-Update als Last-Modified-Basis (1 günstiger Query)
        $lastModRaw = File::where('status', 'approved')->max('updated_at');
        $lastModified = $lastModRaw ? Carbon::parse($lastModRaw) : now();

        // 304 Not Modified: spart Google den vollen Sitemap-Download
        $ifModSince = request()->header('If-Modified-Since');
        if ($ifModSince && strtotime($ifModSince) >= $lastModified->timestamp) {
            return response('', 304)
                ->header('Last-Modified', $lastModified->toRfc7231String());
        }

        // Cache-Key enthält Timestamp -> auto-invalidiert bei neuem File-Approve
        $cacheKey = 'sitemap.urls.' . $lastModified->timestamp;
        $urls = Cache::remember($cacheKey, 86400, function () {
            return SeoService::getSitemapUrls();
        });

        return response()
            ->view('frontend.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=21600')
            ->header('Last-Modified', $lastModified->toRfc7231String());
    }
}
