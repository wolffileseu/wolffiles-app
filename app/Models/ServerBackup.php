<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerBackup extends Model
{
    protected $fillable = [
        'order_id', 'name', 'filename', 'file_size', 's3_path',
        'pterodactyl_backup_id', 'type', 'status', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServerOrder::class, 'order_id');
    }

    public function getFileSizeFormatted(): string
    {
        if ($this->file_size < 1024 * 1024) {
            return round($this->file_size / 1024, 1) . ' KB';
        }
        return round($this->file_size / (1024 * 1024), 1) . ' MB';
    }
}
