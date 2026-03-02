<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Livewire\WithFileUploads;

class TranslationManager extends Page
{
    use WithFileUploads;
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Translations';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.translation-manager';

    public string $selectedLang = '';
    public string $selectedFile = 'messages';
    public string $search = '';
    public string $filter = 'all'; // all, missing, translated
    public string $newLangCode = '';
    public array $editValues = [];

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function mount(): void
    {
        $langs = $this->getLanguages();
        $this->selectedLang = $langs[0] ?? 'de';
    }

    public function getLanguages(): array
    {
        $dirs = glob(lang_path('*'), GLOB_ONLYDIR);
        return array_values(array_filter(array_map('basename', $dirs), fn($d) => $d !== 'en'));
    }

    public function getLanguageStats(): array
    {
        $baseKeys = $this->getBaseKeys();
        $total = count($baseKeys);
        $stats = [];

        foreach ($this->getLanguages() as $lang) {
            $langKeys = $this->getLangKeys($lang);
            $translated = 0;
            $missing = 0;
            $todo = 0;

            foreach ($baseKeys as $key => $value) {
                if (!isset($langKeys[$key])) {
                    $missing++;
                } elseif (str_starts_with($langKeys[$key], '[TODO:')) {
                    $todo++;
                } else {
                    $translated++;
                }
            }

            $stats[$lang] = [
                'total' => $total,
                'translated' => $translated,
                'todo' => $todo,
                'missing' => $missing,
                'percent' => $total > 0 ? round(($translated / $total) * 100) : 0,
            ];
        }

        return $stats;
    }

    public function getTranslationRows(): array
    {
        if (!$this->selectedLang) return [];

        $baseKeys = $this->getBaseKeys();
        $langKeys = $this->getLangKeys($this->selectedLang);
        $rows = [];

        foreach ($baseKeys as $key => $enValue) {
            $langValue = $langKeys[$key] ?? '';
            $isTodo = str_starts_with($langValue, '[TODO:');
            $isMissing = empty($langValue);

            // Filter
            if ($this->filter === 'missing' && !$isMissing && !$isTodo) continue;
            if ($this->filter === 'translated' && ($isMissing || $isTodo)) continue;

            // Search
            if ($this->search) {
                $s = strtolower($this->search);
                if (!str_contains(strtolower($key), $s) &&
                    !str_contains(strtolower($enValue), $s) &&
                    !str_contains(strtolower($langValue), $s)) {
                    continue;
                }
            }

            $rows[$key] = [
                'key' => $key,
                'en' => $enValue,
                'value' => $langValue,
                'status' => $isMissing ? 'missing' : ($isTodo ? 'todo' : 'translated'),
            ];
        }

        return $rows;
    }

    public function saveTranslation(string $key, string $value): void
    {
        $filePath = lang_path("{$this->selectedLang}/{$this->selectedFile}.php");
        if (!file_exists($filePath)) return;

        $translations = include $filePath;
        $translations[$key] = $value;

        $content = "<?php\n\nreturn " . $this->arrayExport($translations) . ";\n";
        file_put_contents($filePath, $content);

        Notification::make()->title('Saved!')->success()->send();
    }

    public function addLanguage(): void
    {
        $lang = strtolower(trim($this->newLangCode));
        if (!$lang || !preg_match('/^[a-z]{2,3}$/', $lang)) {
            Notification::make()->title('Invalid language code')->danger()->send();
            return;
        }

        $langDir = lang_path($lang);
        if (is_dir($langDir)) {
            Notification::make()->title('Language already exists')->warning()->send();
            return;
        }

        mkdir($langDir, 0755, true);

        $enFiles = glob(lang_path('en/*.php'));
        foreach ($enFiles as $enFile) {
            $filename = basename($enFile);
            $translations = include $enFile;
            $marked = $this->markTodo($translations, $lang);
            $content = "<?php\n\nreturn " . $this->arrayExport($marked) . ";\n";
            file_put_contents($langDir . '/' . $filename, $content);
        }

        $this->newLangCode = '';
        $this->selectedLang = $lang;

        Notification::make()->title("Language '{$lang}' created!")->success()->send();
    }

    public function syncAll(): void
    {
        $baseKeys = $this->getBaseKeys();

        foreach ($this->getLanguages() as $lang) {
            $filePath = lang_path("{$lang}/{$this->selectedFile}.php");
            if (!file_exists($filePath)) continue;

            $translations = include $filePath;
            $added = 0;

            foreach ($baseKeys as $key => $enValue) {
                if (!isset($translations[$key])) {
                    $translations[$key] = "[TODO:{$lang}] {$enValue}";
                    $added++;
                }
            }

            if ($added > 0) {
                $content = "<?php\n\nreturn " . $this->arrayExport($translations) . ";\n";
                file_put_contents($filePath, $content);
            }
        }

        Notification::make()->title('All languages synced!')->success()->send();
    }

    public function deleteLanguage(string $lang): void
    {
        if ($lang === 'en' || $lang === 'de') {
            Notification::make()->title('Cannot delete base languages')->danger()->send();
            return;
        }

        $langDir = lang_path($lang);
        if (is_dir($langDir)) {
            File::deleteDirectory($langDir);
            $this->selectedLang = 'de';
            Notification::make()->title("Language '{$lang}' deleted")->success()->send();
        }
    }

    private function getBaseKeys(): array
    {
        $path = lang_path("en/{$this->selectedFile}.php");
        if (!file_exists($path)) return [];
        $data = include $path;
        return is_array($data) ? $data : [];
    }

    private function getLangKeys(string $lang): array
    {
        $path = lang_path("{$lang}/{$this->selectedFile}.php");
        if (!file_exists($path)) return [];
        $data = include $path;
        return is_array($data) ? $data : [];
    }

    private function markTodo(array $array, string $lang): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->markTodo($value, $lang);
            } else {
                $result[$key] = "[TODO:{$lang}] {$value}";
            }
        }
        return $result;
    }

    private function arrayExport(array $array): string
    {
        $lines = ["["];
        foreach ($array as $key => $value) {
            $k = str_replace("'", "\\'", $key);
            if (is_array($value)) {
                $lines[] = "    '{$k}' => " . $this->arrayExport($value) . ",";
            } else {
                $v = str_replace("'", "\\'", $value);
                $lines[] = "    '{$k}' => '{$v}',";
            }
        }
        $lines[] = "]";
        return implode("\n", $lines);
    }

    public function exportCsv(string $lang): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $baseKeys = $this->getBaseKeys();
        $langKeys = $this->getLangKeys($lang);
        $langConfig = config("languages.{$lang}", ["name" => strtoupper($lang)]);

        return response()->streamDownload(function () use ($baseKeys, $langKeys, $lang, $langConfig) {
            $handle = fopen("php://output", "w");
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ["Key", "English", $langConfig["name"] . " ({$lang})", "Status"]);

            foreach ($baseKeys as $key => $enValue) {
                $langValue = $langKeys[$key] ?? "";
                $isTodo = str_starts_with($langValue, "[TODO:");
                $status = empty($langValue) ? "MISSING" : ($isTodo ? "TODO" : "OK");
                $cleanValue = $isTodo ? "" : $langValue;
                fputcsv($handle, [$key, $enValue, $cleanValue, $status]);
            }

            fclose($handle);
        }, "wolffiles-translations-{$lang}.csv");
    }

    public function exportJson(string $lang): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $baseKeys = $this->getBaseKeys();
        $langKeys = $this->getLangKeys($lang);

        $export = [];
        foreach ($baseKeys as $key => $enValue) {
            $langValue = $langKeys[$key] ?? "";
            $isTodo = str_starts_with($langValue, "[TODO:");
            $export[$key] = [
                "en" => $enValue,
                $lang => $isTodo ? "" : $langValue,
            ];
        }

        return response()->streamDownload(function () use ($export) {
            echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, "wolffiles-translations-{$lang}.json");
    }

    public $importFile;

    public function importCsv(): void
    {
        if (!$this->importFile) {
            Notification::make()->title("No file selected")->danger()->send();
            return;
        }

        $path = $this->importFile->getRealPath();
        $handle = fopen($path, "r");

        if (!$handle) {
            Notification::make()->title("Cannot read file")->danger()->send();
            return;
        }

        // BOM entfernen
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Header lesen
        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            Notification::make()->title("Invalid CSV format. Need: Key, English, Translation, Status")->danger()->send();
            return;
        }

        // Sprache aus Header erkennen (3. Spalte = "Français (fr)")
        $langHeader = $header[2] ?? "";
        preg_match("/\(([a-z]{2,3})\)/", $langHeader, $matches);
        $lang = $matches[1] ?? $this->selectedLang;

        if (!$lang || !is_dir(lang_path($lang))) {
            Notification::make()->title("Language '{$lang}' not found")->danger()->send();
            fclose($handle);
            return;
        }

        // Bestehende Übersetzungen laden
        $filePath = lang_path("{$lang}/{$this->selectedFile}.php");
        $translations = file_exists($filePath) ? include $filePath : [];

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            $key = trim($row[0]);
            $translation = trim($row[2]);

            // Nur importieren wenn Key existiert und Übersetzung nicht leer
            if (empty($key) || empty($translation)) {
                $skipped++;
                continue;
            }

            // Nicht importieren wenn noch TODO
            if (str_starts_with($translation, "[TODO:")) {
                $skipped++;
                continue;
            }

            $translations[$key] = $translation;
            $imported++;
        }

        fclose($handle);

        // Datei speichern
        $output = "<?php\n\nreturn " . $this->arrayExport($translations) . ";\n";
        file_put_contents($filePath, $output);

        // Upload File aufräumen
        $this->importFile = null;

        Notification::make()
            ->title("Import erfolgreich!")
            ->body("{$imported} Übersetzungen importiert, {$skipped} übersprungen")
            ->success()
            ->send();
    }


    public $importJsonFile;

    public function importJson(): void
    {
        if (!$this->importJsonFile) {
            Notification::make()->title("No file selected")->danger()->send();
            return;
        }

        try {
            $path = $this->importJsonFile->getRealPath();
            
            if (!$path || !file_exists($path)) {
                Notification::make()->title("File upload failed - try again")->danger()->send();
                $this->importJsonFile = null;
                return;
            }

            $raw = file_get_contents($path);
            // Remove JS-style comments (// ...) that are not valid JSON
            $raw = preg_replace('#^\s*//.*$#m', '', $raw);
            $raw = trim($raw);
            $data = json_decode($raw, true);

            if (!$data || !is_array($data)) {
                Notification::make()
                    ->title("Invalid JSON")
                    ->body("JSON decode error: " . json_last_error_msg())
                    ->danger()->send();
                $this->importJsonFile = null;
                return;
            }

            // Sprache erkennen aus erstem Eintrag
            $firstEntry = reset($data);
            
            if (!is_array($firstEntry)) {
                // Einfaches Format: {"key": "translation"} — nutze selectedLang
                $lang = $this->selectedLang;
                $isSimple = true;
            } else {
                $langCodes = array_diff(array_keys($firstEntry), ["en"]);
                $lang = reset($langCodes) ?: $this->selectedLang;
                $isSimple = false;
            }

            if (!$lang || !is_dir(lang_path($lang))) {
                Notification::make()
                    ->title("Language '{$lang}' not recognized")
                    ->body("Make sure the language exists or select it first")
                    ->danger()->send();
                $this->importJsonFile = null;
                return;
            }

            // Lade ALLE translation files für diese Sprache
            $langDir = lang_path($lang);
            $files = glob("{$langDir}/*.php");
            
            $totalImported = 0;
            $totalSkipped = 0;
            $filesUpdated = [];

            foreach ($files as $transFile) {
                $fileName = basename($transFile, ".php");
                $translations = include $transFile;
                if (!is_array($translations)) continue;
                
                $changed = false;
                foreach ($data as $key => $entry) {
                    // Nur Keys die in dieser Datei existieren
                    if (!array_key_exists($key, $translations)) continue;
                    
                    if ($isSimple) {
                        $translation = trim($entry);
                    } else {
                        $translation = trim($entry[$lang] ?? "");
                    }
                    
                    if (empty($translation) || str_starts_with($translation, "[TODO:")) {
                        $totalSkipped++;
                        continue;
                    }
                    
                    $translations[$key] = $translation;
                    $totalImported++;
                    $changed = true;
                }
                
                if ($changed) {
                    $output = "<?php\n\nreturn " . $this->arrayExport($translations) . ";\n";
                    file_put_contents($transFile, $output);
                    $filesUpdated[] = $fileName;
                }
            }

            $this->importJsonFile = null;
            $this->loadTranslations();

            Notification::make()
                ->title("JSON Import erfolgreich!")
                ->body("{$totalImported} importiert, {$totalSkipped} übersprungen. Files: " . implode(", ", $filesUpdated))
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Log::error("JSON Import Error: " . $e->getMessage());
            Notification::make()
                ->title("Import Error")
                ->body($e->getMessage())
                ->danger()->send();
            $this->importJsonFile = null;
        }
    }
    protected function loadTranslations(): void
    {
        // Reload translations from disk
        $this->dispatch('$refresh');
    }

}
