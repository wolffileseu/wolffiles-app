<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TmTranslation extends Model {
    protected $table = 'tm_translations';
    protected $fillable = ['tm_key_id', 'language_code', 'value', 'is_ai_generated', 'translated_at'];
    protected $casts = ['is_ai_generated' => 'boolean', 'translated_at' => 'datetime'];

    public function tmKey() {
        return $this->belongsTo(TmKey::class);
    }
}
