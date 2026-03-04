<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $mistralKey;
    private string $qdrantUrl;
    private string $collection = 'wolffiles';

    public function __construct()
    {
        $this->mistralKey = config('services.mistral.key');
        $this->qdrantUrl  = config('services.qdrant.url', 'http://localhost:6333');
    }

    public function ensureCollection(): void
    {
        $response = Http::get("{$this->qdrantUrl}/collections/{$this->collection}");
        if ($response->status() === 404) {
            Http::put("{$this->qdrantUrl}/collections/{$this->collection}", [
                'vectors' => ['size' => 1024, 'distance' => 'Cosine']
            ]);
            Log::info("Qdrant collection '{$this->collection}' created.");
        }
    }

    public function indexFile(File $file): bool
    {
        try {
            $vector = $this->getEmbedding($this->buildFileText($file));
            if (!$vector) return false;

            Http::put("{$this->qdrantUrl}/collections/{$this->collection}/points", [
                'points' => [[
                    'id'      => $file->id,
                    'vector'  => $vector,
                    'payload' => [
                        'title'        => $file->title,
                        'category'     => $file->category?->name ?? '',
                        'game'         => $file->game ?? '',
                        'url'          => route('files.show', $file),
                        'download_url' => route('files.download', $file),
                        'file_size'    => $file->file_size ?? '',
                        'downloads'    => $file->download_count ?? 0,
                        'author'       => $file->original_author ?? '',
                        'description'  => substr($file->description ?? '', 0, 500),
                    ]
                ]]
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Embedding failed for file {$file->id}: {$e->getMessage()}");
            return false;
        }
    }

    public function deleteFile(int $fileId): void
    {
        Http::post("{$this->qdrantUrl}/collections/{$this->collection}/points/delete", [
            'points' => [$fileId]
        ]);
    }

    public function search(string $query, int $limit = 8): array
    {
        $vector = $this->getEmbedding($query);
        if (!$vector) return [];

        $response = Http::post("{$this->qdrantUrl}/collections/{$this->collection}/points/search", [
            'vector'       => $vector,
            'limit'        => $limit,
            'with_payload' => true,
        ]);

        if (!$response->ok()) return [];

        return collect($response->json('result', []))
            ->map(fn($hit) => array_merge($hit['payload'], ['score' => round($hit['score'], 3)]))
            ->toArray();
    }

    private function buildFileText(File $file): string
    {
        return implode(' ', array_filter([
            $file->title,
            $file->file_name,
            $file->map_name,
            $file->category?->name,
            $file->game ?? '',
            $file->mod_compatibility ?? '',
            $file->original_author ?? '',
            $file->description ?? '',
            is_string($file->readme_content) ? $file->readme_content : '',
            implode(' ', $file->tags?->pluck('name')->toArray() ?? []),
        ]));
    }

    private function getEmbedding(string $text): ?array
    {
        $response = Http::withToken($this->mistralKey)
            ->post('https://api.mistral.ai/v1/embeddings', [
                'model' => 'mistral-embed',
                'input' => [$text],
            ]);

        if (!$response->ok()) {
            Log::error("Mistral embedding error: " . $response->body());
            return null;
        }

        return $response->json('data.0.embedding');
    }
}
