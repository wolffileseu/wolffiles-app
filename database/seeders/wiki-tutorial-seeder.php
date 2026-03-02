<?php
// =============================================
// RUN IN TINKER: php artisan tinker
// Then paste this code
// =============================================

// Wiki Categories
$wikiCategories = [
    ['name' => 'Glossar', 'slug' => 'glossar', 'description' => 'ET/RtCW Begriffe und Abkürzungen', 'icon' => 'heroicon-o-book-open', 'sort_order' => 1, 'name_translations' => ['de' => 'Glossar', 'en' => 'Glossary']],
    ['name' => 'Maps', 'slug' => 'maps', 'description' => 'Map-Beschreibungen, Strategien und Taktiken', 'icon' => 'heroicon-o-map', 'sort_order' => 2, 'name_translations' => ['de' => 'Maps', 'en' => 'Maps']],
    ['name' => 'Mods', 'slug' => 'mods', 'description' => 'Mod-Dokumentation und Konfiguration', 'icon' => 'heroicon-o-puzzle-piece', 'sort_order' => 3, 'name_translations' => ['de' => 'Mods', 'en' => 'Mods']],
    ['name' => 'Server', 'slug' => 'server', 'description' => 'Server-Konfiguration und CVARs', 'icon' => 'heroicon-o-server', 'sort_order' => 4, 'name_translations' => ['de' => 'Server', 'en' => 'Server']],
    ['name' => 'Gameplay', 'slug' => 'gameplay', 'description' => 'Spielmechaniken, Klassen und Waffen', 'icon' => 'heroicon-o-trophy', 'sort_order' => 5, 'name_translations' => ['de' => 'Gameplay', 'en' => 'Gameplay']],
    ['name' => 'Community', 'slug' => 'community', 'description' => 'Clans, Events und Geschichte', 'icon' => 'heroicon-o-user-group', 'sort_order' => 6, 'name_translations' => ['de' => 'Community', 'en' => 'Community']],
];

foreach ($wikiCategories as $cat) {
    \App\Models\WikiCategory::firstOrCreate(['slug' => $cat['slug']], $cat + ['is_active' => true]);
}

// Tutorial Categories
$tutorialCategories = [
    ['name' => 'Mapping', 'slug' => 'mapping', 'description' => 'Map-Erstellung mit GTKRadiant und anderen Tools', 'icon' => 'heroicon-o-map', 'sort_order' => 1, 'name_translations' => ['de' => 'Mapping', 'en' => 'Mapping']],
    ['name' => 'Server Setup', 'slug' => 'server-setup', 'description' => 'Gameserver aufsetzen und konfigurieren', 'icon' => 'heroicon-o-server-stack', 'sort_order' => 2, 'name_translations' => ['de' => 'Server Setup', 'en' => 'Server Setup']],
    ['name' => 'Modding & Scripting', 'slug' => 'modding-scripting', 'description' => 'Mods erstellen, LUA Scripting, Shader', 'icon' => 'heroicon-o-code-bracket', 'sort_order' => 3, 'name_translations' => ['de' => 'Modding & Scripting', 'en' => 'Modding & Scripting']],
    ['name' => 'Skinning & Models', 'slug' => 'skinning-models', 'description' => 'Skins, Texturen und 3D-Modelle erstellen', 'icon' => 'heroicon-o-paint-brush', 'sort_order' => 4, 'name_translations' => ['de' => 'Skinning & Models', 'en' => 'Skinning & Models']],
    ['name' => 'Video & Moviemaking', 'slug' => 'video-moviemaking', 'description' => 'Frag-Movies und Demos aufnehmen/bearbeiten', 'icon' => 'heroicon-o-video-camera', 'sort_order' => 5, 'name_translations' => ['de' => 'Video & Moviemaking', 'en' => 'Video & Moviemaking']],
    ['name' => 'Allgemein', 'slug' => 'allgemein', 'description' => 'Sonstige Anleitungen und Tipps', 'icon' => 'heroicon-o-light-bulb', 'sort_order' => 6, 'name_translations' => ['de' => 'Allgemein', 'en' => 'General']],
];

foreach ($tutorialCategories as $cat) {
    \App\Models\TutorialCategory::firstOrCreate(['slug' => $cat['slug']], $cat + ['is_active' => true]);
}

echo "Wiki: " . \App\Models\WikiCategory::count() . " categories\n";
echo "Tutorials: " . \App\Models\TutorialCategory::count() . " categories\n";
