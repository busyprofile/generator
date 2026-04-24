<?php

declare(strict_types=1);

namespace App\Services\Order\Features\OrderDetailProvider\Classes\Utils;

use App\Models\User;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;

class TraderFactory
{
    public function make(User $user): Trader
    {
        // Получаем комиссию тимлидера, если есть
        $teamLeaderCommissionRate = 0;
        if ($user->promoCode && $user->promoCode->teamLeader) {
            $teamLeaderCommissionRate = (float)$user->promoCode->teamLeader->referral_commission_percentage;
        }

        // Получаем индивидуальную комиссию трейдера, если она указана
        $traderCommissionRate = null;
        if ($user->trader_commission_rate !== null) {
            $traderCommissionRate = (float)$user->trader_commission_rate;
        }

        return new Trader(
            id: $user->id,
            trustBalance: $user->wallet->trust_balance,
            teamLeaderID: $user->promoCode?->team_leader_id,
            teamLeaderCommissionRate: $teamLeaderCommissionRate,
            traderCommissionRate: $traderCommissionRate,
            additional_team_leader_ids: $user->additional_team_leader_ids ?? [],
        );
    }
}
