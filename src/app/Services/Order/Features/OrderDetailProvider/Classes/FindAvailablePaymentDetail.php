<?php

namespace App\Services\Order\Features\OrderDetailProvider\Classes;

use App\Enums\DetailType;
use App\Enums\DisputeStatus;
use App\Enums\MarketEnum;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Merchant;
use App\Models\PaymentDetail;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\ValueObjects\Settings\PrimeTimeSettings;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\BusinesLogic\Profits;
use App\Services\Order\Features\OrderDetailProvider\Classes\Utils\GatewayFactory;
use App\Services\Order\Features\OrderDetailProvider\Classes\Utils\TraderFactory;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
use App\Services\TraderPriorityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FindAvailablePaymentDetail
{
    protected PrimeTimeSettings $primeTimeBonus;
    protected Carbon $start;
    protected Carbon $end;
    protected Money $exchangePrice;
    protected array $inactiveGatewayIds;
    protected int $maxPendingDisputes;
    protected Money $approximateTotalProfit;
    /**
     * Minimum required trader trust balance in smallest units.
     */
    protected int $minTrustBalanceUnits;

    public function __construct(
        protected Merchant        $merchant,
        protected MarketEnum      $market,
        protected Money           $amount,
        protected ?DetailType     $detailType = null,
        protected ?Currency       $currency = null,
        protected ?PaymentGateway $gateway = null,
        protected ?bool           $transgran = null,
    )
    {
        Log::info('[FindAvailablePaymentDetail] Constructor called.', [
            'merchant_id' => $this->merchant->id,
            'market' => $this->market->value,
            'amount' => $this->amount->toBeauty(),
            'currency_param' => $this->currency?->getCode(),
            'gateway_param_id' => $this->gateway?->id,
            'detail_type_param' => $this->detailType?->value,
            'transgran_param' => $this->transgran,
        ]);

        if (is_null($this->gateway) && is_null($this->currency)) {
            Log::error('[FindAvailablePaymentDetail] Gateway and Currency are both null.');
            throw OrderException::make('Должен быть указан либо gateway, либо currency.');
        }

        $this->primeTimeBonus = services()->settings()->getPrimeTimeBonus();
        $this->start = Carbon::createFromTimeString($this->primeTimeBonus->starts);
        $this->end = Carbon::createFromTimeString($this->primeTimeBonus->ends);
        $this->exchangePrice = services()->market()->getBuyPrice($this->amount->getCurrency(), $this->market);
        $this->inactiveGatewayIds = collect($this->merchant->gateway_settings)
            ->filter(fn($settings) => isset($settings['active']) && $settings['active'] === false)
            ->keys()
            ->all();
        $this->maxPendingDisputes = services()->settings()->getMaxPendingDisputes();
        $this->approximateTotalProfit = $amount->convert($this->exchangePrice, Currency::USDT());
        // Ensure we require at least a minimal positive balance so traders with
        // zero trust balance are not selected.
        $this->minTrustBalanceUnits = max(1, $this->approximateTotalProfit->toUnitsInt());
    }

    public function get(): ?Detail
    {
        Log::info('[FindAvailablePaymentDetail] get() called.');
        $paymentDetail = $this->queryPaymentDetails()->first();

        if (!$paymentDetail) {
            Log::warning('[FindAvailablePaymentDetail] No suitable PaymentDetail found by queryPaymentDetails()->first().');
            return null;
        }
        Log::info('[FindAvailablePaymentDetail] PaymentDetail found.', ['payment_detail_id' => $paymentDetail->id, 'user_id' => $paymentDetail->user_id]);

        $paymentDetail->update([
            'last_used_at' => now()
        ]);

        $randomGatewayID = $paymentDetail->paymentGateways->pluck('id')->random();
        $paymentGateway = PaymentGateway::find($randomGatewayID);
        $user = User::query()
            ->select(['id', 'promo_code_id', 'trader_commission_rate', 'additional_team_leader_ids'])
            ->with([
                'promoCode:id,team_leader_id',
                'promoCode.teamLeader:id,referral_commission_percentage'
            ])
            ->where('id', $paymentDetail->user_id)
            ->first();

        $gateway = (new GatewayFactory($this->merchant))->make($paymentGateway);
        $trader = (new TraderFactory())->make($user);

        return $this->makeDetail($paymentDetail, $gateway, $trader);
    }

    protected function makeDetail(PaymentDetail $paymentDetail, Gateway $gateway, Trader $trader): Detail
    {
        // Используем индивидуальную комиссию трейдера, если она указана
        // иначе используем стандартную комиссию из шлюза
        $traderCommissionRate = $trader->traderCommissionRate ?? $gateway->traderCommissionRate;

        // Применяем бонус в прайм-тайм
        if (now()->between($this->start, $this->end)) {
            $traderCommissionRate = round($traderCommissionRate + $this->primeTimeBonus->rate, 2);
        }

        $teamLeaderCommissionRate = $trader->teamLeaderCommissionRate;

        //Profits
        $profits = Profits::calculate(
            amount: $this->amount,
            exchangeRate: $this->exchangePrice,
            totalCommissionRate: $gateway->serviceCommissionRate,
            traderCommissionRate: $traderCommissionRate,
            teamLeaderCommissionRate: null,
        );

        $traderPaidForOrder = $profits->totalProfit->sub($profits->traderProfit);

        return new Detail(
            id: $paymentDetail->id,
            userID: $paymentDetail->user_id,
            paymentGatewayID: $gateway->id,
            userDeviceID: $paymentDetail->user_device_id,
            dailyLimit: $paymentDetail->daily_limit,
            currentDailyLimit: $paymentDetail->current_daily_limit,
            currency: $paymentDetail->currency,
            exchangePrice: $this->exchangePrice,
            totalProfit: $profits->totalProfit,
            serviceProfit: $profits->serviceProfit,
            merchantProfit: $profits->merchantProfit,
            traderProfit: $profits->traderProfit,
            teamLeaderProfit: $profits->teamLeaderProfit,
            traderCommissionRate: $traderCommissionRate,
            teamLeaderCommissionRate: $teamLeaderCommissionRate,
            traderPaidForOrder: $traderPaidForOrder,
            gateway: $gateway,
            trader: $trader,
            amount: $this->amount,
        );
    }

    protected function queryPaymentDetails(): Builder
    {
        Log::info('[FindAvailablePaymentDetail] queryPaymentDetails() called.');
        
        // Получаем трейдеров с учетом приоритетов категорий для данного мерчанта
        $traderPriorityService = app(TraderPriorityService::class);
        
        Log::info('[FindAvailablePaymentDetail] Getting prioritized traders.', [
            'merchant_id' => $this->merchant->id,
            'filters' => [
                'is_online' => true,
                'stop_traffic' => false,
                'banned_at' => null,
                'min_trust_balance' => $this->minTrustBalanceUnits,
                'max_pending_disputes' => $this->maxPendingDisputes,
            ]
        ]);
        
        try {
            $prioritizedTraders = $traderPriorityService->getTradersForMerchant($this->merchant, [
                'is_online' => true,
                'stop_traffic' => false,
                'banned_at' => null,
                'min_trust_balance' => $this->minTrustBalanceUnits,
                'max_pending_disputes' => $this->maxPendingDisputes,
            ]);
            
            Log::info('[FindAvailablePaymentDetail] TraderPriorityService returned.', [
                'type' => get_class($prioritizedTraders),
                'count' => $prioritizedTraders->count()
            ]);
        } catch (\Exception $e) {
            Log::error('[FindAvailablePaymentDetail] Error in TraderPriorityService.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        $userIds = $prioritizedTraders->pluck('id');
        Log::info('[FindAvailablePaymentDetail] Found prioritized user IDs.', [
            'count' => $userIds->count(), 
            'ids' => $userIds->implode(','),
            'merchant_id' => $this->merchant->id
        ]);

        if ($userIds->isEmpty()) {
            Log::warning('[FindAvailablePaymentDetail] No suitable prioritized users found.');
            return PaymentDetail::query()->whereRaw('1 = 0');
        }

        $query = PaymentDetail::query()
            ->with('paymentGateways:id')
            ->whereNull('archived_at')
            ->where('is_active', true)
            ->where('is_external', false) // Возвращаем фильтр для внутренних реквизитов
            ->whereIn('user_id', $userIds)
            ->whereRaw('(daily_limit - current_daily_limit) >= ?', [$this->amount->toUnitsInt()]);
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after initial user and daily limit filters.');

        $query->where(function ($query) {
            // Проверяем, что сумма сделки больше или равна минимальной сумме сделки
            // или минимальная сумма сделки равна нулю или NULL (не установлена)
            $query->where(function ($q) {
                $q->whereNull('min_order_amount')
                  ->orWhere('min_order_amount', 0)
                  ->orWhere('min_order_amount', '<=', $this->amount->toUnitsInt());
            });

            // Проверяем, что сумма сделки меньше или равна максимальной сумме сделки
            // или максимальная сумма сделки равна нулю или NULL (не установлена)
            $query->where(function ($q) {
                $q->whereNull('max_order_amount')
                  ->orWhere('max_order_amount', 0)
                  ->orWhere('max_order_amount', '>=', $this->amount->toUnitsInt());
            });
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after min/max amount filter.');

        $query->when($this->detailType, function (Builder $query) {
            $query->where('detail_type', $this->detailType);
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after detail_type filter.');

        // Проверяем интервал между сделками
        $query->where(function ($query) {
            $query->whereNull('order_interval_minutes')
                ->orWhere('order_interval_minutes', 0)
                ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_used_at, ?) >= order_interval_minutes', [now()])
                ->orWhereNull('last_used_at');
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after order interval filter.');

        // Фильтрация по уникальности суммы за последние 10 минут
        $query->whereDoesntHave('orders', function ($query) {
            $query->where('status', OrderStatus::SUCCESS)
                ->whereRaw('payment_details.id = orders.payment_detail_id')
                ->whereRaw('CASE 
                    WHEN payment_details.unique_amount_seconds IS NOT NULL THEN orders.finished_at >= DATE_SUB(NOW(), INTERVAL payment_details.unique_amount_seconds SECOND) 
                    ELSE orders.finished_at >= DATE_SUB(NOW(), INTERVAL 600 SECOND) 
                END')
                ->whereRaw('CASE 
                    WHEN payment_details.unique_amount_percentage IS NOT NULL THEN 
                        orders.amount >= ROUND(? * (1 - payment_details.unique_amount_percentage/100), 0) 
                        AND orders.amount <= ROUND(? * (1 + payment_details.unique_amount_percentage/100), 0) 
                    ELSE 
                        orders.amount >= ? 
                        AND orders.amount <= ? 
                END', [
                    $this->amount->toUnitsInt(),
                    $this->amount->toUnitsInt(),
                    $this->amount->mul(0.97)->toUnitsInt(),
                    $this->amount->mul(1.03)->toUnitsInt()
                ]);
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after unique amount (success) filter.');

        // Уникальность суммы для PENDING заказов
        $query->whereDoesntHave('orders', function ($query) {
            $query->where('status', OrderStatus::PENDING)
                ->where('amount', $this->amount->toUnitsInt());
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after unique amount (pending) filter.');

        // Лимит по количеству PENDING заказов
        $query->where(function ($query) {
            $query->whereNull('max_pending_orders_quantity')
                ->orWhere('max_pending_orders_quantity', 0)
                ->orWhereRaw('
            (
                SELECT COUNT(*)
                FROM orders
                WHERE orders.payment_detail_id = payment_details.id
                    AND orders.status = ?
            ) < payment_details.max_pending_orders_quantity
        ', [OrderStatus::PENDING->value]);
        });
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after max pending orders filter.');

        // Фильтрация по PaymentGateway если он задан, или по Currency и Transgran если шлюз не задан
        if ($this->gateway) {
            Log::info('[FindAvailablePaymentDetail] Applying filter: specific gateway.', ['gateway_id' => $this->gateway->id]);
            $query->whereHas('paymentGateways', function (Builder $q) {
                $q->where('payment_gateways.id', $this->gateway->id)
                  ->whereNotIn('payment_gateways.id', $this->inactiveGatewayIds);
            });
        } elseif ($this->currency) {
            Log::info('[FindAvailablePaymentDetail] Applying filter: currency and transgran.', ['currency' => $this->currency->getCode(), 'transgran' => $this->transgran]);
            $query->whereHas('paymentGateways', function (Builder $q) {
                $q->where('currency', $this->currency->getCode())
                  ->whereNotIn('payment_gateways.id', $this->inactiveGatewayIds);

                if (!is_null($this->transgran)) {
                    $q->where('is_transgran', $this->transgran);
                    Log::debug('[FindAvailablePaymentDetail] Applied transgran filter in whereHas.', ['is_transgran' => $this->transgran]);
                }
            });
        }
        Log::debug('[FindAvailablePaymentDetail] PaymentDetail query after gateway/currency/transgran filters.');
        
        // Сортировка с учетом приоритетов категорий трейдеров
        $priorityOrderSql = $this->buildPriorityOrderSql($userIds);
        $query->orderByRaw($priorityOrderSql)
              ->orderByRaw('ISNULL(last_used_at) DESC, last_used_at ASC');
        
        Log::info('[FindAvailablePaymentDetail] queryPaymentDetails() finished building query with trader priorities.');
        return $query;
    }

    /**
     * Построить SQL для сортировки по приоритетам категорий трейдеров
     */
    protected function buildPriorityOrderSql($userIds): string
    {
        if ($userIds->isEmpty()) {
            return '1';
        }

        // Получаем приоритеты категорий для мерчанта
        $priorities = $this->merchant->traderCategoryPriorities()
            ->with('traderCategory')
            ->orderBy('priority')
            ->get();

        if ($priorities->isEmpty()) {
            Log::debug('[FindAvailablePaymentDetail] No category priorities set for merchant, using default order.');
            return '1';
        }

        $caseParts = [];
        
        // Добавляем CASE для каждой категории по приоритету
        foreach ($priorities as $index => $priority) {
            $categoryId = $priority->trader_category_id;
            $caseParts[] = "WHEN users.trader_category_id = {$categoryId} THEN {$index}";
        }
        
        // Трейдеры без категории идут в конце
        $lastPriority = count($caseParts);
        $caseParts[] = "WHEN users.trader_category_id IS NULL THEN {$lastPriority}";
        
        // Остальные категории (если есть) идут после трейдеров без категории
        $caseParts[] = "ELSE " . ($lastPriority + 1);
        
        $caseStatement = "CASE " . implode(' ', $caseParts) . " END";
        
        // Джойним с таблицей users для получения trader_category_id
        $sql = "(SELECT {$caseStatement} FROM users WHERE users.id = payment_details.user_id)";
        
        Log::debug('[FindAvailablePaymentDetail] Built priority order SQL.', [
            'sql' => $sql,
            'priorities_count' => $priorities->count()
        ]);
        
        return $sql;
    }
}
