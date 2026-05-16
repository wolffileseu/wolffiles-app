<?php

namespace App\Console\Commands;

use App\Services\TextureResolutionChecker;
use Illuminate\Console\Command;

class RecheckMissingTexturesCommand extends Command
{
    protected $signature = "textures:recheck {--file= : Only check a specific file_id}";
    protected $description = "Re-check missing textures against current resolvers; mark resolvable ones as resolved.";

    public function handle(TextureResolutionChecker $checker): int
    {
        $fileId = $this->option("file");
        $fileId = $fileId !== null ? (int) $fileId : null;

        $this->info($fileId
            ? "Rechecking missing textures for file #{$fileId}..."
            : "Rechecking ALL unresolved missing textures...");

        $result = $checker->recheckAll($fileId);
        $this->info("Checked: {$result["checked"]}");
        $this->info("Newly resolved: {$result["resolved"]}");

        return self::SUCCESS;
    }
}
