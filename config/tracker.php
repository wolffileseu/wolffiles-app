<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enhanced Tracker — UDP Listener
    |--------------------------------------------------------------------------
    |
    | Settings for the `php artisan tracker:listen` daemon which receives
    | sv_tracker2 UDP packets from ET servers.
    |
    */

    'listen' => [
        // Bind address. 0.0.0.0 to accept from any interface, 127.0.0.1 for localhost-only.
        'host' => env('TRACKER_LISTEN_HOST', '0.0.0.0'),

        // UDP port. Must match sv_tracker2 value in ET server configs.
        'port' => env('TRACKER_LISTEN_PORT', 4444),

        // Max packet size to read. ET tracker packets are well under this.
        'buffer_size' => 65535,

        // How long to wait for a packet before looping (ms). Lower = more responsive
        // but more CPU. 500ms is a good balance for a dedicated daemon.
        'socket_timeout_ms' => 500,

        // Soft-restart: daemon exits after this many seconds so supervisord
        // can give it a fresh PHP process (avoids memory creep).
        // 0 to disable. Default: 1 hour.
        'soft_restart_after_seconds' => 3600,

        // Log every received packet to laravel.log? Very noisy in production;
        // set false once things work. Errors are always logged regardless.
        'verbose_logging' => env('TRACKER_LISTEN_VERBOSE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth / rate limiting
    |--------------------------------------------------------------------------
    */

    'auth' => [
        // IPs that are flat-out rejected. Use when someone's abusing.
        'blocked_ips' => [],

        // If set and non-empty, ONLY accept from these IPs.
        // Useful during initial rollout while we verify the system.
        'allowed_ips' => [],

        // Maximum packets per second per source IP. 0 to disable.
        // 4 servers * ~1 packet/sec avg * safety margin = 50 is plenty.
        'rate_limit_per_ip_per_second' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Server auto-discovery
    |--------------------------------------------------------------------------
    |
    | When a packet arrives from a src_ip we haven't seen before, we try to
    | associate it with an existing tracker_servers row.
    |
    */

    'auto_discover' => [
        // Create new tracker_servers rows for unknown IPs?
        // false = only process packets from IPs we already know from Server Poller.
        'enabled' => true,

        // Mark newly-seen servers as enhanced immediately, or require admin approval?
        'auto_enable_enhanced' => true,  // user chose "Erstmal automatisch, später manuell"
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing
    |--------------------------------------------------------------------------
    */

    'processing' => [
        // Queue connection for ProcessTrackerEventJob. 'database' matches the
        // rest of wolffiles-app.
        'queue_connection' => env('TRACKER_QUEUE_CONNECTION', 'database'),

        // Queue name — isolate tracker jobs from other app queues.
        'queue_name' => env('TRACKER_QUEUE_NAME', 'tracker'),

        // If a packet fails to parse, retry N times before giving up.
        'job_retries' => 3,
    ],

];
