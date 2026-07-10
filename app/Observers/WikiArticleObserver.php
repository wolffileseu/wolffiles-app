<?php

namespace App\Observers;

use Throwable;
use App\Models\WikiCategory;
use App\Models\WikiArticle;
use App\Models\WikiArticleTranslation;
use App\Models\WikiLink;
use App\Models\WikiRevision;
use App\Services\Wiki\WikitextParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WikiArticleObserver
{
    /**
     * Master-Switch: wenn null, dann nicht auto-rendern (z.B. bei seeder).
     * Falls man programmatisch updaten will ohne re-render: WikiArticle::withoutEvents(fn() => ...).
     */
    public static bool $suspendAutoRender = false;

    public function saving(WikiArticle $article): void
    {
        if (self::$suspendAutoRender) return;

        // Auto-render: wenn wikitext geändert wurde, content_html (Master) neu erzeugen
        if ($article->isDirty('wikitext') && !empty($article->wikitext)) {
            try {
                $parser = WikitextParser::make([
                    'locale'     => 'de',
                    'article_id' => $article->id, // ggf. null bei create — Parser kommt damit klar
                    'namespace'  => $article->namespace ?? 'main',
                ]);
                $parsed = $parser->parse($article->wikitext);

                // Auto-Redirect-Erkennung
                if ($parsed->redirectTo) {
                    $article->is_redirect = true;
                    $article->redirect_target = $parsed->redirectTo;
                }

                // Excerpt automatisch füllen wenn leer
                if (empty($article->excerpt)) {
                    $plain = trim(strip_tags($parsed->html));
                    $article->excerpt = mb_substr($plain, 0, 300);
                }

                // Master content (für altes show.blade fallback) befüllen
                $article->content = $parsed->html;
            } catch (Throwable $e) {
                Log::error('WikiArticleObserver: parse failed on saving', [
                    'article_id' => $article->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    public function saved(WikiArticle $article): void
    {
        if (self::$suspendAutoRender) return;
        // Trigger bei: (a) wasChanged wikitext (Update), (b) wasRecentlyCreated mit wikitext (Create)
        $triggerOnUpdate = $article->wasChanged('wikitext');
        $triggerOnCreate = $article->wasRecentlyCreated && !empty($article->wikitext);
        if (!$triggerOnUpdate && !$triggerOnCreate) return;

        DB::transaction(function () use ($article) {
            // 1. Translation für DE (Master) anlegen oder updaten
            $parser = WikitextParser::make([
                'locale'     => 'de',
                'article_id' => $article->id,
                'namespace'  => $article->namespace,
            ]);
            $parsed = $parser->parse($article->wikitext);

            WikiArticleTranslation::updateOrCreate(
                ['wiki_article_id' => $article->id, 'locale' => 'de'],
                [
                    'title'        => $article->title,
                    'wikitext'     => $article->wikitext,
                    'content_html' => $parsed->html,
                    'updated_by'   => auth()->id() ?? $article->user_id,
                ]
            );

            // 2. Categories M2M-Sync (aus [[Category:X]] Links im Wikitext)
            if (!empty($parsed->categories)) {
                $catIds = WikiCategory::whereIn('slug', $parsed->categories)->pluck('id')->all();
                $article->categoriesM2M()->syncWithoutDetaching($catIds);
            }

            // 3. Wiki-Links reindexieren
            WikiLink::where('from_article_id', $article->id)
                    ->where('locale', 'de')
                    ->delete();
            foreach ($parsed->links as $l) {
                WikiLink::create([
                    'from_article_id' => $article->id,
                    'to_article_id'   => $l['exists']
                        ? WikiArticle::where('namespace', $l['namespace'])
                                     ->where('slug', $l['slug'])->value('id')
                        : null,
                    'to_namespace'    => $l['namespace'],
                    'to_slug'         => $l['slug'],
                    'locale'          => 'de',
                ]);
            }
        });
    }

    public function updated(WikiArticle $article): void
    {
        if (self::$suspendAutoRender) return;
        if (!$article->wasChanged('wikitext') && !$article->wasChanged('title')) return;

        // Revision schreiben
        $revisionNumber = ($article->revision_count ?? 0) + 1;

        WikiRevision::create([
            'wiki_article_id' => $article->id,
            'user_id'         => auth()->id() ?? $article->user_id,
            'title'           => $article->title,
            'content'         => $article->content ?? '',
            'change_summary'  => request()->input('change_summary') ?: 'Edited via admin',
            'revision_number' => $revisionNumber,
        ]);

        // revision_count inkrementieren ohne nochmal updated-Event auszulösen
        WikiArticle::withoutEvents(fn () => $article->update(['revision_count' => $revisionNumber]));
    }
}
