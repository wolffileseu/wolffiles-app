<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerClaim;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerServer;
use Illuminate\Http\Request;

class TrackerClaimController extends Controller
{
    /**
     * Show claim form for a player profile
     */
    public function claimPlayer(TrackerPlayer $player)
    {
        if ($player->claimed_by_user_id) {
            return back()->with('error', __('This player profile has already been claimed.'));
        }

        $existingClaim = TrackerClaim::where('user_id', auth()->id())
            ->where('claimable_type', 'player')
            ->where('claimable_id', $player->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingClaim) {
            return back()->with('info', __('You already have a pending claim for this player.'));
        }

        return view('frontend.tracker.claim-player', compact('player'));
    }

    /**
     * Submit a player claim
     */
    public function storePlayerClaim(Request $request, TrackerPlayer $player)
    {
        $request->validate([
            'message' => 'required|string|min:10|max:1000',
            'proof_type' => 'required|in:guid,screenshot,server_admin,known_player,other',
        ]);

        if ($player->claimed_by_user_id) {
            return back()->with('error', __('This player profile has already been claimed.'));
        }

        $this->checkPendingLimit();

        TrackerClaim::create([
            'user_id' => auth()->id(),
            'claimable_type' => 'player',
            'claimable_id' => $player->id,
            'message' => $request->message,
            'proof_type' => $request->proof_type,
        ]);

        return redirect()->route('tracker.player.show', $player)
            ->with('success', __('Your claim has been submitted and is pending moderator review.'));
    }

    /**
     * Show claim form for a clan
     */
    public function claimClan(TrackerClan $clan)
    {
        if ($clan->claimed_by_user_id) {
            return back()->with('error', __('This clan has already been claimed.'));
        }

        $existingClaim = TrackerClaim::where('user_id', auth()->id())
            ->where('claimable_type', 'clan')
            ->where('claimable_id', $clan->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingClaim) {
            return back()->with('info', __('You already have a pending claim for this clan.'));
        }

        return view('frontend.tracker.claim-clan', compact('clan'));
    }

    /**
     * Submit a clan claim
     */
    public function storeClanClaim(Request $request, TrackerClan $clan)
    {
        $request->validate([
            'message' => 'required|string|min:10|max:1000',
            'proof_type' => 'required|in:clan_leader,clan_member,server_admin,other',
            'clan_email' => 'nullable|email|max:255',
            'clan_website' => 'nullable|url|max:255',
            'clan_discord' => 'nullable|string|max:255',
            'clan_description' => 'nullable|string|max:2000',
        ]);

        if ($clan->claimed_by_user_id) {
            return back()->with('error', __('This clan has already been claimed.'));
        }

        $this->checkPendingLimit();

        TrackerClaim::create([
            'user_id' => auth()->id(),
            'claimable_type' => 'clan',
            'claimable_id' => $clan->id,
            'message' => $request->message,
            'proof_type' => $request->proof_type,
            'clan_email' => $request->clan_email,
            'clan_website' => $request->clan_website,
            'clan_discord' => $request->clan_discord,
            'clan_description' => $request->clan_description,
        ]);

        return redirect()->route('tracker.clan.show', $clan)
            ->with('success', __('Your clan claim has been submitted and is pending moderator review.'));
    }

    /**
     * Show claim form for a server
     */
    public function claimServer(TrackerServer $server)
    {
        if ($server->claimed_by_user_id) {
            return back()->with('error', __('This server has already been claimed.'));
        }

        $existingClaim = TrackerClaim::where('user_id', auth()->id())
            ->where('claimable_type', 'server')
            ->where('claimable_id', $server->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingClaim) {
            return back()->with('info', __('You already have a pending claim for this server.'));
        }

        return view('frontend.tracker.claim-server', compact('server'));
    }

    /**
     * Submit a server claim
     */
    public function storeServerClaim(Request $request, TrackerServer $server)
    {
        $request->validate([
            'message' => 'required|string|min:10|max:1000',
            'proof_type' => 'required|in:server_admin,server_hoster,ip_owner,other',
            'server_description' => 'nullable|string|max:2000',
            'server_website' => 'nullable|url|max:255',
            'server_discord' => 'nullable|string|max:255',
            'server_email' => 'nullable|email|max:255',
        ]);

        if ($server->claimed_by_user_id) {
            return back()->with('error', __('This server has already been claimed.'));
        }

        $this->checkPendingLimit();

        // Reuse clan_* fields for server details
        TrackerClaim::create([
            'user_id' => auth()->id(),
            'claimable_type' => 'server',
            'claimable_id' => $server->id,
            'message' => $request->message,
            'proof_type' => $request->proof_type,
            'clan_description' => $request->server_description,
            'clan_website' => $request->server_website,
            'clan_discord' => $request->server_discord,
            'clan_email' => $request->server_email,
        ]);

        return redirect()->route('tracker.server.show', $server)
            ->with('success', __('Your server claim has been submitted and is pending moderator review.'));
    }

    /**
     * Show user's own claims
     */
    public function myClaims()
    {
        $claims = TrackerClaim::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($claim) {
                if ($claim->claimable_type === 'player') {
                    $claim->entity = TrackerPlayer::find($claim->claimable_id);
                } elseif ($claim->claimable_type === 'clan') {
                    $claim->entity = TrackerClan::find($claim->claimable_id);
                } else {
                    $claim->entity = TrackerServer::find($claim->claimable_id);
                }
                return $claim;
            });

        return view('frontend.tracker.my-claims', compact('claims'));
    }

    /**
     * Check pending claim limit
     */
    protected function checkPendingLimit(): void
    {
        $pendingCount = TrackerClaim::where('user_id', auth()->id())->pending()->count();
        if ($pendingCount >= 5) {
            abort(back()->with('error', __('You already have 5 pending claims. Please wait for them to be reviewed.')));
        }
    }
}
