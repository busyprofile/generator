<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $merchant_id
 * @property int $trader_category_id
 * @property int $priority
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Merchant $merchant
 * @property TraderCategory $traderCategory
 */
class MerchantTraderCategoryPriority extends Model
{
    protected $fillable = [
        'merchant_id',
        'trader_category_id',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    /**
     * Получить мерчанта
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Получить категорию трейдера
     */
    public function traderCategory(): BelongsTo
    {
        return $this->belongsTo(TraderCategory::class);
    }

    /**
     * Scope для сортировки по приоритету (0 = самый высокий)
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority');
    }
} 