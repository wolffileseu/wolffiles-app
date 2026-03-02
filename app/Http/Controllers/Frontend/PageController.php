<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function show($slug)
    {
        $page = Page::where("slug", $slug)->firstOrFail();
        abort_unless($page->is_published, 404);

        // Localized content
        $locale = app()->getLocale();
        $title = $page->title_translations[$locale] ?? $page->title;
        $content = $page->content_translations[$locale] ?? $page->content;

        // If markdown, convert and add heading IDs for anchor links
        if (($page->content_type ?? "richtext") === "markdown" || 
            ($content && (str_starts_with(trim($content), "#") || str_contains($content, "##") || str_contains($content, "**")))) {
            $content = Str::markdown($content ?? "");
            // Add id attributes to headings for anchor navigation
            $content = preg_replace_callback(
                "/<(h[1-6])>(.*?)<\/\\1>/i",
                function ($matches) {
                    $tag = $matches[1];
                    $text = strip_tags($matches[2]);
                    $id = Str::slug($text);
                    return "<{$tag} id=\"{$id}\">{$matches[2]}</{$tag}>";
                },
                $content
            );
            $page->_rendered = true;
        }

        // SEO
        $seo = \App\Services\SeoService::defaults();
        $seo["title"] = $title;

        return view("frontend.pages.show", compact("page", "title", "content", "seo"));
    }
}
