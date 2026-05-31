<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClanManager extends Model
{
    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';

    protected $fillable = ['clan_id','user_id','role','invited_by_user_id'];

    public function clan(): BelongsTo { return $this->belongsTo(Clan::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function invitedBy(): BelongsTo { return $this->belongsTo(User::class, 'invited_by_user_id'); }

    public function canEditContent(): bool { return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_EDITOR]); }
    public function canManage(): bool { return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]); }
    public function isOwner(): bool { return $this->role === self::ROLE_OWNER; }
}
