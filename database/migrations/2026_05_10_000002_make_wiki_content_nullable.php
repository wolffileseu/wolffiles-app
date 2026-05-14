<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wiki_articles', function (Blueprint $t) {
            $t->longText('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wiki_articles', function (Blueprint $t) {
            $t->longText('content')->nullable(false)->change();
        });
    }
};
