<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Erweitere wiki_articles um Wikitext, Namespace, Redirect
        Schema::table('wiki_articles', function (Blueprint $t) {
            if (!Schema::hasColumn('wiki_articles', 'wikitext')) {
                $t->longText('wikitext')->nullable()->after('content');
            }
            if (!Schema::hasColumn('wiki_articles', 'namespace')) {
                $t->string('namespace', 32)->default('main')->after('slug');
            }
            if (!Schema::hasColumn('wiki_articles', 'is_redirect')) {
                $t->boolean('is_redirect')->default(false)->after('is_locked');
            }
            if (!Schema::hasColumn('wiki_articles', 'redirect_target')) {
                $t->string('redirect_target', 255)->nullable()->after('is_redirect');
            }
        });

        // Index für Namespace nachziehen (separat, falls Spalte gerade erst erstellt)
        try {
            Schema::table('wiki_articles', function (Blueprint $t) {
                $t->index('namespace', 'wiki_articles_namespace_index');
            });
        } catch (\Throwable $e) { /* index existiert schon */ }

        // Composite Unique (namespace, slug) statt nur slug — MediaWiki erlaubt gleichen Slug in verschiedenen Namespaces
        try {
            Schema::table('wiki_articles', function (Blueprint $t) {
                $t->dropUnique('wiki_articles_slug_unique');
            });
        } catch (\Throwable $e) { /* war ggf. anders benannt */ }
        try {
            Schema::table('wiki_articles', function (Blueprint $t) {
                $t->unique(['namespace', 'slug'], 'wiki_articles_namespace_slug_unique');
            });
        } catch (\Throwable $e) { /* unique existiert schon */ }

        // Backfill: bestehende HTML-content → wikitext (wird beim ersten Edit per WYSIWYG → Wikitext umgeschrieben)
        DB::table('wiki_articles')->whereNull('wikitext')->update([
            'wikitext' => DB::raw('content'),
        ]);

        // 2. Translations-Tabelle (title + wikitext + gerenderter HTML-Cache pro Sprache)
        if (!Schema::hasTable('wiki_article_translations')) {
            Schema::create('wiki_article_translations', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('wiki_article_id');
                $t->string('locale', 8);
                $t->string('title', 255);
                $t->longText('wikitext')->nullable();
                $t->longText('content_html')->nullable();
                $t->unsignedBigInteger('updated_by')->nullable();
                $t->timestamps();
                $t->unique(['wiki_article_id', 'locale'], 'wat_article_locale_unique');
                $t->index('locale');
                $t->foreign('wiki_article_id')->references('id')->on('wiki_articles')->cascadeOnDelete();
            });

            // Backfill: jeder existierende Artikel bekommt eine de-Translation (Master)
            $articles = DB::table('wiki_articles')
                ->select('id', 'title', 'wikitext', 'content', 'user_id', 'created_at', 'updated_at')
                ->get();
            foreach ($articles as $a) {
                DB::table('wiki_article_translations')->insert([
                    'wiki_article_id' => $a->id,
                    'locale'          => 'de',
                    'title'           => $a->title,
                    'wikitext'        => $a->wikitext ?? $a->content,
                    'content_html'    => $a->content,
                    'updated_by'      => $a->user_id,
                    'created_at'      => $a->created_at ?? now(),
                    'updated_at'      => $a->updated_at ?? now(),
                ]);
            }
        }

        // 3. M:N Pivot Categories (von [[Category:X]] Magic Links)
        if (!Schema::hasTable('wiki_article_category')) {
            Schema::create('wiki_article_category', function (Blueprint $t) {
                $t->unsignedBigInteger('wiki_article_id');
                $t->unsignedBigInteger('wiki_category_id');
                $t->primary(['wiki_article_id', 'wiki_category_id'], 'wac_pk');
                $t->foreign('wiki_article_id')->references('id')->on('wiki_articles')->cascadeOnDelete();
                $t->foreign('wiki_category_id')->references('id')->on('wiki_categories')->cascadeOnDelete();
            });

            // Backfill: bestehende 1:N-Zuordnung (wiki_articles.wiki_category_id) in Pivot kopieren
            DB::statement('INSERT IGNORE INTO wiki_article_category (wiki_article_id, wiki_category_id)
                           SELECT id, wiki_category_id FROM wiki_articles
                           WHERE wiki_category_id IS NOT NULL');
        }

        // 4. Talk Pages — Threads
        if (!Schema::hasTable('wiki_talk_threads')) {
            Schema::create('wiki_talk_threads', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('wiki_article_id');
                $t->string('title', 255);
                $t->unsignedBigInteger('created_by');
                $t->boolean('is_resolved')->default(false);
                $t->boolean('is_pinned')->default(false);
                $t->timestamp('last_reply_at')->nullable();
                $t->timestamps();
                $t->index('wiki_article_id');
                $t->index(['wiki_article_id', 'is_pinned', 'last_reply_at'], 'wtt_article_pin_reply_idx');
                $t->foreign('wiki_article_id')->references('id')->on('wiki_articles')->cascadeOnDelete();
            });
        }

        // 5. Talk Pages — Messages (threaded replies)
        if (!Schema::hasTable('wiki_talk_messages')) {
            Schema::create('wiki_talk_messages', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('wiki_talk_thread_id');
                $t->unsignedBigInteger('user_id');
                $t->longText('wikitext');
                $t->longText('content_html')->nullable();
                $t->unsignedBigInteger('reply_to_id')->nullable();
                $t->timestamps();
                $t->index('wiki_talk_thread_id');
                $t->index('reply_to_id');
                $t->foreign('wiki_talk_thread_id')->references('id')->on('wiki_talk_threads')->cascadeOnDelete();
            });
        }

        // 6. Standalone Redirects (Slug → Artikel ohne eigene Page)
        if (!Schema::hasTable('wiki_redirects')) {
            Schema::create('wiki_redirects', function (Blueprint $t) {
                $t->id();
                $t->string('namespace', 32)->default('main');
                $t->string('from_slug', 255);
                $t->unsignedBigInteger('to_article_id');
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->unique(['namespace', 'from_slug'], 'wr_ns_slug_unique');
                $t->foreign('to_article_id')->references('id')->on('wiki_articles')->cascadeOnDelete();
            });
        }

        // 7. Wiki-Links (für Special:WhatLinksHere und Red-Link-Detection)
        if (!Schema::hasTable('wiki_links')) {
            Schema::create('wiki_links', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('from_article_id');
                $t->unsignedBigInteger('to_article_id')->nullable(); // null = Red Link
                $t->string('to_namespace', 32)->default('main');
                $t->string('to_slug', 255);
                $t->string('locale', 8)->default('de');
                $t->timestamps();
                $t->index(['to_article_id', 'locale']);
                $t->index(['to_namespace', 'to_slug', 'locale'], 'wl_ns_slug_locale_idx');
                $t->index('from_article_id');
                $t->foreign('from_article_id')->references('id')->on('wiki_articles')->cascadeOnDelete();
                $t->foreign('to_article_id')->references('id')->on('wiki_articles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_links');
        Schema::dropIfExists('wiki_redirects');
        Schema::dropIfExists('wiki_talk_messages');
        Schema::dropIfExists('wiki_talk_threads');
        Schema::dropIfExists('wiki_article_category');
        Schema::dropIfExists('wiki_article_translations');

        Schema::table('wiki_articles', function (Blueprint $t) {
            try { $t->dropUnique('wiki_articles_namespace_slug_unique'); } catch (\Throwable $e) {}
            try { $t->unique('slug', 'wiki_articles_slug_unique'); } catch (\Throwable $e) {}
            try { $t->dropIndex('wiki_articles_namespace_index'); } catch (\Throwable $e) {}
            $cols = array_filter(['wikitext', 'namespace', 'is_redirect', 'redirect_target'],
                fn($c) => Schema::hasColumn('wiki_articles', $c));
            if (!empty($cols)) $t->dropColumn($cols);
        });
    }
};
