<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection<int, User> $traders
 * @property Collection<int, Merchant> $merchants
 */
class TraderCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Получить трейдеров этой категории
     */
    public function traders(): HasMany
    {
        return $this->hasMany(User::class, 'trader_category_id');
    }

    /**
     * Получить активных трейдеров этой категории
     */
    public function activeTraders(): HasMany
    {
        return $this->traders()
            ->where('is_online', true)
            ->where('stop_traffic', false)
            ->whereNull('banned_at');
    }

    /**
     * Получить мерчантов, которые используют эту категорию в приоритетах
     */
    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_trader_category_priorities')
            ->withPivot('priority')
            ->withTimestamps()
            ->orderByPivot('priority');
    }

    /**
     * Получить приоритеты категории для мерчантов
     */
    public function priorities(): HasMany
    {
        return $this->hasMany(MerchantTraderCategoryPriority::class);
    }

    /**
     * Scope для активных категорий
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить количество активных трейдеров в категории
     */
    public function getActiveTraderCountAttribute(): int
    {
        return $this->activeTraders()->count();
    }
} 