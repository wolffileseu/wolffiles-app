<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::where('is_published', true)
            ->where('published_at', '<=', now())
            ->with('user')
            ->latest('published_at')
            ->paginate(12);

        $pinned = Post::where('is_published', true)
            ->where('published_at', '<=', now())
            ->where('is_pinned', true)
            ->with('user')
            ->latest('published_at')
            ->get();

        return view('frontend.posts.index', compact('posts', 'pinned'));
    }

    public function show(Post $post)
    {
        abort_unless($post->is_published, 404);
        $post->load(['user', 'comments.user', 'tags']);
        $post->increment('view_count');

        $seo = SeoService::forPost($post);
        $jsonLd = ['type' => 'post', 'post' => $post];

        return view('frontend.posts.show', compact('post', 'seo', 'jsonLd'));
    }
}
