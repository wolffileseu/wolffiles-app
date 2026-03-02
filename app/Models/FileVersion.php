<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileVersion extends Model
{
    protected $fillable = [
        'file_id', 'version', 'file_path', 'file_name',
        'file_size', 'file_hash', 'changelog', 'download_count',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
