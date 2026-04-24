<?php

namespace App\Models;

use App\Observers\ProviderTerminalObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $uuid
 * @property int $provider_id
 * @property string $name
 * @property float|null $min_sum
 * @property float|null $max_sum
 * @property int|null $time_for_order
 * @property float|null $rate
 * @property int|null $max_response_time_ms
 * @property int|null $number_of_retries
 * @property array|null $additional_settings
 * @property bool $is_active
 * @property array|null $enabled_detail_types
 * @property Provider $provider
 * @property \Illuminate\Database\Eloquent\Collection<int, Order> $orders
 * @property \Illuminate\Database\Eloquent\Collection<int, RequisiteProviderLog> $requisiteProviderLogs
 * @property \Illuminate\Database\Eloquent\Collection<int, Merchant> $merchants
 */
#[ObservedBy([ProviderTerminalObserver::class])]
class ProviderTerminal extends Model
{
    use HasFactory;

    public const CACHE_KEY_ACTIVE = 'provider_terminals.active';
    public const CACHE_KEY_ALL = 'provider_terminals.all';

    protected $fillable = [
        'uuid',
        'provider_id',
        'name',
        'min_sum',
        'max_sum',
        'time_for_order',
        'rate',
        'max_response_time_ms',
        'number_of_retries',
        'additional_settings',
        'is_active',
        'enabled_detail_types',
    ];

    protected $casts = [
        'min_sum' => 'float',
        'max_sum' => 'float',
        'rate' => 'float',
        'max_response_time_ms' => 'integer',
        'number_of_retries' => 'integer',
        'additional_settings' => 'array',
        'enabled_detail_types' => 'array',
        'is_active' => 'boolean',
    ];

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE);
        Cache::forget(self::CACHE_KEY_ALL);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function requisiteProviderLogs(): HasMany
    {
        return $this->hasMany(RequisiteProviderLog::class);
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'provider_terminal_merchant')
            ->withPivot('is_active')
            ->withTimestamps();
    }
}
