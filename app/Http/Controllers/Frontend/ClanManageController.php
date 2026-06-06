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
    protected function gate(Clan $managedClan, array $allowedRoles = ['leader', 'owner', 'editor']): ClanManager
    {
        $manager = $managedClan->managers()->where('user_id', auth()->id())->first();
        abort_unless($manager && in_array($manager->role, $allowedRoles), 403);
        return $manager;
    }

    /** Dashboard. */
    public function index(Clan $managedClan)
    {
        $manager = $this->gate($managedClan);
        $managedClan->load(['trackerClan.squads', 'managers.user']);

        $members = collect();
        $squads = collect();
        if ($managedClan->trackerClan) {
            $members = TrackerClanMember::with('player', 'squad')
                ->where('clan_id', $managedClan->trackerClan->id)
                ->where('is_active', true)
                ->get()
                ->filter(fn ($m) => $m->player !== null)
                ->sortBy('sort_order');
            $squads = $managedClan->trackerClan->squads;
        }

        $news = Post::where('clan_id', $managedClan->id)->where('type', Post::TYPE_NEWS)->latest()->limit(20)->get();
        $applications = ClanApplication::with('applicant')
            ->where('clan_id', $managedClan->id)
            ->orderByRaw("FIELD(status,'pending','accepted','rejected','withdrawn')")
            ->latest()->get();

        $apiKeys = \App\Models\ClanApiKey::where('clan_id', $managedClan->id)->orderByDesc('id')->get();
        $hasPendingApiKey = $apiKeys->contains(fn($k) => str_starts_with($k->getAttributes()['key'] ?? '', 'PENDING:'));

        // Servers: claimed + auto-detected (by tag prefix on hostname)
        $claimedServers = \App\Models\Tracker\TrackerServer::where('claimed_by_clan_id', $managedClan->id)->orderBy('hostname_clean')->get();
        $autoDetectedServers = $managedClan->autoDetectedServersQuery()->orderBy('hostname_clean')->get();

        // Block-list
        $blocks = \App\Models\ClanMemberBlock::where('clan_id', $managedClan->id)
            ->with(['targetPlayer', 'blockedBy'])
            ->latest()
            ->get();

        return view('frontend.clan.manage', array_merge(compact('manager', 'members', 'squads', 'news', 'applications', 'apiKeys', 'hasPendingApiKey', 'claimedServers', 'autoDetectedServers', 'blocks'), ['clan' => $managedClan]));
    }

    /** Save page content (about, rules, info, links). */
    /** Toggle auto-join detection on the linked tracker_clan. Owner only. */
    public function updateAutoJoin(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, [\App\Models\ClanManager::ROLE_LEADER]);
        $tc = $managedClan->trackerClan;
        if (!$tc) {
            return back()->with('error', __('This clan is not linked to a tracker clan.'));
        }
        $enabled = $request->boolean('auto_join_enabled');
        $tc->update(['auto_join_enabled' => $enabled]);
        return back()->with('success', $enabled
            ? __('Auto-join is now enabled. Players whose name contains the clan tag will be auto-added.')
            : __('Auto-join is now disabled. Members will only be added manually.'));
    }

    public function updateContent(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']); // leader+owner only — editor can't edit content

        // Slug-change is locked for 30 days after each change
        $reservedSlugs = ['manage','propose','recruiting','create','edit','delete','admin','new','tracker','clans'];
        $slugLocked = $managedClan->slug_changed_at && $managedClan->slug_changed_at->diffInDays(now()) < 30;

        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'slug'                => [
                'required','string','min:2','max:50',
                'regex:/^[a-z][a-z0-9-]+$/',
                'not_in:'.implode(',',$reservedSlugs),
                \Illuminate\Validation\Rule::unique('clans','slug')->ignore($managedClan->id),
            ],
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
        ], [
            'slug.regex' => 'Slug must start with a letter and contain only lowercase letters, numbers, and dashes.',
            'slug.not_in' => 'This slug is reserved. Please choose another.',
            'slug.unique' => 'This slug is already taken by another clan.',
        ]);
        $data['is_recruiting'] = $request->boolean('is_recruiting');
        $data['is_published']  = $request->boolean('is_published');

        // If slug changed: enforce 30-day lock + stamp slug_changed_at
        if ($data['slug'] !== $managedClan->slug) {
            if ($slugLocked) {
                $daysLeft = 30 - (int) $managedClan->slug_changed_at->diffInDays(now());
                return back()->withInput()->with('error', __('Slug change is locked for :n more day(s).', ['n' => $daysLeft]));
            }
            $data['slug_changed_at'] = now();
        } else {
            unset($data['slug']); // no change, do not touch slug_changed_at
        }

        $managedClan->update($data);

        return back()->with('success', __('Clan page updated.'));
    }

    /** Update a single member's role_label + squad. */
    public function updateMember(Request $request, Clan $managedClan, TrackerClanMember $member)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan && $member->clan_id === $managedClan->trackerClan->id, 404);
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
    public function storeSquad(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan, 422);
        $data = $request->validate(['name' => 'required|string|max:100']);
        TrackerClanSquad::create([
            'clan_id'    => $managedClan->trackerClan->id,
            'name'       => $data['name'],
            'sort_order' => 0,
        ]);
        return back()->with('success', __('Squad created.'));
    }

    /** Delete a squad (members fall back to unassigned). */
    public function deleteSquad(Clan $managedClan, TrackerClanSquad $squad)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan && $squad->clan_id === $managedClan->trackerClan->id, 404);
        $squad->delete();
        return back()->with('success', __('Squad deleted.'));
    }

    /** Invite a manager by username/email. */
    public function storeManager(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        $data = $request->validate([
            'identifier' => 'required|string|max:255',
            'role'       => 'required|in:admin,editor',
        ]);
        $user = User::where('name', $data['identifier'])->orWhere('email', $data['identifier'])->first();
        if (! $user) {
            return back()->with('error', __('User not found.'));
        }
        if ($managedClan->managers()->where('user_id', $user->id)->exists()) {
            return back()->with('error', __('User is already a manager.'));
        }
        ClanManager::create([
            'clan_id'            => $managedClan->id,
            'user_id'            => $user->id,
            'role'               => $data['role'],
            'invited_by_user_id' => auth()->id(),
        ]);
        return back()->with('success', __('Manager added.'));
    }

    /** Change a manager's role (owner only). */
    public function updateManager(Request $request, Clan $managedClan, ClanManager $manager)
    {
        $this->gate($managedClan, ['leader']);
        abort_unless($manager->clan_id === $managedClan->id, 404);
        if ($manager->role === ClanManager::ROLE_LEADER) {
            return back()->with('error', __('Cannot change the owner role here.'));
        }
        $data = $request->validate(['role' => 'required|in:owner,editor']);
        $manager->update(['role' => $data['role']]);
        return back()->with('success', __('Manager role updated.'));
    }

    /** Remove a manager (owner/admin; cannot remove owner or self-owner). */
    public function deleteManager(Clan $managedClan, ClanManager $manager)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($manager->clan_id === $managedClan->id, 404);
        if ($manager->role === ClanManager::ROLE_LEADER) {
            return back()->with('error', __('Cannot remove the owner.'));
        }
        $manager->delete();
        return back()->with('success', __('Manager removed.'));
    }

    /** Post clan news (goes live immediately for clan-managed news). */
    public function storeNews(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan); // editor+
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:50000',
            'excerpt' => 'nullable|string|max:500',
        ]);
        Post::create([
            'user_id'      => auth()->id(),
            'clan_id'      => $managedClan->id,
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
    public function deleteNews(Clan $managedClan, Post $post)
    {
        $this->gate($managedClan);
        abort_unless($post->clan_id === $managedClan->id, 404);
        $post->delete();
        return back()->with('success', __('News deleted.'));
    }

    /** Accept/reject an application. */
    public function reviewApplication(Request $request, Clan $managedClan, ClanApplication $application)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($application->clan_id === $managedClan->id, 404);
        $data = $request->validate(['decision' => 'required|in:accepted,rejected']);
        $application->update([
            'status'              => $data['decision'],
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at'         => now(),
        ]);
        return back()->with('success', __('Application :status.', ['status' => $data['decision']]));
    }

    /** Transfer ownership of the clan to another existing manager. Owner only. */
    public function transferOwnership(Clan $managedClan, ClanManager $manager)
    {
        $this->gate($managedClan, ['leader']);
        abort_unless($manager->clan_id === $managedClan->id, 404);
        abort_if($manager->role === ClanManager::ROLE_LEADER, 422, __('This person is already the owner.'));

        \Illuminate\Support\Facades\DB::transaction(function () use ($managedClan, $manager) {
            $currentOwner = $managedClan->managers()->where('role', ClanManager::ROLE_LEADER)->first();
            if ($currentOwner) {
                $currentOwner->update(['role' => ClanManager::ROLE_EDITOR]);
            }
            $manager->update(['role' => ClanManager::ROLE_LEADER]);
        });

        return back()->with('success', __('Ownership transferred to :name. You are now an editor.', ['name' => $manager->user->name ?? 'the new owner']));
    }

    /** Toggle visibility of an auto-detected server on the clan's public page. */
    public function toggleServerVisibility(\Illuminate\Http\Request $request, Clan $managedClan, \App\Models\Tracker\TrackerServer $server)
    {
        $this->gate($managedClan, ['leader', 'owner']);

        // Server must either be claimed by this clan OR match the auto-detect pattern
        $isClaimed = $server->claimed_by_clan_id === $managedClan->id;
        $matchesAuto = $managedClan->autoDetectedServersQuery()->where('tracker_servers.id', $server->id)->exists();

        abort_unless($isClaimed || $matchesAuto, 403, 'This server does not belong to your clan.');

        $server->update(['is_visible_for_clan' => $request->boolean('visible')]);

        return back()->with('success', __('Server visibility updated.'));
    }

    /** Live search for tracker_players (JSON, for autocomplete in members tab). */
    public function searchPlayers(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan, 422);

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        // Players already actively assigned (soft-removed players can be re-added)
        $existingPlayerIds = TrackerClanMember::where('clan_id', $managedClan->trackerClan->id)
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
    public function addMember(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan, 422);

        $data = $request->validate([
            'player_id'  => 'required|integer|exists:tracker_players,id',
            'role_label' => 'nullable|string|max:50',
            'squad_id'   => 'nullable|integer|exists:tracker_clan_squads,id',
        ]);

        // Re-activate if previously soft-removed
        $existing = TrackerClanMember::where('clan_id', $managedClan->trackerClan->id)
            ->where('player_id', $data['player_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'is_active'  => true,
                'is_manual'  => true,
                'role_label' => $data['role_label'] ?? $existing->role_label,
                'squad_id'   => $data['squad_id'] ?? $existing->squad_id,
            ]);
            $managedClan->trackerClan->recalcMemberCounts();
            return back()->with('success', __('Member re-added.'));
        }

        TrackerClanMember::create([
            'clan_id'    => $managedClan->trackerClan->id,
            'player_id'  => $data['player_id'],
            'role'       => 'member',
            'role_label' => $data['role_label'] ?? null,
            'squad_id'   => $data['squad_id'] ?? null,
            'is_manual'  => true,
            'is_active'  => true,
            'sort_order' => 0,
            'joined_at'  => now(),
        ]);

        $managedClan->trackerClan->recalcMemberCounts();
        return back()->with('success', __('Member added.'));
    }

    /** Remove (soft) a clan member. Leader + Owner allowed. */
    public function removeMember(Clan $managedClan, TrackerClanMember $member)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan && $member->clan_id === $managedClan->trackerClan->id, 404);

        $member->update(['is_active' => false]);
        return back()->with('success', __('Member removed.'));
    }

    /** Block a member: removes them AND adds them to the block-list to prevent re-pool. */
    public function blockMember(Request $request, Clan $managedClan, TrackerClanMember $member)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($managedClan->trackerClan && $member->clan_id === $managedClan->trackerClan->id, 404);

        $data = $request->validate([
            'block_type' => 'required|in:player_id,name,both',
            'reason'     => 'nullable|string|max:500',
        ]);

        $player = $member->player;
        abort_unless($player, 404);

        \Illuminate\Support\Facades\DB::transaction(function () use ($managedClan, $member, $player, $data) {
            $member->update(['is_active' => false]);
            if (in_array($data['block_type'], ['player_id', 'both'])) {
                \App\Models\ClanMemberBlock::updateOrCreate(
                    ['clan_id' => $managedClan->id, 'block_type' => 'player_id', 'target_player_id' => $player->id],
                    ['target_name' => null, 'blocked_by_user_id' => auth()->id(), 'reason' => $data['reason'] ?? null]
                );
            }
            if (in_array($data['block_type'], ['name', 'both'])) {
                \App\Models\ClanMemberBlock::updateOrCreate(
                    ['clan_id' => $managedClan->id, 'block_type' => 'name', 'target_name' => $player->name_clean ?? $player->name ?? ''],
                    ['target_player_id' => null, 'blocked_by_user_id' => auth()->id(), 'reason' => $data['reason'] ?? null]
                );
            }
        });

        return back()->with('success', __('Player blocked and removed from members.'));
    }

    /** Manually add a block (for users that haven't been auto-pooled yet). */
    public function addBlock(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);

        $data = $request->validate([
            'block_type'       => 'required|in:player_id,name',
            'target_player_id' => 'nullable|integer|exists:tracker_players,id',
            'target_name'      => 'nullable|string|max:255',
            'reason'           => 'nullable|string|max:500',
        ]);

        if ($data['block_type'] === 'player_id' && empty($data['target_player_id'])) {
            return back()->withInput()->with('error', __('Please pick a player.'));
        }
        if ($data['block_type'] === 'name' && empty($data['target_name'])) {
            return back()->withInput()->with('error', __('Please enter a name.'));
        }

        \App\Models\ClanMemberBlock::updateOrCreate(
            [
                'clan_id'           => $managedClan->id,
                'block_type'        => $data['block_type'],
                'target_player_id'  => $data['block_type'] === 'player_id' ? $data['target_player_id'] : null,
                'target_name'       => $data['block_type'] === 'name' ? $data['target_name'] : null,
            ],
            ['blocked_by_user_id' => auth()->id(), 'reason' => $data['reason'] ?? null]
        );

        return back()->with('success', __('Block added.'));
    }

    /** Remove a block entry. */
    public function removeBlock(Clan $managedClan, \App\Models\ClanMemberBlock $block)
    {
        $this->gate($managedClan, ['leader', 'owner']);
        abort_unless($block->clan_id === $managedClan->id, 404);
        $block->delete();
        return back()->with('success', __('Block removed.'));
    }

    /** Leader or Owner requests a new API key. Creates a PENDING entry visible in Filament admin. */
    public function requestApiKey(Request $request, Clan $managedClan)
    {
        $this->gate($managedClan, ['leader', 'owner']);

        $pending = \App\Models\ClanApiKey::where('clan_id', $managedClan->id)
            ->where('key', 'LIKE', 'PENDING:%')
            ->exists();
        if ($pending) {
            return back()->with('error', __('A key request is already pending review.'));
        }

        \App\Models\ClanApiKey::create([
            'clan_id'   => $managedClan->id,
            'key'       => 'PENDING:' . Str::uuid(),
            'label'     => 'Requested by ' . auth()->user()->name . ' on ' . now()->toDateString(),
            'is_active' => false,
        ]);

        return back()->with('success', __('Key request submitted. An admin will review it.'));
    }
}
