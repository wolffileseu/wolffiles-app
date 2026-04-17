<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerRawEvent extends Model
{
    protected $table = 'tracker_raw_events';

    /** This table doesn't use created_at/updated_at — received_at is the truth. */
    public $timestamps = false;

    protected $fillable = [
        'received_at',
        'source_ip',
        'source_port',
        'cmd',
        'size_bytes',
        'payload',
        'processed',
        'processed_at',
        'processing_error',
        'server_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'processed' => 'boolean',
        'size_bytes' => 'integer',
        'source_port' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(TrackerServer::class, 'server_id');
    }
}
