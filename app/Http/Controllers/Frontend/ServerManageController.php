<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Clan;
use App\Models\ClanManager;
use App\Models\Tracker\TrackerServer;
use Illuminate\Http\Request;

class ServerManageController extends Controller
{
    /**
     * Permission check. User must be:
     * - The user who claimed the server (claimed_by_user_id), OR
     * - An owner/admin of the linked clan (claimed_by_clan_id)
     */
    protected function gate(TrackerServer $server): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Is the server claimer?
        if ($server->claimed_by_user_id === $user->id) {
            return;
        }

        // Is owner/admin of the linked clan?
        if ($server->claimed_by_clan_id) {
            $isManager = ClanManager::where('clan_id', $server->claimed_by_clan_id)
                ->where('user_id', $user->id)
                ->whereIn('role', [ClanManager::ROLE_LEADER, ClanManager::ROLE_OWNER])
                ->exists();
            if ($isManager) {
                return;
            }
        }

        abort(403, 'You do not have permission to manage this server.');
    }

    /** Server-manage dashboard. */
    public function index(TrackerServer $server)
    {
        $this->gate($server);

        // List of clans the current user can link the server to (owner/admin of)
        $userManagedClans = Clan::whereHas('managers', function ($q) {
            $q->where('user_id', auth()->id())
              ->whereIn('role', [ClanManager::ROLE_LEADER, ClanManager::ROLE_OWNER]);
        })->orderBy('name')->get();

        return view('frontend.tracker.server-manage', compact('server', 'userManagedClans'));
    }

    /** Save server content (description, rules, logo, banner, links). */
    public function updateContent(Request $request, TrackerServer $server)
    {
        $this->gate($server);

        $reservedSlugs = ['manage','claim','claims','create','edit','delete','admin','new','tracker','servers'];
        $slugLocked = $server->slug_changed_at && $server->slug_changed_at->diffInDays(now()) < 30;

        $data = $request->validate([
            'slug'              => [
                'nullable','string','min:2','max:50',
                'regex:/^[a-z][a-z0-9-]+$/',
                'not_in:'.implode(',', $reservedSlugs),
                \Illuminate\Validation\Rule::unique('tracker_servers','slug')->ignore($server->id),
            ],
            'description'       => 'nullable|string|max:20000',
            'rules'             => 'nullable|string|max:20000',
            'server_website'    => 'nullable|url|max:255',
            'server_discord'    => 'nullable|string|max:255',
            'server_email'      => 'nullable|email|max:255',
            'server_logo_url'   => 'nullable|url|max:500',
            'server_banner_url' => 'nullable|url|max:500',
        ], [
            'slug.regex' => 'Slug must start with a letter and contain only lowercase letters, numbers, and dashes.',
            'slug.not_in' => 'This slug is reserved.',
            'slug.unique' => 'This slug is already taken by another server.',
        ]);

        // Empty slug → null in DB
        $data['slug'] = empty($data['slug']) ? null : $data['slug'];

        // Slug change: enforce 30-day lock + stamp slug_changed_at
        if ($data['slug'] !== $server->slug) {
            if ($slugLocked) {
                $daysLeft = 30 - (int) $server->slug_changed_at->diffInDays(now());
                return back()->withInput()->with('error', __('Slug change is locked for :n more day(s).', ['n' => $daysLeft]));
            }
            $data['slug_changed_at'] = now();
        } else {
            unset($data['slug']);
        }

        $server->update($data);

        return back()->with('success', __('Server settings saved.'));
    }

    /** Link the server to a clan (must be a clan the user manages). */
    public function linkClan(Request $request, TrackerServer $server)
    {
        $this->gate($server);

        $data = $request->validate([
            'clan_id' => 'required|integer|exists:clans,id',
        ]);

        // Verify the user is owner/admin of this clan
        $allowed = ClanManager::where('clan_id', $data['clan_id'])
            ->where('user_id', auth()->id())
            ->whereIn('role', [ClanManager::ROLE_LEADER, ClanManager::ROLE_OWNER])
            ->exists();
        abort_unless($allowed, 403, 'You are not a manager of that clan.');

        $server->update([
            'claimed_by_clan_id'  => $data['clan_id'],
            'is_visible_for_clan' => true,
        ]);

        return back()->with('success', __('Server linked to clan.'));
    }

    /** Unlink the server from its current clan. */
    public function unlinkClan(TrackerServer $server)
    {
        $this->gate($server);

        $server->update([
            'claimed_by_clan_id'  => null,
            'is_visible_for_clan' => false,
        ]);

        return back()->with('success', __('Server unlinked from clan.'));
    }
}
