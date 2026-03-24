<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClanController extends Controller
{
    private function getClan(Request $request)
    {
        return $request->get("_clan");
    }

    public function version()
    {
        return response()->json([
            "version"      => config("clan-tool.version", "1.0.0"),
            "download_url" => config("clan-tool.download_url", null),
            "changelog"    => config("clan-tool.changelog", ""),
            "force_update" => config("clan-tool.force_update", false),
        ]);
    }

    public function me(Request $request)
    {
        $clan = $this->getClan($request);
        return response()->json([
            "id"   => $clan->id,
            "name" => $clan->name,
            "tag"  => $clan->tag,
            "logo" => $clan->logo ? asset("storage/" . $clan->logo) : null,
        ]);
    }

    public function postNews(Request $request)
    {
        $validated = $request->validate([
            "title"   => "required|string|max:255",
            "content" => "required|string",
            "excerpt" => "nullable|string|max:500",
        ]);
        return $this->createPost($request, Post::TYPE_NEWS, $validated);
    }

    public function postEvent(Request $request)
    {
        $validated = $request->validate([
            "title"          => "required|string|max:255",
            "content"        => "required|string",
            "excerpt"        => "nullable|string|max:500",
            "event_date"     => "required|date",
            "event_location" => "nullable|string|max:255",
        ]);
        return $this->createPost($request, Post::TYPE_EVENT, $validated);
    }

    public function postMatch(Request $request)
    {
        $validated = $request->validate([
            "title"          => "required|string|max:255",
            "content"        => "required|string",
            "match_opponent" => "required|string|max:100",
            "match_result"   => "nullable|string|max:50",
            "match_map"      => "nullable|string|max:100",
            "event_date"     => "nullable|date",
        ]);
        return $this->createPost($request, Post::TYPE_MATCH, $validated);
    }

    public function postRecruitment(Request $request)
    {
        $validated = $request->validate([
            "title"                    => "required|string|max:255",
            "content"                  => "required|string",
            "excerpt"                  => "nullable|string|max:500",
            "recruitment_requirements" => "nullable|array",
        ]);
        return $this->createPost($request, Post::TYPE_RECRUITMENT, $validated);
    }

    private function createPost(Request $request, string $type, array $data): \Illuminate\Http\JsonResponse
    {
        $clan = $this->getClan($request);

        $post = Post::create(array_merge($data, [
            "clan_id"      => $clan->id,
            "user_id"      => 1,
            "type"         => $type,
            "slug"         => Str::slug($data["title"]) . "-" . Str::random(6),
            "is_published" => false,
            "published_at" => now(),
        ]));

        return response()->json([
            "success" => true,
            "message" => "Post submitted and pending review.",
            "post_id" => $post->id,
        ], 201);
    }
}
