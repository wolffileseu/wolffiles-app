<?php
namespace App\Console\Commands;

use App\Models\TmKey;
use App\Models\TmLanguage;
use App\Models\TmTranslation;
use Illuminate\Console\Command;

class ImportLangFiles extends Command {
    protected $signature   = 'tm:import';
    protected $description = 'Importiert lang/*/messages.php in die DB';

    public function handle(): int {
        $en = include lang_path('en/messages.php');
        $this->info('Importiere ' . count($en) . ' Keys aus en/messages.php...');

        foreach ($en as $key => $value) {
            TmKey::updateOrCreate(['key' => $key], ['en' => $value]);
        }

        $langs = ['de', 'fr', 'nl', 'pl', 'tr'];
        foreach ($langs as $lang) {
            $file = lang_path("$lang/messages.php");
            if (!file_exists($file)) continue;

            $translations = include $file;
            $enKeys = array_keys($en);
            $count = 0;

            foreach ($translations as $key => $value) {
                if (!in_array($key, $enKeys)) continue;
                if ($value === ($en[$key] ?? null)) continue; // Skip wenn identisch mit EN
                if (empty($value)) continue;

                $tmKey = TmKey::where('key', $key)->first();
                if (!$tmKey) continue;

                TmTranslation::updateOrCreate(
                    ['tm_key_id' => $tmKey->id, 'language_code' => $lang],
                    ['value' => $value, 'translated_at' => now()]
                );
                $count++;
            }
            $this->info("  → $lang: $count Übersetzungen importiert");
        }

        $this->info('✓ Import abgeschlossen!');
        return Command::SUCCESS;
    }
}
