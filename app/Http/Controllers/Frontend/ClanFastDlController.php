<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FastDl\FastDlClan;
use App\Models\FastDl\FastDlClanFile;
use App\Models\FastDl\FastDlDirectory;
use App\Models\FastDl\FastDlGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClanFastDlController extends Controller
{
    public function index()
    {
        $clan = FastDlClan::where('leader_user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        if (!$clan) {
            // Check if user can create a clan
            if (auth()->user()->hasRole('clan_leader') || auth()->user()->hasRole('admin')) {
                $games = FastDlGame::where('is_active', true)->get();
                return view('frontend.fastdl.create-clan', compact('games'));
            }
            return view('frontend.fastdl.no-clan');
        }

        $game = $clan->game;
        $selectedDirs = $clan->selectedDirectories;
        $ownFiles = $clan->ownFiles()->orderBy('directory')->orderBy('filename')->get();

        $availableDirs = FastDlDirectory::where('game_id', $game->id)
            ->where('is_base', false)
            ->where('is_active', true)
            ->get();

        $storageUsed = $clan->ownFiles()->sum('file_size');
        $storageLimitBytes = $clan->storage_limit_mb * 1024 * 1024;
        $storagePercent = $storageLimitBytes > 0 ? min(100, round(($storageUsed / $storageLimitBytes) * 100)) : 0;

        return view('frontend.fastdl.clan-dashboard', compact(
            'clan', 'game', 'selectedDirs', 'ownFiles',
            'availableDirs', 'storageUsed', 'storageLimitBytes', 'storagePercent'
        ));
    }

    public function store(Request $request)
    {
        // Only clan_leader or admin can create
        if (!auth()->user()->hasRole('clan_leader') && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        // Check if user already has a clan
        if (FastDlClan::where('leader_user_id', auth()->id())->exists()) {
            return back()->with('error', __('messages.already_have_clan'));
        }

        $request->validate([
            'name' => 'required|string|max:50|min:2',
            'slug' => 'required|string|max:30|min:2|alpha_dash|unique:fastdl_clans,slug',
            'game_id' => 'required|exists:fastdl_games,id',
        ]);

        // Make sure slug doesn't conflict with game slugs
        $gameSlug = FastDlGame::where('slug', $request->slug)->exists();
        if ($gameSlug) {
            return back()->with('error', __('messages.slug_taken'))->withInput();
        }

        FastDlClan::create([
            'name' => $request->name,
            'slug' => Str::lower($request->slug),
            'game_id' => $request->game_id,
            'leader_user_id' => auth()->id(),
            'is_active' => true,
            'include_base' => true,
            'storage_limit_mb' => 500,
        ]);

        return redirect()->route('clan.fastdl')->with('success', __('messages.clan_created'));
    }

    public function updateDirectories(Request $request)
    {
        $clan = FastDlClan::where('leader_user_id', auth()->id())->firstOrFail();
        $dirIds = $request->input('directories', []);

        $validDirs = FastDlDirectory::where('game_id', $clan->game_id)
            ->where('is_base', false)
            ->where('is_active', true)
            ->whereIn('id', $dirIds)
            ->pluck('id');

        $clan->selectedDirectories()->sync($validDirs);
        return back()->with('success', __('messages.directories_updated'));
    }

    public function upload(Request $request)
    {
        $clan = FastDlClan::where('leader_user_id', auth()->id())->firstOrFail();

        $request->validate([
            'file' => 'required|file|max:102400',
            'directory' => 'required|string|max:50',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $directory = $request->input('directory');

        $currentUsage = $clan->ownFiles()->sum('file_size');
        $limitBytes = $clan->storage_limit_mb * 1024 * 1024;
        if (($currentUsage + $file->getSize()) > $limitBytes) {
            return back()->with('error', __('messages.storage_limit_reached'));
        }

        $s3Path = "fastdl/clans/{$clan->slug}/{$directory}/{$filename}";
        Storage::disk('s3')->put($s3Path, file_get_contents($file));

        FastDlClanFile::updateOrCreate(
            ['clan_id' => $clan->id, 'directory' => $directory, 'filename' => $filename],
            ['s3_path' => $s3Path, 'file_size' => $file->getSize(), 'is_active' => true]
        );

        $clan->update(['storage_used_mb' => round($clan->ownFiles()->sum('file_size') / 1024 / 1024)]);
        return back()->with('success', __('messages.file_uploaded'));
    }

    public function deleteFile(Request $request, FastDlClanFile $file)
    {
        $clan = FastDlClan::where('leader_user_id', auth()->id())->firstOrFail();
        if ($file->clan_id !== $clan->id) abort(403);

        Storage::disk('s3')->delete($file->s3_path);
        $file->delete();

        $clan->update(['storage_used_mb' => round($clan->ownFiles()->sum('file_size') / 1024 / 1024)]);
        return back()->with('success', __('messages.file_deleted'));
    }
}
