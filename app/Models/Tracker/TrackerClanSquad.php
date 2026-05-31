<?php
namespace App\Models\Tracker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TrackerClanSquad extends Model
{
    protected $table = 'tracker_clan_squads';
    protected $fillable = ['clan_id','name','description','sort_order'];

    public function clan(): BelongsTo { return $this->belongsTo(TrackerClan::class, 'clan_id'); }
    public function members(): HasMany { return $this->hasMany(TrackerClanMember::class, 'squad_id'); }
}
