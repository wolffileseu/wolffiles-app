<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\CountryList;
use App\Http\Controllers\Controller;
use App\Models\Tracker\TrackerPlayer;
use Illuminate\Http\Request;

class PlayerManageController extends Controller
{
    /**
     * Gate: only the user who claimed this player (or admin) can manage.
     */
    private function gate(TrackerPlayer $player): void
    {
        $user = auth()->user();
        abort_unless($user, 403);
        $isOwner = $player->claimed_by_user_id === $user->id;
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
        abort_unless($isOwner || $isAdmin, 403, 'You do not own this player.');
    }

    /** Show the manage page for a player. */
    public function index(TrackerPlayer $player)
    {
        $this->gate($player);
        return view('frontend.tracker.player-manage', compact('player'));
    }

    /** Update player profile data. */
    public function updateProfile(Request $request, TrackerPlayer $player)
    {
        $this->gate($player);

        $data = $request->validate([
            'display_name'  => 'nullable|string|max:100',
            'tagline'       => 'nullable|string|max:200',
            'bio'           => 'nullable|string|max:20000',
            'avatar_url'    => 'nullable|url|max:500',
            'banner_url'    => 'nullable|url|max:500',
            'youtube_url'   => 'nullable|url|max:255',
            'twitch_url'    => 'nullable|url|max:255',
            'discord_url'   => 'nullable|string|max:255',
            'twitter_url'   => 'nullable|url|max:255',
            'website_url'   => 'nullable|url|max:255',
            'country_code'  => 'nullable|string|size:2',
        ]);

        // Country: derive country name from code, lock against future auto-updates
        if (isset($data['country_code']) && $data['country_code']) {
            $data['country_code']   = strtoupper($data['country_code']);
            $data['country']        = CountryList::nameFromCode($data['country_code']);
            $data['country_locked'] = true;
        }

        $player->update($data);

        return redirect()
            ->route('tracker.player.manage', $player)
            ->with('success', __('Profile updated.'));
    }
}
