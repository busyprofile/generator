<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $apk_access_token
 * @property string $api_access_token
 * @property Collection<int, PaymentDetail> $paymentDetails
 * @property Collection<int, Order> $orders
 * @property Collection<int, Order> $teamLeaderOrders
 * @property Collection<int, Dispute> $disputes
 * @property Collection<int, SmsLog> $smsLogs
 * @property Collection<int, UserLoginHistory> $loginHistories
 * @property Collection<int, UserDevice> $devices
 * @property Collection<int, UserNote> $notes
 * @property Collection<int, Merchant> $merchants Мерчанты (магазины), к которым имеет доступ саппорт
 * @property Collection<int, OrderAdditionalProfit> $additionalProfits
 * @property Wallet $wallet
 * @property Telegram $telegram
 * @property UserMeta $meta
 * @property User $merchant
 * @property boolean $is_online
 * @property boolean $is_payout_online
 * @property boolean $is_vip
 * @property boolean $payouts_enabled
 * @property boolean $stop_traffic
 * @property float $referral_commission_percentage
 * @property float $trader_commission_rate
 * @property Carbon $traffic_enabled_at
 * @property string $avatar_uuid
 * @property string $avatar_style
 * @property string $google2fa_secret
 * @property int|null $promo_code_id
 * @property Carbon|null $promo_used_at
 * @property array|null $additional_team_leader_ids
 * @property PromoCode|null $promoCode
 * @property Carbon $banned_at
 * @property Carbon $created_at
 * @property Carbon $updated_At
 */
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'apk_access_token',
        'api_access_token',
        'is_online',
        'is_payout_online',
        'is_vip',
        'payouts_enabled',
        'stop_traffic',
        'referral_commission_percentage',
        'trader_commission_rate',
        'traffic_enabled_at',
        'avatar_uuid',
        'avatar_style',
        'google2fa_secret',
        'banned_at',
        'promo_code_id',
        'promo_used_at',
        'merchant_id',
        'additional_team_leader_ids',
        'trader_category_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'apk_access_token',
        'api_access_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
            'promo_used_at' => 'datetime',
            'traffic_enabled_at' => 'datetime',
            'trader_commission_rate' => 'float',
            'referral_commission_percentage' => 'float',
            'additional_team_leader_ids' => 'array',
        ];
    }

    protected function google2faSecret(): Attribute
    {
        return new Attribute(
            // TODO: fix it
            // get: fn ($value) =>  $value ? decrypt($value) : null,
            get: fn ($value) =>  null,
            set: fn ($value) =>  $value ? encrypt($value) : null,
        );
    }

    public function canImpersonate()
    {
        return $this->hasRole('Super Admin');
    }

    public function canBeImpersonated()
    {
        return !$this->hasRole('Super Admin');
    }

    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'trader_id');
    }

    public function teamLeaderOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'team_trader_id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'trader_id');
    }

    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function telegram(): HasOne
    {
        return $this->hasOne(Telegram::class);
    }

    public function meta(): HasOne
    {
        return $this->hasOne(UserMeta::class);
    }

    /**
     * Get the promo code that was used by this user.
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    /**
     * Get the notes for the user.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(UserNote::class);
    }

    /**
     * Get the login histories for the user.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class);
    }

    /**
     * Get the merchant this user belongs to.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get the trader category this user belongs to.
     */
    public function traderCategory(): BelongsTo
    {
        return $this->belongsTo(TraderCategory::class);
    }

    /**
     * Получить саппортов мерчанта
     */
    public function supports(): HasMany
    {
        return $this->hasMany(User::class, 'merchant_id');
    }

    /**
     * Получить мерчанты (магазины), к которым имеет доступ саппорт
     */
    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_supports', 'support_id', 'merchant_id')
            ->withTimestamps();
    }

    /**
     * Получить записи о дополнительной прибыли, полученной этим пользователем как тимлидом.
     */
    public function additionalProfits(): HasMany
    {
        return $this->hasMany(OrderAdditionalProfit::class, 'team_leader_id');
    }

    /**
     * Получить тимлидеров этого трейдера с процентами комиссии
     */
    public function teamLeaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trader_team_leader_relations', 'trader_id', 'team_leader_id')
            ->withPivot(['commission_percentage', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Получить трейдеров этого тимлидера
     */
    public function traders()
    {
        return $this->hasMany(TraderTeamLeaderRelation::class, 'team_leader_id');
    }

    /**
     * Получить основного тимлидера трейдера (для обратной совместимости)
     */
    public function primaryTeamLeader()
    {
        return $this->hasOne(TraderTeamLeaderRelation::class, 'trader_id')
                    ->where('is_primary', true);
    }

    /**
     * Псевдоним для teamLeaders() - используется в контроллере
     */
    public function teamLeaderRelations()
    {
        return $this->teamLeaders();
    }

    /**
     * Получить тимлидеров этого мерчанта
     * 
     * Внимание: этот метод учитывает, что в таблице merchant_team_leader_relations
     * поле merchant_id ссылается на таблицу merchants (id), а не на users (id)
     */
    public function merchantTeamLeaders()
    {
        // 1. Получаем запись из таблицы merchants для текущего пользователя-мерчанта
        $merchantRecord = \App\Models\Merchant::where('user_id', $this->id)->first();
        
        if (!$merchantRecord) {
            // Если запись мерчанта не найдена для этого пользователя,
            // возвращаем пустую коллекцию используя пустое условие
            return $this->belongsToMany(
                User::class,
                'merchant_team_leader_relations',
                'merchant_id',
                'team_leader_id'
            )
            ->withPivot(['commission_percentage', 'is_primary'])
            ->withTimestamps()
            ->wherePivot('merchant_id', 0); // Заведомо пустое условие
        }
        
        // 2. Используем id мерчанта из таблицы merchants для поиска связей
        return $this->belongsToMany(
            User::class,
            'merchant_team_leader_relations',
            'merchant_id',
            'team_leader_id'
        )
        ->withPivot(['commission_percentage', 'is_primary'])
        ->withTimestamps()
        ->wherePivot('merchant_id', $merchantRecord->id);
    }

    /**
     * Получить мерчантов, для которых этот пользователь является тимлидером
     */
    public function merchantsAsTeamLeader()
    {
        return $this->belongsToMany(User::class, 'merchant_team_leader_relations', 'team_leader_id', 'merchant_id')
            ->withPivot(['commission_percentage', 'is_primary'])
            ->withTimestamps();
    }
}
