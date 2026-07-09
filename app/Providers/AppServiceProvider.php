<?php

namespace App\Providers;

use App\Models\BugTracker\Task;
use App\Observers\BugTracker\TaskObserver;
use App\Observers\BugTracker\CommentObserver;
use App\Models\Category;
use App\Observers\CategoryObserver;
use App\Models\Tracker\TrackerBan;
use App\Observers\TrackerBanObserver;
use App\Models\Tracker\TrackerClaim;
use App\Observers\TrackerClaimObserver;
use App\Policies\FilePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Models\Donation;
use App\Policies\DonationPolicy;
use App\Models\Post;
use App\Policies\PostPolicy;
use App\Models\Tag;
use App\Policies\TagPolicy;
use App\Models\Page;
use App\Policies\PagePolicy;
use App\Models\Poll;
use App\Policies\PollPolicy;
use App\Models\Report;
use App\Policies\ReportPolicy;
use App\Models\Badge;
use App\Policies\BadgePolicy;
use App\Models\Menu;
use App\Policies\MenuPolicy;
use App\Models\PartnerLink;
use App\Policies\PartnerLinkPolicy;
use App\Policies\UserPolicy;
use App\Models\LuaScript;
use App\Policies\LuaScriptPolicy;
use App\Models\Tutorial;
use App\Policies\TutorialPolicy;
use App\Models\TutorialCategory;
use App\Policies\TutorialCategoryPolicy;
use App\Policies\WikiArticlePolicy;
use App\Models\WikiCategory;
use App\Policies\WikiCategoryPolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;
use App\Http\View\Composers\DmComposer;
use App\Models\File;
use App\Observers\FileObserver;
use App\Observers\WikiArticleObserver;
use App\Observers\TelegramObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\WikiArticle;
use App\Models\Comment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Pages\Page as FilamentPage;
use Filament\Widgets\Widget as FilamentWidget;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Shield 4 changed its default permission-key format (pascal case + ':' separator).
        // Production stores permissions in the legacy Shield 3.x format and they MUST NOT
        // be renamed, so we override the key builder to reproduce the 3.x naming exactly:
        //   - Resources: "{snake_affix}_{snake('::')_resourceSubject}" e.g. view_any_tracker::server
        //   - Pages:     "page_{ClassBasename}"                        e.g. page_FastDlMonitor
        //   - Widgets:   "widget_{ClassBasename}"                      e.g. widget_StatsOverview
        // The resource subject in Shield 3.x was derived from the resource's path relative
        // to the Resources\ namespace (backslashes stripped, "Resource" suffix removed) —
        // NOT from the model. This matters where they diverge, e.g.
        //   BugTracker\TaskResource            => bug::tracker::task   (not "task")
        //   PlayerReportResource (TrackerPlayerReport) => player::report (not "tracker::player::report")
        // This governs both permission generation and the runtime HasPageShield/
        // HasWidgetShield access checks, keeping existing DB assignments valid.
        FilamentShield::buildPermissionKeyUsing(
            function (string $entity, string $affix, string $subject, string $case, string $separator): string {
                if (is_subclass_of($entity, FilamentPage::class)) {
                    return 'page_' . $subject;
                }

                if (is_subclass_of($entity, FilamentWidget::class)) {
                    return 'widget_' . $subject;
                }

                // Shield's own RoleResource lived at Resources\RoleResource in v3 (subject
                // "role") but moved to Resources\Roles\RoleResource in v4. Preserve the
                // legacy "role" subject so existing view_any_role/create_role/... stay valid.
                if (str_ends_with($entity, '\\RoleResource') && str_contains($entity, 'FilamentShield')) {
                    return Str::snake($affix) . '_role';
                }

                // Resource: reproduce the Shield 3.x resource subject from the FQCN.
                $resourceSubject = Str::of($entity)
                    ->afterLast('Resources\\')
                    ->replace('\\', '')
                    ->beforeLast('Resource')
                    ->toString();

                return Str::snake($affix) . '_' . Str::snake($resourceSubject, '::');
            }
        );

        Task::observe(TaskObserver::class);
        \App\Models\BugTracker\Comment::observe(CommentObserver::class);
        // Telegram notifications
        Comment::observe(TelegramObserver::class);
        User::observe(TelegramObserver::class);
        File::observe(TelegramObserver::class);

        // Uploader API tree cache invalidation
        Category::observe(CategoryObserver::class);
        TrackerBan::observe(TrackerBanObserver::class);
        TrackerClaim::observe(TrackerClaimObserver::class);

        // Resource Policies
        Gate::policy(File::class, FilePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Donation::class, DonationPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(Poll::class, PollPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Badge::class, BadgePolicy::class);
        Gate::policy(Menu::class, MenuPolicy::class);
        Gate::policy(PartnerLink::class, PartnerLinkPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(LuaScript::class, LuaScriptPolicy::class);
        Gate::policy(Tutorial::class, TutorialPolicy::class);
        Gate::policy(TutorialCategory::class, TutorialCategoryPolicy::class);
        Gate::policy(WikiArticle::class, WikiArticlePolicy::class);
        Gate::policy(WikiCategory::class, WikiCategoryPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // Super admin can do everything
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Register observers
        File::observe(FileObserver::class);
        WikiArticle::observe(WikiArticleObserver::class);

        // PM (Direct Messages) view composer: provides $dmUnreadCount in layouts
        View::composer(
            ["components.layouts.app", "components.dm-bell"],
            DmComposer::class
        );
}
    /**
     * Auto-create permissions for all Filament resources.
     * Run once on boot, cached for 24h.
     */

}
