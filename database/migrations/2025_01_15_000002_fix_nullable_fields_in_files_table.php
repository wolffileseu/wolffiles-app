<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('file_name')->nullable()->change();
            $table->string('file_extension')->nullable()->change();
            $table->bigInteger('file_size')->nullable()->default(0)->change();
            $table->string('file_hash')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('file_name')->nullable(false)->change();
            $table->string('file_extension')->nullable(false)->change();
            $table->bigInteger('file_size')->nullable(false)->change();
            $table->string('file_hash')->nullable(false)->change();
            $table->string('mime_type')->nullable(false)->change();
        });
    }
};
