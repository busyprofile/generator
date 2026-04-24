<?php

use App\Models\User;
use App\Models\PromoCode;
use App\Models\TraderTeamLeaderRelation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Мигрируем основных тимлидеров через промокоды
        $traderUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'Trader');
        })->get();
        
        foreach ($traderUsers as $trader) {
            // Если у трейдера есть промокод
            if ($trader->promo_code_id) {
                $promoCode = PromoCode::find($trader->promo_code_id);
                if ($promoCode && $promoCode->team_leader_id) {
                    $teamLeader = User::find($promoCode->team_leader_id);
                    if ($teamLeader) {
                        TraderTeamLeaderRelation::create([
                            'trader_id' => $trader->id,
                            'team_leader_id' => $teamLeader->id,
                            'commission_percentage' => $teamLeader->referral_commission_percentage ?? 0,
                            'is_primary' => true,
                        ]);
                    }
                }
            }
            
            // Мигрируем дополнительных тимлидеров
            if ($trader->additional_team_leader_ids && is_array($trader->additional_team_leader_ids)) {
                foreach ($trader->additional_team_leader_ids as $teamLeaderId) {
                    $teamLeader = User::find($teamLeaderId);
                    if ($teamLeader) {
                        // Проверяем, не добавили ли мы его уже как основного
                        $exists = TraderTeamLeaderRelation::where('trader_id', $trader->id)
                                                         ->where('team_leader_id', $teamLeaderId)
                                                         ->exists();
                        if (!$exists) {
                            TraderTeamLeaderRelation::create([
                                'trader_id' => $trader->id,
                                'team_leader_id' => $teamLeaderId,
                                'commission_percentage' => $teamLeader->referral_commission_percentage ?? 0,
                                'is_primary' => false,
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // При откате миграции данные будут удалены вместе с таблицей
        TraderTeamLeaderRelation::truncate();
    }
}; 