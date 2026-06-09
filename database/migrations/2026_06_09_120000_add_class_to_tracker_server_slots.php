<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_server_slots', function (Blueprint $table) {
            // ET player class: 0=Soldier 1=Medic 2=Engineer 3=FieldOps 4=CovertOps
            // Nullable: RtCW + non-enhanced servers never set this.
            $table->unsignedTinyInteger('class')->nullable()->after('player_id');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_server_slots', function (Blueprint $table) {
            $table->dropColumn('class');
        });
    }
};
