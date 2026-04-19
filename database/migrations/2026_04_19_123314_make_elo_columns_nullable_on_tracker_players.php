<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make elo_rating and elo_peak nullable.
 *
 * The columns were created as NOT NULL DEFAULT 1000, which meant every
 * player — even those never rated — showed 1000/1000 in the UI. Since
 * not all players are eligible for ELO (we require >= 60min playtime),
 * NULL is a semantically correct "unrated" state and the UI renders
 * it as "Unrated" rather than the misleading default value.
 *
 * Using raw ALTER because the original column definition predates our
 * need for nullable; Laravel's Blueprint->change() would also require
 * doctrine/dbal which isn't installed here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tracker_players MODIFY COLUMN elo_rating DECIMAL(8,2) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE tracker_players MODIFY COLUMN elo_peak DECIMAL(8,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE tracker_players SET elo_rating = 1000 WHERE elo_rating IS NULL');
        DB::statement('UPDATE tracker_players SET elo_peak = 1000 WHERE elo_peak IS NULL');
        DB::statement('ALTER TABLE tracker_players MODIFY COLUMN elo_rating DECIMAL(8,2) NOT NULL DEFAULT 1000');
        DB::statement('ALTER TABLE tracker_players MODIFY COLUMN elo_peak DECIMAL(8,2) NOT NULL DEFAULT 1000');
    }
};
