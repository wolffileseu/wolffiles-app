<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reportable_type' => 'required|in:App\Models\File,App\Models\Comment,App\Models\LuaScript',
            'reportable_id' => 'required|integer',
            'reason' => 'required|in:copyright,broken,inappropriate,spam,other',
            'description' => 'nullable|string|max:1000',
            // Honeypot spam protection
            'website_url' => 'max:0',
        ]);

        // Check if already reported by this user
        $existing = Report::where('user_id', auth()->id())
            ->where('reportable_type', $request->reportable_type)
            ->where('reportable_id', $request->reportable_id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return back()->with('error', __('messages.already_reported'));
        }

        Report::create([
            'user_id' => auth()->id(),
            'reportable_type' => $request->reportable_type,
            'reportable_id' => $request->reportable_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        ActivityLogger::log('report', $request->reportable_type, (int)$request->reportable_id, [
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return back()->with('success', __('messages.report_submitted'));
    }
}
