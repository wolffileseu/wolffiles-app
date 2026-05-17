<?php

namespace App\Services\Wiki;

/**
 * Konvertiert HTML (typisch aus einem WYSIWYG-Editor wie TipTap/RichEditor)
 * zurück in MediaWiki-kompatiblen Wikitext.
 *
 * Best-Effort. Komplexe Strukturen werden vereinfacht. Für 95% der Fälle ausreichend.
 */
class HtmlToWikitext
{
    public function convert(string $html): string
    {
        if (trim($html) === '') return '';

        // 0. Vorbereitung: Entities decodieren, Whitespace normalisieren
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/\s+/', ' ', $html);          // collapse whitespace
        $html = preg_replace('/>\s+</', '><', $html);        // inter-tag whitespace
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // 1. Block-Elemente
        $html = $this->convertCodeBlocks($html);   // FIRST: schützt Inhalt vor weiteren Replacements
        $html = $this->convertTables($html);
        $html = $this->convertHeadings($html);
        $html = $this->convertLists($html);
        $html = $this->convertParagraphs($html);
        $html = $this->convertBlockquotes($html);
        $html = $this->convertHorizontalRules($html);

        // 2. Inline
        $html = $this->convertLinks($html);
        $html = $this->convertImages($html);
        $html = $this->convertBoldItalic($html);
        $html = $this->convertInlineCode($html);
        $html = $this->convertLineBreaks($html);

        // 3. Cleanup
        $html = strip_tags($html);                          // verbleibende Tags entfernen
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace("/\n{3,}/", "\n\n", $html);    // max 2 newlines
        $html = preg_replace("/[ \t]+\n/", "\n", $html);    // trailing whitespace pro line

        return trim($html);
    }

    private function convertCodeBlocks(string $html): string
    {
        // <pre><code>X</code></pre> → ```X```
        $html = preg_replace_callback(
            '/<pre[^>]*>\s*<code[^>]*>(.*?)<\/code>\s*<\/pre>/is',
            fn($m) => "\n\n```\n" . strip_tags($m[1]) . "\n```\n\n",
            $html
        );
        // <pre>X</pre> alone
        $html = preg_replace_callback(
            '/<pre[^>]*>(.*?)<\/pre>/is',
            fn($m) => "\n\n```\n" . strip_tags($m[1]) . "\n```\n\n",
            $html
        );
        return $html;
    }

    private function convertTables(string $html): string
    {
        return preg_replace_callback('/<table[^>]*>(.*?)<\/table>/is', function ($m) {
            $tableHtml = $m[1];
            $rows = [];
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rowMatches);
            $headerDone = false;

            foreach ($rowMatches[1] as $rowHtml) {
                preg_match_all('/<(th|td)[^>]*>(.*?)<\/\1>/is', $rowHtml, $cellMatches, PREG_SET_ORDER);
                $cells = [];
                $isHeader = false;
                foreach ($cellMatches as $cm) {
                    if (strtolower($cm[1]) === 'th') $isHeader = true;
                    $cells[] = trim(strip_tags($cm[2]));
                }
                if (empty($cells)) continue;
                $rows[] = ['cells' => $cells, 'header' => $isHeader];
            }

            if (empty($rows)) return '';

            $out = "\n\n";
            $maxCols = max(array_map(fn($r) => count($r['cells']), $rows));
            foreach ($rows as $i => $row) {
                $padded = array_pad($row['cells'], $maxCols, '');
                $out .= '| ' . implode(' | ', $padded) . " |\n";
                if ($i === 0 && $row['header']) {
                    $out .= '|' . str_repeat('---|', $maxCols) . "\n";
                }
            }
            return $out . "\n";
        }, $html);
    }

    private function convertHeadings(string $html): string
    {
        for ($lvl = 1; $lvl <= 6; $lvl++) {
            $eq = str_repeat('=', $lvl);
            $html = preg_replace_callback(
                "/<h{$lvl}[^>]*>(.*?)<\/h{$lvl}>/is",
                fn($m) => "\n\n{$eq} " . trim(strip_tags($m[1])) . " {$eq}\n\n",
                $html
            );
        }
        return $html;
    }

    private function convertLists(string $html): string
    {
        // Rekursiv von außen nach innen — outer ul/ol mehrfach durchgehen für Nesting
        for ($pass = 0; $pass < 5; $pass++) {
            $changed = false;

            // <ul> → * Items
            $newHtml = preg_replace_callback('/<ul[^>]*>(.*?)<\/ul>/is', function ($m) {
                if (strpos($m[1], '<ul') !== false || strpos($m[1], '<ol') !== false) {
                    return $m[0]; // verschachtelt — späterer Pass
                }
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $m[1], $items);
                $out = "\n";
                foreach ($items[1] as $item) {
                    $out .= '* ' . trim(strip_tags($item)) . "\n";
                }
                return $out . "\n";
            }, $html);
            if ($newHtml !== $html) { $html = $newHtml; $changed = true; }

            // <ol>
            $newHtml = preg_replace_callback('/<ol[^>]*>(.*?)<\/ol>/is', function ($m) {
                if (strpos($m[1], '<ul') !== false || strpos($m[1], '<ol') !== false) {
                    return $m[0];
                }
                preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $m[1], $items);
                $out = "\n";
                foreach ($items[1] as $item) {
                    $out .= '# ' . trim(strip_tags($item)) . "\n";
                }
                return $out . "\n";
            }, $html);
            if ($newHtml !== $html) { $html = $newHtml; $changed = true; }

            if (!$changed) break;
        }
        return $html;
    }

    private function convertParagraphs(string $html): string
    {
        return preg_replace_callback('/<p[^>]*>(.*?)<\/p>/is', function ($m) {
            $inner = trim($m[1]);
            if ($inner === '') return '';
            return "\n\n" . $inner . "\n\n";
        }, $html);
    }

    private function convertBlockquotes(string $html): string
    {
        return preg_replace_callback('/<blockquote[^>]*>(.*?)<\/blockquote>/is', function ($m) {
            $inner = trim(strip_tags($m[1]));
            // Jede Zeile mit ":" prefixen (MediaWiki-Indent-Syntax)
            $lines = preg_split('/\n+/', $inner);
            return "\n\n" . implode("\n", array_map(fn($l) => ': ' . trim($l), $lines)) . "\n\n";
        }, $html);
    }

    private function convertHorizontalRules(string $html): string
    {
        return preg_replace('/<hr[^>]*\/?>/i', "\n\n----\n\n", $html);
    }

    private function convertLinks(string $html): string
    {
        return preg_replace_callback('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', function ($m) {
            $href = trim($m[1]);
            $text = trim(strip_tags($m[2]));

            // Interne Wiki-Links: /wiki/foo-bar → [[Foo Bar]]
            if (preg_match('#^/wiki/([^?#]+)#', $href, $w)) {
                $slug = $w[1];
                $niceTitle = ucwords(str_replace(['-', '_'], ' ', $slug));
                if ($text === '' || strcasecmp($text, $niceTitle) === 0) {
                    return "[[{$niceTitle}]]";
                }
                return "[[{$niceTitle}|{$text}]]";
            }

            // Externe URLs
            if (preg_match('#^(https?|ftp)://#', $href)) {
                if ($text === '' || $text === $href) {
                    return $href;
                }
                return "[{$href} {$text}]";
            }

            // Relative oder andere Links — als external mit display behandeln
            return "[{$href} {$text}]";
        }, $html);
    }

    private function convertImages(string $html): string
    {
        return preg_replace_callback('/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*\/?>/i', function ($m) {
            $src = trim($m[1]);
            // Wenn S3-URL: extrahiere nur den Filename
            if (preg_match('#/wiki/(?:images|attachments)/(.+)$#', $src, $w)) {
                return "[[File:{$w[1]}]]";
            }
            // Externe Bilder als bare URL behalten (Wikitext kann das nicht direkt)
            return $src;
        }, $html);
    }

    private function convertBoldItalic(string $html): string
    {
        // Combined bold+italic
        $html = preg_replace_callback('/<(strong|b)[^>]*>\s*<(em|i)[^>]*>(.*?)<\/\2>\s*<\/\1>/is',
            fn($m) => "'''''" . trim(strip_tags($m[3])) . "'''''", $html);
        $html = preg_replace_callback('/<(em|i)[^>]*>\s*<(strong|b)[^>]*>(.*?)<\/\2>\s*<\/\1>/is',
            fn($m) => "'''''" . trim(strip_tags($m[3])) . "'''''", $html);

        // Bold
        $html = preg_replace_callback('/<(strong|b)[^>]*>(.*?)<\/\1>/is',
            fn($m) => "'''" . trim(strip_tags($m[2])) . "'''", $html);

        // Italic
        $html = preg_replace_callback('/<(em|i)[^>]*>(.*?)<\/\1>/is',
            fn($m) => "''" . trim(strip_tags($m[2])) . "''", $html);

        return $html;
    }

    private function convertInlineCode(string $html): string
    {
        return preg_replace_callback('/<code[^>]*>(.*?)<\/code>/is',
            fn($m) => '<code>' . strip_tags($m[1]) . '</code>',  // Wikitext nutzt <code> nativ
            $html
        );
    }

    private function convertLineBreaks(string $html): string
    {
        return preg_replace('/<br\s*\/?>/i', "\n", $html);
    }
}
