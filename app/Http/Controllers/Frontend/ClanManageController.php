<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Clan;
use App\Models\ClanApplication;
use App\Models\ClanManager;
use App\Models\Post;
use App\Models\Tracker\TrackerClanMember;
use App\Models\Tracker\TrackerClanSquad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClanManageController extends Controller
{
    /** Resolve the manager record or abort. */
    protected function gate(Clan $clan, array $allowedRoles = ['owner', 'admin', 'editor']): ClanManager
    {
        $manager = $clan->managers()->where('user_id', auth()->id())->first();
        abort_unless($manager && in_array($manager->role, $allowedRoles), 403);
        return $manager;
    }

    /** Dashboard. */
    public function index(Clan $clan)
    {
        $manager = $this->gate($clan);
        $clan->load(['trackerClan.squads', 'managers.user']);

        $members = collect();
        $squads = collect();
        if ($clan->trackerClan) {
            $members = TrackerClanMember::with('player', 'squad')
                ->where('clan_id', $clan->trackerClan->id)
                ->where('is_active', true)
                ->get()
                ->filter(fn ($m) => $m->player !== null)
                ->sortBy('sort_order');
            $squads = $clan->trackerClan->squads;
        }

        $news = Post::where('clan_id', $clan->id)->where('type', Post::TYPE_NEWS)->latest()->limit(20)->get();
        $applications = ClanApplication::with('applicant')
            ->where('clan_id', $clan->id)
            ->orderByRaw("FIELD(status,'pending','accepted','rejected','withdrawn')")
            ->latest()->get();

        $apiKeys = \App\Models\ClanApiKey::where('clan_id', $clan->id)->orderByDesc('id')->get();
        $hasPendingApiKey = $apiKeys->contains(fn($k) => str_starts_with($k->getAttributes()['key'] ?? '', 'PENDING:'));

        // Servers: claimed + auto-detected (by tag prefix on hostname)
        $claimedServers = \App\Models\Tracker\TrackerServer::where('claimed_by_clan_id', $clan->id)->orderBy('hostname_clean')->get();
        $autoDetectedServers = $clan->autoDetectedServersQuery()->orderBy('hostname_clean')->get();

        return view('frontend.clan.manage', compact('clan', 'manager', 'members', 'squads', 'news', 'applications', 'apiKeys', 'hasPendingApiKey', 'claimedServers', 'autoDetectedServers'));
    }

    /** Save page content (about, rules, info, links). */
    public function updateContent(Request $request, Clan $clan)
    {
        $this->gate($clan); // editor+ allowed
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'tag_display'         => 'nullable|string|max:50',
            'description'         => 'nullable|string|max:20000',
            'rules'               => 'nullable|string|max:20000',
            'location'            => 'nullable|string|max:255',
            'founded'             => 'nullable|string|max:50',
            'website'             => 'nullable|url|max:255',
            'contact_discord'     => 'nullable|string|max:255',
            'ts_address'          => 'nullable|string|max:255',
            'logo'                => 'nullable|url|max:500',
            'banner'              => 'nullable|url|max:500',
            'is_recruiting'       => 'nullable|boolean',
            'recruitment_summary' => 'nullable|string|max:5000',
            'is_published'        => 'nullable|boolean',
        ]);
        $data['is_recruiting'] = $request->boolean('is_recruiting');
        $data['is_published']  = $request->boolean('is_published');
        $clan->update($data);

        return back()->with('success', __('Clan page updated.'));
    }

    /** Update a single member's role_label + squad. */
    public function updateMember(Request $request, Clan $clan, TrackerClanMember $member)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($clan->trackerClan && $member->clan_id === $clan->trackerClan->id, 404);
        $data = $request->validate([
            'role_label' => 'nullable|string|max:50',
            'squad_id'   => 'nullable|integer|exists:tracker_clan_squads,id',
            'sort_order' => 'nullable|integer',
        ]);
        $member->update([
            'role_label' => $data['role_label'] ?? null,
            'squad_id'   => $data['squad_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? $member->sort_order,
        ]);
        return back()->with('success', __('Member updated.'));
    }

    /** Create a squad. */
    public function storeSquad(Request $request, Clan $clan)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($clan->trackerClan, 422);
        $data = $request->validate(['name' => 'required|string|max:100']);
        TrackerClanSquad::create([
            'clan_id'    => $clan->trackerClan->id,
            'name'       => $data['name'],
            'sort_order' => 0,
        ]);
        return back()->with('success', __('Squad created.'));
    }

    /** Delete a squad (members fall back to unassigned). */
    public function deleteSquad(Clan $clan, TrackerClanSquad $squad)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($clan->trackerClan && $squad->clan_id === $clan->trackerClan->id, 404);
        $squad->delete();
        return back()->with('success', __('Squad deleted.'));
    }

    /** Invite a manager by username/email. */
    public function storeManager(Request $request, Clan $clan)
    {
        $this->gate($clan, ['owner', 'admin']);
        $data = $request->validate([
            'identifier' => 'required|string|max:255',
            'role'       => 'required|in:admin,editor',
        ]);
        $user = User::where('name', $data['identifier'])->orWhere('email', $data['identifier'])->first();
        if (! $user) {
            return back()->with('error', __('User not found.'));
        }
        if ($clan->managers()->where('user_id', $user->id)->exists()) {
            return back()->with('error', __('User is already a manager.'));
        }
        ClanManager::create([
            'clan_id'            => $clan->id,
            'user_id'            => $user->id,
            'role'               => $data['role'],
            'invited_by_user_id' => auth()->id(),
        ]);
        return back()->with('success', __('Manager added.'));
    }

    /** Change a manager's role (owner only). */
    public function updateManager(Request $request, Clan $clan, ClanManager $manager)
    {
        $this->gate($clan, ['owner']);
        abort_unless($manager->clan_id === $clan->id, 404);
        if ($manager->role === ClanManager::ROLE_OWNER) {
            return back()->with('error', __('Cannot change the owner role here.'));
        }
        $data = $request->validate(['role' => 'required|in:admin,editor']);
        $manager->update(['role' => $data['role']]);
        return back()->with('success', __('Manager role updated.'));
    }

    /** Remove a manager (owner/admin; cannot remove owner or self-owner). */
    public function deleteManager(Clan $clan, ClanManager $manager)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($manager->clan_id === $clan->id, 404);
        if ($manager->role === ClanManager::ROLE_OWNER) {
            return back()->with('error', __('Cannot remove the owner.'));
        }
        $manager->delete();
        return back()->with('success', __('Manager removed.'));
    }

    /** Post clan news (goes live immediately for clan-managed news). */
    public function storeNews(Request $request, Clan $clan)
    {
        $this->gate($clan); // editor+
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:50000',
            'excerpt' => 'nullable|string|max:500',
        ]);
        Post::create([
            'user_id'      => auth()->id(),
            'clan_id'      => $clan->id,
            'type'         => Post::TYPE_NEWS,
            'title'        => $data['title'],
            'slug'         => Str::slug($data['title']) . '-' . Str::random(6),
            'excerpt'      => $data['excerpt'] ?? null,
            'content'      => $data['content'],
            'is_published' => true,
            'published_at' => now(),
        ]);
        return back()->with('success', __('News posted.'));
    }

    /** Delete a news post. */
    public function deleteNews(Clan $clan, Post $post)
    {
        $this->gate($clan);
        abort_unless($post->clan_id === $clan->id, 404);
        $post->delete();
        return back()->with('success', __('News deleted.'));
    }

    /** Accept/reject an application. */
    public function reviewApplication(Request $request, Clan $clan, ClanApplication $application)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($application->clan_id === $clan->id, 404);
        $data = $request->validate(['decision' => 'required|in:accepted,rejected']);
        $application->update([
            'status'              => $data['decision'],
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at'         => now(),
        ]);
        return back()->with('success', __('Application :status.', ['status' => $data['decision']]));
    }

    /** Toggle visibility of an auto-detected server on the clan's public page. */
    public function toggleServerVisibility(\Illuminate\Http\Request $request, Clan $clan, \App\Models\Tracker\TrackerServer $server)
    {
        $this->gate($clan, ['owner', 'admin']);

        // Server must either be claimed by this clan OR match the auto-detect pattern
        $isClaimed = $server->claimed_by_clan_id === $clan->id;
        $matchesAuto = $clan->autoDetectedServersQuery()->where('tracker_servers.id', $server->id)->exists();

        abort_unless($isClaimed || $matchesAuto, 403, 'This server does not belong to your clan.');

        $server->update(['is_visible_for_clan' => $request->boolean('visible')]);

        return back()->with('success', __('Server visibility updated.'));
    }

    /** Live search for tracker_players (JSON, for autocomplete in members tab). */
    public function searchPlayers(Request $request, Clan $clan)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($clan->trackerClan, 422);

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        // Players already actively assigned (soft-removed players can be re-added)
        $existingPlayerIds = TrackerClanMember::where('clan_id', $clan->trackerClan->id)
            ->where('is_active', true)
            ->pluck('player_id')->all();

        $players = \App\Models\Tracker\TrackerPlayer::where('status', 'active')
            ->where('name_clean', 'LIKE', "%{$q}%")
            ->whereNotIn('id', $existingPlayerIds)
            ->orderByDesc('total_play_time_minutes')
            ->limit(15)
            ->get(['id', 'name_clean', 'name_html', 'country_code', 'elo_rating']);

        return response()->json($players->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name_clean,
            'name_html' => $p->name_html,
            'country' => $p->country_code,
            'elo' => (int) ($p->elo_rating ?? 0),
        ]));
    }

    /** Manually add a tracker_player as clan member. */
    public function addMember(Request $request, Clan $clan)
    {
        $this->gate($clan, ['owner', 'admin']);
        abort_unless($clan->trackerClan, 422);

        $data = $request->validate([
            'player_id'  => 'required|integer|exists:tracker_players,id',
            'role_label' => 'nullable|string|max:50',
            'squad_id'   => 'nullable|integer|exists:tracker_clan_squads,id',
        ]);

        // Re-activate if previously soft-removed
        $existing = TrackerClanMember::where('clan_id', $clan->trackerClan->id)
            ->where('player_id', $data['player_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'is_active'  => true,
                'is_manual'  => true,
                'role_label' => $data['role_label'] ?? $existing->role_label,
                'squad_id'   => $data['squad_id'] ?? $existing->squad_id,
            ]);
            return back()->with('success', __('Member re-added.'));
        }

        TrackerClanMember::create([
            'clan_id'    => $clan->trackerClan->id,
            'player_id'  => $data['player_id'],
            'role'       => 'member',
            'role_label' => $data['role_label'] ?? null,
            'squad_id'   => $data['squad_id'] ?? null,
            'is_manual'  => true,
            'is_active'  => true,
            'sort_order' => 0,
            'joined_at'  => now(),
        ]);

        return back()->with('success', __('Member added.'));
    }

    /** Remove (soft) a clan member. Owner only. */
    public function removeMember(Clan $clan, TrackerClanMember $member)
    {
        $this->gate($clan, ['owner']);
        abort_unless($clan->trackerClan && $member->clan_id === $clan->trackerClan->id, 404);

        $member->update(['is_active' => false]);
        return back()->with('success', __('Member removed.'));
    }

    /** Owner requests a new API key. Creates a PENDING entry visible in Filament admin. */
    public function requestApiKey(Request $request, Clan $clan)
    {
        $this->gate($clan, ['owner']);

        $pending = \App\Models\ClanApiKey::where('clan_id', $clan->id)
            ->where('key', 'LIKE', 'PENDING:%')
            ->exists();
        if ($pending) {
            return back()->with('error', __('A key request is already pending review.'));
        }

        \App\Models\ClanApiKey::create([
            'clan_id'   => $clan->id,
            'key'       => 'PENDING:' . Str::uuid(),
            'label'     => 'Requested by ' . auth()->user()->name . ' on ' . now()->toDateString(),
            'is_active' => false,
        ]);

        return back()->with('success', __('Key request submitted. An admin will review it.'));
    }
}
