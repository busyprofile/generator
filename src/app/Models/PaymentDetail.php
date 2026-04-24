<?php

namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\MoneyCast;
use App\Enums\DetailType;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $detail
 * @property DetailType $detail_type
 * @property string $initials
 * @property boolean $is_active
 * @property boolean $is_external
 * @property Money $daily_limit
 * @property Money $current_daily_limit
 * @property Money $max_pending_orders_quantity
 * @property Money|null $min_order_amount
 * @property Money|null $max_order_amount
 * @property float|null $unique_amount_percentage Процент отклонения для проверки уникальности суммы заказа
 * @property int|null $unique_amount_seconds Интервал времени в секундах для проверки уникальности суммы заказа
 * @property int|null $order_interval_minutes
 * @property Currency $currency
 * @property int $user_id
 * @property int $user_device_id
 * @property User $user
 * @property UserDevice $userDevice
 * @property Collection<int, PaymentGateway> $paymentGateways
 * @property Collection<int, Order> $orders
 * @property Carbon $archived_at
 * @property Carbon $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PaymentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'detail',
        'detail_type',
        'initials',
        'is_active',
        'is_external',
        'daily_limit',
        'current_daily_limit',
        'max_pending_orders_quantity',
        'min_order_amount',
        'max_order_amount',
        'unique_amount_percentage',
        'unique_amount_seconds',
        'order_interval_minutes',
        'currency',
        'user_id',
        'user_device_id',
        'archived_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_external' => 'boolean',
        'daily_limit' => MoneyCast::class,
        'current_daily_limit' => MoneyCast::class,
        'min_order_amount' => MoneyCast::class,
        'max_order_amount' => MoneyCast::class,
        'unique_amount_percentage' => 'float',
        'unique_amount_seconds' => 'integer',
        'currency' => CurrencyCast::class,
        'detail_type' => DetailType::class,
        'archived_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function paymentGateways(): BelongsToMany
    {
        return $this->belongsToMany(PaymentGateway::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
