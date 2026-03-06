<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TmLanguage extends Model {
    protected $table = 'tm_languages';
    protected $fillable = ['code', 'name', 'flag', 'is_active', 'sort_order'];
    protected $casts = ['is_active' => 'boolean'];

    public function translations() {
        return $this->hasMany(TmTranslation::class, 'language_code', 'code');
    }

    public function getTranslatedCount(): int {
        return $this->translations()->whereNotNull('value')->whereNotIn('value', [''])->count();
    }

    public function getProgressPercent(): int {
        $total = TmKey::where('is_active', true)->count();
        if ($total === 0) return 0;
        return (int) round($this->getTranslatedCount() / $total * 100);
    }
}
