<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property float $amount
 * @property string $status
 * @property string|null $period
 * @property \Carbon\Carbon|null $period_end
 */
class ServerInvoice extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'amount', 'currency', 'period',
        'period_start', 'period_end', 'status', 'payment_method',
        'payment_transaction_id', 'payment_details', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServerOrder::class, 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getInvoiceNumber(): string
    {
        return 'WF-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
