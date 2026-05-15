<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use Illuminate\Database\Seeder;

class EtuiFileFromFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $imported = 0;
        $skipped = 0;

        foreach (['etmain', 'etlegacy'] as $slug) {
            $mod = EtuiMod::where('slug', $slug)->first();
            if (! $mod) {
                $this->command?->warn("Mod '{$slug}' not seeded — skipping fixture import");
                continue;
            }

            $paths = glob(base_path("tests/fixtures/etui/{$slug}/*.menu")) ?: [];
            foreach ($paths as $path) {
                $raw = file_get_contents($path);
                if ($raw === false) {
                    continue;
                }
                // .menu fixtures ship with Latin-1 / Windows-1252 bytes for
                // credits, author names etc. MySQL utf8mb4 columns reject the
                // raw 0xF6 (ö) and friends — convert through mbstring before
                // the hash is computed so the dedup key reflects the stored
                // form, not the on-disk bytes.
                $content = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
                $name = basename($path);
                $hash = hash('sha256', $content);

                // Unique constraint (mod_id, name, hash) makes this idempotent —
                // a repeated seeder run with unchanged fixtures hits the existing
                // row via firstOrCreate and increments the skipped counter.
                $existed = EtuiFile::where('mod_id', $mod->id)
                    ->where('name', $name)
                    ->where('hash', $hash)
                    ->exists();

                if ($existed) {
                    $skipped++;
                    continue;
                }

                EtuiFile::create([
                    'mod_id' => $mod->id,
                    'name' => $name,
                    'content' => $content,
                    'source' => 'stock',
                    'uploader_id' => null,
                    'is_public' => true,
                    'hash' => $hash,
                    'parse_status' => 'pending',
                ]);
                $imported++;
            }
        }

        $this->command?->info("ETUI fixtures: imported {$imported} new files, skipped {$skipped} duplicates");
    }
}
