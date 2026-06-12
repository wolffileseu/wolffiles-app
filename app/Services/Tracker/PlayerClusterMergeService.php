<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\DB;

/**
 * Groups duplicate tracker_players by normalized identity key and merges the
 * safe clusters via PlayerMergeService. Identity rule per cluster:
 *
 *   distinct real_guid_hash == 1            -> MERGE  (GUID proves same person)
 *   distinct real_guid_hash >= 2            -> REVIEW (different people)
 *   0 GUID and key in blacklist             -> SKIP   (generic name)
 *   0 GUID and mb_strlen(key) < 3           -> SKIP   (too generic/ambiguous)
 *   0 GUID otherwise                        -> MERGE
 *
 * Keep selection: most total_sessions, tiebreak newest last_seen_at.
 */
class PlayerClusterMergeService
{
    /** Generic default names that many distinct people share. */
    public const BLACKLIST = [
        'etplayer','player','unknownplayer','unknown','unnamedplayer',
        'noname','bot','etconsole','console','admin',
    ];

    public function __construct(private PlayerMergeService $merger) {}

    /**
     * @return array{clusters:int, merged:int, skipped:int, review:int, rows_removed:int, details:array}
     */
    public function run(bool $dryRun = true, int $minKeyLen = 3, int $limitClusters = 0): array
    {
        $rows = DB::table('tracker_players')
            ->whereNull('merged_into')
            ->get(['id','name','name_clean','real_guid_hash','last_seen_at','total_sessions']);

        // Build clusters by normalized key
        $clusters = [];
        foreach ($rows as $r) {
            $key = ColorCodeService::normalizeKey($r->name ?? $r->name_clean ?? '');
            if ($key === '') continue;
            $clusters[$key][] = $r;
        }

        $merged = $skipped = $review = $rowsRemoved = 0;
        $details = [];
        $processed = 0;

        foreach ($clusters as $key => $members) {
            if (count($members) < 2) continue;

            $guids = array_values(array_unique(array_filter(array_map(
                fn($m) => $m->real_guid_hash, $members
            ))));
            $nGuid = count($guids);

            // Decide action
            if ($nGuid >= 2) {
                $review++;
                $details[] = ['key'=>$key,'action'=>'REVIEW','rows'=>count($members),'guids'=>$nGuid];
                continue;
            }
            if ($nGuid === 0 && (in_array($key, self::BLACKLIST, true) || mb_strlen($key) < $minKeyLen)) {
                $skipped++;
                $details[] = ['key'=>$key,'action'=>'SKIP','rows'=>count($members),'guids'=>0];
                continue;
            }

            // Pick keep: most sessions, tiebreak newest last_seen_at
            usort($members, function ($a, $b) {
                $sa = (int) $a->total_sessions; $sb = (int) $b->total_sessions;
                if ($sa !== $sb) return $sb <=> $sa;
                return strcmp((string) $b->last_seen_at, (string) $a->last_seen_at);
            });
            $keep = $members[0];
            $rest = array_slice($members, 1);

            $clusterRemoved = 0;
            foreach ($rest as $m) {
                $res = $this->merger->merge(
                    (int) $keep->id, (int) $m->id, $dryRun,
                    ['take_latest_name' => true, 'recompute_totals' => true]
                );
                if ($res['ok']) $clusterRemoved++;
            }
            $merged++;
            $rowsRemoved += $clusterRemoved;
            $details[] = ['key'=>$key,'action'=>'MERGE','rows'=>count($members),'guids'=>$nGuid,'keep'=>(int)$keep->id,'removed'=>$clusterRemoved];

            if ($limitClusters > 0 && ++$processed >= $limitClusters) break;
        }

        return [
            'clusters'     => count(array_filter($clusters, fn($c)=>count($c)>1)),
            'merged'       => $merged,
            'skipped'      => $skipped,
            'review'       => $review,
            'rows_removed' => $rowsRemoved,
            'details'      => $details,
        ];
    }
}
