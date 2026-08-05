<?php

namespace App\Models\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'sup_notification_prefs';

    protected $fillable = ['user_id', 'event', 'channel', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    public const EVENTS = [
        'ticket_created'   => 'New ticket',
        'ticket_assigned'  => 'Assigned to me',
        'user_replied'     => 'User replied',
        'ticket_escalated' => 'Overdue / escalated',
        'daily_digest'     => 'Daily digest',
    ];

    public const CHANNELS = [
        'panel'      => 'Admin panel',
        'discord_dm' => 'Discord DM',
        'telegram'   => 'Telegram',
        'mail'       => 'E-Mail',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
