<?php

namespace App\Http\Controllers\TeamLeader;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReferralController extends Controller
{
    /**
     * Отображает список рефералов команды лидера.
     */
    public function index()
    {
        // Получаем все промокоды, созданные текущим пользователем
        $promoCodes = PromoCode::where('team_leader_id', auth()->id())->pluck('id');

        // Сбор всех трейдеров: как через промокоды, так и через новую систему TraderTeamLeaderRelation
        $usersFromPromoCodes = User::with(['promoCode'])
            ->whereIn('promo_code_id', $promoCodes)
            ->pluck('id');
        
        $usersFromRelations = \App\Models\TraderTeamLeaderRelation::where('team_leader_id', auth()->id())
            ->pluck('trader_id');
        
        $usersFromAdditional = User::whereJsonContains('additional_team_leader_ids', auth()->id())
            ->pluck('id');
            
        // Собираем мерчантов (через join, чтобы получить user_id)
        $merchantUserIds = \App\Models\MerchantTeamLeaderRelation::where('team_leader_id', auth()->id())
            ->join('merchants', 'merchant_team_leader_relations.merchant_id', '=', 'merchants.id')
            ->pluck('merchants.user_id');
        
        // Объединяем все ID
        $referralsIds = $usersFromPromoCodes->concat($usersFromRelations)
            ->concat($usersFromAdditional)
            ->concat($merchantUserIds)
            ->unique();
            
        // Получаем пользователей по собранным ID
        $referrals = User::with(['promoCode'])
            ->whereIn('id', $referralsIds)
            ->latest('promo_used_at')
            ->paginate(request()->per_page ?? 10);

        // Получаем статистику по заказам для каждого реферала
        $referralsIds = $referrals->pluck('id');

        // Подсчет количества сделок и прибыли (основные сделки с team_leader_id)
        $referralStats = Order::select('trader_id')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(team_leader_profit) as total_team_leader_profit')
            ->where('status', OrderStatus::SUCCESS)
            ->whereIn('trader_id', $referralsIds)
            ->where('team_leader_id', auth()->id())
            ->groupBy('trader_id')
            ->get()
            ->keyBy('trader_id');

        // Статистика по дополнительным сделкам (order_additional_profits)
        $additionalStats = DB::table('order_additional_profits as oap')
            ->join('orders as o', 'oap.order_id', '=', 'o.id')
            ->selectRaw('o.trader_id, COUNT(*) as orders_count, SUM(oap.profit_amount) as total_team_leader_profit')
            ->where('oap.team_leader_id', auth()->id())
            ->whereIn('o.trader_id', $referralsIds)
            ->groupBy('o.trader_id')
            ->get()
            ->keyBy('trader_id');

        // Преобразуем данные, объединяя основные и дополнительные сделки
        $enrichedReferrals = $referrals->through(function ($referral) use ($referralStats, $additionalStats) {
            $isMerchant = $referral->hasRole('Merchant');
            $referral->is_merchant = $isMerchant;
            
            if ($isMerchant) {
                // Получаем merchant_id из таблицы merchants по user_id
                $merchantRecord = \App\Models\Merchant::where('user_id', $referral->id)->first();
                $merchantId = $merchantRecord ? $merchantRecord->id : null;
                // Считаем сделки и профит по order_additional_profits
                $merchantStats = DB::table('order_additional_profits as oap')
                    ->join('orders as o', 'oap.order_id', '=', 'o.id')
                    ->where('oap.team_leader_id', auth()->id())
                    ->where('o.merchant_id', $merchantId)
                    ->selectRaw('COUNT(*) as orders_count, SUM(oap.profit_amount) as total_team_leader_profit')
                    ->first();
                $ordersCount = $merchantStats->orders_count ?? 0;
                $profitUnits = $merchantStats->total_team_leader_profit ?? 0;
                $referral->orders_count = $ordersCount;
                $referral->total_team_leader_profit = Money::fromUnits($profitUnits, Currency::USDT());
            } else {
                $stats = $referralStats[$referral->id] ?? null;
                $add  = $additionalStats[$referral->id] ?? null;
                $ordersCount = ($stats->orders_count ?? 0) + ($add->orders_count ?? 0);
                $profitUnits = ($stats->total_team_leader_profit ?? 0) + ($add->total_team_leader_profit ?? 0);
                $referral->orders_count = $ordersCount;
                $referral->total_team_leader_profit = Money::fromUnits($profitUnits, Currency::USDT());
            }
            // Получаем комиссию в зависимости от типа
            if ($isMerchant) {
                $relation = \App\Models\MerchantTeamLeaderRelation::where('merchant_id', $merchantId)
                    ->where('team_leader_id', auth()->id())
                    ->first();
            } else {
                $relation = \App\Models\TraderTeamLeaderRelation::where('trader_id', $referral->id)
                    ->where('team_leader_id', auth()->id())
                    ->first();
            }
            $referral->commission_percentage = $relation->commission_percentage ?? auth()->user()->referral_commission_percentage ?? 0;
            $referral->is_primary_teamleader = $relation->is_primary ?? false;
            $referral->relation_created_at = $relation?->created_at;
            return $referral;
        });

        return Inertia::render('Referral/Index', [
            'referrals' => ReferralResource::collection($enrichedReferrals)
        ]);
    }
}
