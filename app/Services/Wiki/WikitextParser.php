<?php

namespace App\Services\Wiki;

use App\Models\WikiArticle;
use Illuminate\Support\Str;

class WikitextParser
{
    private string $locale;
    private ?int $articleId;
    private string $namespace;

    private array $stash = [];
    private int $stashCounter = 0;

    private ParseResult $result;

    public function __construct(string $locale = 'de', ?int $articleId = null, string $namespace = 'main')
    {
        $this->locale    = $locale;
        $this->articleId = $articleId;
        $this->namespace = $namespace;
    }

    public static function make(array $context = []): self
    {
        return new self(
            $context['locale']     ?? 'de',
            $context['article_id'] ?? null,
            $context['namespace']  ?? 'main',
        );
    }

    public function parse(string $wikitext): ParseResult
    {
        $this->result       = new ParseResult();
        $this->stash        = [];
        $this->stashCounter = 0;

        if (trim($wikitext) === '') {
            $this->result->html = '';
            return $this->result;
        }

        $text = str_replace(["\r\n", "\r"], "\n", $wikitext);

        $text = $this->extractMagicWords($text);

        if ($redirect = $this->detectRedirect($text)) {
            $this->result->redirectTo = $redirect;
            $this->result->html = '<div class="wiki-redirect-notice">Redirect: '
                . htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') . '</div>';
            return $this->result;
        }

        $text = $this->stashNowiki($text);
        $text = $this->stashRefs($text);
        $text = $this->stashCodeFences($text);

        $text = $this->stashEmphasis($text);

        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $text = $this->parseTables($text);
        $text = $this->parseHeadings($text);
        $text = $this->parseLists($text);
        $text = $this->parseHorizontalRules($text);
        $text = $this->parseInline($text);
        $text = $this->parseTemplates($text);
        $text = $this->parseCategories($text);
        $text = $this->parseFiles($text);
        $text = $this->wrapParagraphs($text);
        $text = $this->unstash($text);
        $text = $this->unstashEmphasis($text);
        $text = $this->appendFootnotes($text);
        $text = $this->insertToc($text);

        $this->result->html = $text;
        return $this->result;
    }

    private function extractMagicWords(string $text): string
    {
        if (strpos($text, '__NOTOC__') !== false) {
            $this->result->suppressToc = true;
            $text = str_replace('__NOTOC__', '', $text);
        }
        if (strpos($text, '__FORCETOC__') !== false) {
            $this->result->forceToc = true;
            $text = str_replace('__FORCETOC__', '', $text);
        }
        if (strpos($text, '__TOC__') !== false) {
            $this->result->hasInlineToc = true;
            $text = str_replace('__TOC__', "\x02TOCMARK\x02", $text);
        }
        return $text;
    }

    private function detectRedirect(string $text): ?string
    {
        if (preg_match('/^\s*#REDIRECT\s*\[\[([^\]\|]+)(?:\|[^\]]*)?\]\]/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function stash(string $content, string $tag = 'STASH'): string
    {
        $token = "\x01" . $tag . '_' . (++$this->stashCounter) . "\x01";
        $this->stash[$token] = $content;
        return $token;
    }

    private function stashNowiki(string $text): string
    {
        return preg_replace_callback('/<nowiki>(.*?)<\/nowiki>/is', function ($m) {
            return $this->stash(htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'), 'NOWIKI');
        }, $text);
    }

    private function stashRefs(string $text): string
    {
        return preg_replace_callback('/<ref(?:\s+name="([^"]*)")?\s*>(.*?)<\/ref>/is', function ($m) {
            $content = trim($m[2]);
            $idx = count($this->result->footnotes);
            $this->result->footnotes[$idx] = $content;
            $n = $idx + 1;
            $sup = '<sup class="wiki-ref"><a href="#wiki-fn-' . $n . '" id="wiki-fnref-' . $n . '">[' . $n . ']</a></sup>';
            return $this->stash($sup, 'REF');
        }, $text);
    }

    private function stashCodeFences(string $text): string
    {
        return preg_replace_callback('/```([a-zA-Z0-9_-]*)\n(.*?)\n```/s', function ($m) {
            $lang = $m[1] !== '' ? ' class="language-' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '"' : '';
            $body = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            return $this->stash('<pre class="wiki-code"><code' . $lang . '>' . $body . '</code></pre>', 'CODE');
        }, $text);
    }

    private function unstash(string $text): string
    {
        for ($i = 0; $i < 5; $i++) {
            $new = strtr($text, $this->stash);
            if ($new === $text) break;
            $text = $new;
        }
        return $text;
    }

    private function parseTables(string $text): string
    {
        $lines = explode("\n", $text);
        $out = [];
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];
            if (preg_match('/^\s*\|.+\|\s*$/', $line)
                && isset($lines[$i + 1])
                && preg_match('/^\s*\|[\s\-:|]+\|\s*$/', $lines[$i + 1])) {

                $headers = $this->splitTableRow($line);
                $i += 2;
                $rows = [];
                while ($i < $count && preg_match('/^\s*\|.+\|\s*$/', $lines[$i])) {
                    $rows[] = $this->splitTableRow($lines[$i]);
                    $i++;
                }

                $html = '<table class="wiki-table"><thead><tr>';
                foreach ($headers as $h) {
                    $html .= '<th>' . $h . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($rows as $r) {
                    $html .= '<tr>';
                    foreach ($r as $c) {
                        $html .= '<td>' . $c . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                $out[] = $this->stash($html, 'TABLE');
                continue;
            }
            $out[] = $line;
            $i++;
        }
        return implode("\n", $out);
    }

    private function splitTableRow(string $line): array
    {
        $line = trim($line);
        $line = preg_replace('/^\|/', '', $line);
        $line = preg_replace('/\|$/', '', $line);
        return array_map('trim', explode('|', $line));
    }

    private function parseHeadings(string $text): string
    {
        $self = $this;
        $usedAnchors = [];
        $sectionCounter = 0;

        return preg_replace_callback(
            '/^(={1,6})\s*(.+?)\s*\1\s*$/m',
            function ($m) use ($self, &$usedAnchors, &$sectionCounter) {
                $level = strlen($m[1]);
                $text  = trim($m[2]);
                $plainText = strip_tags($text);

                $baseAnchor = Str::slug($plainText) ?: 'section';
                $anchor = $baseAnchor;
                $n = 2;
                while (in_array($anchor, $usedAnchors, true)) {
                    $anchor = $baseAnchor . '-' . $n++;
                }
                $usedAnchors[] = $anchor;

                $sectionCounter++;
                $self->result->toc[] = [
                    'level'  => $level,
                    'text'   => $plainText,
                    'anchor' => $anchor,
                    'number' => (string) $sectionCounter,
                ];

                $editLink = '';
                if ($self->articleIdInternal()) {
                    $editLink = '<span class="wiki-editsection">[<a href="#" '
                        . 'data-section="' . $sectionCounter . '" '
                        . 'class="wiki-editsection-link">edit</a>]</span>';
                }

                return '<h' . $level . ' id="' . $anchor . '" class="wiki-heading">'
                    . $editLink . '<span class="wiki-headline">' . $text . '</span>'
                    . '</h' . $level . '>';
            },
            $text
        );
    }

    public function articleIdInternal(): ?int
    {
        return $this->articleId;
    }

    private function parseLists(string $text): string
    {
        $lines = explode("\n", $text);
        $out = [];
        $stack = [];

        $closeAll = function () use (&$stack, &$out) {
            while (!empty($stack)) {
                $top = array_pop($stack);
                $out[] = '</' . $top[0] . '>';
            }
        };

        foreach ($lines as $line) {
            $rawLine = $line;
            if (preg_match('/^([*#:;]+)\s?(.*)$/', $line, $m)) {
                $markers = $m[1];
                $body    = $m[2];
                $depth   = strlen($markers);
                $lastChar = substr($markers, -1);

                $tag = match ($lastChar) {
                    '*'      => 'ul',
                    '#'      => 'ol',
                    ':', ';' => 'dl',
                    default  => 'ul',
                };

                while (count($stack) > $depth) {
                    $top = array_pop($stack);
                    $out[] = '</' . $top[0] . '>';
                }
                while (count($stack) < $depth) {
                    $newTag = $tag;
                    if (count($stack) < $depth - 1) {
                        $charAtPos = $markers[count($stack)] ?? $lastChar;
                        $newTag = match ($charAtPos) {
                            '*'      => 'ul',
                            '#'      => 'ol',
                            ':', ';' => 'dl',
                            default  => 'ul',
                        };
                    }
                    $out[] = '<' . $newTag . ' class="wiki-list">';
                    $stack[] = [$newTag, count($stack) + 1];
                }
                if (!empty($stack) && end($stack)[0] !== $tag) {
                    $top = array_pop($stack);
                    $out[] = '</' . $top[0] . '>';
                    $out[] = '<' . $tag . ' class="wiki-list">';
                    $stack[] = [$tag, $depth];
                }

                if ($lastChar === ';') {
                    if (str_contains($body, ' : ')) {
                        [$term, $def] = explode(' : ', $body, 2);
                        $out[] = '<dt>' . trim($term) . '</dt><dd>' . trim($def) . '</dd>';
                    } else {
                        $out[] = '<dt>' . $body . '</dt>';
                    }
                } elseif ($lastChar === ':') {
                    $out[] = '<dd>' . $body . '</dd>';
                } else {
                    $out[] = '<li>' . $body . '</li>';
                }
            } else {
                $closeAll();
                $out[] = $rawLine;
            }
        }
        $closeAll();
        return implode("\n", $out);
    }

    private function parseHorizontalRules(string $text): string
    {
        return preg_replace('/^----+\s*$/m', '<hr class="wiki-hr">', $text);
    }

    private function parseInline(string $text): string
    {
        $text = preg_replace_callback('/\[\[([^\]\n\|]+)(?:\|([^\]\n]+))?\]\]/', function ($m) {
            $target  = trim($m[1]);
            $display = isset($m[2]) ? trim($m[2]) : null;

            if (preg_match('/^Category:(.+)$/i', $target, $cm)) {
                $catSlug = Str::slug(trim($cm[1]));
                $this->result->categories[] = $catSlug;
                return '';
            }

            if (preg_match('/^(?:File|Datei|Bild):(.+)$/i', $target)) {
                return '[[' . $target . ($display ? '|' . $display : '') . ']]';
            }

            $ns = 'main';
            $slug = $target;
            if (preg_match('/^([A-Za-z][A-Za-z0-9_]+):(.+)$/', $target, $nm)) {
                $candidateNs = strtolower($nm[1]);
                if (in_array($candidateNs, ['help', 'template', 'talk'], true)) {
                    $ns   = $candidateNs;
                    $slug = trim($nm[2]);
                }
            }

            $slugified = Str::slug($slug);
            $title = $display ?: $slug;

            $exists = WikiArticle::where('namespace', $ns)
                ->where('slug', $slugified)
                ->exists();

            $this->result->links[] = [
                'namespace' => $ns,
                'slug'      => $slugified,
                'title'     => $slug,
                'exists'    => $exists,
            ];

            $url = '/wiki/' . ($ns !== 'main' ? $ns . ':' : '') . $slugified;
            $cls = $exists ? 'wikilink' : 'wikilink wikilink-new';
            $titleAttr = $exists ? '' : ' title="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . ' (Seite existiert noch nicht)"';

            return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '"' . $titleAttr . '>'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a>';
        }, $text);

        $text = preg_replace_callback('/\[((?:https?|ftp):\/\/[^\s\]]+)\s+([^\]]+)\]/', function ($m) {
            return '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
                . '" class="external" rel="nofollow noopener" target="_blank">'
                . $m[2] . '</a>';
        }, $text);

        $extCounter = 0;
        $text = preg_replace_callback('/\[((?:https?|ftp):\/\/[^\s\]]+)\]/', function ($m) use (&$extCounter) {
            $extCounter++;
            return '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
                . '" class="external external-autonumber" rel="nofollow noopener" target="_blank">['
                . $extCounter . ']</a>';
        }, $text);

        $text = preg_replace_callback('/(?<![">=])(https?:\/\/[^\s<]+[^\s<\.,;:\)\]])/', function ($m) {
            return '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
                . '" class="external external-bare" rel="nofollow noopener" target="_blank">'
                . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</a>';
        }, $text);


        return $text;
    }

    private function parseTemplates(string $text): string
    {
        return preg_replace_callback('/\{\{([^}|\n]+)(\|[^}]*)?\}\}/', function ($m) {
            $name = trim($m[1]);
            return '<span class="wiki-template" data-template="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">'
                . '{{' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '}}</span>';
        }, $text);
    }

    private function parseCategories(string $text): string
    {
        $this->result->categories = array_values(array_unique($this->result->categories));
        return $text;
    }

    private function parseFiles(string $text): string
    {
        return preg_replace_callback('/\[\[(?:File|Datei|Bild):([^\]\n\|]+)(?:\|([^\]\n]+))?\]\]/i', function ($m) {
            $path = trim($m[1]);
            $opts = isset($m[2]) ? array_map('trim', explode('|', $m[2])) : [];

            $caption = null;
            $thumb   = false;
            $align   = null;
            $width   = null;
            foreach ($opts as $opt) {
                if ($opt === 'thumb' || $opt === 'thumbnail') { $thumb = true; continue; }
                if (in_array($opt, ['left', 'right', 'center', 'none'], true)) { $align = $opt; continue; }
                if (preg_match('/^(\d+)px$/', $opt, $wm)) { $width = (int)$wm[1]; continue; }
                $caption = $opt;
            }

            $this->result->files[] = ['path' => $path, 'caption' => $caption];

            $url = '/storage/wiki/' . ltrim($path, '/');
            try {
                if (function_exists('config') && config('filesystems.disks.s3')) {
                    $url = \Illuminate\Support\Facades\Storage::disk('s3')->url('wiki/' . ltrim($path, '/'));
                }
            } catch (\Throwable $e) { /* fallback */ }

            $widthAttr = $width ? ' width="' . $width . '"' : '';
            $alignClass = $align ? ' wiki-img-' . $align : '';

            $img = '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
                 . ' alt="' . htmlspecialchars($caption ?? $path, ENT_QUOTES, 'UTF-8') . '"'
                 . $widthAttr . ' class="wiki-img' . $alignClass . '" loading="lazy">';

            if ($thumb || $caption) {
                $cap = $caption ? '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>' : '';
                return '<figure class="wiki-figure' . $alignClass . '">' . $img . $cap . '</figure>';
            }
            return $img;
        }, $text);
    }

    private function wrapParagraphs(string $text): string
    {
        $blocks = preg_split('/\n{2,}/', $text);
        $out = [];
        $blockOpeners = ['<h1', '<h2', '<h3', '<h4', '<h5', '<h6',
                         '<ul', '<ol', '<dl', '<table', '<pre', '<hr',
                         '<figure', '<blockquote', '<div', "\x01"];

        foreach ($blocks as $block) {
            $trim = trim($block);
            if ($trim === '') continue;

            $isBlock = false;
            foreach ($blockOpeners as $opener) {
                if (str_starts_with($trim, $opener)) {
                    $isBlock = true;
                    break;
                }
            }
            if (!$isBlock && preg_match('/^\x01[A-Z]+_\d+\x01$/', $trim)) {
                $isBlock = true;
            }

            if ($isBlock) {
                $out[] = $trim;
            } else {
                $withBreaks = nl2br($trim, false);
                $out[] = '<p>' . $withBreaks . '</p>';
            }
        }
        return implode("\n\n", $out);
    }

    private function appendFootnotes(string $text): string
    {
        if (empty($this->result->footnotes)) {
            return $text;
        }
        $html = '<div class="wiki-footnotes"><ol>';
        foreach ($this->result->footnotes as $i => $note) {
            $n = $i + 1;
            $rendered = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
            $rendered = preg_replace_callback('/\[((?:https?|ftp):\/\/[^\s\]]+)\s+([^\]]+)\]/', function ($m) {
                return '<a href="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8')
                    . '" class="external" rel="nofollow noopener" target="_blank">'
                    . htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8') . '</a>';
            }, $rendered);
            $html .= '<li id="wiki-fn-' . $n . '">'
                  . $rendered
                  . ' <a href="#wiki-fnref-' . $n . '" class="wiki-fn-back">&#8593;</a>'
                  . '</li>';
        }
        $html .= '</ol></div>';
        return $text . "\n\n" . $html;
    }

    private function insertToc(string $text): string
    {
        if ($this->result->suppressToc) {
            return str_replace("\x02TOCMARK\x02", '', $text);
        }

        $toc = $this->result->toc;
        $shouldRender = $this->result->forceToc
                     || $this->result->hasInlineToc
                     || count($toc) >= 4;

        if (!$shouldRender || empty($toc)) {
            return str_replace("\x02TOCMARK\x02", '', $text);
        }

        $html = '<div class="wiki-toc" id="toc"><div class="wiki-toc-title">Inhaltsverzeichnis</div><ul>';
        foreach ($toc as $i => $entry) {
            $indent = str_repeat('&nbsp;&nbsp;', max(0, $entry['level'] - 1));
            $html .= '<li class="wiki-toc-l' . $entry['level'] . '">'
                  . $indent
                  . '<a href="#' . htmlspecialchars($entry['anchor'], ENT_QUOTES, 'UTF-8') . '">'
                  . '<span class="wiki-toc-num">' . $entry['number'] . '</span> '
                  . htmlspecialchars($entry['text'], ENT_QUOTES, 'UTF-8')
                  . '</a></li>';
        }
        $html .= '</ul></div>';

        if ($this->result->hasInlineToc) {
            return str_replace("\x02TOCMARK\x02", $html, $text);
        }

        $text = preg_replace('/(<h[1-6][^>]*>)/', $html . "\n$1", $text, 1);
        return $text;
    }

    private function stashEmphasis(string $text): string
    {
        $text = preg_replace_callback("/'''''(.+?)'''''/s", function ($m) {
            return "\x02EM5OPEN\x02" . $m[1] . "\x02EM5CLOSE\x02";
        }, $text);
        $text = preg_replace_callback("/'''(.+?)'''/s", function ($m) {
            return "\x02EM3OPEN\x02" . $m[1] . "\x02EM3CLOSE\x02";
        }, $text);
        $text = preg_replace_callback("/''(.+?)''/s", function ($m) {
            return "\x02EM2OPEN\x02" . $m[1] . "\x02EM2CLOSE\x02";
        }, $text);
        return $text;
    }

    private function unstashEmphasis(string $text): string
    {
        $text = str_replace(
            ["\x02EM5OPEN\x02", "\x02EM5CLOSE\x02"],
            ['<strong><em>', '</em></strong>'],
            $text
        );
        $text = str_replace(
            ["\x02EM3OPEN\x02", "\x02EM3CLOSE\x02"],
            ['<strong>', '</strong>'],
            $text
        );
        $text = str_replace(
            ["\x02EM2OPEN\x02", "\x02EM2CLOSE\x02"],
            ['<em>', '</em>'],
            $text
        );
        return $text;
    }
}
