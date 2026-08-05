<?php

namespace App\Models\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailFailure extends Model
{
    protected $table = 'sup_email_failures';

    protected $fillable = [
        'from_address', 'subject', 'message_id', 'reason',
        'raw_mime', 'ticket_id', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
