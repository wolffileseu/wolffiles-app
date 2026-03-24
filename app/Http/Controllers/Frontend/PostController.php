<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Clan;
use App\Models\Post;
use App\Services\SeoService;

class PostController extends Controller
{
    public function index()
    {
        $query = Post::where("is_published", true)
            ->where("published_at", "<=", now())
            ->with(["user", "clan", "tags"])
            ->latest("published_at");

        // Filter
        if (request("type")) {
            $query->where("type", request("type"));
        }
        if (request("clan")) {
            $query->where("clan_id", request("clan"));
        }

        $posts  = $query->paginate(12)->withQueryString();
        $pinned = Post::where("is_published", true)
            ->where("published_at", "<=", now())
            ->where("is_pinned", true)
            ->with(["user", "clan"])
            ->latest("published_at")
            ->get();

        $clans = Clan::where("is_active", true)->orderBy("name")->get();
        $types = Post::TYPES;

        return view("frontend.posts.index", compact("posts", "pinned", "clans", "types"));
    }

    public function show(Post $post)
    {
        abort_unless($post->is_published, 404);
        $post->load(["user", "clan", "comments.user", "tags"]);
        $post->increment("view_count");
        $seo    = SeoService::forPost($post);
        $jsonLd = ["type" => "post", "post" => $post];
        return view("frontend.posts.show", compact("post", "seo", "jsonLd"));
    }
}
