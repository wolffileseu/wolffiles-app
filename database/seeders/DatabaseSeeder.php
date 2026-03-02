<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Badge;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $admin = Role::create(['name' => 'admin']);
        $moderator = Role::create(['name' => 'moderator']);
        $user = Role::create(['name' => 'user']);

        // Admin user
        $adminUser = User::create([
            'name' => 'Admin',
            'email' => 'admin@wolffiles.eu',
            'password' => bcrypt('changeme123'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // =====================
        // CATEGORIES - Games
        // =====================
        $et = Category::create(['name' => 'ET', 'type' => 'game', 'sort_order' => 1,
            'name_translations' => ['en' => 'Enemy Territory', 'de' => 'Enemy Territory']]);
        $rtcw = Category::create(['name' => 'RtCW', 'type' => 'game', 'sort_order' => 2,
            'name_translations' => ['en' => 'Return to Castle Wolfenstein', 'de' => 'Return to Castle Wolfenstein']]);
        $etqw = Category::create(['name' => 'ET Quake Wars', 'type' => 'game', 'sort_order' => 3]);
        $etDom = Category::create(['name' => 'ET-Domination', 'type' => 'game', 'sort_order' => 4]);
        $etFort = Category::create(['name' => 'ETFortress', 'type' => 'game', 'sort_order' => 5]);
        $movies = Category::create(['name' => 'Movies', 'type' => 'game', 'sort_order' => 6]);
        $tce = Category::create(['name' => 'True Combat Elite', 'type' => 'game', 'sort_order' => 7]);
        $wolfClassic = Category::create(['name' => 'Wolf Classic', 'type' => 'game', 'sort_order' => 8]);
        $wolfenstein = Category::create(['name' => 'Wolfenstein', 'type' => 'game', 'sort_order' => 9]);

        // ET Subcategories
        $etSubs = ['Maps', 'Mods', 'Bots', 'Lua', 'Mini-Mods', 'Skinpacks', 'Soundpacks',
                   'Music', 'Tools', 'Prefabs', 'Patches', 'Full Version', 'Stuff',
                   'WinAmp-Skins', 'Revaluation'];
        foreach ($etSubs as $i => $sub) {
            Category::create(['name' => $sub, 'parent_id' => $et->id, 'type' => 'file', 'sort_order' => $i + 1]);
        }

        // RtCW Subcategories
        foreach (['Maps', 'Mods', 'Tools', 'Skinpacks', 'Soundpacks', 'Patches'] as $i => $sub) {
            Category::create(['name' => $sub, 'parent_id' => $rtcw->id, 'type' => 'file', 'sort_order' => $i + 1]);
        }

        // LUA Categories
        Category::create(['name' => 'Admin Tools', 'type' => 'lua', 'sort_order' => 1]);
        Category::create(['name' => 'Fun Scripts', 'type' => 'lua', 'sort_order' => 2]);
        Category::create(['name' => 'Gameplay', 'type' => 'lua', 'sort_order' => 3]);
        Category::create(['name' => 'Server Management', 'type' => 'lua', 'sort_order' => 4]);
        Category::create(['name' => 'Anti-Cheat', 'type' => 'lua', 'sort_order' => 5]);
        Category::create(['name' => 'Utilities', 'type' => 'lua', 'sort_order' => 6]);

        // =====================
        // BADGES
        // =====================
        Badge::create(['name' => 'First Upload', 'description' => 'Uploaded your first file', 'color' => '#4CAF50', 'criteria_type' => 'first_upload', 'criteria_value' => 1]);
        Badge::create(['name' => 'Contributor', 'description' => '10 approved uploads', 'color' => '#2196F3', 'criteria_type' => 'uploads_count', 'criteria_value' => 10]);
        Badge::create(['name' => 'Top Contributor', 'description' => '50 approved uploads', 'color' => '#FFD700', 'criteria_type' => 'uploads_count', 'criteria_value' => 50]);
        Badge::create(['name' => 'Legend', 'description' => '100 approved uploads', 'color' => '#FF5722', 'criteria_type' => 'uploads_count', 'criteria_value' => 100]);
        Badge::create(['name' => 'Popular', 'description' => 'A file reached 1000 downloads', 'color' => '#9C27B0', 'criteria_type' => 'downloads_total', 'criteria_value' => 1000]);

        // =====================
        // MENUS
        // =====================
        $mainMenu = Menu::create(['name' => 'Main Navigation', 'location' => 'header']);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Home', 'route' => 'home', 'sort_order' => 1,
            'title_translations' => ['en' => 'Home', 'de' => 'Startseite']]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Files', 'route' => 'files.index', 'sort_order' => 2,
            'title_translations' => ['en' => 'Files', 'de' => 'Dateien']]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Categories', 'route' => 'categories.index', 'sort_order' => 3,
            'title_translations' => ['en' => 'Categories', 'de' => 'Kategorien']]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'LUA Scripts', 'route' => 'lua.index', 'sort_order' => 4]);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Upload', 'route' => 'files.upload', 'sort_order' => 5, 'icon' => 'heroicon-o-arrow-up-tray']);
        MenuItem::create(['menu_id' => $mainMenu->id, 'title' => 'Discord', 'url' => 'https://discord.com/invite/wzkRyWWuxP', 'target' => '_blank', 'sort_order' => 6]);

        $footerMenu = Menu::create(['name' => 'Footer', 'location' => 'footer']);
        MenuItem::create(['menu_id' => $footerMenu->id, 'title' => 'Impressum', 'url' => '/page/impressum', 'sort_order' => 1]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'title' => 'Privacy Policy', 'url' => '/page/privacy-policy', 'sort_order' => 2]);
        MenuItem::create(['menu_id' => $footerMenu->id, 'title' => 'Contact', 'url' => '/page/contact', 'sort_order' => 3]);

        // =====================
        // SETTINGS
        // =====================
        Setting::set('site_name', 'Wolffiles.eu', 'string', 'general');
        Setting::set('site_description', 'Your file resources for Wolfenstein: Enemy Territory', 'string', 'general');
        Setting::set('max_upload_size', '500', 'integer', 'uploads');
        Setting::set('auto_approve_trusted', 'false', 'boolean', 'uploads');
        Setting::set('clamav_enabled', 'false', 'boolean', 'security');
        Setting::set('discord_webhook', '', 'string', 'notifications');
        Setting::set('featured_label', 'Map of the Week', 'string', 'content');
    }
}
