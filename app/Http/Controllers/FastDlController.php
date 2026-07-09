<?php

namespace App\Http\Controllers;

use App\Models\FastDl\FastDlClan;
use App\Models\FastDl\FastDlClanFile;
use App\Models\FastDl\FastDlDirectory;
use App\Models\FastDl\FastDlFile;
use App\Models\FastDl\FastDlGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FastDlController extends Controller
{
    /**
     * Serve file: dl.wolffiles.eu/{game}/{directory}/{filename}
     * Or clan:    dl.wolffiles.eu/{clan}/{directory}/{filename}
     */
    public function serve(Request $request, string $first, string $directory, string $filename)
    {
        // Try clan first
        $clan = FastDlClan::where('slug', $first)->where('is_active', true)->first();

        if ($clan) {
            return $this->serveClanFile($clan, $directory, $filename);
        }

        // Try game
        /** @var FastDlGame|null $game */
        $game = FastDlGame::where('slug', $first)->where('is_active', true)->first();
        if (!$game) {
            abort(404);
        }

        return $this->serveGameFile($game, $directory, $filename);
    }

    private function serveGameFile(FastDlGame $game, string $dirSlug, string $filename)
    {
        /** @var FastDlDirectory|null $dir */
        $dir = FastDlDirectory::where('game_id', $game->id)
            ->where('slug', $dirSlug)
            ->where('is_active', true)
            ->first();

        if (!$dir) abort(404);

        $file = FastDlFile::where('directory_id', $dir->id)
            ->where('filename', $filename)
            ->where('is_active', true)
            ->first();

        if (!$file) abort(404);

        // Increment download count
        $file->increment('download_count');

        // Log download
        DB::table('fastdl_downloads')->insert([
            'path' => "{$game->slug}/{$dirSlug}/{$filename}",
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Redirect to S3
        return redirect(Storage::disk('s3')->temporaryUrl(
            $file->s3_path,
            now()->addMinutes(10),
            ['ResponseContentDisposition' => 'attachment; filename="' . $file->filename . '"']
        ));
    }

    private function serveClanFile(FastDlClan $clan, string $dirSlug, string $filename)
    {
        $game = $clan->game;

        // 1. Check clan's own files first
        $clanFile = FastDlClanFile::where('clan_id', $clan->id)
            ->where('directory', $dirSlug)
            ->where('filename', $filename)
            ->where('is_active', true)
            ->first();

        if ($clanFile) {
            DB::table('fastdl_downloads')->insert([
                'path' => "{$clan->slug}/{$dirSlug}/{$filename}",
                'ip' => request()->ip(),
                'clan_id' => $clan->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return redirect(Storage::disk('s3')->temporaryUrl(
                $clanFile->s3_path,
                now()->addMinutes(10),
                ['ResponseContentDisposition' => 'attachment; filename="' . $clanFile->filename . '"']
            ));
        }

        // 2. Check base directory (if include_base)
        if ($clan->include_base) {
            $baseDir = $game->directories()->where('slug', $dirSlug)->first();
            if ($baseDir) {
                $file = FastDlFile::where('directory_id', $baseDir->id)
                    ->where('filename', $filename)
                    ->where('is_active', true)
                    ->first();

                if ($file) {
                    $file->increment('download_count');
                    return redirect(Storage::disk('s3')->temporaryUrl(
            $file->s3_path,
            now()->addMinutes(10),
            ['ResponseContentDisposition' => 'attachment; filename="' . $file->filename . '"']
        ));
                }
            }
        }

        // 3. Check selected directories
        $selectedDirIds = $clan->selectedDirectories()->pluck('fastdl_directories.id');
        /** @var FastDlDirectory|null $dir */
        $dir = FastDlDirectory::where('game_id', $game->id)
            ->where('slug', $dirSlug)
            ->whereIn('id', $selectedDirIds)
            ->first();

        if ($dir) {
            $file = FastDlFile::where('directory_id', $dir->id)
                ->where('filename', $filename)
                ->where('is_active', true)
                ->first();

            if ($file) {
                $file->increment('download_count');
                return redirect(Storage::disk('s3')->temporaryUrl(
            $file->s3_path,
            now()->addMinutes(10),
            ['ResponseContentDisposition' => 'attachment; filename="' . $file->filename . '"']
        ));
            }
        }

        abort(404);
    }

    /**
     * Index page showing available games
     */
    public function index()
    {
        $games = FastDlGame::where('is_active', true)->withCount('directories')->get();
        return response()->json(['games' => $games]);
    }

    /**
     * List files in a directory: dl.wolffiles.eu/{game}/{directory}/
     */
    public function listDirectory(Request $request, string $first, string $directory)
    {
        // Try clan
        $clan = FastDlClan::where('slug', $first)->where('is_active', true)->first();
        if ($clan) {
            $game = $clan->game;
        } else {
        /** @var FastDlGame|null $game */
            $game = FastDlGame::where('slug', $first)->where('is_active', true)->first();
        }

        if (!$game) abort(404);

        $allFiles = collect();

        if ($clan) {
            $clanFiles = FastDlClanFile::where('clan_id', $clan->id)
                ->where('directory', $directory)
                ->where('is_active', true)
                ->orderBy('filename')
                ->get(['filename', 'file_size', 'updated_at']);
            $allFiles = $allFiles->merge($clanFiles);

            if ($clan->include_base) {
                $baseDir = FastDlDirectory::where('game_id', $game->id)
                    ->where('slug', $directory)
                    //->where('is_base', true)
                    ->where('is_active', true)
                    ->first();
                if ($baseDir) {
                    $baseFiles = FastDlFile::where('directory_id', $baseDir->id)
                        ->where('is_active', true)
                        ->orderBy('filename')
                        ->get(['filename', 'file_size', 'updated_at']);
                    $allFiles = $allFiles->merge($baseFiles);
                }
            }
        } else {
            /** @var FastDlDirectory|null $dir */
            $dir = FastDlDirectory::where('game_id', $game->id)
                ->where('slug', $directory)
                ->where('is_active', true)
                ->first();
            if (!$dir) abort(404);
            $allFiles = FastDlFile::where('directory_id', $dir->id)
                ->where('is_active', true)
                ->orderBy('filename')
                ->get(['filename', 'file_size', 'updated_at']);
        }

        $allFiles = $allFiles->unique('filename')->sortBy('filename');
        $dirLabel = $clan ? $clan->slug . '/' . $directory : $directory;
        $output = "# " . $game->name . " / " . $dirLabel . "\n# Files: " . $allFiles->count() . "\n\n";
        foreach ($allFiles as $f) {
            $output .= $f->filename . "\n";
        }

        return response($output, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * List directories for a game: dl.wolffiles.eu/{game}/
     */
    public function listGame(Request $request, string $first)
    {
        /** @var FastDlGame|null $game */
        $game = FastDlGame::where('slug', $first)->where('is_active', true)->first();
        if (!$game) abort(404);

        $dirs = $game->directories()->where('is_active', true)->get();
        $output = "# " . $game->name . "\n# Directories: " . $dirs->count() . "\n\n";
        foreach ($dirs as $d) {
            $count = $d->files()->where('is_active', true)->count();
            $output .= $d->slug . "/ (" . $count . " files)\n";
        }

        return response($output, 200, ['Content-Type' => 'text/plain']);
    }

}
