<?php

namespace App\Services;

use App\Models\User;
use App\Models\File;
use App\Services\SocialMedia\SocialMediaService;

class AutoApproveService
{
    /**
     * Check if a user is trusted and should get auto-approved uploads.
     * A user is trusted if:
     * - They have the 'trusted_uploader' flag set
     * - OR they have 10+ approved uploads with no rejections in last 30 days
     * - OR they are a moderator/admin
     */
    public static function shouldAutoApprove(User $user): bool
    {
        // Admins and moderators always auto-approve
        if ($user->isModerator()) {
            return true;
        }

        // Explicitly trusted users
        if ($user->is_trusted_uploader) {
            return true;
        }

        // Auto-trust: 10+ approved files, no recent rejections
        $approvedCount = File::where('user_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $recentRejections = File::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return $approvedCount >= 10 && $recentRejections === 0;
    }

    /**
     * Process a newly uploaded file.
     * Auto-approves if user is trusted, otherwise sets to pending.
     */
    public static function processUpload(File $file): void
    {
        if ($file->user && self::shouldAutoApprove($file->user)) {
            $file->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'published_at' => now(),
            ]);

            // Notify Discord
            DiscordWebhookService::notifyFileApproved($file);
            app(SocialMediaService::class)->broadcastFileApproved($file);
        }
    }
}
