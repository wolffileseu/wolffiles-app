<?php

namespace App\Services;

use ReflectionClass;
use InvalidArgumentException;
use Exception;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling AWS S3 multipart uploads via presigned URLs.
 * Browser uploads file chunks directly to S3, bypassing the application server.
 */
class MultipartUploadService
{
    /**
     * Get the underlying S3Client from the s3 disk.
     */
    private function client(): S3Client
    {
        $disk = Storage::disk('s3');
        $adapter = $disk->getAdapter();

        // Reflection to access private $client property
        $reflection = new ReflectionClass($adapter);
        $prop = $reflection->getProperty('client');
        $prop->setAccessible(true);

        return $prop->getValue($adapter);
    }

    private function bucket(): string
    {
        return config('filesystems.disks.s3.bucket');
    }

    /**
     * Allowed upload targets and their S3 path prefixes.
     */
    public const TARGETS = [
        'files' => 'files',
        'demos' => 'demos',
        'fastdl' => 'fastdl/uploads',
    ];

    /**
     * Generate a unique S3 key for the upload.
     */
    public function generateKey(string $target, string $filename): string
    {
        if (!isset(self::TARGETS[$target])) {
            throw new InvalidArgumentException("Invalid upload target: {$target}");
        }

        $prefix = self::TARGETS[$target];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $ext = $ext ? '.' . strtolower($ext) : '';
        $datePath = date('Y/m');
        $uuid = Str::uuid()->toString();

        return "{$prefix}/{$datePath}/{$uuid}{$ext}";
    }

    /**
     * Initialize a new multipart upload.
     * Returns: ['uploadId' => string, 'key' => string]
     */
    public function initiate(string $key, ?string $contentType = null): array
    {
        $args = [
            'Bucket' => $this->bucket(),
            'Key' => $key,
        ];

        if ($contentType) {
            $args['ContentType'] = $contentType;
        }

        $result = $this->client()->createMultipartUpload($args);

        return [
            'uploadId' => $result['UploadId'],
            'key' => $key,
        ];
    }

    /**
     * Get a presigned URL for uploading a single part.
     * URL is valid for 2 hours (long enough for slow uploads).
     */
    public function signPart(string $uploadId, string $key, int $partNumber): string
    {
        $client = $this->client();

        $command = $client->getCommand('UploadPart', [
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'PartNumber' => $partNumber,
        ]);

        $request = $client->createPresignedRequest($command, '+2 hours');

        return (string) $request->getUri();
    }

    /**
     * Complete the multipart upload after all parts are uploaded.
     * $parts is array of [['PartNumber' => 1, 'ETag' => '"..."'], ...]
     * Returns the public URL of the completed object.
     */
    public function complete(string $uploadId, string $key, array $parts): string
    {
        // Sort parts by PartNumber - S3 requires this
        usort($parts, fn($a, $b) => $a['PartNumber'] <=> $b['PartNumber']);

        $result = $this->client()->completeMultipartUpload([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'UploadId' => $uploadId,
            'MultipartUpload' => ['Parts' => $parts],
        ]);

        return $result['Location'] ?? Storage::disk('s3')->url($key);
    }

    /**
     * Abort an incomplete multipart upload.
     * IMPORTANT: Without abort, S3 keeps the parts and charges for storage.
     */
    public function abort(string $uploadId, string $key): void
    {
        try {
            $this->client()->abortMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $key,
                'UploadId' => $uploadId,
            ]);
        } catch (Exception $e) {
            Log::warning('Multipart abort failed', [
                'uploadId' => $uploadId,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * List incomplete multipart uploads (for cleanup).
     */
    public function listIncomplete(): array
    {
        $result = $this->client()->listMultipartUploads([
            'Bucket' => $this->bucket(),
        ]);

        return $result['Uploads'] ?? [];
    }
}
