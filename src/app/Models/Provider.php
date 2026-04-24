<?php

namespace App\Models;

use App\Enums\ProviderIntegrationEnum;
use App\Observers\ProviderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property ProviderIntegrationEnum $integration
 * @property int|null $trader_id
 * @property bool $is_active
 * @property User|null $trader
 * @property \Illuminate\Database\Eloquent\Collection<int, ProviderTerminal> $providerTerminals
 */
#[ObservedBy([ProviderObserver::class])]
class Provider extends Model
{
    use HasFactory;

    public const CACHE_KEY_ACTIVE = 'providers.active';
    public const CACHE_KEY_ALL = 'providers.all';

    protected $fillable = [
        'uuid',
        'name',
        'integration',
        'trader_id',
        'is_active',
    ];

    protected $casts = [
        'integration' => ProviderIntegrationEnum::class,
        'is_active' => 'boolean',
    ];

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE);
        Cache::forget(self::CACHE_KEY_ALL);
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    public function providerTerminals(): HasMany
    {
        return $this->hasMany(ProviderTerminal::class);
    }
}
