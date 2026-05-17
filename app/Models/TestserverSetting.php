<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TestserverSetting extends Model
{
    protected $table = 'testserver_settings';

    protected $fillable = [
        'feature_enabled',
        'public_visible',
        'require_login',
        'turnstile_enabled',
        'turnstile_site_key',
        'turnstile_secret_key',
        'rate_limit_enabled',
        'anon_max_per_hour',
        'anon_max_per_day',
        'user_max_per_hour',
        'user_max_per_day',
        'cooldown_minutes',
        'default_session_minutes',
        'public_intro_text',
        'public_rules_text',
    ];

    protected $casts = [
        'feature_enabled'         => 'boolean',
        'public_visible'          => 'boolean',
        'require_login'           => 'boolean',
        'turnstile_enabled'       => 'boolean',
        'rate_limit_enabled'      => 'boolean',
        'anon_max_per_hour'       => 'integer',
        'anon_max_per_day'        => 'integer',
        'user_max_per_hour'       => 'integer',
        'user_max_per_day'        => 'integer',
        'cooldown_minutes'        => 'integer',
        'default_session_minutes' => 'integer',
    ];

    /**
     * Singleton-Accessor: TestserverSetting::current()
     * Wird gecached für 5 Min, automatisch invalidiert beim Save.
     */
    public static function current(): self
    {
        return Cache::remember('testserver_settings', now()->addMinutes(5), function () {
            return static::query()->firstOrCreate(['id' => 1]);
        });
    }

    /** Cache busten wenn jemand speichert */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('testserver_settings'));
        static::deleted(fn () => Cache::forget('testserver_settings'));
    }
}
