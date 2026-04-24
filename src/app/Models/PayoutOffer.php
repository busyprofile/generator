<?php

namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\DetailTypesCast;
use App\Casts\MoneyCast;
use App\Enums\DetailType;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property Money $max_amount
 * @property Money $min_amount
 * @property Currency $currency
 * @property Collection<int, DetailType> $detail_types
 * @property boolean $active
 * @property boolean $occupied
 * @property int $payment_gateway_id
 * @property int $owner_id
 * @property PaymentGateway $paymentGateway
 * @property \Illuminate\Database\Eloquent\Collection<int, Payout> $payouts
 * @property User $owner
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PayoutOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'max_amount',
        'min_amount',
        'currency',
        'detail_types',
        'active',
        'occupied',
        'payment_gateway_id',
        'owner_id',
    ];

    protected $casts = [
        'max_amount' => MoneyCast::class,
        'min_amount' => MoneyCast::class,
        'currency' => CurrencyCast::class,
        'detail_types' => DetailTypesCast::class
    ];

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
