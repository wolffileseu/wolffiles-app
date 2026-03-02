<?php

namespace App\Services;

use App\Models\User;
use App\Models\Badge;
use Illuminate\Support\Facades\Log;

class AchievementService
{
    /**
     * Check and award all applicable badges to a user.
     */
    public static function checkAll(User $user): array
    {
        $awarded = [];

        foreach (self::getCheckers() as $checker) {
            $badge = $checker($user);
            if ($badge) {
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    /**
     * Award a badge to a user (if not already earned).
     */
    public static function award(User $user, string $badgeSlug): ?Badge
    {
        $badge = Badge::where('slug', $badgeSlug)->first();
        if (!$badge) return null;

        // Check if already earned
        if ($user->badges()->where('badge_id', $badge->id)->exists()) {
            return null;
        }

        $user->badges()->attach($badge->id, ['earned_at' => now()]);

        // Notify user
        $user->notify(new \App\Notifications\BadgeEarned($badge));

        return $badge;
    }

    /**
     * Get all achievement checkers.
     */
    private static function getCheckers(): array
    {
        return [
            // First Upload
            function (User $user) {
                $uploads = $user->files()->where('status', 'approved')->count();
                if ($uploads >= 1) return self::award($user, 'first-upload');
                return null;
            },

            // 10 Uploads
            function (User $user) {
                $uploads = $user->files()->where('status', 'approved')->count();
                if ($uploads >= 10) return self::award($user, 'prolific-uploader');
                return null;
            },

            // 50 Uploads
            function (User $user) {
                $uploads = $user->files()->where('status', 'approved')->count();
                if ($uploads >= 50) return self::award($user, 'master-uploader');
                return null;
            },

            // First Comment
            function (User $user) {
                $comments = $user->comments()->count();
                if ($comments >= 1) return self::award($user, 'first-comment');
                return null;
            },

            // 100 Comments
            function (User $user) {
                $comments = $user->comments()->count();
                if ($comments >= 100) return self::award($user, 'chatterbox');
                return null;
            },

            // File hits 100 downloads
            function (User $user) {
                $popular = $user->files()->where('download_count', '>=', 100)->exists();
                if ($popular) return self::award($user, 'popular-file');
                return null;
            },

            // File hits 1000 downloads
            function (User $user) {
                $viral = $user->files()->where('download_count', '>=', 1000)->exists();
                if ($viral) return self::award($user, 'viral-file');
                return null;
            },

            // File gets 5-star average (min 5 ratings)
            function (User $user) {
                $perfect = $user->files()
                    ->where('average_rating', '>=', 4.8)
                    ->where('rating_count', '>=', 5)
                    ->exists();
                if ($perfect) return self::award($user, 'five-star');
                return null;
            },

            // 1 year member
            function (User $user) {
                if ($user->created_at->diffInYears(now()) >= 1) {
                    return self::award($user, 'veteran');
                }
                return null;
            },
        ];
    }

    /**
     * Get predefined badge definitions for seeding.
     */
    public static function getBadgeDefinitions(): array
    {
        return [
            ['name' => 'First Upload', 'slug' => 'first-upload', 'description' => 'Upload your first file', 'icon' => '📦'],
            ['name' => 'Prolific Uploader', 'slug' => 'prolific-uploader', 'description' => 'Upload 10 files', 'icon' => '📚'],
            ['name' => 'Master Uploader', 'slug' => 'master-uploader', 'description' => 'Upload 50 files', 'icon' => '🏅'],
            ['name' => 'First Comment', 'slug' => 'first-comment', 'description' => 'Write your first comment', 'icon' => '💬'],
            ['name' => 'Chatterbox', 'slug' => 'chatterbox', 'description' => 'Write 100 comments', 'icon' => '🗣️'],
            ['name' => 'Popular File', 'slug' => 'popular-file', 'description' => 'Have a file reach 100 downloads', 'icon' => '🔥'],
            ['name' => 'Viral File', 'slug' => 'viral-file', 'description' => 'Have a file reach 1000 downloads', 'icon' => '🚀'],
            ['name' => 'Five Star', 'slug' => 'five-star', 'description' => 'Get a perfect 5-star rating', 'icon' => '⭐'],
            ['name' => 'Veteran', 'slug' => 'veteran', 'description' => 'Be a member for 1 year', 'icon' => '🎖️'],
        ];
    }
}
