<?php

namespace App\Console\Commands;

use App\Models\WikiArticle;
use App\Models\WikiArticleTranslation;
use App\Observers\WikiArticleObserver;
use App\Services\Wiki\HtmlToWikitext;
use App\Services\Wiki\WikitextParser;
use Illuminate\Console\Command;

class ConvertWikiHtmlToWikitext extends Command
{
    protected $signature = 'wiki:convert-html
                            {slug? : Slug eines einzelnen Artikels, oder leer für alle}
                            {--dry-run : Output zeigen ohne zu speichern}
                            {--force : Auch Artikel re-konvertieren die schon Wikitext aussehen}';

    protected $description = 'Konvertiert HTML in wiki_articles.wikitext zurück zu echtem Wikitext';

    public function handle(): int
    {
        $converter = new HtmlToWikitext();

        $query = WikiArticle::query();
        if ($slug = $this->argument('slug')) {
            $query->where('slug', $slug);
        }
        $articles = $query->get();

        if ($articles->isEmpty()) {
            $this->error('Keine Artikel gefunden.');
            return Command::FAILURE;
        }

        $this->info("Gefunden: {$articles->count()} Artikel" . ($this->option('dry-run') ? ' (DRY-RUN)' : ''));
        $this->newLine();

        $stats = ['converted' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($articles as $article) {
            $wt = (string) $article->wikitext;

            // Detect: sieht es schon nach Wikitext aus?
            $looksLikeHtml = $this->looksLikeHtml($wt);
            if (!$looksLikeHtml && !$this->option('force')) {
                $this->line("  <fg=gray>SKIP</> {$article->slug} (sieht nicht nach HTML aus)");
                $stats['skipped']++;
                continue;
            }

            try {
                $newWikitext = $converter->convert($wt);

                if (trim($newWikitext) === '') {
                    $this->warn("  EMPTY {$article->slug} (Konvertierung lieferte leeren String, übersprungen)");
                    $stats['skipped']++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("<fg=cyan>=== {$article->slug} ===</>");
                    $this->line("<fg=gray>--- BEFORE (HTML) ---</>");
                    $this->line(mb_substr($wt, 0, 300) . (mb_strlen($wt) > 300 ? '…' : ''));
                    $this->line("<fg=green>--- AFTER (Wikitext) ---</>");
                    $this->line(mb_substr($newWikitext, 0, 300) . (mb_strlen($newWikitext) > 300 ? '…' : ''));
                    $this->newLine();
                } else {
                    // Suspend auto-render: wir rendern unten selbst, damit das Save nicht zweimal läuft
                    WikiArticleObserver::$suspendAutoRender = true;
                    $article->update(['wikitext' => $newWikitext]);
                    WikiArticleObserver::$suspendAutoRender = false;

                    // Manuell rendern + translation aktualisieren
                    $parser = WikitextParser::make([
                        'locale'     => 'de',
                        'article_id' => $article->id,
                        'namespace'  => $article->namespace,
                    ]);
                    $parsed = $parser->parse($newWikitext);

                    $article->update(['content' => $parsed->html]);

                    WikiArticleTranslation::updateOrCreate(
                        ['wiki_article_id' => $article->id, 'locale' => 'de'],
                        [
                            'title'        => $article->title,
                            'wikitext'     => $newWikitext,
                            'content_html' => $parsed->html,
                            'updated_by'   => $article->user_id,
                        ]
                    );

                    $this->info("  OK   {$article->slug} ({$article->title}) — " .
                                strlen($wt) . " → " . strlen($newWikitext) . " bytes");
                }

                $stats['converted']++;
            } catch (\Throwable $e) {
                $this->error("  FAIL {$article->slug}: {$e->getMessage()}");
                $stats['errors']++;
            }
        }

        $this->newLine();
        $this->info("Stats: {$stats['converted']} konvertiert, {$stats['skipped']} übersprungen, {$stats['errors']} Fehler");

        return Command::SUCCESS;
    }

    /**
     * Heuristik: Sieht der String mehr nach HTML aus als nach Wikitext?
     */
    private function looksLikeHtml(string $s): bool
    {
        $htmlScore = 0;
        $wikiScore = 0;

        // HTML-Marker
        if (preg_match('/<p[\s>]/', $s)) $htmlScore += 3;
        if (preg_match('/<h[1-6][\s>]/', $s)) $htmlScore += 3;
        if (preg_match('/<(strong|b|em|i)[\s>]/', $s)) $htmlScore += 2;
        if (preg_match('/<a\s+[^>]*href=/', $s)) $htmlScore += 2;
        if (preg_match('/<(ul|ol)[\s>]/', $s)) $htmlScore += 2;
        if (preg_match('/&(amp|lt|gt|quot|#\d+);/', $s)) $htmlScore += 1;

        // Wikitext-Marker
        if (preg_match('/^={1,6}\s+.+\s+={1,6}\s*$/m', $s)) $wikiScore += 3;
        if (preg_match("/'''[^']+'''/", $s)) $wikiScore += 2;
        if (preg_match('/\[\[[^\]]+\]\]/', $s)) $wikiScore += 3;
        if (preg_match('/^\*\s+/m', $s)) $wikiScore += 1;

        return $htmlScore > $wikiScore;
    }
}
