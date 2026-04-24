<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderTeamLeaderRelation extends Model
{
    protected $fillable = [
        'trader_id',
        'team_leader_id',
        'commission_percentage',
        'is_primary',  // флаг для обозначения основного тимлидера (для совместимости)
    ];

    protected $casts = [
        'commission_percentage' => 'float',
        'is_primary' => 'boolean',
    ];

    /**
     * Трейдер в этой связи
     */
    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    /**
     * Тимлидер в этой связи
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }
} 