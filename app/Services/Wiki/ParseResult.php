<?php

namespace App\Services\Wiki;

/**
 * Ergebnis eines WikitextParser::parse()-Laufs.
 */
class ParseResult
{
    /** @var string Gerendertes HTML */
    public string $html = '';

    /** @var array<int, array{level:int, text:string, anchor:string, number:string}> */
    public array $toc = [];

    /** @var array<int, array{namespace:string, slug:string, title:string, exists:bool}> */
    public array $links = [];

    /** @var array<int, string> Slugs der referenzierten Categories */
    public array $categories = [];

    /** @var array<int, array{path:string, caption:?string}> */
    public array $files = [];

    /** @var string|null Wenn dieser Artikel ein Redirect ist: Ziel-Slug (mit optionalem ns:) */
    public ?string $redirectTo = null;

    /** @var bool Magic-Word __NOTOC__ gesetzt */
    public bool $suppressToc = false;

    /** @var bool Magic-Word __FORCETOC__ gesetzt */
    public bool $forceToc = false;

    /** @var bool Magic-Word __TOC__ gesetzt (TOC inline an dieser Stelle gerendert) */
    public bool $hasInlineToc = false;

    /** @var array<int, string> Footnote-Inhalte aus <ref>-Tags */
    public array $footnotes = [];

    public function toArray(): array
    {
        return [
            'html'         => $this->html,
            'toc'          => $this->toc,
            'links'        => $this->links,
            'categories'   => $this->categories,
            'files'        => $this->files,
            'redirectTo'   => $this->redirectTo,
            'suppressToc'  => $this->suppressToc,
            'forceToc'     => $this->forceToc,
            'hasInlineToc' => $this->hasInlineToc,
            'footnotes'    => $this->footnotes,
        ];
    }
}
