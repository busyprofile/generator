<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Casts\CurrencyCast;
use App\Casts\MoneyCast;
use App\Enums\BalanceType;
use App\Enums\DetailType;
use App\Enums\PayoutStatus;
use App\Enums\PayoutSubStatus;
use App\Observers\PayoutObserver;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $external_id
 * @property string $detail
 * @property DetailType $detail_type
 * @property string $detail_initials
 * @property Money $payout_amount
 * @property Currency $currency
 * @property Money $base_liquidity_amount
 * @property Money $liquidity_amount
 * @property float $service_commission_rate
 * @property Money $service_commission_amount
 * @property Money $trader_profit_amount
 * @property float $trader_exchange_markup_rate
 * @property Money $trader_exchange_markup_amount
 * @property Money $base_exchange_price
 * @property Money $exchange_price
 * @property PayoutStatus $status
 * @property string $status_name
 * @property PayoutSubStatus $sub_status
 * @property string $callback_url
 * @property int $payout_offer_id
 * @property int $payout_gateway_id
 * @property int $payment_gateway_id
 * @property int $sub_payment_gateway_id
 * @property int $trader_id
 * @property int $owner_id
 * @property string $refuse_reason
 * @property string $cancel_reason
 * @property int $previous_trader_id
 * @property string $video_receipt
 * @property PayoutOffer $payoutOffer
 * @property PayoutGateway $payoutGateway
 * @property PaymentGateway $paymentGateway
 * @property PaymentGateway $subPaymentGateway
 * @property FundsOnHold $liquidityHold
 * @property FundsOnHold $commissionHold
 * @property User $trader
 * @property User $owner
 * @property User $previousTrader
 * @property Carbon $finished_at
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection<int, CallbackLog> $callbackLogs
 */
#[ObservedBy([PayoutObserver::class])]
class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'external_id',
        'detail',
        'detail_type',
        'detail_initials',
        'payout_amount',
        'currency',
        'base_liquidity_amount',
        'liquidity_amount',
        'service_commission_rate',
        'service_commission_amount',
        'trader_profit_amount',
        'trader_exchange_markup_rate',
        'trader_exchange_markup_amount',
        'base_exchange_price',
        'exchange_price',
        'status',
        'sub_status',
        'callback_url',
        'payout_offer_id',
        'payout_gateway_id',
        'payment_gateway_id',
        'sub_payment_gateway_id',
        'trader_id',
        'owner_id',
        'refuse_reason',
        'cancel_reason',
        'previous_trader_id',
        'video_receipt',
        'finished_at',
        'expires_at',
    ];

    protected $casts = [
        'detail_type' => DetailType::class,
        'status' => PayoutStatus::class,
        'sub_status' => PayoutSubStatus::class,
        'expires_at' => 'datetime',
        'finished_at' => 'datetime',
        'currency' => CurrencyCast::class,
        'payout_amount' => MoneyCast::class,
        'base_liquidity_amount' => BaseCurrencyMoneyCast::class,
        'liquidity_amount' => BaseCurrencyMoneyCast::class,
        'service_commission_amount' => BaseCurrencyMoneyCast::class,
        'trader_profit_amount' => BaseCurrencyMoneyCast::class,
        'trader_exchange_markup_amount' => BaseCurrencyMoneyCast::class,
        'base_exchange_price' => MoneyCast::class,
        'exchange_price' => MoneyCast::class,
    ];

    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trans("payout.status.{$attributes['status']}"),
        );
    }

    public function payoutOffer(): BelongsTo
    {
        return $this->belongsTo(PayoutOffer::class);
    }

    public function payoutGateway(): BelongsTo
    {
        return $this->belongsTo(PayoutGateway::class);
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function subPaymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'sub_payment_gateway_id');
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    public function previousTrader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_trader_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function liquidityHold(): MorphOne
    {
        return $this->morphOne(FundsOnHold::class, 'holdable')
            ->where('destination_wallet_balance_type', BalanceType::TRUST);
    }

    public function commissionHold(): MorphOne
    {
        return $this->morphOne(FundsOnHold::class, 'holdable')
            ->where('destination_wallet_balance_type', BalanceType::COMMISSION);
    }

    /**
     * Получить логи колбеков для выплаты.
     * 
     * @return MorphMany
     */
    public function callbackLogs(): MorphMany
    {
        return $this->morphMany(CallbackLog::class, 'callbackable');
    }
}
