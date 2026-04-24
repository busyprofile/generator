<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\CurrencyCast;
use App\Casts\MoneyCast;
use App\Enums\MarketEnum;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Models\Provider;
use App\Models\ProviderTerminal;
use App\Observers\OrderObserver;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $external_id
 * @property Money $base_amount
 * @property Money $amount
 * @property Money $total_profit
 * @property Money $trader_profit
 * @property Money $team_leader_profit
 * @property Money $merchant_profit
 * @property Money $service_profit
 * @property Money $trader_paid_for_order
 * @property Currency $currency
 * @property MarketEnum $market
 * @property Money $conversion_price
 * @property float $trader_commission_rate
 * @property float $team_leader_commission_rate
 * @property float $total_service_commission_rate
 * @property OrderStatus $status
 * @property OrderSubStatus $sub_status
 * @property string $status_name
 * @property string $callback_url
 * @property string $success_url
 * @property string $fail_url
 * @property array $amount_updates_history
 * @property boolean $is_h2h
 * @property int $payment_gateway_id
 * @property int $payment_detail_id
 * @property int|null $provider_id
 * @property int|null $provider_terminal_id
 * @property string|null $provider_order_id
 * @property int $trader_id
 * @property int $team_leader_id
 * @property int $merchant_id
 * @property PaymentGateway $paymentGateway
 * @property PaymentDetail $paymentDetail
 * @property Provider|null $provider
 * @property ProviderTerminal|null $providerTerminal
 * @property User $trader
 * @property User $teamLeader
 * @property Merchant $merchant
 * @property SmsLog $smsLog
 * @property Dispute $dispute
 * @property Carbon $finished_at
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection<int, CallbackLog> $callbackLogs
 * @property Collection<int, OrderAdditionalProfit> $additionalProfits
 */
#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'external_id',
        'base_amount',// TODO remove в далеком будущем
        'amount',
        'total_profit',
        'trader_profit',
        'team_leader_profit',
        'merchant_profit',
        'service_profit',
        'trader_paid_for_order',
        'currency',
        'market',
        'conversion_price',
        'trader_commission_rate',
        'team_leader_commission_rate',
        'total_service_commission_rate',
        'status',
        'sub_status',
        'callback_url',
        'success_url',
        'fail_url',
        'amount_updates_history',
        'is_h2h',
        'payment_gateway_id',
        'payment_detail_id',
        'provider_id',
        'provider_terminal_id',
        'provider_order_id',
        'trader_id',
        'team_leader_id',
        'merchant_id',
        'expires_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'sub_status' => OrderSubStatus::class,
        'expires_at' => 'datetime',
        'finished_at' => 'datetime',
        'currency' => CurrencyCast::class,
        'market' => MarketEnum::class,
        'base_amount' => MoneyCast::class,
        'amount' => MoneyCast::class,
        'total_profit' => BaseCurrencyMoneyCast::class,
        'trader_profit' => BaseCurrencyMoneyCast::class,
        'team_leader_profit' => BaseCurrencyMoneyCast::class,
        'merchant_profit' => BaseCurrencyMoneyCast::class,
        'service_profit' => BaseCurrencyMoneyCast::class,
        'trader_paid_for_order' => BaseCurrencyMoneyCast::class,
        'conversion_price' => MoneyCast::class,
        'amount_updates_history' => 'array',
    ];

    protected static function booted()
    {
        // Убираем глобальный scope, так как для внешних реквизитов payment_detail_id может быть null
        // static::addGlobalScope(function (Builder $builder) {
        //     $builder->whereNotNull('payment_detail_id');
        // });
    }

    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trans("order.status.{$attributes['status']}"),
        );
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentDetail(): BelongsTo
    {
        return $this->belongsTo(PaymentDetail::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function providerTerminal(): BelongsTo
    {
        return $this->belongsTo(ProviderTerminal::class);
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function smsLog(): HasOne
    {
        return $this->hasOne(SmsLog::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Получить логи колбеков для заказа.
     * 
     * @return MorphMany
     */
    public function callbackLogs(): MorphMany
    {
        return $this->morphMany(CallbackLog::class, 'callbackable');
    }

    /**
     * Получить записи о дополнительной прибыли по этому заказу.
     */
    public function additionalProfits(): HasMany
    {
        return $this->hasMany(OrderAdditionalProfit::class);
    }
}
