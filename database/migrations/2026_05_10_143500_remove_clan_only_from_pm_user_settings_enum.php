<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove "clan_only" from who_can_message enum.
 *
 * Rationale: there is no reliable Clan-membership relation between
 * platform Users on this codebase (clans table has no user pivot,
 * users.clan is freetext, tracker_clan_members uses player_id not
 * user_id). Adding a "clan_only" privacy setting that we cannot
 * enforce reliably would mislead users.
 *
 * If a real clan-membership system is added later, restore the value.
 *
 * Idempotent: down() restores the original enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        // First migrate any existing "clan_only" rows to "everyone"
        DB::table("pm_user_settings")
            ->where("who_can_message", "clan_only")
            ->update(["who_can_message" => "everyone"]);

        DB::statement("
            ALTER TABLE pm_user_settings
            MODIFY COLUMN who_can_message
            ENUM('everyone', 'nobody')
            NOT NULL
            DEFAULT 'everyone'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE pm_user_settings
            MODIFY COLUMN who_can_message
            ENUM('everyone', 'clan_only', 'nobody')
            NOT NULL
            DEFAULT 'everyone'
        ");
    }
};
