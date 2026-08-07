<?php

namespace App\Http\Controllers\Frontend;

use Exception;
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
    public function index(Request $request)
    {
        $userId = auth()->id();

        $clans = FastDlClan::managedBy($userId)
            ->where('is_active', true)
            ->with('game')
            ->get();

        $canCreate = auth()->user()->hasRole('clan_leader') || auth()->user()->hasRole('admin');

        // Games the user does not yet have a Fast Download for
        $usedGameIds = $clans->pluck('game_id')->all();
        $availableGames = FastDlGame::where('is_active', true)
            ->whereNotIn('id', $usedGameIds)
            ->get();

        // No Fast Download yet -> show create form (or "no access" notice)
        if ($clans->isEmpty()) {
            if ($canCreate) {
                return view('frontend.fastdl.create-clan', [
                    'games' => $availableGames,
                    'hasClans' => false,
                ]);
            }
            return view('frontend.fastdl.no-clan');
        }

        // User explicitly wants to add a Fast Download for another game
        if ($request->boolean('create') && $canCreate && $availableGames->isNotEmpty()) {
            return view('frontend.fastdl.create-clan', [
                'games' => $availableGames,
                'hasClans' => true,
            ]);
        }

        // Pick the active Fast Download: by ?game=slug, otherwise the first one
        $clan = null;
        if ($request->filled('game')) {
            $wanted = $request->get('game');
            $clan = $clans->first(fn ($c) => $c->game && $c->game->slug === $wanted);
        }
        $clan = $clan ?: $clans->first();

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
            'availableDirs', 'storageUsed', 'storageLimitBytes', 'storagePercent',
            'clans', 'availableGames', 'canCreate'
        ));
    }

    public function store(Request $request)
    {
        // Only clan_leader or admin can create
        if (!auth()->user()->hasRole('clan_leader') && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:50|min:2',
            'slug' => 'required|string|max:30|min:2|alpha_dash|unique:fastdl_clans,slug',
            'game_id' => 'required|exists:fastdl_games,id',
        ]);

        // One Fast Download per user *and* game (not per user globally)
        $alreadyForGame = FastDlClan::where('leader_user_id', auth()->id())
            ->where('game_id', $request->game_id)
            ->exists();
        if ($alreadyForGame) {
            return back()->with('error', __('messages.already_have_clan'))->withInput();
        }

        // Make sure slug doesn't conflict with game slugs
        $gameSlug = FastDlGame::where('slug', $request->slug)->exists();
        if ($gameSlug) {
            return back()->with('error', __('messages.slug_taken'))->withInput();
        }

        $clan = FastDlClan::create([
            'name' => $request->name,
            'slug' => Str::lower($request->slug),
            'game_id' => $request->game_id,
            'leader_user_id' => auth()->id(),
            'is_active' => true,
            'include_base' => true,
            'storage_limit_mb' => 500,
        ]);

        return redirect()
            ->route('clan.fastdl', ['game' => $clan->game->slug])
            ->with('success', __('messages.clan_created'));
    }

    public function updateDirectories(Request $request)
    {
        $clan = $this->resolveClan($request);
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
        $clan = $this->resolveClan($request);

        $isMultipart = $request->filled('file_s3_key');

        $rules = ['directory' => 'required|string|max:50'];
        if ($isMultipart) {
            $rules['file_s3_key'] = 'required|string|max:500';
            $rules['file_filename'] = 'required|string|max:255';
            $rules['file_size'] = 'required|integer|min:1';
        } else {
            $rules['file'] = 'required|file|max:102400';
        }

        $request->validate($rules);

        $directory = $request->input('directory');

        if ($isMultipart) {
            $filename = $request->input('file_filename');
            $fileSize = (int) $request->input('file_size');
            $tempS3Key = $request->input('file_s3_key');
        } else {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }

        $currentUsage = $clan->ownFiles()->sum('file_size');
        $limitBytes = $clan->storage_limit_mb * 1024 * 1024;
        if (($currentUsage + $fileSize) > $limitBytes) {
            return back()->with('error', __('messages.storage_limit_reached'));
        }

        $finalS3Path = "fastdl/clans/{$clan->slug}/{$directory}/{$filename}";

        if ($isMultipart) {
            // File ist schon in S3 unter fastdl/uploads/... -> wir kopieren zur finalen Location
            try {
                Storage::disk('s3')->copy($tempS3Key, $finalS3Path);
                Storage::disk('s3')->delete($tempS3Key);
            } catch (Exception $e) {
                return back()->with('error', 'S3 copy failed: ' . $e->getMessage());
            }
        } else {
            Storage::disk('s3')->put($finalS3Path, file_get_contents($file));
        }

        FastDlClanFile::updateOrCreate(
            ['clan_id' => $clan->id, 'directory' => $directory, 'filename' => $filename],
            ['s3_path' => $finalS3Path, 'file_size' => $fileSize, 'is_active' => true]
        );

        $clan->update(['storage_used_mb' => round($clan->ownFiles()->sum('file_size') / 1024 / 1024)]);
        return back()->with('success', __('messages.file_uploaded'));
    }

    public function deleteFile(Request $request, FastDlClanFile $file)
    {
        $clan = FastDlClan::managedBy()
            ->where('id', $file->clan_id)
            ->firstOrFail();

        Storage::disk('s3')->delete($file->s3_path);
        $file->delete();

        $clan->update(['storage_used_mb' => round($clan->ownFiles()->sum('file_size') / 1024 / 1024)]);
        return back()->with('success', __('messages.file_deleted'));
    }

    /**
     * Resolve the Fast Download space the request acts on.
     * Uses the posted clan_id (scoped to the current user) when present,
     * otherwise falls back to the user's first space.
     */
    protected function resolveClan(Request $request): FastDlClan
    {
        $query = FastDlClan::managedBy()
            ->where('is_active', true);

        if ($request->filled('clan_id')) {
            $query->where('id', $request->input('clan_id'));
        }

        return $query->firstOrFail();
    }
}
