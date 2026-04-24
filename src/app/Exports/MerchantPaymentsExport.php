<?php

namespace App\Exports;

use App\Models\Order; // Предполагаем, что модель называется Order
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;
use App\Models\User; // Добавляем модель User

class MerchantPaymentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;
    protected User $merchantUser; // Изменяем тип на User

    public function __construct(User $merchantUser, string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
        $this->endDate = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        $this->merchantUser = $merchantUser; // Сохраняем объект пользователя
    }

    public function query()
    {
        // Используем подтвержденную логику из OrderQueriesEloquent.php
        // Модель Order связана с Merchant, а у Merchant есть user_id.
        $query = Order::query()
            ->whereRelation('merchant', 'user_id', $this->merchantUser->id);

        // Добавляем условия по дате и сортировку
        return $query->whereBetween('created_at', [$this->startDate, $this->endDate])
                    ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'UUID Заказа',
            'Сумма',
            'Валюта',
            'Курс',
            'Прибыль мерчанта',
            'Базовая валюта',
            'Сумма комиссии сервиса',
            'Валюта комиссии сервиса',
            'Статус',
            'Внешний ID',
            'Дата создания',
            'Тип',
            // Добавьте другие необходимые заголовки
        ];
    }

    public function map($order): array
    {
        // Воспроизводим логику расчета service_commission_amount_total из OrderResource
        $serviceCommissionAmount = $order->total_profit->mul($order->total_service_commission_rate / 100);

        return [
            $order->uuid,
            $order->amount->toPrecision(), 
            $order->currency->getCode(),   
            $order->conversion_price ? $order->conversion_price->toPrecision() : '',
            $order->merchant_profit->toPrecision(),
            $order->merchant_profit->getCurrency()->getCode(), 
            $serviceCommissionAmount->toPrecision(), // Сумма комиссии сервиса
            $serviceCommissionAmount->getCurrency()->getCode(), // Валюта комиссии сервиса (базовая)
            $order->status_name, 
            $order->external_id,
            Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
            $order->is_h2h ? 'H2H' : 'Merchant',
        ];
    }
} 