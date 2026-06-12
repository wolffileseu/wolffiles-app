<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerPlayerReport;
use App\Models\Tracker\TrackerPlayerReportEvidence;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackerPlayerReportController extends Controller
{
    public function create(TrackerPlayer $player)
    {
        return view('frontend.tracker.report-player', compact('player'));
    }

    public function store(Request $request, TrackerPlayer $player)
    {
        $request->validate([
            'description'    => 'required|string|min:10|max:1000',
            'reported_guid'  => 'nullable|string|regex:/^[a-fA-F0-9]{2,64}$/',
            'contact'        => 'nullable|string|max:255',
            'screenshots'    => 'nullable|array|max:5',
            'screenshots.*'  => 'image|max:10240', // 10MB each
            // Honeypot
            'website_url'    => 'max:0',
        ], [
            'reported_guid.regex' => __('The GUID must be hexadecimal (2-64 characters).'),
        ]);

        // Rate limit: max 5 reports per user per day
        $today = TrackerPlayerReport::where('user_id', auth()->id())
            ->where('created_at', '>=', now()->subDay())->count();
        if ($today >= 5) {
            return back()->with('error', __('You have reached the daily report limit. Please try again tomorrow.'));
        }

        // Duplicate guard: existing pending report by this user for this player
        $existing = TrackerPlayerReport::where('user_id', auth()->id())
            ->where('reported_player_id', $player->id)
            ->where('status', 'pending')->exists();
        if ($existing) {
            return back()->with('error', __('You already have a pending report for this player.'));
        }

        $report = TrackerPlayerReport::create([
            'user_id'            => auth()->id(),
            'reported_player_id' => $player->id,
            'reported_guid'      => $request->reported_guid,
            'description'        => $request->description,
            'contact'            => $request->contact,
            'status'             => 'pending',
        ]);

        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $file) {
                $path = $file->store('report-evidence', 's3');
                TrackerPlayerReportEvidence::create([
                    'report_id'  => $report->id,
                    'file_path'  => $path,
                    'created_at' => now(),
                ]);
            }
        }

        ActivityLogger::log('player_report', TrackerPlayer::class, $player->id, [
            'report_id' => $report->id,
        ]);

        return redirect()->route('tracker.player.show', $player)
            ->with('success', __('Thank you. Your report has been submitted and will be reviewed by our team.'));
    }
}
