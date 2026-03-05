<?php

namespace App\Helpers;

class BBCode
{
    public static function parse(string $text): string
    {
        // Erst HTML-Entities escapen
        $text = e($text);

        // BBCode Tags parsen
        $patterns = [
            // Fett
            '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
            // Kursiv
            '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
            // Unterstrichen
            '/\[u\](.*?)\[\/u\]/is' => '<span class="underline">$1</span>',
            // Durchgestrichen
            '/\[s\](.*?)\[\/s\]/is' => '<span class="line-through">$1</span>',
            // Farbe
            '/\[color=(#[0-9a-fA-F]{3,6}|[a-zA-Z]+)\](.*?)\[\/color\]/is' => '<span style="color:$1">$2</span>',
            // Größe
            '/\[size=([0-9]+)\](.*?)\[\/size\]/is' => '<span style="font-size:$1px">$2</span>',
            // Link mit Text
            '/\[url=(https?:\/\/[^\]]+)\](.*?)\[\/url\]/is' => '<a href="$1" class="text-blue-400 hover:text-blue-300 underline" target="_blank" rel="nofollow noopener">$2</a>',
            // Link ohne Text
            '/\[url\](https?:\/\/[^\[]+)\[\/url\]/is' => '<a href="$1" class="text-blue-400 hover:text-blue-300 underline" target="_blank" rel="nofollow noopener">$1</a>',
            // Bild
            '/\[img\](https?:\/\/[^\[]+)\[\/img\]/is' => '<img src="$1" alt="image" class="max-w-full rounded-lg my-2" loading="lazy">',
            // Bild mit Größe
            '/\[img width=([0-9]+)\](https?:\/\/[^\[]+)\[\/img\]/is' => '<img src="$2" alt="image" style="max-width:$1px" class="rounded-lg my-2" loading="lazy">',
            // Code inline
            '/\[code\](.*?)\[\/code\]/is' => '<code class="bg-gray-900 text-green-400 px-2 py-1 rounded text-sm font-mono">$1</code>',
            // Code Block
            '/\[codeblock\](.*?)\[\/codeblock\]/is' => '<pre class="bg-gray-900 border border-gray-700 rounded-lg p-4 my-2 overflow-x-auto"><code class="text-green-400 text-sm font-mono">$1</code></pre>',
            // Zitat ohne Autor
            '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote class="border-l-4 border-blue-500 bg-gray-900/50 pl-4 py-2 my-2 text-gray-400 italic">$1</blockquote>',
            // Zitat mit Autor
            '/\[quote=(.*?)\](.*?)\[\/quote\]/is' => '<blockquote class="border-l-4 border-blue-500 bg-gray-900/50 pl-4 py-2 my-2"><div class="text-blue-400 text-sm font-semibold mb-1">$1:</div><div class="text-gray-400 italic">$2</div></blockquote>',
            // Spoiler
            '/\[spoiler\](.*?)\[\/spoiler\]/is' => '<details class="bg-gray-900/50 border border-gray-700 rounded-lg my-2"><summary class="cursor-pointer px-4 py-2 text-gray-400 hover:text-white transition">Spoiler</summary><div class="px-4 py-2 text-gray-300">$1</div></details>',
            // Spoiler mit Titel
            '/\[spoiler=(.*?)\](.*?)\[\/spoiler\]/is' => '<details class="bg-gray-900/50 border border-gray-700 rounded-lg my-2"><summary class="cursor-pointer px-4 py-2 text-gray-400 hover:text-white transition">$1</summary><div class="px-4 py-2 text-gray-300">$2</div></details>',
            // Liste ungeordnet
            '/\[list\](.*?)\[\/list\]/is' => '<ul class="list-disc list-inside my-2 space-y-1">$1</ul>',
            // Liste geordnet
            '/\[list=1\](.*?)\[\/list\]/is' => '<ol class="list-decimal list-inside my-2 space-y-1">$1</ol>',
            // Listen-Element
            '/\[\*\](.*?)(?=\[\*\]|\[\/list\])/is' => '<li class="text-gray-300">$1</li>',
            // Horizontale Linie
            '/\[hr\]/i' => '<hr class="border-gray-700 my-4">',
            // YouTube
            '/\[youtube\](?:https?:\/\/(?:www\.)?youtube\.com\/watch\?v=|https?:\/\/youtu\.be\/)([a-zA-Z0-9_-]+)(?:[^\[]*)\[\/youtube\]/is' => '<div class="my-2 aspect-video max-w-lg"><iframe src="https://www.youtube-nocookie.com/embed/$1" class="w-full h-full rounded-lg" frameborder="0" allowfullscreen></iframe></div>',
            // Zentriert
            '/\[center\](.*?)\[\/center\]/is' => '<div class="text-center">$1</div>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Zeilenumbrüche
        $text = nl2br($text);

        return $text;
    }

    // BBCode Tags für die Toolbar
    public static function getToolbarButtons(): array
    {
        return [
            ['tag' => 'b', 'icon' => 'fas fa-bold', 'title' => 'Bold'],
            ['tag' => 'i', 'icon' => 'fas fa-italic', 'title' => 'Italic'],
            ['tag' => 'u', 'icon' => 'fas fa-underline', 'title' => 'Underline'],
            ['tag' => 's', 'icon' => 'fas fa-strikethrough', 'title' => 'Strikethrough'],
            ['tag' => 'url', 'icon' => 'fas fa-link', 'title' => 'Link'],
            ['tag' => 'img', 'icon' => 'fas fa-image', 'title' => 'Image'],
            ['tag' => 'code', 'icon' => 'fas fa-code', 'title' => 'Code'],
            ['tag' => 'quote', 'icon' => 'fas fa-quote-left', 'title' => 'Quote'],
            ['tag' => 'spoiler', 'icon' => 'fas fa-eye-slash', 'title' => 'Spoiler'],
            ['tag' => 'list', 'icon' => 'fas fa-list', 'title' => 'List'],
            ['tag' => 'youtube', 'icon' => 'fab fa-youtube', 'title' => 'YouTube'],
            ['tag' => 'hr', 'icon' => 'fas fa-minus', 'title' => 'Line', 'selfClosing' => true],
        ];
    }
}
