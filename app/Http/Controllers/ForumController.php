<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForumController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::root()
            ->with(['children.threads', 'threads'])
            ->get();

        return view('forum.index', compact('categories'));
    }

    public function category(ForumCategory $category)
    {
        $threads = $category->threads()
            ->sorted()
            ->with(['user', 'lastPostUser'])
            ->withCount('posts')
            ->paginate(20);

        $subcategories = $category->children()->get();

        return view('forum.category', compact('category', 'threads', 'subcategories'));
    }

    public function thread(ForumCategory $category, ForumThread $thread)
    {
        $thread->incrementViews();

        $posts = $thread->posts()
            ->with('user')
            ->orderBy('created_at')
            ->paginate(15);

        return view('forum.thread', compact('category', 'thread', 'posts'));
    }

    public function createThread(ForumCategory $category)
    {
        abort_if($category->is_locked, 403, __('messages.forum_category_locked'));

        return view('forum.create-thread', compact('category'));
    }

    public function storeThread(Request $request, ForumCategory $category)
    {
        abort_if($category->is_locked, 403);

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'body' => 'required|string|min:10|max:50000',
        ]);

        $thread = $category->threads()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . Str::random(5),
            'last_post_at' => now(),
            'last_post_user_id' => auth()->id(),
        ]);

        $thread->posts()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('forum.thread', [$category, $thread])
            ->with('success', __('messages.forum_thread_created'));
    }

    public function storePost(Request $request, ForumCategory $category, ForumThread $thread)
    {
        abort_if($thread->is_locked, 403, __('messages.forum_thread_locked'));

        $validated = $request->validate([
            'body' => 'required|string|min:3|max:50000',
        ]);

        $thread->posts()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('forum.thread', [$category, $thread, 'page' => $thread->posts()->paginate(15)->lastPage()])
            ->with('success', __('messages.forum_reply_posted'));
    }

    public function editPost(ForumPost $post)
    {
        abort_unless($post->canEdit(auth()->user()), 403);

        return view('forum.edit-post', compact('post'));
    }

    public function updatePost(Request $request, ForumPost $post)
    {
        abort_unless($post->canEdit(auth()->user()), 403);

        $validated = $request->validate([
            'body' => 'required|string|min:3|max:50000',
        ]);

        $post->update([
            'body' => $validated['body'],
            'edited_at' => now(),
            'edited_by' => auth()->id(),
        ]);

        return redirect()
            ->route('forum.thread', [$post->thread->category, $post->thread])
            ->with('success', __('messages.forum_post_updated'));
    }

    public function deletePost(ForumPost $post)
    {
        abort_unless($post->canDelete(auth()->user()), 403);

        if ($post->is_first_post) {
            $category = $post->thread->category;
            $post->thread->posts()->delete();
            $post->thread->delete();
            return redirect()
                ->route('forum.category', $category)
                ->with('success', __('messages.forum_thread_deleted'));
        }

        $post->delete();
        return back()->with('success', __('messages.forum_post_deleted'));
    }

    public function togglePin(ForumThread $thread)
    {
        abort_unless(ForumPost::canModerate(auth()->user()), 403);

        $thread->update(['is_pinned' => !$thread->is_pinned]);

        $msg = $thread->is_pinned ? __('messages.forum_thread_pinned') : __('messages.forum_thread_unpinned');
        return back()->with('success', $msg);
    }

    public function toggleLock(ForumThread $thread)
    {
        abort_unless(ForumPost::canModerate(auth()->user()), 403);

        $thread->update(['is_locked' => !$thread->is_locked]);

        $msg = $thread->is_locked ? __('messages.forum_thread_locked_msg') : __('messages.forum_thread_unlocked');
        return back()->with('success', $msg);
    }

    public function moveThread(Request $request, ForumThread $thread)
    {
        abort_unless(ForumPost::canModerate(auth()->user()), 403);

        $validated = $request->validate([
            'forum_category_id' => 'required|exists:forum_categories,id',
        ]);

        $thread->update(['forum_category_id' => $validated['forum_category_id']]);

        return back()->with('success', __('messages.forum_thread_moved'));
    }

    public function deleteThread(ForumThread $thread)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $category = $thread->category;
        $thread->posts()->delete();
        $thread->delete();

        return redirect()
            ->route('forum.category', $category)
            ->with('success', __('messages.forum_thread_deleted'));
    }
}
