<?php

namespace App\Models\FastDl;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FastDlClanFile extends Model
{
    protected $table = 'fastdl_clan_files';

    protected $fillable = [
        'clan_id', 'directory', 'filename', 's3_path', 'file_size', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    public function clan(): BelongsTo
    {
        return $this->belongsTo(FastDlClan::class, 'clan_id');
    }
}
