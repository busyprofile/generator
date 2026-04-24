<?php

namespace App\Models;

use App\Enums\DisputeStatus;
use App\Enums\DisputeCancelReason;
use App\Observers\DisputeObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $receipt
 * @property int $order_id
 * @property int $trader_id
 * @property DisputeStatus $status
 * @property string $reason
 * @property DisputeCancelReason $cancel_reason
 * @property Order $order
 * @property User $trader
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[ObservedBy([DisputeObserver::class])]
class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt',
        'order_id',
        'trader_id',
        'status',
        'reason',
        'cancel_reason',
    ];

    protected $casts = [
        'status' => DisputeStatus::class,
        'cancel_reason' => DisputeCancelReason::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
