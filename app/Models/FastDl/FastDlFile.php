<?php

namespace App\Models\FastDl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FastDlFile extends Model
{
    protected $table = 'fastdl_files';

    protected $fillable = [
        'directory_id', 'filename', 's3_path', 'file_size', 'checksum',
        'source', 'wolffiles_file_id', 'is_active', 'download_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(FastDlDirectory::class, 'directory_id');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
