<?php

namespace App\Services\Tracker;

/**
 * Parses the payload of a 'ws' (weapon stats) tracker packet.
 *
 * The format is produced by ETLegacy's G_createStats() function in
 * src/game/g_match.c:543 (line as of etlegacy master, 2026-04). The string
 * is sent from client via "statsall" command response, then the server
 * appends clientinfo in sv_tracker.c:384 via
 *   Tracker_Send("%s\\%s", msg, Tracker_createClientInfo(slot))
 *
 * Format:
 *   ws <slot> <rounds> <weapon_mask>[<weapon_stats>][<damage_section>]
 *      <skill_mask><skill_xp>[<rating> <rating_delta>][<prestige>]
 *      \<ping>\<score>\<P>\<class>\<name>
 *
 * The [brackets] indicate conditional sections:
 *   - weapon_stats & damage_section: only present when weapon_mask > 0
 *   - rating/rating_delta: only with FEATURE_RATING compiled in
 *   - prestige: only with FEATURE_PRESTIGE compiled in
 *
 * Since we don't know at parse-time which FEATURE_* flags the sending
 * server was compiled with, we parse defensively and leave any
 * unrecognized trailing values in a 'tail' array for later inspection.
 */
class WeaponStatsParser
{
    /**
     * Skill enum order as in ETLegacy bg_public.h skillType_t:
     *   SK_BATTLE_SENSE = 0
     *   SK_EXPLOSIVES_AND_CONSTRUCTION = 1
     *   SK_FIRST_AID = 2
     *   SK_SIGNALS = 3
     *   SK_LIGHT_WEAPONS = 4
     *   SK_HEAVY_WEAPONS = 5
     *   SK_MILITARY_INTELLIGENCE_AND_SCOPED_WEAPONS = 6
     */
    public const SKILL_NAMES = [
        0 => 'battle_sense',
        1 => 'engineering',
        2 => 'medic',
        3 => 'signals',
        4 => 'light_weapons',
        5 => 'heavy_weapons',
        6 => 'covert_ops',
    ];

    /**
     * Parse a 'ws' payload string.
     *
     * Returns null if the payload cannot be interpreted as a ws packet.
     * Returns a structured array otherwise — see parseStructured() for the shape.
     */
    public function parse(string $payload): ?array
    {
        // Strip leading 'ws ' if present (payload from tracker_raw_events
        // sometimes includes it, sometimes only the args — be tolerant).
        $payload = ltrim($payload);
        if (str_starts_with($payload, 'ws ')) {
            $payload = substr($payload, 3);
        }

        // Split off the clientinfo suffix. The stats portion ends at the
        // first backslash — everything from that point on is the
        // server-appended clientinfo block.
        $backslashPos = strpos($payload, '\\');
        if ($backslashPos === false) {
            // No clientinfo — still try to parse the stats part alone
            $statsPart = $payload;
            $clientinfoPart = '';
        } else {
            $statsPart = rtrim(substr($payload, 0, $backslashPos));
            $clientinfoPart = substr($payload, $backslashPos + 1);
        }

        $tokens = preg_split('/\s+/', trim($statsPart));
        if ($tokens === false || count($tokens) < 3) {
            return null;
        }

        try {
            return $this->parseStructured($tokens, $clientinfoPart);
        } catch (\Throwable $e) {
            // Malformed packet — let caller log the raw payload for inspection.
            return null;
        }
    }

    /**
     * Core parser. Consumes $tokens in-order and returns a structured array.
     *
     * @param array<int,string> $tokens  Space-separated numeric tokens from
     *                                   the stats section (no 'ws' prefix,
     *                                   no clientinfo).
     * @param string            $clientinfoPart  The backslash-delimited
     *                                           clientinfo (without leading `\`).
     *
     * @return array{
     *   slot: int,
     *   rounds: int,
     *   weapon_mask: int,
     *   weapons: array<int, array{hits:int,atts:int,kills:int,deaths:int,headshots:int}>,
     *   damage: array<string,int|float>|null,
     *   time_played_pct: float|null,
     *   skill_mask: int,
     *   skills: array<int,int>,
     *   tail: array<int,string>,
     *   client: array{ping:int,score:int,p:string,class:int,name:string}|null,
     * }
     */
    private function parseStructured(array $tokens, string $clientinfoPart): array
    {
        $i = 0;
        $slot = (int) $tokens[$i++];
        $rounds = (int) $tokens[$i++];
        $weaponMask = (int) $tokens[$i++];

        // Per-weapon stats: 5 values per set bit in weapon_mask.
        $weapons = [];
        if ($weaponMask > 0) {
            for ($bit = 0; $bit < 32; $bit++) {
                if (($weaponMask & (1 << $bit)) === 0) {
                    continue;
                }
                // Need 5 more tokens for this weapon
                if ($i + 4 >= count($tokens)) {
                    throw new \RuntimeException("truncated weapon stats at bit $bit");
                }
                $weapons[$bit] = [
                    'hits'      => (int) $tokens[$i++],
                    'atts'      => (int) $tokens[$i++],
                    'kills'     => (int) $tokens[$i++],
                    'deaths'    => (int) $tokens[$i++],
                    'headshots' => (int) $tokens[$i++],
                ];
            }
        }

        // Damage section: 10 values, ONLY if weapon_mask > 0.
        // Last value is a float (time_played_pct), rest are ints.
        $damage = null;
        $timePlayedPct = null;
        if ($weaponMask > 0) {
            if ($i + 9 >= count($tokens)) {
                throw new \RuntimeException("truncated damage section");
            }
            $damage = [
                'given'          => (int) $tokens[$i++],
                'received'       => (int) $tokens[$i++],
                'team_given'     => (int) $tokens[$i++],
                'team_received'  => (int) $tokens[$i++],
                'gibs'           => (int) $tokens[$i++],
                'kill_assists'   => (int) $tokens[$i++],
                'self_kills'     => (int) $tokens[$i++],
                'team_kills'     => (int) $tokens[$i++],
                'team_gibs'      => (int) $tokens[$i++],
            ];
            $timePlayedPct = (float) $tokens[$i++];
        }

        // Skill section.
        // Next token is skill_mask, followed by N values where N = popcount(mask).
        // NOTE: We've observed 7 values in the payload for mask=19 (popcount=3),
        // which contradicts the code. Keeping strict for now; if we see
        // parse errors in logs we'll investigate the skill section more.
        if ($i >= count($tokens)) {
            throw new \RuntimeException("missing skill_mask");
        }
        $skillMask = (int) $tokens[$i++];
        $setBits = [];
        for ($bit = 0; $bit < 32; $bit++) {
            if (($skillMask & (1 << $bit)) !== 0) {
                $setBits[] = $bit;
            }
        }
        $skillBitCount = count($setBits);

        // Detect SINGLE vs PAIR mode for skill values.
        //
        // Standard mode (campaign/stopwatch/lms etc.): 1 int per set skill bit
        //   (current map XP only)
        // PRESTIGE mode (non-campaign/sw/lms with g_prestige.integer > 0):
        //   2 ints per set skill bit (current skillpoints + delta from startskillpoints)
        //
        // We look ahead in $tokens to decide: if there are >= 2*N ints
        // followed by something non-int (float/rating), treat as PAIR mode.
        $remaining = array_slice($tokens, $i);
        $leadingInts = 0;
        foreach ($remaining as $t) {
            if (is_numeric($t) && !str_contains($t, '.')) {
                $leadingInts++;
            } else {
                break;
            }
        }

        $isPairMode = ($skillBitCount > 0 && $leadingInts >= 2 * $skillBitCount);
        $valuesPerSkill = $isPairMode ? 2 : 1;

        $skills = [];
        foreach ($setBits as $bit) {
            if ($i + ($valuesPerSkill - 1) >= count($tokens)) {
                throw new \RuntimeException("truncated skill values at bit $bit");
            }
            if ($isPairMode) {
                $skills[$bit] = [
                    'current' => (int) $tokens[$i++],
                    'delta'   => (int) $tokens[$i++],
                ];
            } else {
                $skills[$bit] = (int) $tokens[$i++];
            }
        }

        // Everything remaining goes into 'tail' — this typically contains
        // rating / rating_delta / prestige depending on server compilation.
        $tail = array_slice($tokens, $i);
        $skillMode = $isPairMode ? 'pair' : 'single';

        // Parse clientinfo: ping\score\P\class\name
        $client = null;
        if ($clientinfoPart !== '') {
            $parts = explode('\\', $clientinfoPart);
            if (count($parts) >= 5) {
                $client = [
                    'ping'  => (int) $parts[0],
                    'score' => (int) $parts[1],
                    'p'     => (string) $parts[2],
                    'class' => (int) $parts[3],
                    'name'  => implode('\\', array_slice($parts, 4)),
                ];
            }
        }

        return [
            'slot'            => $slot,
            'rounds'          => $rounds,
            'weapon_mask'     => $weaponMask,
            'weapons'         => $weapons,
            'damage'          => $damage,
            'time_played_pct' => $timePlayedPct,
            'skill_mask'      => $skillMask,
            'skill_mode'      => $skillMode,
            'skills'          => $skills,
            'tail'            => $tail,
            'client'          => $client,
        ];
    }
}
