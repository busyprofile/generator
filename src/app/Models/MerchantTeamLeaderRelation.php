<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantTeamLeaderRelation extends Model
{
    protected $fillable = [
        'merchant_id',
        'team_leader_id',
        'commission_percentage',
        'is_primary'
    ];

    protected $casts = [
        'commission_percentage' => 'float',
        'is_primary' => 'boolean'
    ];

    /**
     * Мерчант в этой связи
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Тимлидер в этой связи
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }
} 