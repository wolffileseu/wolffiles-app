<?php

namespace App\Services\Wiki;

/**
 * Line-based diff für Wikitext-Revisionen.
 * Klassischer LCS-Algorithmus, gibt Hunks zurück (added/removed/unchanged).
 */
class WikitextDiff
{
    /**
     * @return array<int, array{type: 'unchanged'|'added'|'removed'|'context', old_line: ?int, new_line: ?int, text: string}>
     */
    public function diff(string $oldText, string $newText): array
    {
        $oldLines = preg_split('/\R/', $oldText);
        $newLines = preg_split('/\R/', $newText);

        $lcs = $this->lcsMatrix($oldLines, $newLines);
        return $this->backtrack($lcs, $oldLines, $newLines, count($oldLines), count($newLines));
    }

    /**
     * Render Diff als HTML side-by-side.
     */
    public function renderSideBySide(string $oldText, string $newText): string
    {
        $hunks = $this->diff($oldText, $newText);

        $html = '<table class="wiki-diff-table" style="width:100%; border-collapse:collapse; font-family:ui-monospace,monospace; font-size:13px;">';
        $html .= '<thead><tr><th style="width:3rem;"></th><th>Alt</th><th style="width:3rem;"></th><th>Neu</th></tr></thead><tbody>';

        // Pair up hunks: removed+added auf gleicher Zeile darstellen wenn möglich
        $i = 0; $count = count($hunks);
        while ($i < $count) {
            $h = $hunks[$i];

            if ($h['type'] === 'removed' && isset($hunks[$i + 1]) && $hunks[$i + 1]['type'] === 'added') {
                $next = $hunks[$i + 1];
                $html .= '<tr>';
                $html .= '<td class="wd-num" style="background:#7f1d1d; color:#fca5a5; text-align:right; padding:2px 6px;">' . ($h['old_line'] ?? '') . '</td>';
                $html .= '<td class="wd-old" style="background:#450a0a; color:#fecaca; padding:2px 8px; white-space:pre-wrap;">' . htmlspecialchars($h['text']) . '</td>';
                $html .= '<td class="wd-num" style="background:#14532d; color:#86efac; text-align:right; padding:2px 6px;">' . ($next['new_line'] ?? '') . '</td>';
                $html .= '<td class="wd-new" style="background:#052e16; color:#bbf7d0; padding:2px 8px; white-space:pre-wrap;">' . htmlspecialchars($next['text']) . '</td>';
                $html .= '</tr>';
                $i += 2;
                continue;
            }

            if ($h['type'] === 'removed') {
                $html .= '<tr>';
                $html .= '<td class="wd-num" style="background:#7f1d1d; color:#fca5a5; text-align:right; padding:2px 6px;">' . ($h['old_line'] ?? '') . '</td>';
                $html .= '<td class="wd-old" style="background:#450a0a; color:#fecaca; padding:2px 8px; white-space:pre-wrap;">' . htmlspecialchars($h['text']) . '</td>';
                $html .= '<td></td><td></td>';
                $html .= '</tr>';
                $i++;
                continue;
            }

            if ($h['type'] === 'added') {
                $html .= '<tr>';
                $html .= '<td></td><td></td>';
                $html .= '<td class="wd-num" style="background:#14532d; color:#86efac; text-align:right; padding:2px 6px;">' . ($h['new_line'] ?? '') . '</td>';
                $html .= '<td class="wd-new" style="background:#052e16; color:#bbf7d0; padding:2px 8px; white-space:pre-wrap;">' . htmlspecialchars($h['text']) . '</td>';
                $html .= '</tr>';
                $i++;
                continue;
            }

            // unchanged — Context (vor und nach changes, sonst weglassen)
            $html .= '<tr>';
            $html .= '<td class="wd-num" style="color:#6b7280; text-align:right; padding:2px 6px;">' . ($h['old_line'] ?? '') . '</td>';
            $html .= '<td style="padding:2px 8px; color:#9ca3af; white-space:pre-wrap;">' . htmlspecialchars($h['text']) . '</td>';
            $html .= '<td class="wd-num" style="color:#6b7280; text-align:right; padding:2px 6px;">' . ($h['new_line'] ?? '') . '</td>';
            $html .= '<td style="padding:2px 8px; color:#9ca3af; white-space:pre-wrap;">' . htmlspecialchars($h['text']) . '</td>';
            $html .= '</tr>';
            $i++;
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Stats: wieviele Zeilen added/removed.
     */
    public function stats(string $oldText, string $newText): array
    {
        $hunks = $this->diff($oldText, $newText);
        $added = 0; $removed = 0; $unchanged = 0;
        foreach ($hunks as $h) {
            if ($h['type'] === 'added')     $added++;
            elseif ($h['type'] === 'removed') $removed++;
            elseif ($h['type'] === 'unchanged') $unchanged++;
        }
        return ['added' => $added, 'removed' => $removed, 'unchanged' => $unchanged];
    }

    private function lcsMatrix(array $old, array $new): array
    {
        $m = count($old);
        $n = count($new);
        $c = [];
        for ($i = 0; $i <= $m; $i++) {
            $c[$i] = array_fill(0, $n + 1, 0);
        }
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($old[$i - 1] === $new[$j - 1]) {
                    $c[$i][$j] = $c[$i - 1][$j - 1] + 1;
                } else {
                    $c[$i][$j] = max($c[$i - 1][$j], $c[$i][$j - 1]);
                }
            }
        }
        return $c;
    }

    private function backtrack(array $c, array $old, array $new, int $i, int $j, array &$out = []): array
    {
        // Iterative version (sonst Stack-Overflow bei langen Texten)
        $stack = [];
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $old[$i - 1] === $new[$j - 1]) {
                $stack[] = ['type' => 'unchanged', 'old_line' => $i, 'new_line' => $j, 'text' => $old[$i - 1]];
                $i--; $j--;
            } elseif ($j > 0 && ($i === 0 || $c[$i][$j - 1] >= $c[$i - 1][$j])) {
                $stack[] = ['type' => 'added', 'old_line' => null, 'new_line' => $j, 'text' => $new[$j - 1]];
                $j--;
            } elseif ($i > 0 && ($j === 0 || $c[$i][$j - 1] < $c[$i - 1][$j])) {
                $stack[] = ['type' => 'removed', 'old_line' => $i, 'new_line' => null, 'text' => $old[$i - 1]];
                $i--;
            } else {
                break;
            }
        }
        return array_reverse($stack);
    }
}
