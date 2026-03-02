<?php

namespace App\Services;

use App\Models\File;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;

class SeoService
{
    /**
     * Generate meta tags for a file.
     */
    public static function forFile(File $file): array
    {
        $description = \Illuminate\Support\Str::limit(strip_tags($file->description ?? ''), 160);
        $image = $file->screenshots->first()?->url;

        return [
            'title' => $file->title . ' - Wolffiles.eu',
            'description' => $description ?: "Download {$file->title} for Wolfenstein: Enemy Territory",
            'og:title' => $file->title,
            'og:description' => $description,
            'og:image' => $image,
            'og:url' => route('files.show', $file),
            'og:type' => 'article',
            'twitter:card' => $image ? 'summary_large_image' : 'summary',
            'keywords' => implode(', ', array_filter([
                $file->title,
                $file->map_name,
                $file->category?->name,
                'Wolfenstein Enemy Territory',
                $file->game,
            ])),
        ];
    }

    /**
     * Generate meta tags for a category.
     */
    public static function forCategory(Category $category): array
    {
        return [
            'title' => $category->name . ' - Wolffiles.eu',
            'description' => $category->description ?? "Browse {$category->name} files for Wolfenstein: Enemy Territory",
            'og:title' => $category->name . ' Files',
            'og:url' => route('categories.show', $category),
        ];
    }

    /**
     * Generate meta tags for a page/post.
     */
    public static function forPost(Post $post): array
    {
        return [
            'title' => $post->title . ' - Wolffiles.eu',
            'description' => \Illuminate\Support\Str::limit(strip_tags($post->content), 160),
            'og:title' => $post->title,
            'og:type' => 'article',
            'og:url' => route('posts.show', $post),
        ];
    }

    /**
     * Default meta tags.
     */
    public static function defaults(): array
    {
        return [
            'title' => 'Wolffiles.eu - Your ET File Resources',
            'description' => 'The largest collection of maps, mods, skins and tools for Wolfenstein: Enemy Territory',
            'og:site_name' => 'Wolffiles.eu',
            'og:locale' => app()->getLocale() === 'de' ? 'de_DE' : 'en_US',
            'twitter:site' => '@wolffiles',
        ];
    }

    /**
     * Generate sitemap XML entries for approved files.
     */
    public static function getSitemapUrls(int $limit = 50000): array
    {
        $urls = [];

        // Static pages
        $urls[] = ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('files.index'), 'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['url' => route('categories.index'), 'priority' => '0.8', 'changefreq' => 'weekly'];

        // Categories
        Category::where('is_active', true)->each(function ($cat) use (&$urls) {
            $urls[] = ['url' => route('categories.show', $cat), 'priority' => '0.7', 'changefreq' => 'weekly'];
        });

        // Files
        File::where('status', 'approved')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->each(function ($file) use (&$urls) {
                $urls[] = [
                    'url' => route('files.show', $file),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                    'lastmod' => $file->updated_at->toW3cString(),
                ];
            });

        return $urls;
    }
}
