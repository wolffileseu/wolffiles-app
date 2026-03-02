<?php

namespace App\Services;

use App\Models\File;
use App\Services\DiscordWebhookService;
use App\Services\TelegramNotificationService;
use App\Models\FileScreenshot;
use App\Jobs\AnalyzeUploadedFile;
use App\Jobs\ScanFileForViruses;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class FileUploadService
{
    public function __construct(
        protected FileAnalyzerService $analyzer
    ) {}

    /**
     * Handle a new file upload
     */
    public function upload(
        UploadedFile $uploadedFile,
        array $data,
        int $userId,
        array $screenshots = []
    ): File {
        // Store file to S3
        $fileName = $uploadedFile->getClientOriginalName();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $s3Path = $this->generateS3Path($fileName, $data['category_id'] ?? null);

        Storage::disk('s3')->putFileAs(
            dirname($s3Path),
            $uploadedFile,
            basename($s3Path)
        );

        // Create file record
        $file = File::create([
            'user_id' => $userId,
            'category_id' => $data['category_id'],
            'title' => $data['title'] ?? pathinfo($fileName, PATHINFO_FILENAME),
            'description' => $data['description'] ?? null,
            'file_path' => $s3Path,
            'file_name' => $fileName,
            'file_extension' => $extension,
            'file_size' => $uploadedFile->getSize(),
            'file_hash' => hash_file('sha256', $uploadedFile->getRealPath()),
            'mime_type' => $uploadedFile->getMimeType(),
            'game' => $data['game'] ?? null,
            'version' => $data['version'] ?? null,
            'original_author' => $data['original_author'] ?? null,
            'status' => 'pending',
        ]);

        // Handle manually uploaded screenshots
        foreach ($screenshots as $index => $screenshot) {
            if ($screenshot instanceof UploadedFile) {
                $this->uploadScreenshot($file, $screenshot, $index);
            }
        }

        // Dispatch background jobs
        AnalyzeUploadedFile::dispatch($file);
        ScanFileForViruses::dispatch($file);

        return $file;
    }

    /**
     * Upload a screenshot for a file
     */
    public function uploadScreenshot(File $file, UploadedFile $image, int $order = 0): FileScreenshot
    {
        $uuid = Str::uuid();
        $ext = strtolower($image->getClientOriginalExtension());
        $basePath = "screenshots/{$file->id}";

        // Store original
        $originalPath = "{$basePath}/{$uuid}.{$ext}";
        Storage::disk('s3')->putFileAs(dirname($originalPath), $image, basename($originalPath));

        // Create thumbnail
        $thumbnailPath = "{$basePath}/thumb_{$uuid}.{$ext}";
        try {
            $thumb = Image::read($image->getRealPath());
            $thumb->cover(400, 225); // 16:9 aspect ratio
            $tempThumb = tempnam(sys_get_temp_dir(), 'thumb');
            $thumb->save($tempThumb);
            Storage::disk('s3')->put($thumbnailPath, file_get_contents($tempThumb));
            unlink($tempThumb);
        } catch (\Exception $e) {
            $thumbnailPath = $originalPath;
        }

        return FileScreenshot::create([
            'file_id' => $file->id,
            'path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
            'source' => 'manual',
            'original_name' => $image->getClientOriginalName(),
            'sort_order' => $order,
            'is_primary' => $order === 0 && $file->screenshots()->count() === 0,
        ]);
    }

    /**
     * Store an extracted image (from PK3 analysis)
     */
    public function storeExtractedImage(File $file, string $localPath, string $originalName, string $source): FileScreenshot
    {
        $uuid = Str::uuid();
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $basePath = "screenshots/{$file->id}";

        // Upload original
        $s3Path = "{$basePath}/{$uuid}.{$ext}";
        Storage::disk('s3')->put($s3Path, file_get_contents($localPath));

        // Create thumbnail
        $thumbPath = "{$basePath}/thumb_{$uuid}.{$ext}";
        try {
            $thumb = Image::read($localPath);
            $thumb->cover(400, 225);
            $tempThumb = tempnam(sys_get_temp_dir(), 'thumb');
            $thumb->save($tempThumb);
            Storage::disk('s3')->put($thumbPath, file_get_contents($tempThumb));
            unlink($tempThumb);
        } catch (\Exception) {
            $thumbPath = $s3Path;
        }

        return FileScreenshot::create([
            'file_id' => $file->id,
            'path' => $s3Path,
            'thumbnail_path' => $thumbPath,
            'source' => $source,
            'original_name' => $originalName,
            'sort_order' => $file->screenshots()->count(),
            'is_primary' => $file->screenshots()->count() === 0,
        ]);
    }

    protected function generateS3Path(string $fileName, ?int $categoryId): string
    {
        $date = now()->format('Y/m');
        $uuid = Str::uuid();
        $safeFileName = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return "files/{$date}/{$uuid}/{$safeFileName}.{$ext}";
    }

    /**
     * Approve a file
     */
    public function approve(File $file, int $reviewerId, ?array $updates = null): File
    {
        $data = [
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'published_at' => now(),
        ];

        if ($updates) {
            $data = array_merge($data, array_intersect_key($updates, array_flip([
                'title', 'description', 'category_id', 'game', 'version', 'original_author',
            ])));
        }

        $file->update($data);

        // Update category file count
        $file->category->increment('files_count');

        // Update user upload count
        $file->user->increment('total_uploads');

        // Notify Discord
        DiscordWebhookService::notifyFileApproved($file);

        // Broadcast to all social media channels
        app(\App\Services\SocialMedia\SocialMediaService::class)->broadcastFileApproved($file);

        // Telegram notification
        app(TelegramNotificationService::class)->notifyFileApproved($file);

        return $file->fresh();
    }

    /**
     * Reject a file
     */
    public function reject(File $file, int $reviewerId, string $reason): File
    {
        $file->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
        return $file->fresh();
    }

    /**
     * Generate a temporary download URL
     */
    public function getDownloadUrl(File $file): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $file->file_path,
            now()->addMinutes(30)
        );
    }
}
