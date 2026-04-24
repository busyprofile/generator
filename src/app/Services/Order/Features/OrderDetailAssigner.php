<?php

namespace App\Services\Order\Features;

use App\DTO\Order\AssignDetailsToOrderDTO;
use App\Enums\BalanceType;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Enums\TransactionType;
use App\Events\DetailsAssignedToOrderEvent;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderAdditionalProfit;
use App\Models\User;
use App\Services\Order\Features\OrderDetailProvider\OrderDetailProvider;
use App\Services\Order\Utils\DailyLimit;
use App\Services\Order\BusinesLogic\Profits;
use App\Services\Money\Money;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentDetail;

class OrderDetailAssigner
{
    public function __construct(
        protected Order $order,
        protected AssignDetailsToOrderDTO $data
    )
    {
        if ($this->order->status->notEquals(OrderStatus::PENDING)) {
            throw OrderException::orderIsFinished($this->order);
        }
    }

    public function assign(): Order
    {
        return DB::transaction(function () {
            $merchant = queries()->merchant()->findByID($this->order->merchant_id);

            $details = (new OrderDetailProvider(
                order: $this->order,
                merchant: $merchant,
                amount: $this->order->base_amount,
                currency: $this->data->gateway?->currency ?? $this->order->currency,
                gateway: $this->data->gateway,
                detailType: $this->data->detailType,
                transgran: $this->data->transgran,
            ))->provide();

                    // Обновляем основные детали заказа, поля тимлидеров будут обновлены позже
            $updateData = [
                'amount' => $details->amount,
                'total_profit' => $details->totalProfit,
                'merchant_profit' => $details->merchantProfit,
                'trader_profit' => $details->traderProfit,
                // 'team_leader_profit' => $details->teamLeaderProfit, // Будет 0 или null
                'trader_paid_for_order' => $details->traderPaidForOrder,
                'conversion_price' => $details->exchangePrice,
                'trader_commission_rate' => $details->traderCommissionRate,
                // 'team_leader_commission_rate' => $details->teamLeaderCommissionRate, // Будет 0.0
                'total_service_commission_rate' => $details->gateway->serviceCommissionRate,
                'payment_gateway_id' => $details->gateway->id,
                'payment_detail_id' => $details->id,
                'provider_id' => $details->providerId,
                'provider_terminal_id' => $details->providerTerminalId,
                'provider_order_id' => $details->providerOrderId,
                'trader_id' => $details->trader->id,
                // 'team_leader_id' => $details->trader->teamLeaderID, // Будет null
                'expires_at' => now()->addMinutes($details->gateway->reservationTime),
                'sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT,
            ];

            // Сохраняем внешние реквизиты в кэше для использования в API ответе (только если нет реального PaymentDetail)
            if ($details->externalRequisites && $details->id === null) {
                $cacheKey = "order_external_requisites_{$this->order->id}";
                \Cache::put($cacheKey, $details->externalRequisites, now()->addHours(24));
                \Log::info('[OrderDetailAssigner] Сохранены внешние реквизиты в кэше', [
                    'order_id' => $this->order->id,
                    'cache_key' => $cacheKey,
                    'requisites' => $details->externalRequisites,
                ]);
            }

            // external_id НЕ перезаписывается при работе через внешних провайдеров
            // Для callback используется uuid ордера

            $this->order->update($updateData);

            // --- Начало блока расчета комиссий тимлидеров ---
            $trader = User::find($details->trader->id);
            \Log::debug('OrderDetailAssigner: трейдер и его тимлидеры', [
                'order_id' => $this->order->id,
                'trader_id' => $trader->id,
                'team_leaders_relations_count' => $trader->teamLeaders()->count(),
                'trader_main_tl_id' => $trader->team_leader_id,
                'trader_additional_tl_ids' => $trader->additional_team_leader_ids,
            ]);
            
            $additionalProfitsData = [];
            // $finalMainTeamLeaderProfit, $primaryTeamLeaderId, $firstTeamLeaderCommissionRateToStore - удалены, т.к. нет главного ТЛ
            
            $teamLeaderRelations = $trader->teamLeaders()->get();
            \Log::debug('OrderDetailAssigner: Получены teamLeaderRelations', [
                'order_id' => $this->order->id,
                'count' => $teamLeaderRelations->count(),
                'relations_data' => $teamLeaderRelations->toArray(), // Для просмотра, что именно получили
            ]);

            if ($teamLeaderRelations->isEmpty()) {
                // СТАРАЯ ЛОГИКА: используем team_leader_id и additional_team_leader_ids с пользователя
                \Log::debug('OrderDetailAssigner: Используется старая логика для тимлидеров', ['order_id' => $this->order->id]);
                
                $teamLeadersToProcessInfo = []; // Собираем ID и ставки всех тимлидеров для уникальной обработки

                // 1. Обработка "основного" тимлидера из $trader->team_leader_id
                if ($trader->team_leader_id) {
                    $mainTlUser = User::find($trader->team_leader_id);
                    if ($mainTlUser) {
                        $commissionRate = (float) $mainTlUser->referral_commission_percentage;
                        if ($commissionRate > 0) {
                            $teamLeadersToProcessInfo[$mainTlUser->id] = [
                                'id' => $mainTlUser->id,
                                'rate' => $commissionRate,
                                'source' => 'trader_main_old_system'
                            ];
                        }
                    }
                }

                // 2. Обработка тимлидеров из $trader->additional_team_leader_ids
                $additionalTeamLeaderIds = $trader->additional_team_leader_ids ?? [];
                if (!empty($additionalTeamLeaderIds)) {
                    $additionalTeamLeaders = User::whereIn('id', $additionalTeamLeaderIds)->get()->keyBy('id');
                    foreach ($additionalTeamLeaderIds as $teamLeaderId) {
                        if ($additionalTeamLeader = $additionalTeamLeaders->get($teamLeaderId)) {
                            $commissionRate = (float) $additionalTeamLeader->referral_commission_percentage;
                            if ($commissionRate > 0) {
                                // Если тимлидер уже добавлен (например, он был основным), его ставка не перезапишется,
                                // что корректно, если мы хотим отдать приоритет информации из team_leader_id.
                                // Если нужна другая логика (например, максимальная ставка), ее нужно будет реализовать.
                                // Текущая логика: первая найденная ставка (main TL, затем additional TL)
                                if (!isset($teamLeadersToProcessInfo[$teamLeaderId])) {
                                    $teamLeadersToProcessInfo[$teamLeaderId] = [
                                        'id' => $teamLeaderId,
                                        'rate' => $commissionRate,
                                        'source' => 'trader_additional_old_system'
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // 3. Расчет профита для всех собранных уникальных тимлидеров (старая система)
                \Log::debug('OrderDetailAssigner (Старая логика): Перед расчетом профитов', [
                    'order_id' => $this->order->id,
                    'teamLeadersToProcessInfo_count' => count($teamLeadersToProcessInfo),
                    'teamLeadersToProcessInfo_data' => $teamLeadersToProcessInfo,
                ]);
                if (!empty($teamLeadersToProcessInfo)) {
                    $profitCalculator = new Profits();
                    $baseAmountForCalc = $details->totalProfit;

                    foreach ($teamLeadersToProcessInfo as $tlInfo) {
                         \Log::debug('TL PROFIT CALC (старая система, унифицировано)', [
                            'order_id' => $this->order->id,
                            'team_leader_id' => $tlInfo['id'],
                            'commission_rate' => $tlInfo['rate'],
                            'baseAmountForCalc' => $baseAmountForCalc->toBeauty(),
                            'source' => $tlInfo['source'],
                        ]);

                        $additionalProfit = $profitCalculator->calculateAdditionalTeamLeaderProfit(
                            $baseAmountForCalc,
                            $tlInfo['rate']
                        );

                        \Log::debug('TL PROFIT RESULT (старая система, унифицировано)', [
                            'order_id' => $this->order->id,
                            'team_leader_id' => $tlInfo['id'],
                            'profit' => $additionalProfit->toBeauty(),
                        ]);

                        if ($additionalProfit instanceof Money && $additionalProfit->greaterThanZero()) {
                            $additionalProfitsData[] = [
                                'order_id' => $this->order->id,
                                'team_leader_id' => $tlInfo['id'],
                                'commission_rate' => $tlInfo['rate'],
                                'profit_amount' => $additionalProfit,
                                'source' => $tlInfo['source'], // Добавим источник для отладки
                            ];
                        }
                    }
                }

            } else {
                // НОВАЯ ЛОГИКА: используем TraderTeamLeaderRelation.
                \Log::debug('OrderDetailAssigner: Используется новая логика для тимлидеров (TraderTeamLeaderRelation)', ['order_id' => $this->order->id]);
                
                $uniqueTeamLeaderRelations = $teamLeaderRelations->unique(function ($relation) {
                    return $relation->pivot->team_leader_id;
                })->values();
                \Log::debug('OrderDetailAssigner (Новая логика): Получены uniqueTeamLeaderRelations', [
                    'order_id' => $this->order->id,
                    'count' => $uniqueTeamLeaderRelations->count(),
                    'unique_relations_data' => $uniqueTeamLeaderRelations->toArray(),
                ]);

                if ($uniqueTeamLeaderRelations->isNotEmpty()) {
                    $profitCalculator = new Profits();
                    $baseAmountForCalc = $details->totalProfit;

                    foreach ($uniqueTeamLeaderRelations as $relation) {
                        $teamLeaderId = $relation->pivot->team_leader_id;
                        $commissionRate = (float) $relation->pivot->commission_percentage;
                                        
                        \Log::debug('Уникальный Тимлидер (новая система) для заказа', [
                            'order_id' => $this->order->id,
                            'team_leader_id' => $teamLeaderId,
                            'commission_rate' => $commissionRate
                        ]);
                        
                        if ($commissionRate <= 0) continue;
                        
                        $currentTeamLeaderProfit = $profitCalculator->calculateAdditionalTeamLeaderProfit(
                            $baseAmountForCalc,
                            $commissionRate
                        );
                        
                        \Log::debug('TL PROFIT RESULT (новая система, уникальный)', [
                            'order_id' => $this->order->id,
                            'team_leader_id' => $teamLeaderId,
                            'profit' => $currentTeamLeaderProfit->toBeauty(),
                        ]);
                        
                        if ($currentTeamLeaderProfit instanceof Money && $currentTeamLeaderProfit->greaterThanZero()) {
                            $additionalProfitsData[] = [
                                'order_id' => $this->order->id,
                                'team_leader_id' => $teamLeaderId,
                                'commission_rate' => $commissionRate,
                                'profit_amount' => $currentTeamLeaderProfit,
                                'source' => 'trader_relation_new_system', // Добавим источник для отладки
                            ];
                        }
                    }
                }
            }
            
            // Обновляем поля тимлидеров в заказе на null/0, так как теперь все в OrderAdditionalProfit
            $this->order->team_leader_id = null;
            $this->order->team_leader_profit = Money::zero($details->totalProfit->getCurrency());
            $this->order->team_leader_commission_rate = 0.0;

            // Сохраняем все дополнительные профиты (теперь это профиты ВСЕХ тимлидеров)
            // и СУММИРУЕМ их для вычета из прибыли сервиса
            $totalAdditionalTraderTeamLeaderProfit = Money::zero($details->totalProfit->getCurrency());
            
            \Log::debug('OrderDetailAssigner: Перед сохранением в OrderAdditionalProfit', [
                'order_id' => $this->order->id,
                'additionalProfitsData_count' => count($additionalProfitsData),
                'additionalProfitsData_content' => $additionalProfitsData, // Осторожно, может быть много данных
            ]);

            if (!empty($additionalProfitsData)) {
                foreach ($additionalProfitsData as $key => $profitData) {
                    // Убедимся, что profit_amount это Money объект для суммирования
                    $profitAmountMoney = $profitData['profit_amount'];
                    if (!$profitAmountMoney instanceof Money) { 
                        \Log::warning('Profit amount is not a Money object in additionalProfitsData', [
                            'order_id' => $this->order->id, 
                            'data' => $profitData
                        ]);
                        continue; 
                    }

                    // Суммируем только профиты тимлидеров ТРЕЙДЕРА для отдельного контроля, если нужно
                    // Позже из netServiceProfit будет вычтена общая сумма всех additional profits, включая мерчантских
                    if (in_array($profitData['source'], ['trader_relation_new_system', 'trader_main_old_system', 'trader_additional_old_system'])) {
                        if ($profitAmountMoney->greaterThanZero()) {
                            $totalAdditionalTraderTeamLeaderProfit = $totalAdditionalTraderTeamLeaderProfit->add($profitAmountMoney);
                        }
                    }
                    // Преобразуем в юниты для сохранения в БД, если еще не сделано
                    $additionalProfitsData[$key]['profit_amount'] = $profitAmountMoney->toUnits();
                }
                OrderAdditionalProfit::insert($additionalProfitsData);
            }
            
            // Рассчитываем и обновляем ЧИСТУЮ прибыль сервиса
            $netServiceProfit = $details->serviceProfit ?? $details->totalProfit; // Начальная "грязная" прибыль сервиса

            // Вычитаем профиты ВСЕХ уникальных тимлидеров трейдера
            if ($totalAdditionalTraderTeamLeaderProfit->greaterThanZero()) {
                $netServiceProfit = $netServiceProfit->sub($totalAdditionalTraderTeamLeaderProfit);
            }

            // Вычитаем профиты тимлидеров мерчанта
            $merchantTeamLeaderRelations = \App\Models\MerchantTeamLeaderRelation::where('merchant_id', $this->order->merchant_id)
                ->with('teamLeader') // Загружаем тимлидера для информации
                ->get();
            $totalAdditionalMerchantTeamLeaderProfit = Money::zero($details->totalProfit->getCurrency());

            if ($merchantTeamLeaderRelations->isNotEmpty()) {
                \Log::debug('OrderDetailAssigner: Найдены тимлидеры мерчанта', [
                    'order_id' => $this->order->id,
                    'count' => $merchantTeamLeaderRelations->count(),
                    'relations' => $merchantTeamLeaderRelations->toArray()
                ]);
                $profitCalculator = new Profits(); // Profit calculator уже должен быть инициализирован ранее, но для изоляции можно и здесь
                $baseAmountForMerchantTlCommission = $details->totalProfit; // База для комиссии ТЛ мерчанта (общая сумма ордера)

                $merchantTlAdditionalProfitsData = [];

                foreach ($merchantTeamLeaderRelations as $relation) {
                    $teamLeaderId = $relation->team_leader_id;
                    $commissionRate = (float) $relation->commission_percentage;

                    \Log::debug('OrderDetailAssigner: Обработка тимлидера мерчанта', [
                        'order_id' => $this->order->id,
                        'team_leader_id' => $teamLeaderId,
                        'commission_rate' => $commissionRate,
                        'base_for_calc' => $baseAmountForMerchantTlCommission->toBeauty(),
                    ]);

                    if ($commissionRate <= 0) {
                        \Log::debug('OrderDetailAssigner: Пропуск ТЛ мерчанта из-за нулевой ставки', ['team_leader_id' => $teamLeaderId]);
                        continue;
                    }
                    
                    $commissionAmount = $profitCalculator->calculateAdditionalTeamLeaderProfit(
                        $baseAmountForMerchantTlCommission, 
                        $commissionRate
                    );

                    \Log::debug('OrderDetailAssigner: Рассчитан профит ТЛ мерчанта', [
                        'order_id' => $this->order->id,
                        'team_leader_id' => $teamLeaderId,
                        'profit_amount' => $commissionAmount->toBeauty(),
                    ]);

                    if ($commissionAmount->greaterThanZero()) {
                        $merchantTlAdditionalProfitsData[] = [
                            'order_id' => $this->order->id,
                            'team_leader_id' => $teamLeaderId,
                            'commission_rate' => $commissionRate,
                            'profit_amount' => $commissionAmount, // Сохраняем как Money объект
                            'source' => 'merchant',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $totalAdditionalMerchantTeamLeaderProfit = $totalAdditionalMerchantTeamLeaderProfit->add($commissionAmount);
                    } else {
                        \Log::debug('OrderDetailAssigner: Профит ТЛ мерчанта нулевой или отрицательный', ['team_leader_id' => $teamLeaderId]);
                    }
                }

                // Добавляем профиты ТЛ мерчанта в OrderAdditionalProfit
                if (!empty($merchantTlAdditionalProfitsData)) {
                    $merchantTlAdditionalProfitsDataForDb = array_map(function ($data) {
                        $data['profit_amount'] = $data['profit_amount']->toUnits();
                        return $data;
                    }, $merchantTlAdditionalProfitsData);
                    OrderAdditionalProfit::insert($merchantTlAdditionalProfitsDataForDb);
                    \Log::debug('OrderDetailAssigner: Сохранены профиты ТЛ мерчанта', ['count' => count($merchantTlAdditionalProfitsDataForDb)]);
                }
            }

            // Вычитаем общую сумму профитов тимлидеров мерчанта из чистой прибыли сервиса
            if ($totalAdditionalMerchantTeamLeaderProfit->greaterThanZero()) {
                $netServiceProfit = $netServiceProfit->sub($totalAdditionalMerchantTeamLeaderProfit);
                \Log::debug('OrderDetailAssigner: Вычтен профит ТЛ мерчанта из прибыли сервиса', [
                    'order_id' => $this->order->id,
                    'subtracted_amount' => $totalAdditionalMerchantTeamLeaderProfit->toBeauty(),
                    'net_service_profit_after' => $netServiceProfit->toBeauty(),
                ]);
            }

            if ($netServiceProfit->lessThanZero()) {
                \Log::warning('Service profit became negative after deducting all team leader commissions', [
                    'order_id' => $this->order->id,
                    'initial_gross_service_profit' => ($details->serviceProfit ?? $details->totalProfit)->toUnits(),
                    'total_trader_tl_profit_sum' => $totalAdditionalTraderTeamLeaderProfit->toUnits(),
                    'total_merchant_tl_profit_sum' => $totalAdditionalMerchantTeamLeaderProfit->toUnits(), // Добавляем в лог
                    'final_net_service_profit' => $netServiceProfit->toUnits(),
                ]);
                $netServiceProfit = Money::zero(($details->serviceProfit ?? $details->totalProfit)->getCurrency());
            }

            // --- Лог для отладки ---
            \Log::debug('Перед сохранением заказа (все ТЛ унифицированы)', [
                'order_id' => $this->order->id,
                'netServiceProfit' => $netServiceProfit->toUnits(),
                'totalTraderTeamLeaderProfitCalculated' => $totalAdditionalTraderTeamLeaderProfit->toUnits(),
                'totalMerchantTeamLeaderProfitCalculated' => $totalAdditionalMerchantTeamLeaderProfit->toUnits(), // Добавляем в лог
                'order_team_leader_id' => $this->order->team_leader_id, // Должен быть null
                'order_team_leader_profit_field' => ($this->order->team_leader_profit instanceof Money) ? $this->order->team_leader_profit->toUnits() : $this->order->team_leader_profit, // Должен быть 0
                'order_team_leader_commission_rate' => $this->order->team_leader_commission_rate, // Должен быть 0.0
            ]);
            
            $this->order->service_profit = $netServiceProfit;
            // team_leader_id, team_leader_profit, team_leader_commission_rate уже установлены в null/0 выше
            $this->order->save();
            
            \Log::debug('Service profit after all TLs processing and merchant TLs (if any)', [
                'order_id' => $this->order->id,
                'netServiceProfit' => $netServiceProfit->toUnits(),
            ]);
            $this->order->refresh();
            // --- КОНЕЦ блока вычета комиссий тимлидеров мерчанта ---

            // Проверяем PaymentDetail перед increment
            \Log::debug('OrderDetailAssigner: Before DailyLimit::increment', [
                'order_id' => $this->order->id,
                'payment_detail_id' => $this->order->payment_detail_id,
                'payment_detail_exists' => $this->order->payment_detail_id ? 
                    (PaymentDetail::find($this->order->payment_detail_id) ? 'YES' : 'NO') : 'NULL',
                'amount' => $this->order->amount->toBeauty(),
            ]);

            // Для внешних реквизитов (Garex) payment_detail_id может быть null
            if ($this->order->payment_detail_id !== null) {
                DailyLimit::increment($this->order->payment_detail_id, $this->order->amount, $this->order->created_at);
            } else {
                \Log::info('OrderDetailAssigner: Skipping DailyLimit::increment for external requisites', [
                    'order_id' => $this->order->id,
                    'payment_detail_id' => $this->order->payment_detail_id,
                ]);
            }

            // Проверяем что у трейдера есть кошелек перед списанием средств
            \Log::debug('OrderDetailAssigner: Before wallet takeFromBalance', [
                'order_id' => $this->order->id,
                'trader_id' => $this->order->trader->id,
                'trader_has_wallet' => $this->order->trader->wallet ? 'YES' : 'NO',
                'wallet_id' => $this->order->trader->wallet?->id,
                'amount_to_take' => $this->order->trader_paid_for_order->toBeauty(),
                'trust_balance_raw' => $this->order->trader->wallet?->getAttributes()['trust_balance'] ?? 'NULL',
                'trust_balance_casted' => $this->order->trader->wallet?->trust_balance ? 'OBJECT' : 'NULL/FALSE',
            ]);

            if ($this->order->trader->wallet) {
                services()->wallet()->takeFromBalance(
                    $this->order->trader->wallet->id,
                    $this->order->trader_paid_for_order,
                    TransactionType::PAYMENT_FOR_OPENED_ORDER,
                    BalanceType::TRUST
                );
            } else {
                \Log::warning('OrderDetailAssigner: Trader has no wallet, skipping takeFromBalance', [
                    'order_id' => $this->order->id,
                    'trader_id' => $this->order->trader->id,
                ]);
            }

            DetailsAssignedToOrderEvent::dispatch($this->order);

            return $this->order;
        });
    }


}
