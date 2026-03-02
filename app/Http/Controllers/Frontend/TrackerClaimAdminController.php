<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerClaim;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerPlayer;
use Illuminate\Http\Request;

class TrackerClaimAdminController extends Controller
{
    /**
     * List all claims (filterable by status)
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = TrackerClaim::with('user')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $claims = $query->paginate(25)->withQueryString();

        // Eager-load the entities
        $claims->getCollection()->transform(function ($claim) {
            if ($claim->claimable_type === 'player') {
                $claim->entity = TrackerPlayer::find($claim->claimable_id);
            } else {
                $claim->entity = TrackerClan::find($claim->claimable_id);
            }
            return $claim;
        });

        $pendingCount = TrackerClaim::pending()->count();

        return view('frontend.tracker.admin-claims', compact('claims', 'status', 'pendingCount'));
    }

    /**
     * Show single claim detail
     */
    public function show(TrackerClaim $claim)
    {
        $claim->load('user', 'reviewer');

        if ($claim->claimable_type === 'player') {
            $claim->entity = TrackerPlayer::find($claim->claimable_id);
        } else {
            $claim->entity = TrackerClan::with('activeMembers.player')->find($claim->claimable_id);
        }

        // Other claims for the same entity
        $otherClaims = TrackerClaim::where('claimable_type', $claim->claimable_type)
            ->where('claimable_id', $claim->claimable_id)
            ->where('id', '!=', $claim->id)
            ->with('user')
            ->get();

        return view('frontend.tracker.admin-claim-detail', compact('claim', 'otherClaims'));
    }

    /**
     * Approve a claim
     */
    public function approve(Request $request, TrackerClaim $claim)
    {
        if (!$claim->isPending()) {
            return back()->with('error', 'This claim has already been reviewed.');
        }

        $claim->approve(auth()->id(), $request->get('review_note'));

        return redirect()->route('tracker.admin.claims')
            ->with('success', "Claim #{$claim->id} approved successfully.");
    }

    /**
     * Reject a claim
     */
    public function reject(Request $request, TrackerClaim $claim)
    {
        $request->validate(['review_note' => 'required|string|max:500']);

        if (!$claim->isPending()) {
            return back()->with('error', 'This claim has already been reviewed.');
        }

        $claim->reject(auth()->id(), $request->review_note);

        return redirect()->route('tracker.admin.claims')
            ->with('success', "Claim #{$claim->id} rejected.");
    }
}
