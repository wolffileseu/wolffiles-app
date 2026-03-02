<?php

namespace App\Http\Controllers;

use App\Models\EttvSlot;
use App\Models\File;
use App\Services\PterodactylService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class EttvController extends Controller
{
    public function __construct(protected PterodactylService $pterodactyl) {}

    public function index()
    {
        $slots = EttvSlot::with(['demo', 'event', 'user'])->orderBy('slot_number')->get();
        $liveSlots = $slots->where('status', 'relay');
        $playingSlots = $slots->where('status', 'playing');
        $showcaseSlots = $slots->where('mode', 'showcase');
        /** @phpstan-ignore argument.type */
        return view('ettv.index', compact('slots', 'liveSlots', 'playingSlots', 'showcaseSlots'));
    }

    public function watchDemo(Request $request, File $demo)
    {
        if (!str_ends_with($demo->filename, '.tv_84')) {
            return back()->with('error', __('Nur ETTV Demos (.tv_84) koennen gestreamt werden.'));
        }

        $key = 'ettv-watch:' . ($request->user()->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('error', __('Rate limit erreicht. Bitte warte.'));
        }
        RateLimiter::hit($key, 3600);

        $slot = EttvSlot::where('status', 'idle')->where('slot_number', '>=', 2)->orderBy('slot_number')->first();
        if (!$slot) {
            return back()->with('error', __('Alle ETTV Server sind belegt.'));
        }

        $tempPath = storage_path('app/temp/' . $demo->filename);
        @mkdir(dirname($tempPath), 0755, true);
        Storage::disk('s3')->copy($demo->file_path, 'temp/' . $demo->filename);

        if (!$this->pterodactyl->uploadDemo($slot, $tempPath)) {
            @unlink($tempPath);
            return back()->with('error', __('Demo Upload fehlgeschlagen.'));
        }
        @unlink($tempPath);

        $slot->update([
            'status' => 'playing',
            'mode' => 'demo',
            'demo_id' => $demo->id,
            'demo_name' => pathinfo($demo->filename, PATHINFO_FILENAME),
            'map_name' => $demo->map_name ?? null,
            'user_id' => $request->user()?->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->pterodactyl->startServer($slot);

        /** @phpstan-ignore argument.type */
        return view('ettv.watch', ['slot' => $slot, 'demo' => $demo]);
    }
}
