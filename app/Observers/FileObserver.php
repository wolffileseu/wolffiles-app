<?php

namespace App\Observers;

use App\Models\File;
use App\Models\Badge;
use Illuminate\Support\Facades\Storage;
use App\Services\OmnibotWaypointService;

class FileObserver
{
    /**
     * When a file is approved, check for badge assignments
     */
    public function updated(File $file): void
    {
        if ($file->isDirty('status') && $file->status === 'approved') {
            $this->checkBadges($file);
            $this->scanForWaypoints($file);
        }
    }

    /**
     * When a file is being deleted, clean up S3
     */
    public function deleting(File $file): void
    {
        // Delete the file from S3
        if ($file->file_path) {
            try {
                Storage::disk('s3')->delete($file->file_path);
            } catch (\Exception $e) {
                \Log::warning("Failed to delete S3 file: {$file->file_path} - {$e->getMessage()}");
            }
        }

        // Delete all screenshots from S3
        foreach ($file->screenshots as $screenshot) {
            if ($screenshot->path) {
                try {
                    Storage::disk('s3')->delete($screenshot->path);
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete S3 screenshot: {$screenshot->path}");
                }
                if ($screenshot->thumbnail_path) {
                    try {
                        Storage::disk('s3')->delete($screenshot->thumbnail_path);
                    } catch (\Exception $e) {}
                }
            }
        }

        // Delete screenshot records
        $file->screenshots()->delete();

        // Delete related records
        $file->comments()->delete();
        $file->ratings()->delete();
        $file->favorites()->delete();
        $file->downloads()->delete();
        $file->versions()->delete();
    }

    /**
     * Check and assign badges to uploader
     */
    protected function checkBadges(File $file): void
    {
        $user = $file->user;
        if (!$user) return;

        $approvedCount = $user->files()->where('status', 'approved')->count();
        $totalDownloads = $user->files()->sum('download_count');

        // First Upload badge
        if ($approvedCount === 1) {
            $badge = Badge::where('slug', 'first-upload')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        // Prolific Uploader (10+)
        if ($approvedCount >= 10) {
            $badge = Badge::where('slug', 'prolific-uploader')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        // Popular Files (1000+ total downloads)
        if ($totalDownloads >= 1000) {
            $badge = Badge::where('slug', 'popular-files')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        // Update user total uploads count
        $user->update(['total_uploads' => $approvedCount]);
    }

    /**
     * Scan approved file for Omni-Bot waypoints
     */
    protected function scanForWaypoints(File $file): void
    {
        try {
            $service = app(OmnibotWaypointService::class);
            $result = $service->scanFile($file);
            if (!empty($result['new'])) {
                \Log::info("Omnibot: Found " . count($result['new']) . " new waypoints in {$file->file_name}");
            }
        } catch (\Exception $e) {
            \Log::warning("Omnibot scan failed: " . $e->getMessage());
        }
    }
}
