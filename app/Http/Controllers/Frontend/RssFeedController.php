<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function files(): Response
    {
        $files = File::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->with(['category', 'user'])
            ->limit(50)
            ->get();

        $content = view('frontend.rss.files', compact('files'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
