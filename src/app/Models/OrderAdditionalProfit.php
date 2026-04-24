<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Money\Money;

/**
 * @property int $id
 * @property int $order_id
 * @property int $team_leader_id
 * @property float $commission_rate
 * @property Money $profit_amount
 * @property Order $order
 * @property User $teamLeader
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrderAdditionalProfit extends Model
{
    use HasFactory;

    protected $table = 'order_additional_profits';

    protected $fillable = [
        'order_id',
        'team_leader_id',
        'commission_rate',
        'profit_amount',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'profit_amount' => BaseCurrencyMoneyCast::class,
            'source' => 'string',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }
} 