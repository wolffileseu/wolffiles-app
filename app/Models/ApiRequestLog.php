<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'api_request_logs';
    public $timestamps = false;
    protected $fillable = [
        'endpoint', 'method', 'ip_hash', 'user_agent',
        'client_type', 'response_ms', 'status_code',
        'query_string', 'created_at',
    ];
}
