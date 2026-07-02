<?php

namespace App\Services\Wiki;

use App\Models\WikiMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WikiMediaService
{
    public const DISK = 's3';
    // MUSS zum Parser passen: WikitextParser::parseFiles() => Storage::disk('s3')->url('wiki/'.$name)
    public const DIR = 'wiki';
    public const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'gif', 'webp']; // svg bewusst raus (XSS)
    public const MAX_BYTES = 8 * 1024 * 1024; // 8 MB

    /**
     * Lädt ein Bild in den Wiki-Media-Pool (S3 + DB-Row).
     * ->filename ist exakt das, was in [[File:...]] kommt.
     */
    public function store(UploadedFile $file, int $userId, ?int $articleId = null): WikiMedia
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: ($file->guessExtension() ?: ''));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \InvalidArgumentException("Dateityp .{$ext} nicht erlaubt. Erlaubt: " . implode(', ', self::ALLOWED_EXT));
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Datei zu gross (max ' . (self::MAX_BYTES / 1048576) . ' MB).');
        }

        $base     = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'bild';
        $filename = $this->uniqueFilename($base, $ext);
        $key      = self::DIR . '/' . $filename;

        Storage::disk(self::DISK)->putFileAs(self::DIR, $file, $filename, 'public');

        // Hetzner Object Storage: 'public' im putFileAs reicht nicht zuverlaessig.
        // Expliziter PutObjectAcl-Call via setVisibility, sonst 403 vom CDN.
        try {
            Storage::disk(self::DISK)->setVisibility($key, 'public');
        } catch (\Throwable $e) {
            \Log::warning('WikiMediaService: setVisibility failed for '.$key.': '.$e->getMessage());
        }

        return WikiMedia::create([
            'wiki_article_id' => $articleId,
            'user_id'         => $userId,
            'path'            => $key,       // voller S3-Key: wiki/foo.png
            'filename'        => $filename,  // Referenz im Wikitext: [[File:foo.png]]
            'mime_type'       => $file->getClientMimeType() ?: ('image/' . ($ext === 'jpg' ? 'jpeg' : $ext)),
            'file_size'       => $file->getSize(),
            'type'            => 'image',
        ]);
    }

    /** Eindeutiger, slugifizierter Dateiname im wiki/-Pool. Kollision => -2, -3 ... */
    public function uniqueFilename(string $base, string $ext): string
    {
        $candidate = "{$base}.{$ext}";
        $i = 1;
        while (
            WikiMedia::where('filename', $candidate)->exists()
            || Storage::disk(self::DISK)->exists(self::DIR . '/' . $candidate)
        ) {
            $i++;
            $candidate = "{$base}-{$i}.{$ext}";
        }
        return $candidate;
    }

    /** Fertiges Wikitext-Snippet fuer ein gespeichertes Bild. */
    public function wikitextFor(WikiMedia $m, ?string $caption = null, bool $thumb = true, ?int $width = null): string
    {
        $opts = [];
        if ($thumb)  { $opts[] = 'thumb'; }
        if ($width)  { $opts[] = $width . 'px'; }
        $opts[] = $caption ?? '';
        return '[[File:' . $m->filename . '|' . implode('|', $opts) . ']]';
    }
}
