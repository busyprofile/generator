<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantTeamLeaderRelation;
use App\Models\User;
use App\Utils\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantTeamLeaderController extends Controller
{
    /**
     * Получить список тимлидеров для мерчанта
     */
    public function getMerchantTeamLeaders(Merchant $merchant)
    {
        $teamLeaders = MerchantTeamLeaderRelation::where('merchant_id', $merchant->id)
            ->with(['teamLeader:id,name,email'])
            ->get()
            ->map(function ($relation) {
                return [
                    'id' => $relation->id,
                    'team_leader_id' => $relation->team_leader_id,
                    'commission_percentage' => $relation->commission_percentage,
                    'team_leader_name' => $relation->teamLeader->name,
                    'team_leader_email' => $relation->teamLeader->email,
                ];
            });

        return response()->json($teamLeaders);
    }

    /**
     * Сохранить связи тимлидеров для мерчанта
     */
    public function saveMerchantTeamLeaders(Request $request, Merchant $merchant)
    {
        $request->validate([
            'team_leaders' => 'required|array',
            'team_leaders.*.team_leader_id' => 'required|exists:users,id',
            'team_leaders.*.commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        Transaction::run(function () use ($request, $merchant) {
            Log::info('Сохранение тимлидеров для мерчанта', [
                'merchant_id' => $merchant->id,
                'team_leaders' => $request->team_leaders
            ]);

            // Удаляем существующие связи
            $merchant->teamLeaders()->detach();

            // Создаем новые связи
            foreach ($request->team_leaders as $teamLeader) {
                MerchantTeamLeaderRelation::create([
                    'merchant_id' => $merchant->id,
                    'team_leader_id' => $teamLeader['team_leader_id'],
                    'commission_percentage' => $teamLeader['commission_percentage'],
                ]);
            }
        });

        return response()->json(['message' => 'Тимлидеры успешно сохранены']);
    }
} 