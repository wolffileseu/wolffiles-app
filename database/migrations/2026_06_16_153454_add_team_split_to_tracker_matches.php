<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_matches', function (Blueprint $table) {
            $table->smallInteger('allies_at_start')->unsigned()->nullable()->after('players_at_end');
            $table->smallInteger('axis_at_start')->unsigned()->nullable()->after('allies_at_start');
            $table->smallInteger('spec_at_start')->unsigned()->nullable()->after('axis_at_start');
            $table->smallInteger('allies_at_end')->unsigned()->nullable()->after('spec_at_start');
            $table->smallInteger('axis_at_end')->unsigned()->nullable()->after('allies_at_end');
            $table->smallInteger('spec_at_end')->unsigned()->nullable()->after('axis_at_end');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_matches', function (Blueprint $table) {
            $table->dropColumn([
                'allies_at_start', 'axis_at_start', 'spec_at_start',
                'allies_at_end', 'axis_at_end', 'spec_at_end',
            ]);
        });
    }
};
