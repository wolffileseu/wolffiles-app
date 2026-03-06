<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TmKey extends Model {
    protected $table = 'tm_keys';
    protected $fillable = ['key', 'en', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function translations() {
        return $this->hasMany(TmTranslation::class, 'tm_key_id');
    }
}
