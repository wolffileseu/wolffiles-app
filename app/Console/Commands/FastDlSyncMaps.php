<?php

namespace App\Console\Commands;

use App\Models\FastDl\FastDlGame;
use App\Models\FastDl\FastDlFile;
use App\Models\File;
use Illuminate\Console\Command;

class FastDlSyncMaps extends Command
{
    protected $signature = 'fastdl:sync-maps';
    protected $description = 'Sync maps from Wolffiles database to Fast Download';

    // Map FastDL game slug to Wolffiles game string
    private array $gameMap = [
        'et' => 'ET',
        'rtcw' => 'RtCW',
    ];

    public function handle(): void
    {
        $games = FastDlGame::where('auto_sync', true)->where('is_active', true)->get();

        foreach ($games as $game) {
            $baseDir = $game->directories()->where('is_base', true)->first();
            if (!$baseDir) {
                $this->warn("No base directory for {$game->name}");
                continue;
            }

            $gameString = $this->gameMap[$game->slug] ?? $game->slug;

            $files = File::where('status', 'approved')
                ->where('game', $gameString)
                ->where('file_extension', 'pk3')
                ->get();

            $synced = 0;
            $skipped = 0;

            foreach ($files as $file) {
                $filename = $file->file_name;
                if (!$filename || !str_ends_with(strtolower($filename), '.pk3')) continue;

                // Check if already exists
                $exists = FastDlFile::where('directory_id', $baseDir->id)
                    ->where('filename', $filename)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                FastDlFile::create([
                    'directory_id' => $baseDir->id,
                    'filename' => $filename,
                    's3_path' => $file->file_path,
                    'file_size' => $file->file_size ?? 0,
                    'source' => 'auto_sync',
                    'wolffiles_file_id' => $file->id,
                    'is_active' => true,
                ]);
                $synced++;
            }

            $total = FastDlFile::where('directory_id', $baseDir->id)->count();
            $this->info("{$game->name}: {$synced} new, {$skipped} skipped, {$total} total files");
        }

        $this->info('Sync complete!');
    }
}
