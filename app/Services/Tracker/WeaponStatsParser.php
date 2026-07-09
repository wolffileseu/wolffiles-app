<?php

namespace App\Services\Tracker;

use Throwable;
use RuntimeException;

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
    public function parse(string $payload, int $fieldsPerWeapon = 5): ?array
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
            return $this->parseStructured($tokens, $clientinfoPart, $fieldsPerWeapon);
        } catch (Throwable $e) {
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
    private function parseStructured(array $tokens, string $clientinfoPart, int $fieldsPerWeapon = 5): array
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
                // Need N more tokens for this weapon (5 = stock ETL/silEnT/nitmod,
                // 6 = jaymod, which inserts a 'subshots' field at position 3).
                // jaymod field order (g_match.cpp:422, logPrint=false):
                //   hits atts subshots kills deaths headshots
                if ($i + $fieldsPerWeapon - 1 >= count($tokens)) {
                    throw new RuntimeException("truncated weapon stats at bit $bit");
                }
                if ($fieldsPerWeapon === 6) {
                    $hits      = (int) $tokens[$i++];
                    $atts      = (int) $tokens[$i++];
                    $i++; // subshots — jaymod-specific, intentionally discarded
                    $kills     = (int) $tokens[$i++];
                    $deaths    = (int) $tokens[$i++];
                    $headshots = (int) $tokens[$i++];
                } else {
                    $hits      = (int) $tokens[$i++];
                    $atts      = (int) $tokens[$i++];
                    $kills     = (int) $tokens[$i++];
                    $deaths    = (int) $tokens[$i++];
                    $headshots = (int) $tokens[$i++];
                }
                $weapons[$bit] = [
                    'hits'      => $hits,
                    'atts'      => $atts,
                    'kills'     => $kills,
                    'deaths'    => $deaths,
                    'headshots' => $headshots,
                ];
            }
        }

        // Damage section: ETL source says "9 ints + 1 float = 10 tokens",
        // but in practice we see anything from 9 to 11. Parse defensively:
        // consume up to 9 named fields, and probe for an optional float
        // time_played_pct afterwards. Any extras stay in $tail.
        //
        // We consume a damage section only when weapon_mask > 0, per source.
        $damage = null;
        $timePlayedPct = null;
        if ($weaponMask > 0) {
            $damageFields = [
                'given', 'received', 'team_given', 'team_received',
                'gibs', 'kill_assists', 'self_kills', 'team_kills', 'team_gibs',
            ];
            $damage = [];
            foreach ($damageFields as $field) {
                if ($i >= count($tokens)) break;
                $damage[$field] = (int) $tokens[$i++];
            }
            // Optional trailing time_played_pct. Accept it if the next token
            // looks like a percentage (float 0-100, OR a small int 0-100).
            // If it looks like a skill_mask (large int, >200 typical), leave it.
            if ($i < count($tokens)) {
                $next = $tokens[$i];
                $isFloat = str_contains($next, '.');
                $numeric = is_numeric($next) ? (float) $next : null;
                if ($isFloat || ($numeric !== null && $numeric >= 0 && $numeric <= 100)) {
                    $timePlayedPct = (float) $next;
                    $i++;
                }
            }
        }

        // Skill section — entirely optional.
        //
        // The ETL source schema says: skill_mask (1 int) + N values where
        // N = popcount(mask) in SINGLE mode, or 2*N in PRESTIGE/PAIR mode.
        //
        // In practice we see payloads where the skill section is missing
        // entirely (mid-match ws-updates before stats stabilise), truncated,
        // or where the "time_played_pct" trailing value was emitted as an
        // integer instead of a float. We parse defensively rather than abort
        // the whole packet.
        $skillMask = 0;
        $skills = [];
        $skillMode = 'single';

        if ($i < count($tokens)) {
            $maybeMask = (int) $tokens[$i];
            $setBits = [];
            for ($bit = 0; $bit < 32; $bit++) {
                if (($maybeMask & (1 << $bit)) !== 0) {
                    $setBits[] = $bit;
                }
            }
            $skillBitCount = count($setBits);

            // How many tokens remain AFTER consuming the putative mask?
            $remaining = count($tokens) - ($i + 1);

            // Only consume as skill_mask if the remainder can plausibly
            // fit the skills. Otherwise the "mask" was in reality the
            // rating / tail / something else.
            $canFitSingle = $skillBitCount > 0 && $remaining >= $skillBitCount;
            $canFitPair   = $skillBitCount > 0 && $remaining >= 2 * $skillBitCount;

            if ($skillBitCount === 0) {
                // mask = 0 or no bits set — consume it, no skills follow
                $skillMask = $maybeMask;
                $i++;
            } elseif ($canFitPair || $canFitSingle) {
                $skillMask = $maybeMask;
                $i++;
                $skillMode = $canFitPair ? 'pair' : 'single';

                foreach ($setBits as $bit) {
                    if ($skillMode === 'pair') {
                        if ($i + 1 >= count($tokens)) break;
                        $skills[$bit] = [
                            'current' => (int) $tokens[$i++],
                            'delta'   => (int) $tokens[$i++],
                        ];
                    } else {
                        if ($i >= count($tokens)) break;
                        $skills[$bit] = (int) $tokens[$i++];
                    }
                }
            } else {
                // The value at $i doesn't look like a mask that fits.
                // Leave it in the tail array for downstream inspection.
            }
        }

        // Everything remaining goes into 'tail' — this typically contains
        // rating / rating_delta / prestige depending on server compilation,
        // or leftover bytes when the skill section was absent/truncated.
        $tail = array_slice($tokens, $i);

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
