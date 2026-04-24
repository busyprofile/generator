<?php

namespace App\Models;

use App\Enums\MarketEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property string $domain
 * @property string $callback_url
 * @property boolean $active
 * @property int $user_id
 * @property User $user
 * @property Collection<int, Order> $orders
 * @property Collection<int, Category> $categories
 * @property Collection<int, User> $supports Саппорты, имеющие доступ к этому мерчанту
 * @property array $settings
 * @property array $gateway_settings
 * @property MarketEnum $market
 * @property int|null $max_order_wait_time
 * @property int|null $min_order_amounts
 * @property int|null $max_order_amounts
 * @property Carbon $validated_at
 * @property Carbon $banned_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'domain',
        'callback_url',
        'token',
        'user_id',
        'active',
        'settings',
        'gateway_settings',
        'market',
        'max_order_wait_time',
        'min_order_amounts',
        'max_order_amounts',
        'validated_at',
        'banned_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'gateway_settings' => 'array',
        'min_order_amounts' => 'array',
        'max_order_amounts' => 'array',
        'validated_at' => 'datetime',
        'banned_at' => 'datetime',
        'market' => MarketEnum::class,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Получить категории, к которым принадлежит мерчант.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
    
    /**
     * Получить саппортов, которые имеют доступ к этому мерчанту
     */
    public function supports(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'merchant_supports', 'merchant_id', 'support_id')
            ->withTimestamps();
    }

    /**
     * Получить тимлидеров этого мерчанта
     */
    public function teamLeaders()
    {
        return $this->belongsToMany(User::class, 'merchant_team_leader_relations', 'merchant_id', 'team_leader_id')
            ->withPivot('commission_percentage')
            ->withTimestamps();
    }

    /**
     * Получить приоритеты категорий трейдеров для этого мерчанта
     */
    public function traderCategoryPriorities(): HasMany
    {
        return $this->hasMany(MerchantTraderCategoryPriority::class);
    }

    /**
     * Получить категории трейдеров с приоритетами для этого мерчанта
     */
    public function traderCategories(): BelongsToMany
    {
        return $this->belongsToMany(TraderCategory::class, 'merchant_trader_category_priorities')
            ->withPivot('priority')
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    public function merchantTeamLeaders()
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'merchant_team_leader_relations',
            'merchant_id',
            'team_leader_id'
        )->withPivot(['commission_percentage', 'is_primary'])->withTimestamps();
    }

}
