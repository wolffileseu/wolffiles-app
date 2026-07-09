<?php

namespace App\Http\Controllers\Frontend;

use App\Models\File;
use App\Models\LuaScript;
use App\Models\Post;
use App\Models\Demo;
use App\Models\Tutorial;
use App\Models\WikiArticle;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Notifications\NewComment;
use App\Services\ActivityLogger;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
        ]);

        // Map short names to full class names, but also accept full class names
        $typeMap = [
            'file' => File::class,
            'lua_script' => LuaScript::class,
            'post' => Post::class,
            // Also accept full class names directly
            'App\Models\File' => File::class,
            'App\Models\LuaScript' => LuaScript::class,
            'App\Models\Post' => Post::class,
        ];

        $requestType = $request->commentable_type;
        $morphClass = $typeMap[$requestType] ?? null;

        if (!$morphClass) {
            return back()->withErrors(['commentable_type' => 'Invalid type.']);
        }

        $commentable = $morphClass::findOrFail($request->commentable_id);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'commentable_type' => $morphClass,
            'commentable_id' => $commentable->id,
            'body' => $request->body,
            'is_approved' => true,
        ]);

        ActivityLogger::comment($comment, $commentable);
        /** @var File|Demo|Tutorial|WikiArticle $commentable */
        // Notify file/content owner about new comment
        if ($commentable->user && $commentable->user->id !== auth()->id()) {
            $title = $commentable->title ?? $commentable->name ?? 'your content';
            $url = method_exists($commentable, 'getUrlAttribute') 
                ? $commentable->url 
                : (($commentable instanceof File) ? route('files.show', $commentable) : url('/'));
            $commentable->user->notify(new NewComment($comment, $title, $url));
        }

        return back()->with('success', __('messages.comment_posted'));
    }

    public function destroy(Comment $comment)
    {
        abort_unless(auth()->id() === $comment->user_id || auth()->user()->isModerator(), 403);
        ActivityLogger::commentDelete($comment);
        $comment->delete();
        return back()->with('success', __('messages.comment_deleted'));
    }
}
