<?php

namespace App\Models\Tracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackerServerSetting extends Model
{
    protected $table = 'tracker_server_settings';
    public $timestamps = false;

    protected $fillable = ['server_id', 'key', 'value', 'updated_at'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime'];
    }

    public function server(): BelongsTo { return $this->belongsTo(TrackerServer::class, 'server_id'); }
}
