<?php

namespace App\Services\Pm;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Mews\Purifier\Facades\Purifier;

/**
 * Renders user-supplied PM bodies into safe HTML.
 *
 * Pipeline:
 *   1. CommonMark with GFM extension (tables, strikethrough, autolinks, task lists)
 *   2. Raw HTML in the markdown is stripped (html_input: strip)
 *   3. HTMLPurifier with the "pm" profile sanitizes the output
 *
 * Defense-in-depth: even if a tag slips through CommonMark, Purifier filters it.
 */
class PmMarkdownRenderer
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            "html_input"           => "strip",   // remove raw HTML before parsing
            "allow_unsafe_links"   => false,     // strip javascript:, data:, etc.
            "max_nesting_level"    => 5,         // pathological-input safety
            "renderer"             => [
                "soft_break" => "<br>",
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new AutolinkExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Convert Markdown to sanitized HTML.
     *
     * @param  string  $markdown  Raw user input
     * @return string  Safe HTML, ready to be echoed unescaped in Blade {!! !!}
     */
    public function render(string $markdown): string
    {
        if (trim($markdown) === "") {
            return "";
        }

        // 1. Markdown -> HTML (with html_input=strip already removing raw tags)
        $html = (string) $this->converter->convert($markdown);

        // 2. Sanitize via Purifier with the "pm" profile
        return Purifier::clean($html, "pm");
    }

    /**
     * Render plain text: escape HTML, convert newlines to <br>.
     * Used when body_format = "plain".
     */
    public function renderPlain(string $text): string
    {
        if (trim($text) === "") {
            return "";
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, "UTF-8");
        return nl2br($escaped, false);
    }

    /**
     * Render based on body_format.
     */
    public function renderFor(string $body, string $format = "markdown"): string
    {
        return match ($format) {
            "plain"  => $this->renderPlain($body),
            default  => $this->render($body),
        };
    }

    /**
     * Strip Markdown formatting and return plain text.
     * Useful for notification preview snippets and email subject lines.
     *
     * @param  string  $markdown
     * @param  int  $maxLength  Truncate at N chars (0 = no truncation)
     */
    public function toPlainText(string $markdown, int $maxLength = 0): string
    {
        // Render to HTML, then strip ALL tags
        $html  = $this->render($markdown);
        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8"));

        if ($maxLength > 0 && mb_strlen($plain) > $maxLength) {
            $plain = mb_substr($plain, 0, $maxLength - 1) . "\u{2026}"; // ellipsis
        }

        return $plain;
    }

    /**
     * Stub for Phase 4+: extract @username mentions.
     * Returns empty array now; will scan markdown for @mentions later.
     *
     * @return array<string>
     */
    public function extractMentions(string $markdown): array
    {
        // TODO: implement in later phase when @mentions are wired up
        return [];
    }
}
