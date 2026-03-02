<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TranslationManager extends Command
{
    protected $signature = 'translations:manage
        {action : list|missing|add-language|sync}
        {--lang= : Language code (e.g. fr, nl, es)}
        {--file=messages : Translation file name}';

    protected $description = 'Manage translations: list keys, find missing, add languages';

    public function handle(): void
    {
        $action = $this->argument('action');

        match ($action) {
            'list' => $this->listKeys(),
            'missing' => $this->findMissing(),
            'add-language' => $this->addLanguage(),
            'sync' => $this->syncLanguages(),
            default => $this->error("Unknown action: {$action}. Use: list, missing, add-language, sync"),
        };
    }

    /**
     * List all translation keys from the base language (en)
     */
    private function listKeys(): void
    {
        $file = $this->option('file');
        $basePath = lang_path("en/{$file}.php");

        if (!file_exists($basePath)) {
            $this->error("File not found: {$basePath}");
            return;
        }

        $translations = include $basePath;
        $keys = $this->flattenKeys($translations);

        $this->info("Translation keys in en/{$file}.php: " . count($keys));
        $this->newLine();

        foreach ($keys as $key => $value) {
            $preview = mb_substr($value, 0, 60);
            if (mb_strlen($value) > 60) $preview .= '...';
            $this->line("  <comment>{$key}</comment> => {$preview}");
        }
    }

    /**
     * Find missing keys across all languages
     */
    private function findMissing(): void
    {
        $file = $this->option('file');
        $basePath = lang_path("en/{$file}.php");
        $baseTranslations = include $basePath;
        $baseKeys = $this->flattenKeys($baseTranslations);

        $languages = $this->getLanguages();
        $totalMissing = 0;

        foreach ($languages as $lang) {
            if ($lang === 'en') continue;

            $langPath = lang_path("{$lang}/{$file}.php");
            if (!file_exists($langPath)) {
                $this->warn("  [{$lang}] File missing: {$lang}/{$file}.php — ALL keys missing!");
                $totalMissing += count($baseKeys);
                continue;
            }

            $langTranslations = include $langPath;
            $langKeys = $this->flattenKeys($langTranslations);

            $missing = array_diff_key($baseKeys, $langKeys);
            $extra = array_diff_key($langKeys, $baseKeys);

            if (empty($missing) && empty($extra)) {
                $this->info("  [{$lang}] ✅ All " . count($baseKeys) . " keys present");
            } else {
                if (!empty($missing)) {
                    $this->warn("  [{$lang}] ❌ Missing " . count($missing) . " keys:");
                    foreach ($missing as $key => $value) {
                        $this->line("    <error>-</error> {$key}");
                    }
                    $totalMissing += count($missing);
                }
                if (!empty($extra)) {
                    $this->line("  [{$lang}] ⚠️  Extra " . count($extra) . " keys (not in en):");
                    foreach ($extra as $key => $value) {
                        $this->line("    <comment>+</comment> {$key}");
                    }
                }
            }
            $this->newLine();
        }

        if ($totalMissing === 0) {
            $this->info("🎉 All languages are in sync!");
        } else {
            $this->warn("Total missing: {$totalMissing} keys. Run 'translations:manage sync' to fix.");
        }
    }

    /**
     * Add a new language based on English
     */
    private function addLanguage(): void
    {
        $lang = $this->option('lang');
        if (!$lang) {
            $this->error('Please specify --lang=xx (e.g. --lang=fr)');
            return;
        }

        $langDir = lang_path($lang);
        if (is_dir($langDir)) {
            $this->warn("Language '{$lang}' already exists!");
            if (!$this->confirm('Do you want to add missing keys?')) return;
            $this->syncSingleLanguage($lang);
            return;
        }

        // Create language directory
        mkdir($langDir, 0755, true);

        // Copy all translation files from English
        $enFiles = glob(lang_path('en/*.php'));
        foreach ($enFiles as $enFile) {
            $filename = basename($enFile);
            $translations = include $enFile;

            // Mark all values as untranslated
            $marked = $this->markUntranslated($translations, $lang);

            $content = "<?php\n\nreturn " . $this->arrayToString($marked) . ";\n";
            file_put_contents($langDir . '/' . $filename, $content);

            $keyCount = count($this->flattenKeys($translations));
            $this->info("Created {$lang}/{$filename} with {$keyCount} keys (marked as TODO)");
        }

        $this->newLine();
        $this->info("✅ Language '{$lang}' created!");
        $this->line("All values are prefixed with [TODO:{$lang}] — replace them with actual translations.");
        $this->line("Edit files in: lang/{$lang}/");
    }

    /**
     * Sync all languages — add missing keys from English
     */
    private function syncLanguages(): void
    {
        $languages = $this->getLanguages();

        foreach ($languages as $lang) {
            if ($lang === 'en') continue;
            $this->syncSingleLanguage($lang);
        }
    }

    private function syncSingleLanguage(string $lang): void
    {
        $enFiles = glob(lang_path('en/*.php'));

        foreach ($enFiles as $enFile) {
            $filename = basename($enFile);
            $langPath = lang_path("{$lang}/{$filename}");

            $enTranslations = include $enFile;
            $enKeys = $this->flattenKeys($enTranslations);

            if (!file_exists($langPath)) {
                $marked = $this->markUntranslated($enTranslations, $lang);
                $content = "<?php\n\nreturn " . $this->arrayToString($marked) . ";\n";
                file_put_contents($langPath, $content);
                $this->info("[{$lang}] Created {$filename} with " . count($enKeys) . " keys");
                continue;
            }

            $langTranslations = include $langPath;
            $langKeys = $this->flattenKeys($langTranslations);

            $missing = array_diff_key($enKeys, $langKeys);

            if (empty($missing)) {
                $this->line("[{$lang}/{$filename}] ✅ In sync");
                continue;
            }

            // Add missing keys to the file
            $content = file_get_contents($langPath);

            // Find the last ]; in the file
            $lastBracket = strrpos($content, '];');
            if ($lastBracket === false) {
                $this->error("[{$lang}/{$filename}] Cannot parse file structure");
                continue;
            }

            $additions = "\n    // --- Added by sync " . date('Y-m-d') . " ---\n";
            foreach ($missing as $key => $value) {
                $escapedValue = str_replace("'", "\\'", $value);
                $additions .= "    '{$key}' => '[TODO:{$lang}] {$escapedValue}',\n";
            }

            $content = substr($content, 0, $lastBracket) . $additions . substr($content, $lastBracket);
            file_put_contents($langPath, $content);

            $this->info("[{$lang}/{$filename}] ➕ Added " . count($missing) . " missing keys");
        }
    }

    /**
     * Get all available languages
     */
    private function getLanguages(): array
    {
        $dirs = glob(lang_path('*'), GLOB_ONLYDIR);
        return array_map('basename', $dirs);
    }

    /**
     * Flatten nested array keys with dot notation
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenKeys($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Mark all values as untranslated
     */
    private function markUntranslated(array $array, string $lang): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->markUntranslated($value, $lang);
            } else {
                $result[$key] = "[TODO:{$lang}] {$value}";
            }
        }
        return $result;
    }

    /**
     * Convert array to formatted PHP string
     */
    private function arrayToString(array $array, int $indent = 1): string
    {
        $pad = str_repeat('    ', $indent);
        $result = "[\n";

        foreach ($array as $key => $value) {
            $escapedKey = str_replace("'", "\\'", $key);
            if (is_array($value)) {
                $result .= "{$pad}'{$escapedKey}' => " . $this->arrayToString($value, $indent + 1) . ",\n";
            } else {
                $escapedValue = str_replace("'", "\\'", $value);
                $result .= "{$pad}'{$escapedKey}' => '{$escapedValue}',\n";
            }
        }

        $result .= str_repeat('    ', $indent - 1) . ']';
        return $result;
    }
}
