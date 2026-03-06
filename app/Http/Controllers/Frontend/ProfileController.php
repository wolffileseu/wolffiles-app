<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $user->load(['badges']);
        if ($user->badges->isEmpty()) {
            $badgeIds = DB::table('badge_user')->where('user_id', $user->id)->pluck('badge_id');
            if ($badgeIds->isNotEmpty()) {
                $user->setRelation('badges', \App\Models\Badge::whereIn('id', $badgeIds)->get());
            }
        }
        $uploadHeatmap = $user->files()->where('status', 'approved')->selectRaw('DATE(created_at) as date, COUNT(*) as count')->groupBy('date')->pluck('count', 'date')->toArray();
        $uploads = $user->files()->where('status', 'approved')->with(['category', 'screenshots'])->orderByDesc('created_at')->paginate(12);
        $luaScripts = $user->luaScripts()->where('status', 'approved')->orderByDesc('created_at')->get();
        return view('frontend.profile.show', compact('user', 'uploads', 'luaScripts', 'uploadHeatmap'));
    }

    public function favorites()
    {
        $favorites = auth()->user()->favorites()->with(['file.category', 'file.screenshots'])->latest()->paginate(24);
        return view('frontend.profile.favorites', compact('favorites'));
    }

    public function myUploads()
    {
        $files = auth()->user()->files()->with(['category', 'screenshots'])->latest()->paginate(24);
        return view('frontend.profile.uploads', compact('files'));
    }

    public function settings()
    {
        return view('frontend.profile.settings');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'bio'     => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'discord_username' => 'nullable|string|max:100',
            'telegram_username' => 'nullable|string|max:100',
            'clan' => 'nullable|string|max:100',
            'favorite_games' => 'nullable|array',
            'locale'  => 'required|string|max:10',
        ]);
        auth()->user()->update(array_merge($request->only(['name', 'bio', 'website', 'locale', 'discord_username', 'telegram_username', 'clan']), ['favorite_games' => $request->input('favorite_games', [])]));
        ActivityLogger::profileUpdate(auth()->user(), $request->only(['name', 'bio', 'website', 'locale', 'discord_username', 'telegram_username', 'clan', 'favorite_games']));
        return back()->with('success', __('messages.settings_updated'));
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,gif|max:2048']);
        $user = auth()->user();
        if ($user->avatar && Storage::disk('s3')->exists($user->avatar)) {
            Storage::disk('s3')->delete($user->avatar);
        }
        $file   = $request->file('avatar');
        $ext    = strtolower($file->getClientOriginalExtension());
        $s3Path = 'avatars/user_' . $user->id . '.' . $ext;
        $stream = fopen($file->getRealPath(), 'r');
        Storage::disk('s3')->put($s3Path, $stream, 'public');
        if (is_resource($stream)) fclose($stream);
        $user->update(['avatar' => $s3Path]);
        ActivityLogger::profileUpdate($user, ['avatar' => $s3Path]);
        return back()->with('success', __('messages.avatar_updated') ?? 'Profilbild aktualisiert.');
    }

    public function deleteAvatar()
    {
        $user = auth()->user();
        if ($user->avatar && Storage::disk('s3')->exists($user->avatar)) {
            Storage::disk('s3')->delete($user->avatar);
        }
        $user->update(['avatar' => null]);
        return back()->with('success', __('messages.avatar_deleted') ?? 'Profilbild entfernt.');
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'key'   => 'required|string|in:comments,downloads,ratings,telegram,newsletter',
            'value' => 'required|in:0,1,true,false',
        ]);
        $user  = auth()->user();
        $prefs = $user->notification_preferences ?? [];
        $prefs[$request->key] = filter_var($request->value, FILTER_VALIDATE_BOOLEAN);
        $user->update(['notification_preferences' => $prefs]);
        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $request->validate(["password" => ["required", "current_password"]], [], ["password.userDeletion"]);
        $user = auth()->user();
        auth()->logout();
        if ($user->avatar && Storage::disk("s3")->exists($user->avatar)) { Storage::disk("s3")->delete($user->avatar); }
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect("/");
    }
}
