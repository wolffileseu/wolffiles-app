<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        // Load badges - try relationship first, fallback to pivot table
        if (true) {
            $user->load(['badges']);
        }

        // If badges relationship returned empty, try loading from badge_user pivot
        if ($user->badges->isEmpty()) {
            $badgeIds = DB::table('badge_user')->where('user_id', $user->id)->pluck('badge_id');
            if ($badgeIds->isNotEmpty()) {
                $user->setRelation('badges', \App\Models\Badge::whereIn('id', $badgeIds)->get());
            }
        }

        $uploads = $user->files()
            ->where('status', 'approved')
            ->with(['category', 'screenshots'])
            ->orderByDesc('created_at')
            ->paginate(12);

        $luaScripts = $user->luaScripts()
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();

        return view('frontend.profile.show', compact('user', 'uploads', 'luaScripts'));
    }

    public function favorites()
    {
        $favorites = auth()->user()->favorites()
            ->with(['file.category', 'file.screenshots'])
            ->latest()
            ->paginate(24);

        return view('frontend.profile.favorites', compact('favorites'));
    }

    public function myUploads()
    {
        $files = auth()->user()->files()
            ->with(['category', 'screenshots'])
            ->latest()
            ->paginate(24);

        return view('frontend.profile.uploads', compact('files'));
    }

    public function settings()
    {
        return view('frontend.profile.settings');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'locale' => 'required|in:en,de',
        ]);

        auth()->user()->update($request->only(['name', 'bio', 'website', 'locale']));

        ActivityLogger::profileUpdate(auth()->user(), $request->only(['name', 'bio', 'website', 'locale']));

        return back()->with('success', __('messages.settings_updated'));
    }
}
