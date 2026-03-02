<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerClanMember;
use App\Models\Tracker\TrackerPlayer;
use Illuminate\Support\Facades\DB;

class ClanDetectionService
{
    protected array $patterns = [
        '/^\[([^\]]{1,15})\]\s*/u',
        '/^\{([^\}]{1,15})\}\s*/u',
        '/^\<([^\>]{1,15})\>\s*/u',
        '/^=([^=]{1,15})=\s*/u',
        '/^-([^-]{1,15})-\s*/u',
        '/^\|([^|]{1,15})\|\s*/u',
        '/^([A-Za-z0-9!@#$%^&*]{1,8})\|/u',
        '/\s*\[([^\]]{1,15})\]$/u',
        '/^\.([A-Za-z0-9]{1,6})\s+/u',
    ];

    protected array $knownClans = [
        'F|A' => 'Fearless Assassins', 'eG' => 'evil Gamers', 'TWC' => 'The Wolf Clan',
        'UJE' => 'UJE Clan', 'ETc' => 'ET Clan', 'TOP' => 'The Old Players',
        'HBC' => 'Hells Basket Clan', 'BBA' => 'BBA Clan', 'ETS' => 'ET Server Clan',
        'PHS' => 'Polish Soldiers', 'TMM' => 'Team Muppet', 'TGS' => 'The German Server',
        'B4F' => 'Bunker4Fun', 'ROX' => 'Clan Rox', 'HoJ' => 'House of Judgment',
    ];

    public function detectClanTag(string $nameClean): array
    {
        $name = trim($nameClean);
        if (strlen($name) < 2) return ['tag' => null, 'tag_clean' => null, 'name' => null];

        foreach ($this->knownClans as $tag => $clanName) {
            $escaped = preg_quote($tag, '/');
            if (preg_match("/(?:^=?\^?[0-9a-zA-Z]?{$escaped}|\[{$escaped}\]|\{{$escaped}\}|^{$escaped}\||\|{$escaped}\|)/i", $name)) {
                return ['tag' => $tag, 'tag_clean' => $tag, 'name' => $clanName];
            }
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $name, $m)) {
                $clean = preg_replace('/\^[0-9a-zA-Z]/', '', $m[1]);
                $clean = trim($clean, '=|-[]{}.<> ');
                if ($clean && strlen($clean) >= 2 && strlen($clean) <= 15) {
                    return ['tag' => $m[1], 'tag_clean' => $clean, 'name' => null];
                }
            }
        }

        return ['tag' => null, 'tag_clean' => null, 'name' => null];
    }

    public function processAllPlayers(): array
    {
        $stats = ['processed' => 0, 'clans_created' => 0, 'members_added' => 0];

        TrackerPlayer::where('last_seen_at', '>=', now()->subDays(30))
            ->where('status', 'active')
            ->chunkById(500, function ($players) use (&$stats) {
                foreach ($players as $player) {
                    $result = $this->detectClanTag($player->name_clean ?? $player->name ?? '');
                    if ($result['tag_clean']) {
                        $clan = TrackerClan::firstOrCreate(
                            ['tag_clean' => $result['tag_clean']],
                            [
                                'tag' => $result['tag'],
                                'name' => $result['name'],
                                'first_seen_at' => now(),
                                'last_seen_at' => now(),
                                'status' => 'active',
                            ]
                        );
                        if ($clan->wasRecentlyCreated) $stats['clans_created']++;
                        $clan->update(['last_seen_at' => now()]);

                        TrackerClanMember::updateOrCreate(
                            ['clan_id' => $clan->id, 'player_id' => $player->id],
                            ['is_active' => $player->last_seen_at >= now()->subDays(14), 'joined_at' => now()]
                        );
                        $stats['members_added']++;
                    }
                    $stats['processed']++;
                }
            });

        // Update counts
        DB::statement("UPDATE tracker_clans c SET
            member_count = (SELECT COUNT(*) FROM tracker_clan_members WHERE clan_id = c.id),
            active_member_count = COALESCE((SELECT COUNT(*) FROM tracker_clan_members WHERE clan_id = c.id AND is_active = 1), 0)
        ");

        return $stats;
    }
}
