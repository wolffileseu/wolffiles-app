<?php

namespace App\Observers;

use App\Models\File;
use App\Models\Badge;
use Illuminate\Support\Facades\Storage;
use App\Services\OmnibotWaypointService;
use App\Services\EmbeddingService;

class FileObserver
{
    public function updated(File $file): void
    {
        if ($file->isDirty('status') && $file->status === 'approved') {
            $this->checkBadges($file);
            $this->scanForWaypoints($file);
            $this->indexEmbedding($file);
        }
    }

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

        // Remove from Qdrant index
        try {
            app(EmbeddingService::class)->deleteFile($file->id);
        } catch (\Exception $e) {
            \Log::warning("Failed to remove file from Qdrant: {$e->getMessage()}");
        }
    }

    protected function checkBadges(File $file): void
    {
        $user = $file->user;
        if (!$user) return;

        $approvedCount  = $user->files()->where('status', 'approved')->count();
        $totalDownloads = $user->files()->sum('download_count');

        if ($approvedCount === 1) {
            $badge = Badge::where('slug', 'first-upload')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        if ($approvedCount >= 10) {
            $badge = Badge::where('slug', 'prolific-uploader')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        if ($totalDownloads >= 1000) {
            $badge = Badge::where('slug', 'popular-files')->first();
            if ($badge && !$user->badges->contains($badge->id)) {
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
            }
        }

        $user->update(['total_uploads' => $approvedCount]);
    }

    protected function scanForWaypoints(File $file): void
    {
        try {
            $service = app(OmnibotWaypointService::class);
            $result  = $service->scanFile($file);
            if (!empty($result['new'])) {
                \Log::info("Omnibot: Found " . count($result['new']) . " new waypoints in {$file->file_name}");
            }
        } catch (\Exception $e) {
            \Log::warning("Omnibot scan failed: " . $e->getMessage());
        }
    }

    protected function indexEmbedding(File $file): void
    {
        try {
            app(EmbeddingService::class)->indexFile($file);
            \Log::info("Embedding indexed for file {$file->id}");
        } catch (\Exception $e) {
            \Log::warning("Embedding indexing failed: " . $e->getMessage());
        }
    }
}
