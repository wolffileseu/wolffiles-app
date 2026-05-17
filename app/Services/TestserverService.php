<?php

namespace App\Services;

use App\Models\Testserver;
use App\Models\TestserverSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\File as FileModel;
use App\Models\TestserverLoadedMap;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestserverService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.pterodactyl.url', 'https://panel.wolffiles.eu'), '/');
        $this->apiKey  = config('services.pterodactyl.api_key', '');
    }

    /* ─────────────────────────────────────────
     * Reservation Flow (Public Frontend → Job)
     * ─────────────────────────────────────────*/

    /**
     * Findet den nächsten freien Testserver oder null wenn alle belegt.
     */
    public function findAvailableServer(): ?Testserver
    {
        return Testserver::available()
            ->orderBy('slot_number')
            ->first();
    }

    /**
     * Erstellt eine neue Session-Reservierung.
     * Status: pending (Server muss noch konfiguriert + restartet werden)
     */
    public function reserveSession(
        Testserver $server,
        string $mapSlug,
        string $modName,
        string $ipAddress,
        ?string $userAgent = null,
        ?int $userId = null,
        ?string $countryCode = null,
        ?int $mapFileId = null,
        ?string $mapPk3Filename = null,
    ): TestserverSession {
        return TestserverSession::create([
            'testserver_id'    => $server->id,
            'user_id'          => $userId,
            'ip_address'       => $ipAddress,
            'user_agent'       => $userAgent,
            'country_code'     => $countryCode,
            'mod_name'         => $modName,
            'map_slug'         => $mapSlug,
            'map_file_id'      => $mapFileId,
            'map_pk3_filename' => $mapPk3Filename,
            'connect_address'  => $server->connect_string, // accessor
            'status'           => 'pending',
            // session_token + connect_password werden vom Model auto-generiert
        ]);
    }

    /**
     * Markiert Server als reserviert (sperrt für andere Sessions).
     */
    public function lockServer(Testserver $server): bool
    {
        return $server->update([
            'status'         => 'reserving',
            'last_session_at'=> now(),
        ]);
    }

    /* ─────────────────────────────────────────
     * Pterodactyl API (Power, Variables, Status)
     * ─────────────────────────────────────────*/

    public function setStartupVariable(string $uuid, string $key, string $value): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->put("{$this->baseUrl}/api/client/servers/{$uuid}/startup/variable", [
                    'key'   => $key,
                    'value' => $value,
                ]);

            if (!$response->successful()) {
                Log::warning("Testserver: setStartupVariable failed", [
                    'uuid' => $uuid, 'key' => $key,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Testserver: setStartupVariable exception: {$e->getMessage()}");
            return false;
        }
    }

    public function setStartupVariables(string $uuid, array $variables): bool
    {
        $allOk = true;
        foreach ($variables as $key => $value) {
            if (!$this->setStartupVariable($uuid, $key, (string) $value)) {
                $allOk = false;
            }
        }
        return $allOk;
    }

    public function powerSignal(string $uuid, string $signal): bool
    {
        // signal: start | stop | restart | kill
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/power", [
                    'signal' => $signal,
                ]);
            return $response->successful() || $response->status() === 204;
        } catch (\Throwable $e) {
            Log::error("Testserver: powerSignal exception: {$e->getMessage()}");
            return false;
        }
    }

    public function getResources(string $uuid): ?array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/client/servers/{$uuid}/resources");
            return $response->successful() ? $response->json('attributes') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function sendCommand(string $uuid, string $command): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/command", [
                    'command' => $command,
                ]);
            return $response->successful() || $response->status() === 204;
        } catch (\Throwable $e) {
            Log::error("Testserver: sendCommand exception: {$e->getMessage()}");
            return false;
        }
    }

    /* ─────────────────────────────────────────
     * High-Level Operations
     * ─────────────────────────────────────────*/

    /**
     * Konfiguriert den Server mit den Session-Parametern (Variables setzen)
     * und triggert einen Restart. Das ist der Hauptcall im LaunchJob.
     */
    public function applySessionAndRestart(TestserverSession $session): bool
    {
        $server = $session->testserver;
        $uuid   = $server->pterodactyl_uuid;

        // 1. Pro Mod den richtigen CONFIG_FILE wählen
        $mod = \App\Models\TestserverMod::where('fs_game_dir', $session->mod_name)->first();
        $configFile = $mod?->default_config_file ?: 'etl_server.cfg';

        // 2. BSP-Name aus loaded-maps holen (echter Map-Name, nicht slug)
        $loaded = TestserverLoadedMap::where('testserver_id', $session->testserver_id)->first();
        $mapName = $loaded?->bsp_name ?: $session->map_slug;

        $vars = [
            'MAP'             => $mapName,
            'MOD'             => $session->mod_name,
            'CONFIG_FILE'     => $configFile,
            'SERVER_PASSWORD' => $session->connect_password,
            'SERVER_NAME'     => "^7Wolffiles.eu ^8| ^7Testserver ^5#" . $server->slot_number,
        ];

        if (!$this->setStartupVariables($uuid, $vars)) {
            $session->update([
                'status'        => 'failed',
                'error_message' => 'Failed to set Pterodactyl startup variables',
            ]);
            return false;
        }

        // 2. Status: launching
        $session->update([
            'status'     => 'launching',
            'started_at' => now(),
        ]);

        // 3. Restart triggern
        if (!$this->powerSignal($uuid, 'restart')) {
            $session->update([
                'status'        => 'failed',
                'error_message' => 'Failed to send restart signal',
            ]);
            return false;
        }

        return true;
    }

    /**
     * Markiert Session als active und setzt expires_at.
     * Wird aufgerufen nachdem Server-Status "running" bestätigt ist.
     */
    public function activateSession(TestserverSession $session): void
    {
        $session->update([
            'status'     => 'active',
            'started_at' => $session->started_at ?? now(),
            'expires_at' => now()->addMinutes($session->testserver->max_session_minutes),
        ]);

        $session->testserver->update([
            'status'         => 'active',
            'total_sessions' => $session->testserver->total_sessions + 1,
        ]);
    }

    /**
     * Beendet Session und resettet Server auf Default-Mod/Map.
     */
    public function endSession(TestserverSession $session, string $reason = 'expired'): bool
    {
        $server = $session->testserver;
        $uuid   = $server->pterodactyl_uuid;

        // 1. Session markieren
        $session->update([
            'status'   => 'expiring',
            'ended_at' => now(),
        ]);

        // 2. Server-Variablen auf Default zurücksetzen
        $this->setStartupVariables($uuid, [
            'MAP'             => $server->default_map,
            'MOD'             => $server->default_mod,
            'CONFIG_FILE'     => $server->default_config,
            'SERVER_PASSWORD' => '',
            'SERVER_NAME'     => "^7Wolffiles.eu ^8| ^7Testserver ^5#" . $server->slot_number . " ^2(frei)",
        ]);

        // 3. Server stoppen (Container bleibt, ist nur idle)
        $this->powerSignal($uuid, 'stop');

        // 4. Finalisieren
        $session->update(['status' => $reason]); // expired | cancelled | failed
        $server->update(['status' => 'idle']);

        return true;
    }

    /* ─────────────────────────────────────────
     * Rate-Limiting
     * ─────────────────────────────────────────*/

    public function hasReachedRateLimit(string $ipAddress, int $maxPerHour = 2, int $maxPerDay = 6): array
    {
        $hourCount = TestserverSession::forIp($ipAddress)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $dayCount = TestserverSession::forIp($ipAddress)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            'limited'        => $hourCount >= $maxPerHour || $dayCount >= $maxPerDay,
            'hour_count'     => $hourCount,
            'hour_max'       => $maxPerHour,
            'day_count'      => $dayCount,
            'day_max'        => $maxPerDay,
            'cooldown_until' => $hourCount >= $maxPerHour
                ? TestserverSession::forIp($ipAddress)->latest()->first()?->created_at?->addHour()
                : null,
        ];
    }

    public function hasActiveSession(string $ipAddress): ?TestserverSession
    {
        return TestserverSession::forIp($ipAddress)
            ->active()
            ->latest()
            ->first();
    }

    /* ─────────────────────────────────────────
     * Helpers
     * ─────────────────────────────────────────*/


    /* ─────────────────────────────────────────
     * MAP AUTO-LOADER (S3 → Pterodactyl Container)
     * ─────────────────────────────────────────*/

    /**
     * Stellt sicher, dass die Map im Container vorhanden ist.
     * - Wenn bereits geladen + selbe Map → skip (cache hit)
     * - Wenn andere Map geladen → alte löschen, neue laden
     * - Wenn nichts geladen → neue laden
     * - Wenn Map in pak0/1/2 (vanilla) → keine Aktion, vermerken
     */
    public function ensureMapLoaded(\App\Models\Testserver $server, string $mapSlug): array
    {
        // Vanilla-Maps die in pak1/pak2 schon drin sind
        $vanillaMaps = [
            'oasis','goldrush','radar','railgun','battery',
            'fueldump','siwa','airport','beach','et_ice',
        ];
        if (in_array(strtolower($mapSlug), $vanillaMaps, true)) {
            $existing = TestserverLoadedMap::where('testserver_id', $server->id)->first();
            $newCount = ($existing && $existing->map_slug === $mapSlug)
                ? $existing->use_count + 1
                : 1;
            TestserverLoadedMap::updateOrCreate(
                ['testserver_id' => $server->id],
                [
                    'map_slug'      => $mapSlug,
                    'bsp_name'      => $mapSlug,
                    'pk3_filenames' => [],
                    'source'        => 'vanilla',
                    'loaded_at'     => now(),
                    'last_used_at'  => now(),
                    'use_count'     => $newCount,
                ]
            );
            return ['success' => true, 'cached' => true, 'source' => 'vanilla'];
        }

        // Bereits geladene Map?
        $current = TestserverLoadedMap::where('testserver_id', $server->id)->first();
        if ($current && $current->map_slug === $mapSlug) {
            $current->increment('use_count');
            $current->update(['last_used_at' => now()]);
            return ['success' => true, 'cached' => true, 'source' => 'cache'];
        }

        // Map-File aus Wolffiles-DB suchen
        $file = \App\Models\File::where('slug', $mapSlug)
            ->whereHas('category', function ($q) {
                $q->where('slug', 'maps')
                  ->whereHas('parent', fn ($p) => $p->where('slug', 'et'));
            })
            ->where('status', 'approved')
            ->first();

        if (!$file || !$file->file_path) {
            \Log::warning("Map-Loader: Map nicht in Wolffiles-DB", ['slug' => $mapSlug]);
            return ['success' => false, 'error' => "Map '{$mapSlug}' nicht im Wolffiles-Katalog"];
        }

        // Alte Map-PK3s löschen (falls eine drin war)
        if ($current && !empty($current->pk3_filenames)) {
            $this->deleteFilesFromContainer(
                $server->pterodactyl_uuid,
                '/etmain',
                $current->pk3_filenames
            );
            \Log::info("Map-Loader: Alte Map geloescht", [
                'server' => $server->name,
                'old_map' => $current->map_slug,
                'files' => $current->pk3_filenames,
            ]);
        }

        // Signed URL erstellen
        $signedUrl = \Storage::disk('s3')->temporaryUrl(
            $file->file_path,
            now()->addMinutes(10)
        );

        $ext = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
        $beforeFiles = $this->listContainerFiles($server->pterodactyl_uuid, '/etmain');
        $beforePk3Names = array_column(
            array_filter($beforeFiles, fn($f) => str_ends_with(strtolower($f['name']), '.pk3')),
            'name'
        );

        if ($ext === 'pk3') {
            // ── DIRECT-PK3: kein Decompress nötig ──
            // FastDL filename hat Vorrang (matched mit dem was Client-side erwartet wird)
            $fastdl = \DB::table('fastdl_files')
                ->where('wolffiles_file_id', $file->id)
                ->where('is_active', 1)
                ->orderByDesc('id')
                ->first();

            // Self-Healing: Wenn kein FastDL-Eintrag, BSP-Name aus PK3 extrahieren + Auto-Sync
            if (!$fastdl) {
                $bsp = $this->extractBspNameFromPk3($file->file_path);
                if ($bsp) {
                    // Filename = BSP-Name + .pk3 (mit Original-Casing soweit möglich)
                    $autoFilename = $this->guessOriginalPk3Name($file->file_path, $bsp);
                    \Log::info('Map-Loader: Auto-Sync FastDL-Eintrag', [
                        'wolffiles_file_id' => $file->id,
                        'bsp' => $bsp,
                        'filename' => $autoFilename,
                    ]);
                    // FastDL Base-Directory für ET finden
                    $etBaseDir = \DB::table('fastdl_directories')
                        ->join('fastdl_games', 'fastdl_games.id', '=', 'fastdl_directories.game_id')
                        ->where('fastdl_games.slug', 'et')
                        ->where('fastdl_directories.is_base', 1)
                        ->select('fastdl_directories.id')
                        ->first();
                    if ($etBaseDir) {
                        // Check ob's schon einen Eintrag mit diesem Filename gibt (andere Map-Version)
                        $existing = \DB::table('fastdl_files')
                            ->where('directory_id', $etBaseDir->id)
                            ->where('filename', $autoFilename)
                            ->first();
                        if ($existing) {
                            // Existiert schon - aktualisieren falls neuere Version
                            $existingFile = \App\Models\File::find($existing->wolffiles_file_id);
                            if (!$existingFile || $file->created_at > $existingFile->created_at) {
                                \DB::table('fastdl_files')
                                    ->where('id', $existing->id)
                                    ->update([
                                        's3_path' => $file->file_path,
                                        'file_size' => $file->file_size ?? 0,
                                        'source' => 'auto_loader',
                                        'wolffiles_file_id' => $file->id,
                                        'is_active' => 1,
                                        'updated_at' => now(),
                                    ]);
                                \Log::info('Map-Loader: FastDL-Eintrag UPDATED (neuere Version)', [
                                    'fastdl_id' => $existing->id,
                                    'old_wolf_id' => $existing->wolffiles_file_id,
                                    'new_wolf_id' => $file->id,
                                ]);
                            } else {
                                \Log::info('Map-Loader: FastDL-Eintrag existiert (alt-er ist neuer)', [
                                    'fastdl_id' => $existing->id,
                                ]);
                            }
                            $fastdl = \DB::table('fastdl_files')->find($existing->id);
                        } else {
                            $newId = \DB::table('fastdl_files')->insertGetId([
                                'directory_id' => $etBaseDir->id,
                                'filename' => $autoFilename,
                                's3_path' => $file->file_path,
                                'file_size' => $file->file_size ?? 0,
                                'source' => 'auto_loader',
                                'wolffiles_file_id' => $file->id,
                                'is_active' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $fastdl = \DB::table('fastdl_files')->find($newId);
                        }
                    }
                }
            }

            $pk3Filename = $fastdl?->filename ?: basename($file->file_path);
            $pullOk = $this->pullFileToContainer(
                $server->pterodactyl_uuid,
                $signedUrl,
                '/etmain',
                $pk3Filename
            );
            if (!$pullOk) {
                return ['success' => false, 'error' => 'Pterodactyl Pull-API failed (PK3 direct)'];
            }
            \Log::info("Map-Loader: Direct-PK3 gepullt", ['file' => $pk3Filename]);
            sleep(3);

        } elseif ($ext === 'zip') {
            // ── ZIP-ARCHIVE: Pull + Decompress ──
            $zipFilename = $mapSlug . '_' . time() . '.zip';
            $pullOk = $this->pullFileToContainer(
                $server->pterodactyl_uuid,
                $signedUrl,
                '/etmain',
                $zipFilename
            );
            if (!$pullOk) {
                return ['success' => false, 'error' => 'Pterodactyl Pull-API failed (ZIP)'];
            }
            sleep(2);

            $decompressOk = $this->decompressInContainer(
                $server->pterodactyl_uuid,
                '/etmain',
                $zipFilename
            );
            if (!$decompressOk) {
                $this->deleteFilesFromContainer($server->pterodactyl_uuid, '/etmain', [$zipFilename]);
                return ['success' => false, 'error' => 'Pterodactyl Decompress failed'];
            }
            sleep(1);
            $this->deleteFilesFromContainer($server->pterodactyl_uuid, '/etmain', [$zipFilename]);

        } else {
            return ['success' => false, 'error' => "Unsupported file extension: .{$ext}"];
        }

        // Welche PK3s sind neu hinzugekommen?
        $afterFiles = $this->listContainerFiles($server->pterodactyl_uuid, '/etmain');
        $afterPk3s = array_filter($afterFiles, fn($f) => str_ends_with(strtolower($f['name']), '.pk3'));
        $newPk3s = array_filter($afterPk3s, fn($f) => !in_array($f['name'], $beforePk3Names));
        $newPk3Names = array_values(array_column($newPk3s, 'name'));
        $totalBytes = array_sum(array_column($newPk3s, 'size'));

        // BSP-Name aus PK3 extrahieren
        $bspName = $this->extractBspNameFromPk3($file->file_path);
        if (!$bspName) {
            $bspName = $mapSlug;
            \Log::warning("Map-Loader: BSP-Name fallback auf slug", ['slug' => $mapSlug]);
        }

        // DB-Tracking aktualisieren
        TestserverLoadedMap::updateOrCreate(
            ['testserver_id' => $server->id],
            [
                'map_slug'      => $mapSlug,
                'bsp_name'      => $bspName,
                'file_id'       => $file->id,
                'pk3_filenames' => $newPk3Names,
                'total_bytes'   => $totalBytes,
                'source'        => 's3',
                'loaded_at'     => now(),
                'last_used_at'  => now(),
                'use_count'     => 1,
            ]
        );

        \Log::info("Map-Loader: Map geladen", [
            'server' => $server->name,
            'map' => $mapSlug,
            'bsp' => $bspName,
            'pk3s' => $newPk3Names,
            'size_mb' => round($totalBytes / 1024 / 1024, 1),
        ]);

        return [
            'success'   => true,
            'cached'    => false,
            'source'    => 's3',
            'pk3s'      => $newPk3Names,
            'size_mb'   => round($totalBytes / 1024 / 1024, 1),
        ];
    }

    /* ─────────────────────────────────────────
     * Low-Level Pterodactyl File API
     * ─────────────────────────────────────────*/


    /**
     * Extrahiert den ersten BSP-Filename aus der Map-ZIP auf S3.
     * Wird lokal heruntergeladen (temp file), ZIP parsed, BSP-Name gefunden.
     * Returns: lowercase BSP-Name ohne maps/-Prefix und .bsp-Suffix
     *          z.B. "(um)arena" oder "ae_wizerness"
     */

    /**
     * Versucht den originalen PK3-Filename aus dem S3-PK3 zu lesen (mit Original-Casing).
     * Falls extraction failed, fallback auf $bspName + .pk3.
     */
    protected function guessOriginalPk3Name(string $s3Pk3Path, string $bspName): string
    {
        try {
            $url = \Storage::disk('s3')->temporaryUrl($s3Pk3Path, now()->addMinutes(2));
            $tmp = tempnam(sys_get_temp_dir(), 'pk3_name_');
            $bytes = @file_get_contents($url);
            if (!$bytes) {
                @unlink($tmp);
                return $bspName . '.pk3';
            }
            file_put_contents($tmp, $bytes);

            $pk3 = new \ZipArchive();
            if ($pk3->open($tmp) !== true) {
                @unlink($tmp);
                return $bspName . '.pk3';
            }

            // Original-Casing aus BSP-Filename im PK3 holen
            $realBspName = null;
            for ($i = 0; $i < $pk3->numFiles; $i++) {
                $name = $pk3->getNameIndex($i);
                if (preg_match('#^maps/(.+)\\.bsp$#i', $name, $m)) {
                    $realBspName = $m[1]; // Original-Casing!
                    break;
                }
            }
            $pk3->close();
            @unlink($tmp);

            return ($realBspName ?: $bspName) . '.pk3';
        } catch (\Throwable $e) {
            \Log::error('guessOriginalPk3Name failed: ' . $e->getMessage());
            return $bspName . '.pk3';
        }
    }

    protected function extractBspNameFromPk3(string $s3FilePath): ?string
    {
        try {
            $url = \Storage::disk('s3')->temporaryUrl($s3FilePath, now()->addMinutes(5));
            $ext = strtolower(pathinfo($s3FilePath, PATHINFO_EXTENSION));
            $tmpPk3 = tempnam(sys_get_temp_dir(), 'mappk3_');

            if ($ext === 'pk3') {
                // Direkter PK3-Download - keine ZIP-Schicht
                $bytes = @file_get_contents($url);
                if (!$bytes) { @unlink($tmpPk3); return null; }
                file_put_contents($tmpPk3, $bytes);

            } elseif ($ext === 'zip') {
                // ZIP runter, erste PK3 darin extrahieren
                $tmpZip = tempnam(sys_get_temp_dir(), 'mapzip_');
                $bytes = @file_get_contents($url);
                if (!$bytes) { @unlink($tmpZip); @unlink($tmpPk3); return null; }
                file_put_contents($tmpZip, $bytes);

                $zip = new \ZipArchive();
                if ($zip->open($tmpZip) !== true) {
                    @unlink($tmpZip); @unlink($tmpPk3); return null;
                }
                $pk3Found = false;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (str_ends_with(strtolower($name), '.pk3')) {
                        file_put_contents($tmpPk3, $zip->getFromIndex($i));
                        $pk3Found = true;
                        break;
                    }
                }
                $zip->close();
                @unlink($tmpZip);
                if (!$pk3Found) { @unlink($tmpPk3); return null; }
            } else {
                @unlink($tmpPk3);
                return null;
            }

            // PK3 oeffnen, ersten maps/*.bsp finden
            $pk3 = new \ZipArchive();
            if ($pk3->open($tmpPk3) !== true) { @unlink($tmpPk3); return null; }

            $bspName = null;
            for ($i = 0; $i < $pk3->numFiles; $i++) {
                $name = $pk3->getNameIndex($i);
                if (preg_match('#^maps/(.+)\\.bsp$#i', $name, $m)) {
                    $bspName = strtolower($m[1]);
                    break;
                }
            }
            $pk3->close();
            @unlink($tmpPk3);

            return $bspName;
        } catch (\Throwable $e) {
            \Log::error("BSP-Extract failed: {$e->getMessage()}");
            return null;
        }
    }

    protected function pullFileToContainer(string $uuid, string $url, string $directory, string $filename): bool
    {
        try {
            $r = Http::withHeaders($this->headers() + ['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/files/pull", [
                    'url' => $url,
                    'directory' => $directory,
                    'filename' => $filename,
                    'use_header' => false,
                ]);
            return $r->successful() || $r->status() === 204;
        } catch (\Throwable $e) {
            \Log::error("Pull failed: {$e->getMessage()}");
            return false;
        }
    }

    protected function decompressInContainer(string $uuid, string $root, string $file): bool
    {
        try {
            $r = Http::withHeaders($this->headers() + ['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/files/decompress", [
                    'root' => $root,
                    'file' => $file,
                ]);
            return $r->successful() || $r->status() === 204;
        } catch (\Throwable $e) {
            \Log::error("Decompress failed: {$e->getMessage()}");
            return false;
        }
    }

    protected function deleteFilesFromContainer(string $uuid, string $root, array $files): bool
    {
        if (empty($files)) return true;
        try {
            $r = Http::withHeaders($this->headers() + ['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/api/client/servers/{$uuid}/files/delete", [
                    'root' => $root,
                    'files' => array_values($files),
                ]);
            return $r->successful() || $r->status() === 204;
        } catch (\Throwable $e) {
            \Log::error("Delete failed: {$e->getMessage()}");
            return false;
        }
    }

    protected function listContainerFiles(string $uuid, string $directory): array
    {
        try {
            $r = Http::withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/client/servers/{$uuid}/files/list?directory=" . urlencode($directory));

            if (!$r->successful()) return [];
            $files = [];
            foreach ($r->json('data', []) as $f) {
                $a = $f['attributes'];
                $files[] = [
                    'name'    => $a['name'],
                    'size'    => $a['size'],
                    'is_file' => $a['is_file'],
                ];
            }
            return $files;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    protected function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'Application/vnd.pterodactyl.v1+json',
        ];
    }
}
