<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Category;
use App\Services\FileAnalyzerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportAll extends Command
{
    protected $signature = 'wolffiles:import-all
        {--base=files : S3 base path to scan}
        {--user=1 : User ID for the uploader}
        {--auto-approve : Automatically approve imported files}
        {--analyze : Run file analysis (extract images, map name, etc.)}
        {--dry-run : Show what would be imported without doing it}
        {--game= : Only import a specific game (e.g. ET, RtCW)}
        {--limit=0 : Limit number of files to import (0 = no limit)}
        {--skip-existing : Skip files that already exist in DB (default behavior)}';

    protected $description = 'Bulk import ALL files from S3 by auto-detecting game/category from folder structure';

    // Map S3 folder names to game names in DB
    private array $gameMap = [
        'ET' => 'ET',
        'RtCW' => 'RtCW',
        'ET Quake Wars' => 'ET Quake Wars',
        'ET-Domination' => 'ET-Domination',
        'ETFortress' => 'ETFortress',
        'Movies' => 'Movies',
        'True Combat Elite' => 'True Combat Elite',
        'Wolf Classic' => 'Wolf Classic',
        'Wolfenstein' => 'Wolfenstein',
    ];

    // Supported file extensions
    private array $supportedExtensions = [
        'pk3', 'zip', 'rar', '7z', 'gz', 'tar', 'bz2',
        'lua', 'cfg', 'txt', 'doc', 'pdf',
        'exe', 'msi', 'dmg', 'run', 'sh', 'bat',
        'pk3zip', 'bsp',
    ];

    private int $imported = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $categoriesCreated = 0;

    public function handle(): int
    {
        $basePath = rtrim($this->option('base'), '/');
        $dryRun = $this->option('dry-run');
        $onlyGame = $this->option('game');
        $limit = (int) $this->option('limit');

        $this->info('===========================================');
        $this->info('  Wolffiles.eu — Bulk Import Tool');
        $this->info('===========================================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN — nothing will be saved.');
            $this->newLine();
        }

        // Step 1: Scan game folders
        $this->info("Scanning S3 base path: {$basePath}/");
        $gameFolders = Storage::disk('s3')->directories($basePath);

        if (empty($gameFolders)) {
            $this->error("No folders found at {$basePath}/");
            return 1;
        }

        $this->info("Found " . count($gameFolders) . " game folders.");
        $this->newLine();

        // Step 2: Process each game folder
        foreach ($gameFolders as $gameFolder) {
            $gameName = basename($gameFolder);

            // Filter by game if specified
            if ($onlyGame && $gameName !== $onlyGame) {
                continue;
            }

            $this->info("━━━ 🎮 {$gameName} ━━━");

            // Find or create parent category (game)
            $gameCategory = $this->findOrCreateGameCategory($gameName, $dryRun);

            if (!$gameCategory && !$dryRun) {
                $this->error("  Could not find/create game category for: {$gameName}");
                continue;
            }

            // Scan subcategory folders
            $subFolders = Storage::disk('s3')->directories($gameFolder);

            if (empty($subFolders)) {
                // Files directly in game folder (no subcategories)
                $this->importFolder($gameFolder, $gameCategory, $gameName, $dryRun, $limit);
            } else {
                foreach ($subFolders as $subFolder) {
                    $subName = basename($subFolder);
                    $this->info("  📁 {$subName}");

                    // Find or create subcategory
                    $subCategory = $this->findOrCreateSubCategory(
                        $subName, $gameCategory, $dryRun
                    );

                    // Import files from this subfolder (and its subdirectories)
                    $this->importFolderRecursive($subFolder, $subCategory, $gameName, $dryRun, $limit);

                    if ($limit > 0 && $this->imported >= $limit) {
                        $this->warn("  Limit of {$limit} reached.");
                        break 2;
                    }
                }
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('===========================================');
        $this->info('  Import Complete!');
        $this->info('===========================================');
        $this->table(
            ['Imported', 'Skipped (duplicate)', 'Errors', 'Categories Created'],
            [[$this->imported, $this->skipped, $this->errors, $this->categoriesCreated]]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No files were actually imported.');
        }

        return 0;
    }

    /**
     * Import files from a single folder (non-recursive)
     */
    private function importFolder(
        string $folder,
        ?Category $category,
        string $gameName,
        bool $dryRun,
        int $limit
    ): void {
        $files = $this->getFiles($folder);

        if ($files->isEmpty()) {
            $this->line("    No supported files found.");
            return;
        }

        $this->line("    {$files->count()} files found.");

        foreach ($files as $filePath) {
            if ($limit > 0 && $this->imported >= $limit) return;
            $this->importFile($filePath, $category, $gameName, $dryRun);
        }
    }

    /**
     * Import files recursively (including subdirectories)
     */
    private function importFolderRecursive(
        string $folder,
        ?Category $category,
        string $gameName,
        bool $dryRun,
        int $limit
    ): void {
        // Import files in this folder
        $this->importFolder($folder, $category, $gameName, $dryRun, $limit);

        // Also scan subdirectories
        $subDirs = Storage::disk('s3')->directories($folder);
        foreach ($subDirs as $subDir) {
            if ($limit > 0 && $this->imported >= $limit) return;
            $this->importFolderRecursive($subDir, $category, $gameName, $dryRun, $limit);
        }
    }

    /**
     * Get supported files from a folder
     */
    private function getFiles(string $folder): \Illuminate\Support\Collection
    {
        return collect(Storage::disk('s3')->files($folder))
            ->filter(function ($file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $fileName = basename($file);
                // Skip hidden files, thumbs, temp files
                if (Str::startsWith($fileName, '.') || $fileName === 'Thumbs.db' || $fileName === '.DS_Store') {
                    return false;
                }
                return in_array($ext, $this->supportedExtensions);
            });
    }

    /**
     * Import a single file
     */
    private function importFile(
        string $filePath,
        ?Category $category,
        string $gameName,
        bool $dryRun
    ): void {
        $fileName = basename($filePath);

        // Check if already imported (by path or filename)
        if (File::where('file_path', $filePath)->exists() ||
            File::where('file_name', $fileName)->where('game', $gameName)->exists()) {
            $this->skipped++;
            return;
        }

        if ($dryRun) {
            $this->line("    [DRY] {$fileName}");
            $this->imported++;
            return;
        }

        try {
            // Build title from filename
            $title = pathinfo($fileName, PATHINFO_FILENAME);
            $title = str_replace(['_', '-'], ' ', $title);
            // Don't over-capitalize — keep version numbers etc.
            $title = ucfirst(trim($title));

            // Get file size
            $fileSize = 0;
            $mimeType = 'application/octet-stream';
            try {
                $fileSize = Storage::disk('s3')->size($filePath);
                $mimeType = Storage::disk('s3')->mimeType($filePath) ?? $mimeType;
            } catch (\Exception $e) {
                // Ignore, use defaults
            }

            // Generate slug
            $slug = Str::slug($title);
            $slugBase = $slug;
            $counter = 1;
            while (File::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $counter++;
            }

            $fileData = [
                'user_id' => $this->option('user'),
                'title' => $title,
                'slug' => $slug,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_extension' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'game' => $gameName,
                'category_id' => $category?->id,
                'status' => $this->option('auto-approve') ? 'approved' : 'pending',
                'published_at' => $this->option('auto-approve') ? now() : null,
            ];

            // Generate file hash if file is small enough (< 50MB)
            if ($fileSize > 0 && $fileSize < 500 * 1024 * 1024) {
                try {
                    $tempPath = tempnam(sys_get_temp_dir(), 'wf_');
                    file_put_contents($tempPath, Storage::disk('s3')->get($filePath));
                    $fileData['file_hash'] = hash_file('sha256', $tempPath);

                    // Run analysis if requested
                    if ($this->option('analyze')) {
                        $this->analyzeFile($tempPath, $fileName, $fileData);
                    }

                    @unlink($tempPath);
                } catch (\Exception $e) {
                    // Skip hash/analysis for this file
                }
            }

            $file = File::create($fileData);
            $this->imported++;

        } catch (\Exception $e) {
            $this->errors++;
            $this->error("    ✗ {$fileName}: {$e->getMessage()}");
        }
    }

    /**
     * Analyze a file and update data array
     */
    private function analyzeFile(string $tempPath, string $fileName, array &$fileData): void
    {
        try {
            $analyzer = app(FileAnalyzerService::class);
            $analysis = $analyzer->analyze($tempPath, $fileName);

            if (!empty($analysis['map_name'])) $fileData['map_name'] = $analysis['map_name'];
            if (!empty($analysis['readme_content'])) $fileData['readme_content'] = $analysis['readme_content'];
            if (!empty($analysis['extracted_metadata'])) {
    $fileData['extracted_metadata'] = json_decode(
        json_encode($analysis['extracted_metadata'], JSON_INVALID_UTF8_SUBSTITUTE), true
    );
}
        } catch (\Exception $e) {
            // Silently skip analysis errors
        }
    }

    /**
     * Find or create a game (parent) category
     */
    private function findOrCreateGameCategory(string $gameName, bool $dryRun): ?Category
    {
        // Try exact match first
        $category = Category::whereNull('parent_id')
            ->where('name', $gameName)
            ->first();

        if ($category) return $category;

        // Try case-insensitive match
        $category = Category::whereNull('parent_id')
            ->whereRaw('LOWER(name) = ?', [strtolower($gameName)])
            ->first();

        if ($category) return $category;

        // Create new game category
        if ($dryRun) {
            $this->line("  [DRY] Would create game category: {$gameName}");
            $this->categoriesCreated++;
            return null;
        }

        $maxSort = Category::whereNull('parent_id')->max('sort_order') ?? 0;

        $category = Category::create([
            'name' => $gameName,
            'slug' => Str::slug($gameName),
            'type' => 'game',
            'is_active' => true,
            'sort_order' => $maxSort + 1,
            'name_translations' => ['en' => $gameName, 'de' => $gameName],
        ]);

        $this->categoriesCreated++;
        $this->info("  ✚ Created game category: {$gameName} (ID: {$category->id})");

        return $category;
    }

    /**
     * Find or create a subcategory under a game
     */
    private function findOrCreateSubCategory(
        string $subName,
        ?Category $gameCategory,
        bool $dryRun
    ): ?Category {
        if (!$gameCategory) return null;

        // Try exact match
        $category = Category::where('parent_id', $gameCategory->id)
            ->where('name', $subName)
            ->first();

        if ($category) return $category;

        // Try case-insensitive match
        $category = Category::where('parent_id', $gameCategory->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($subName)])
            ->first();

        if ($category) return $category;

        // Try with common name variations
        $variations = [
            'Skinpacks - MP' => ['Skinpacks', 'Skinpacks MP'],
            'Skinpacks - SP' => ['Skinpacks SP'],
            'MP-Mods' => ['Mods MP', 'Multiplayer Mods'],
            'SP-Mods' => ['Mods SP', 'Singleplayer Mods'],
            'Full Version' => ['Full Versions', 'Vollversion'],
        ];

        if (isset($variations[$subName])) {
            foreach ($variations[$subName] as $alt) {
                $category = Category::where('parent_id', $gameCategory->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($alt)])
                    ->first();
                if ($category) return $category;
            }
        }

        // Create new subcategory
        if ($dryRun) {
            $this->line("    [DRY] Would create subcategory: {$gameCategory->name} → {$subName}");
            $this->categoriesCreated++;
            return null;
        }

        $maxSort = Category::where('parent_id', $gameCategory->id)->max('sort_order') ?? 0;

        $category = Category::create([
            'name' => $subName,
            'slug' => Str::slug($gameCategory->name . '-' . $subName),
            'parent_id' => $gameCategory->id,
            'type' => 'file',
            'is_active' => true,
            'sort_order' => $maxSort + 1,
            'name_translations' => ['en' => $subName, 'de' => $subName],
        ]);

        $this->categoriesCreated++;
        $this->info("    ✚ Created subcategory: {$gameCategory->name} → {$subName} (ID: {$category->id})");

        return $category;
    }
}