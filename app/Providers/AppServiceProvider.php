<?php

namespace App\Providers;

use App\Models\File;
use App\Observers\FileObserver;
use App\Observers\TelegramObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Telegram notifications
        Comment::observe(TelegramObserver::class);
        User::observe(TelegramObserver::class);
        File::observe(TelegramObserver::class);

        // Resource Policies
        Gate::policy(\App\Models\File::class, \App\Policies\FilePolicy::class);
        Gate::policy(\App\Models\Category::class, \App\Policies\CategoryPolicy::class);
        Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
        Gate::policy(\App\Models\Donation::class, \App\Policies\DonationPolicy::class);
        Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
        Gate::policy(\App\Models\Tag::class, \App\Policies\TagPolicy::class);
        Gate::policy(\App\Models\Page::class, \App\Policies\PagePolicy::class);
        Gate::policy(\App\Models\Poll::class, \App\Policies\PollPolicy::class);
        Gate::policy(\App\Models\Report::class, \App\Policies\ReportPolicy::class);
        Gate::policy(\App\Models\Badge::class, \App\Policies\BadgePolicy::class);
        Gate::policy(\App\Models\Menu::class, \App\Policies\MenuPolicy::class);
        Gate::policy(\App\Models\PartnerLink::class, \App\Policies\PartnerLinkPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\LuaScript::class, \App\Policies\LuaScriptPolicy::class);
        Gate::policy(\App\Models\Tutorial::class, \App\Policies\TutorialPolicy::class);
        Gate::policy(\App\Models\TutorialCategory::class, \App\Policies\TutorialCategoryPolicy::class);
        Gate::policy(\App\Models\WikiArticle::class, \App\Policies\WikiArticlePolicy::class);
        Gate::policy(\App\Models\WikiCategory::class, \App\Policies\WikiCategoryPolicy::class);
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);

        // Super admin can do everything
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Register observers
        File::observe(FileObserver::class);
    }
    /**
     * Auto-create permissions for all Filament resources.
     * Run once on boot, cached for 24h.
     */

}
