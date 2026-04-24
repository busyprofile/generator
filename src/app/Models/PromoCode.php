<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $team_leader_id
 * @property string $code
 * @property int $max_uses
 * @property int $used_count
 * @property boolean $is_active
 * @property User $teamLeader
 * @property Collection<int, User> $users
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PromoCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_leader_id',
        'code',
        'max_uses',
        'used_count',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    /**
     * Get the team leader that owns the promo code.
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /**
     * Get the users that used this promo code.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'promo_code_id');
    }

    /**
     * Check if the promo code can be used.
     */
    public function canBeUsed(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment the used count of the promo code.
     */
    public function incrementUsedCount(): void
    {
        $this->increment('used_count');

        // Если достигнуто максимальное количество использований, деактивируем промокод
        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Decrement the used count of the promo code.
     */
    public function decrementUsedCount(): void
    {
        if ($this->used_count > 0) {
            $this->decrement('used_count');
            
            // Если промокод был деактивирован из-за лимита, активируем его обратно
            if (!$this->is_active && $this->max_uses > 0 && $this->used_count < $this->max_uses) {
                $this->update(['is_active' => true]);
            }
        }
    }
}
