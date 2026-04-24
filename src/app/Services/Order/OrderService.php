<?php

namespace App\Services\Order;

use App\Contracts\OrderServiceContract;
use App\Enums\OrderSubStatus;
use App\Models\Order;
use App\Services\Money\Money;
use App\DTO\Order\CreateOrderDTO;
use App\DTO\Order\AssignDetailsToOrderDTO;
use App\Services\Order\Features\OrderDetailAssigner;
use App\Services\Order\Features\OrderMaker;
use App\Services\Order\Features\OrderOperator;
use App\Utils\Transaction;
use App\Models\MerchantTeamLeaderRelation;
use App\Models\OrderAdditionalProfit;
use App\Enums\OrderStatus;
use App\Events\OrderFinishedAsFailedEvent;
use App\Exceptions\OrderException;
use App\Models\User;
use App\Services\RequisiteProviders\ProviderSelector;
use App\Services\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderService implements OrderServiceContract
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    public function create(CreateOrderDTO $data): Order
    {
        return $this->transaction(function () use ($data) {
            \Log::error('[OrderService] create() start', [
                'merchant_id' => $data->merchant->id ?? null,
                'amount' => $data->amount?->toBeauty(),
                'gateway_id' => $data->paymentGateway?->id,
                'detail_type' => $data->paymentDetailType?->value,
                'transgran' => $data->transgran,
            ]);

            $order = (new OrderMaker($data))->create();

            if (! $data->manually) {
                \Log::error('[OrderService] assigning details to order', [
                    'order_id' => $order->id,
                ]);
                $order = (new OrderDetailAssigner(
                    order: $order,
                    data: new AssignDetailsToOrderDTO(
                        gateway: $data->paymentGateway,
                        detailType: $data->paymentDetailType,
                        transgran: $data->transgran,
                    )
                ))->assign();
                \Log::error('[OrderService] assign completed', [
                    'order_id' => $order->id,
                    'payment_detail_id' => $order->payment_detail_id,
                ]);
            }

            return $order;
        });
    }

    public function assignDetailsToOrder(int $orderID, AssignDetailsToOrderDTO $data): Order
    {
        return $this->transaction(function () use ($orderID, $data) {
            $order = Order::where('id', $orderID)->lockForUpdate()->first();

            return (new OrderDetailAssigner($order, $data))->assign();
        });
    }

    public function finishOrderAsSuccessful(int $orderID, OrderSubStatus $subStatus): void
    {
        $this->transaction(function () use ($orderID, $subStatus) {
            (new OrderOperator($orderID))->finishOrderAsSuccessful($subStatus);
        });
    }

    public function finishOrderAsFailed(int $orderID, OrderSubStatus $subStatus): bool
    {
        return Transaction::run(function () use ($orderID, $subStatus) {
            $order = Order::where('id', $orderID)->with('providerTerminal.provider')->lockForUpdate()->first();

            if ($order->status->notEquals(OrderStatus::PENDING)) {
                throw new OrderException('Order already finished');
            }

            // Отменяем сделку у провайдера (если есть внешний ID и терминал)
            $this->cancelOrderAtProvider($order);

            $updated = $order->update([
                'status' => OrderStatus::FAIL,
                'sub_status' => $subStatus,
                'finished_at' => Carbon::now(),
            ]);

            OrderFinishedAsFailedEvent::dispatch($order);
            
            if ($updated && $order->paymentDetail && $order->paymentDetail->user) {
                $this->checkAndApplyConsecutiveFailedOrdersLimit($order->paymentDetail->user);
            }

            return $updated;
        });
    }

    /**
     * Отменить сделку у провайдера (если применимо)
     */
    protected function cancelOrderAtProvider(Order $order): void
    {
        // Проверяем, что сделка создана через провайдера и имеет provider_order_id
        if (!$order->provider_terminal_id || !$order->provider_order_id) {
            return;
        }

        $terminal = $order->providerTerminal;
        if (!$terminal) {
            return;
        }

        try {
            $providerSelector = new ProviderSelector();
            $terminalConfig = $providerSelector->getActiveTerminals()
                ->firstWhere('id', $terminal->id);
            
            if (!$terminalConfig) {
                Log::warning('[OrderService] Terminal not found in cache for cancel', [
                    'order_id' => $order->id,
                    'terminal_id' => $terminal->id,
                ]);
                return;
            }

            $provider = $providerSelector->createProviderFromTerminal($terminalConfig);
            
            if ($provider) {
                $cancelled = $provider->cancelOrder($order);
                
                Log::info('[OrderService] Cancel order at provider result', [
                    'order_id' => $order->id,
                    'provider' => $provider->getName(),
                    'cancelled' => $cancelled,
                ]);
            }
        } catch (\Throwable $e) {
            // Логируем ошибку, но не прерываем отмену сделки
            Log::error('[OrderService] Failed to cancel order at provider', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reopenFinishedOrder(int $orderID, OrderSubStatus $subStatus): void
    {
        $this->transaction(function () use ($orderID, $subStatus) {
            (new OrderOperator($orderID))->reopenFinishedOrder($subStatus);
        });
    }

    public function updateAmount(int $orderID, Money $amount): void
    {
        $this->transaction(function () use ($orderID, $amount) {
            (new OrderOperator($orderID))->updateAmount($amount);
        });
    }

    /**
     * Рассчитать комиссии для тимлидеров от мерчанта
     */
    protected function calculateMerchantTeamLeaderCommissions(Order $order): void
    {
        // Получаем всех тимлидеров мерчанта с их комиссиями
        $merchantTeamLeaders = MerchantTeamLeaderRelation::where('merchant_id', $order->merchant_id)
            ->with('teamLeader')
            ->get();

        if ($merchantTeamLeaders->isEmpty()) {
            return; // Если нет тимлидеров мерчанта, ничего не делаем
        }

        $totalMerchantTeamLeaderCommissions = Money::zero($order->total_profit->getCurrency());

        foreach ($merchantTeamLeaders as $relation) {
            // Рассчитываем комиссию для каждого тимлидера
            // ВАЖНО: Уточнить базу для расчета комиссии тимлидера мерчанта.
            // По умолчанию, как и в вашем примере, будем брать % от СУММЫ ЗАКАЗА В USDT ($order->total_profit).
            // Если база должна быть другая (например, % от прибыли мерчанта ($order->merchant_profit) 
            // или % от валовой/чистой прибыли сервиса), то $baseAmountForCommission нужно изменить.
            $baseAmountForCommission = $order->total_profit; 
            $commissionRate = (float) $relation->commission_percentage;

            if ($commissionRate <= 0) {
                continue;
            }
            
            $commissionAmount = $baseAmountForCommission->mul($commissionRate / 100);

            if ($commissionAmount->equalsToZero() || $commissionAmount->lessThanZero()) { // Проверка на ноль или отрицательное значение
                continue;
            }

            // Создаем запись о дополнительной прибыли
            OrderAdditionalProfit::create([
                'order_id' => $order->id,
                'team_leader_id' => $relation->team_leader_id,
                'commission_rate' => $commissionRate,
                'profit_amount' => $commissionAmount, // Сохраняем как объект Money, каст в модели сделает свое дело
                'source' => 'merchant',
            ]);

            // Суммируем комиссию для последующего вычета из прибыли сервиса
            $totalMerchantTeamLeaderCommissions = $totalMerchantTeamLeaderCommissions->add($commissionAmount);

            // Также обновляем общее поле team_leader_profit в заказе (для общей информации)
            // Это поле будет содержать сумму комиссий основного тимлидера трейдера + всех тимлидеров мерчанта
            $order->team_leader_profit = $order->team_leader_profit->add($commissionAmount);
        }

        // Вычитаем общую сумму комиссий тимлидеров мерчанта из прибыли сервиса
        if ($totalMerchantTeamLeaderCommissions->greaterThanZero()) { // Используем greaterThanZero()
            // $order->service_profit здесь уже содержит прибыль сервиса ПОСЛЕ вычета комиссий тимлидеров ТРЕЙДЕРА
            $currentServiceProfitAfterTraderTLs = $order->service_profit;
            $finalNetServiceProfit = $currentServiceProfitAfterTraderTLs->sub($totalMerchantTeamLeaderCommissions);

            // Убедимся, что прибыль сервиса не стала отрицательной
            if ($finalNetServiceProfit->lessThanZero()) { // Используем lessThanZero()
                \Log::warning('Service profit became negative after deducting MERCHANT team leader commissions', [
                    'order_id' => $order->id,
                    'service_profit_before_merchant_tl' => $currentServiceProfitAfterTraderTLs->toUnits(),
                    'merchant_tl_commissions_total' => $totalMerchantTeamLeaderCommissions->toUnits(),
                    'final_net_service_profit' => $finalNetServiceProfit->toUnits(),
                ]);
                $order->service_profit = Money::zero($order->total_profit->getCurrency());
            } else {
                $order->service_profit = $finalNetServiceProfit;
            }
        }

        // Сохраняем обновленные service_profit и team_leader_profit
        // Важно: save() должен быть вызван здесь, чтобы сохранить изменения service_profit и team_leader_profit
        $order->save(); 
    }

    /**
     * Обработка успешного заказа
     */
    public function handleSuccessfulOrder(Order $order): void
    {
        // Рассчитываем комиссии тимлидеров от мерчанта
        $this->calculateMerchantTeamLeaderCommissions($order);
    }

    protected function checkAndApplyConsecutiveFailedOrdersLimit(User $user): void
    {
        $settings = services()->settings()->getMaxConsecutiveFailedOrders();
        $limitCount = (int) ($settings['count'] ?? 0);
        $limitPeriodMinutes = (int) ($settings['period'] ?? 0);

        if ($limitCount <= 0) { // Достаточно проверить только count, период используется для выборки
            return;
        }

        $queryBase = Order::whereHas('paymentDetail', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });

        if ($limitPeriodMinutes > 0) {
            $periodStartTime = Carbon::now()->subMinutes($limitPeriodMinutes);
            $queryBase->where('created_at', '>=', $periodStartTime);
        }

        // Получаем ПОСЛЕДНИЕ $limitCount ордеров пользователя (в рамках периода, если он задан)
        // Сортируем по ID или created_at в ОБРАТНОМ порядке, чтобы первыми шли самые новые
        $recentOrders = $queryBase->orderBy('id', 'DESC') 
                                  ->take($limitCount)
                                  ->get();

        // Если фактическое количество полученных ордеров меньше, чем установленный лимит для проверки последовательности,
        // то пользователь еще не мог набрать $limitCount неудачных ордеров ПОДРЯД.
        if ($recentOrders->count() < $limitCount) {
            return;
        }

        // Теперь проверяем, что ВСЕ из этих $limitCount ордеров имеют статус FAIL.
        // Поскольку мы отсортировали их от новых к старым, это и будет проверка последовательности.
        foreach ($recentOrders as $order) {
            if ($order->status->notEquals(OrderStatus::FAIL)) {
                // Если хотя бы один ордер НЕ в статусе FAIL, значит, последовательность прервана.
                return;
            }
        }

        // Если все $limitCount последних ордеров (в рамках периода) имеют статус FAIL,
        // то останавливаем трафик пользователю.
        $user->update([
            'stop_traffic' => true,
            'traffic_enabled_at' => null, // Сбрасываем дату включения трафика
        ]);

        // Опционально: логирование события
        // \Illuminate\Support\Facades\Log::info("User ID {$user->id} traffic stopped due to {$limitCount} consecutive failed orders.");
    }

    protected function transaction(callable $callback): mixed
    {
        return Transaction::run(function () use ($callback) {
            return $callback();
        });
    }
}