<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SeoService;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = SeoService::getSitemapUrls();

        return response()
            ->view('frontend.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }
}
