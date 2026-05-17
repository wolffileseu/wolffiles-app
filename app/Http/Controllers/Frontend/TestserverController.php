<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\ExpireTestSessionJob;
use App\Jobs\LaunchTestSessionJob;
use App\Models\File;
use App\Models\Testserver;
use App\Models\TestserverMod;
use App\Models\TestserverSession;
use App\Models\TestserverSetting;
use App\Services\TestserverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestserverController extends Controller
{
    public function __construct(
        protected TestserverService $service
    ) {}

    /* ────────────────────────────────────────────
     * GET /testserver – Übersicht aller Server
     * ────────────────────────────────────────────*/
    public function index(): View
    {
        $this->abortIfDisabled();

        $servers = Testserver::public()
            ->orderBy('slot_number')
            ->get();

        return view('frontend.testserver.index', [
            'servers'  => $servers,
            'settings' => TestserverSetting::current(),
        ]);
    }

    /* ────────────────────────────────────────────
     * GET /testserver/launch?map={slug} – Auswahl-Page
     * ────────────────────────────────────────────*/
    public function launch(Request $request): View|RedirectResponse
    {
        $this->abortIfDisabled();

        $mapSlug = $request->query('map');
        $mapFile = null;

        if ($mapSlug) {
            $mapFile = File::where('slug', $mapSlug)->first();
        }

        $servers = Testserver::public()
            ->orderBy('slot_number')
            ->get();

        // Wenn der User bereits eine aktive Session hat, redirect dorthin
        $existing = TestserverSession::forIp($request->ip())
            ->active()
            ->latest()
            ->first();

        if ($existing) {
            return redirect()->route('testserver.show', $existing->session_token)
                ->with('info', 'Du hast bereits eine aktive Session');
        }

        return view('frontend.testserver.launch', [
            'servers'  => $servers,
            'mapSlug'  => $mapSlug,
            'mapFile'  => $mapFile,
            'settings' => TestserverSetting::current(),
        ]);
    }

    /* ────────────────────────────────────────────
     * POST /testserver/reserve – Session erstellen
     * ────────────────────────────────────────────*/
    public function reserve(Request $request): JsonResponse
    {
        $this->abortIfDisabled();

        $data = $request->validate([
            'testserver_slug' => 'required|string|exists:testservers,slug',
            'mod_slug'        => 'required|string|exists:testserver_mods,slug',
            'map_slug'        => 'required|string|max:128',
        ]);

        $settings = TestserverSetting::current();
        $ip = $request->ip();

        // Login-Pflicht prüfen
        if ($settings->require_login && !auth()->check()) {
            return response()->json([
                'success' => false,
                'error'   => 'Für Testsessions ist ein Login erforderlich.',
            ], 403);
        }

        // Rate-Limit Check (nur wenn aktiviert)
        if ($settings->rate_limit_enabled) {
            $maxHour = auth()->check() ? $settings->user_max_per_hour : $settings->anon_max_per_hour;
            $maxDay  = auth()->check() ? $settings->user_max_per_day  : $settings->anon_max_per_day;

            $limit = $this->service->hasReachedRateLimit($ip, $maxHour, $maxDay);
            if ($limit['limited']) {
                return response()->json([
                    'success' => false,
                    'error'   => "Rate-Limit erreicht ({$limit['hour_count']}/{$maxHour} pro Stunde, "
                                . "{$limit['day_count']}/{$maxDay} pro Tag). "
                                . "Bitte später erneut versuchen.",
                ], 429);
            }

            // Cooldown prüfen (Sekunden seit letzter Session)
            if ($settings->cooldown_minutes > 0) {
                $lastSession = TestserverSession::forIp($ip)->latest()->first();
                if ($lastSession && $lastSession->created_at->diffInMinutes(now()) < $settings->cooldown_minutes) {
                    $waitMin = $settings->cooldown_minutes
                        - (int) $lastSession->created_at->diffInMinutes(now());
                    return response()->json([
                        'success' => false,
                        'error'   => "Bitte warte noch {$waitMin} Minute(n) bis zur nächsten Session.",
                    ], 429);
                }
            }
        }

        // Aktive Session derselben IP? Dann blockieren
        $active = $this->service->hasActiveSession($ip);
        if ($active) {
            return response()->json([
                'success'  => false,
                'error'    => 'Du hast bereits eine aktive Session.',
                'redirect' => route('testserver.show', $active->session_token),
            ], 409);
        }

        // Server suchen
        $server = Testserver::where('slug', $data['testserver_slug'])
            ->public()
            ->first();

        if (!$server || !$server->isAvailable()) {
            return response()->json([
                'success' => false,
                'error'   => 'Dieser Server ist gerade nicht verfügbar.',
            ], 409);
        }

        // Mod prüfen (passt Mod zum gewählten Server?)
        $allowedSlugs = $server->allowedMods()->pluck('slug')->toArray();
        if (!in_array($data['mod_slug'], $allowedSlugs)) {
            return response()->json([
                'success' => false,
                'error'   => 'Dieser Mod ist auf diesem Server nicht erlaubt.',
            ], 422);
        }

        $mod = TestserverMod::where('slug', $data['mod_slug'])->first();

        // Map-Datei finden (optional)
        $mapFile = File::where('slug', $data['map_slug'])->first();

        // Session anlegen
        $session = $this->service->reserveSession(
            server:         $server,
            mapSlug:        $data['map_slug'],
            modName:        $mod->fs_game_dir,
            ipAddress:      $ip,
            userAgent:      $request->userAgent(),
            userId:         auth()->id(),
            countryCode:    $request->header('CF-IPCountry'),
            mapFileId:      $mapFile?->id,
            mapPk3Filename: $mapFile?->filename,
        );

        // Server locken + Job dispatchen
        $this->service->lockServer($server);
        LaunchTestSessionJob::dispatch($session->id);

        return response()->json([
            'success'  => true,
            'token'    => $session->session_token,
            'redirect' => route('testserver.show', $session->session_token),
        ]);
    }

    /* ────────────────────────────────────────────
     * GET /testserver/s/{token} – Connect-Page
     * ────────────────────────────────────────────*/
    public function show(string $token): View|RedirectResponse
    {
        $session = TestserverSession::where('session_token', $token)
            ->with(['testserver', 'mapFile'])
            ->first();

        if (!$session) {
            return redirect()->route('testserver.launch')
                ->with('error', 'Session nicht gefunden.');
        }

        return view('frontend.testserver.show', [
            'session'  => $session,
            'settings' => TestserverSetting::current(),
        ]);
    }

    /* ────────────────────────────────────────────
     * GET /testserver/s/{token}/status – JSON für Polling
     * ────────────────────────────────────────────*/
    public function status(string $token): JsonResponse
    {
        $session = TestserverSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'id'                => $session->id,
            'status'            => $session->status,
            'started_at'        => $session->started_at?->toIso8601String(),
            'expires_at'        => $session->expires_at?->toIso8601String(),
            'ended_at'          => $session->ended_at?->toIso8601String(),
            'remaining_seconds' => $session->remaining_seconds,
            'connect_address'   => $session->connect_address,
            'connect_password'  => $session->connect_password,
            'map_slug'          => $session->map_slug,
            'mod_name'          => $session->mod_name,
            'error_message'     => $session->error_message,
        ]);
    }

    /* ────────────────────────────────────────────
     * POST /testserver/s/{token}/cancel – Session beenden
     * ────────────────────────────────────────────*/
    public function cancel(Request $request, string $token): JsonResponse
    {
        $session = TestserverSession::where('session_token', $token)->first();

        if (!$session) {
            return response()->json(['error' => 'not_found'], 404);
        }

        // Nur IP-Owner darf abbrechen (oder eingeloggter User)
        $isOwner = $session->ip_address === $request->ip()
                || (auth()->check() && $session->user_id === auth()->id());

        if (!$isOwner) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        if ($session->isFinished()) {
            return response()->json(['success' => true, 'already_ended' => true]);
        }

        ExpireTestSessionJob::dispatch($session->id, 'cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Session wird beendet, Server resetted in wenigen Sekunden.',
        ]);
    }

    /* ────────────────────────────────────────────
     * Helper: Feature-Toggle Check
     * ────────────────────────────────────────────*/
    protected function abortIfDisabled(): void
    {
        if (!TestserverSetting::current()->feature_enabled) {
            abort(404);
        }
    }
}
