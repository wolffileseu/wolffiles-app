<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // "Node DE-1 Falkenstein"
            $table->unsignedInteger('pterodactyl_node_id');   // Pterodactyl Node ID
            $table->string('location')->default('DE');        // Country code
            $table->string('fqdn')->nullable();               // node1.wolffiles.eu
            $table->unsignedInteger('memory_total_mb');        // Total RAM
            $table->unsignedInteger('memory_allocated_mb')->default(0);
            $table->unsignedInteger('disk_total_mb');          // Total Disk
            $table->unsignedInteger('disk_allocated_mb')->default(0);
            $table->unsignedSmallInteger('max_servers')->default(30);
            $table->unsignedSmallInteger('active_servers')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_full')->default(false);
            $table->timestamps();
        });

        // Add node_id FK to server_orders
        Schema::table('server_orders', function (Blueprint $table) {
            $table->foreignId('node_id')->nullable()->after('node')->constrained('server_nodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('server_orders', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
        Schema::dropIfExists('server_nodes');
    }
};
