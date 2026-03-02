<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $events = Event::whereIn('status', ['approved', 'live', 'completed'])
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->orderBy('starts_at')
            ->get();

        $liveEvents = Event::where('status', 'live')->get();
        $upcomingEvents = Event::upcoming()->orderBy('starts_at')->take(10)->get();

        /** @phpstan-ignore argument.type */
        /** @phpstan-ignore argument.type */
        return view('events.index', compact('events', 'liveEvents', 'upcomingEvents', 'month', 'year'));
    }

    public function show(Event $event)
    {
        if ($event->isPending()) abort(404);
        /** @phpstan-ignore argument.type */
        /** @phpstan-ignore argument.type */
        return view('events.show', compact('event'));
    }

    public function create()
    {
        /** @phpstan-ignore argument.type */
        /** @phpstan-ignore argument.type */
        return view('events.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:128',
            'description' => 'nullable|string|max:2000',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'team_axis' => 'nullable|string|max:64',
            'team_allies' => 'nullable|string|max:64',
            'map_name' => 'nullable|string|max:64',
            'match_type' => 'nullable|string|max:32',
            'gametype' => 'nullable|string|max:32',
            'mod_name' => 'nullable|string|max:32',
            'match_server_ip' => 'nullable|string|max:64',
            'match_server_port' => 'nullable|integer|between:1024,65535',
            'ettv_enabled' => 'nullable|boolean',
        ]);

        $validated['submitted_by'] = $request->user()->id;
        $validated['status'] = 'pending';
        $validated['slug'] = Str::slug($validated['title'] . '-' . now()->format('Y-m-d'));
        $validated['ettv_enabled'] = $validated['ettv_enabled'] ?? true;
        $validated['gametype'] = $validated['gametype'] ?? 'stopwatch';
        $validated['mod_name'] = $validated['mod_name'] ?? 'etpro';

        Event::create($validated);

        return redirect()->route('events.index')->with('success', __('Event eingereicht! Ein Moderator wird es pruefen.'));
    }
}
