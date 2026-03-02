<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuaScriptScreenshot extends Model
{
    protected $fillable = ['lua_script_id', 'path', 'thumbnail_path', 'sort_order', 'is_primary'];
    protected function casts(): array { return ['is_primary' => 'boolean']; }
    public function luaScript(): BelongsTo { return $this->belongsTo(LuaScript::class); }
    public function getUrlAttribute(): string { return \Storage::disk('s3')->url($this->path); }
}
