<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CRITICAL data-correctness fix.
 *
 * Across the tracker_* schema, 10 columns carry ON UPDATE CURRENT_TIMESTAMP.
 * Every one of them is a "when did this happen" timestamp — started_at,
 * joined_at, recorded_at, polled_at, computed_at — which must NOT be
 * rewritten on every row update. ON UPDATE silently corrupts these fields:
 * running an aggregation, cleanup, or any UPDATE statement snaps the
 * timestamp to "now", destroying historical ordering.
 *
 * This was first noticed when tracker_matches.started_at started clustering
 * at a single timestamp after MatchLifecycleHandler's aggregation backfill —
 * and again when tracker_server_slots.connected_at appeared ~1ms AFTER the
 * disconnected_at on the same row, which broke WeaponStatsHandler's
 * slot lookup for every non-bot player.
 *
 * This migration removes the clause everywhere it appears on tracker_*
 * tables. Default CURRENT_TIMESTAMP for initial insert is preserved.
 *
 * NOTE: tracker_raw_events.received_at is the ultimate source of truth for
 * historical reconstruction. Fixing it here means from this point forward
 * it stays honest. Rows already damaged stay damaged, but we have no
 * earlier source to recover them from.
 */
return new class extends Migration
{
    private array $targets = [
        ['tracker_clan_members',           'joined_at',    'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_elo_history',            'recorded_at',  'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_matches',                'started_at',   'TIMESTAMP(3) NOT NULL DEFAULT current_timestamp(3)'],
        ['tracker_player_rankings_30d',    'computed_at',  'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_player_sessions',        'started_at',   'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_player_snapshots',       'polled_at',    'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_raw_events',             'received_at',  'TIMESTAMP(3) NOT NULL DEFAULT current_timestamp(3)'],
        ['tracker_server_history',         'polled_at',    'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_server_rankings',        'computed_at',  'TIMESTAMP    NULL DEFAULT current_timestamp()'],
        ['tracker_server_top_players',     'computed_at',  'TIMESTAMP    NULL DEFAULT current_timestamp()'],
    ];

    public function up(): void
    {
        foreach ($this->targets as [$table, $column, $definition]) {
            // Pull the live column definition so nullability/default match reality
            $col = DB::selectOne("
                SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
            ", [$table, $column]);

            if (!$col) {
                // Column doesn't exist — skip (schema may diverge across envs)
                continue;
            }

            // Build ALTER that preserves nullability + default but drops ON UPDATE
            $nullability = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
            $type = $col->COLUMN_TYPE;
            $defaultClause = '';
            if ($col->COLUMN_DEFAULT !== null) {
                $raw = $col->COLUMN_DEFAULT;
                // MySQL returns functions un-quoted; literals need quoting
                if (stripos($raw, 'current_timestamp') !== false) {
                    $defaultClause = 'DEFAULT ' . $raw;
                } else {
                    $defaultClause = 'DEFAULT ' . DB::getPdo()->quote($raw);
                }
            }

            DB::statement("
                ALTER TABLE `{$table}`
                MODIFY COLUMN `{$column}` {$type} {$nullability} {$defaultClause}
            ");
        }
    }

    public function down(): void
    {
        // We don't restore ON UPDATE — that clause is the bug.
    }
};
