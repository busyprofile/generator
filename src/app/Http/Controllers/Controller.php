<?php

namespace App\Http\Controllers;

use App\Enums\DetailType;
use App\Enums\DisputeStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\ObjectValues\TableFilters\TableFiltersValue;
use Carbon\Carbon;

abstract class Controller
{
    public function getTableFilters(): TableFiltersValue
    {
        $currentRoute = request()->route()->getName();
        $sessionKey = 'table_filters_' . $currentRoute;

        // Проверяем, что это GET-запрос
        if (request()->isMethod('GET')) {
            // Если запрос пустой, пытаемся загрузить сохраненные параметры из сессии
            if (empty(request()->all())) {
                $savedFilters = session($sessionKey);
                if ($savedFilters) {
                    // Перенаправляем на этот же роут, но с сохраненными параметрами без возврата
                    header('Location: ' . route($currentRoute, $savedFilters));
                    exit();
                }
            } else {
                // Сохраняем текущие параметры запроса в сессию для этого роута
                session([$sessionKey => request()->all()]);
            }
        }

        $orderStatusesInput = request()->input('filters.orderStatuses');
        if (is_array($orderStatusesInput)) {
            $orderStatuses = $orderStatusesInput;
        } elseif (is_string($orderStatusesInput) && $orderStatusesInput !== '') {
            $orderStatuses = explode(',', $orderStatusesInput);
        } else {
            $orderStatuses = [];
        }

        foreach ($orderStatuses as $key => $value) {
            if (! OrderStatus::tryFrom($value)) {
                unset($orderStatuses[$key]);
            }
        }

        $disputeStatusesInput = request()->input('filters.disputeStatuses');
        if (is_array($disputeStatusesInput)) {
            $disputeStatuses = $disputeStatusesInput;
        } elseif (is_string($disputeStatusesInput) && $disputeStatusesInput !== '') {
            $disputeStatuses = explode(',', $disputeStatusesInput);
        } else {
            $disputeStatuses = [];
        }

        foreach ($disputeStatuses as $key => $value) {
            if (! DisputeStatus::tryFrom($value)) {
                unset($disputeStatuses[$key]);
            }
        }

        $invoiceStatusesInput = request()->input('filters.invoiceStatuses');
        if (is_array($invoiceStatusesInput)) {
            $invoiceStatuses = $invoiceStatusesInput;
        } elseif (is_string($invoiceStatusesInput) && $invoiceStatusesInput !== '') {
            $invoiceStatuses = explode(',', $invoiceStatusesInput);
        } else {
            $invoiceStatuses = [];
        }

        foreach ($invoiceStatuses as $key => $value) {
            if (! InvoiceStatus::tryFrom($value)) {
                unset($invoiceStatuses[$key]);
            }
        }

        $apiLogStatusesInput = request()->input('filters.apiLogStatuses');
        if (is_array($apiLogStatusesInput)) {
            $apiLogStatuses = $apiLogStatusesInput;
        } elseif (is_string($apiLogStatusesInput) && $apiLogStatusesInput !== '') {
            $apiLogStatuses = explode(',', $apiLogStatusesInput);
        } else {
            $apiLogStatuses = [];
        }

        foreach ($apiLogStatuses as $key => $value) {
            if (! in_array($value, [0, 1])) {
                unset($apiLogStatuses[$key]);
            }
        }

        $detailTypesInput = request()->input('filters.detailTypes');
        if (is_array($detailTypesInput)) {
            $detailTypes = $detailTypesInput;
        } elseif (is_string($detailTypesInput) && $detailTypesInput !== '') {
            $detailTypes = explode(',', $detailTypesInput);
        } else {
            $detailTypes = [];
        }

        foreach ($detailTypes as $key => $value) {
            if (! DetailType::tryFrom($value)) {
                unset($detailTypes[$key]);
            }
        }

        $rolesInput = request()->input('filters.roles'); // Получаем значение как есть

        if (is_array($rolesInput)) {
            $roles = $rolesInput; // Если уже массив, используем его
        } elseif (is_string($rolesInput) && $rolesInput !== '') {
            $roles = explode(',', $rolesInput); // Если строка, то разделяем
        } else {
            $roles = []; // В противном случае (null или пустая строка) — пустой массив
        }
        
        $roles = array_filter($roles); // Удаляем пустые значения (например, если была передана пустая строка в массиве)
 
        $startDateInput = request()->input('filters.startDate');
        $startDate = null;
        if ($startDateInput) {
            try {
                // Проверяем формат dd/mm/yy или dd/mm/yyyy
                if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $startDateInput)) {
                    // Преобразуем dd/mm/yy в dd/mm/yyyy (добавляем 20 в начало года)
                    $parts = explode('/', $startDateInput);
                    $startDateInput = "{$parts[0]}/{$parts[1]}/20{$parts[2]}";
                }
                $startDate = Carbon::createFromFormat('d/m/Y', $startDateInput);
            } catch (\Exception $e) {
                \Log::warning('Ошибка парсинга даты startDate', [
                    'startDate' => $startDateInput,
                    'error' => $e->getMessage()
                ]);
                // $startDate остается null
            }
        }

        $endDateInput = request()->input('filters.endDate');
        $endDate = null;
        if ($endDateInput) {
            try {
                // Проверяем формат dd/mm/yy или dd/mm/yyyy
                if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $endDateInput)) {
                    // Преобразуем dd/mm/yy в dd/mm/yyyy (добавляем 20 в начало года)
                    $parts = explode('/', $endDateInput);
                    $endDateInput = "{$parts[0]}/{$parts[1]}/20{$parts[2]}";
                }
                $endDate = Carbon::createFromFormat('d/m/Y', $endDateInput);
            } catch (\Exception $e) {
                \Log::warning('Ошибка парсинга даты endDate', [
                    'endDate' => $endDateInput,
                    'error' => $e->getMessage()
                ]);
                // $endDate остается null
            }
        }

        if ($startDate && $endDate && $endDate->lessThan($startDate)) {
            $endDate = null;
        }

        $externalID = request()->input('filters.externalID');
        $uuid = request()->input('filters.uuid');
        $paymentGateway = request()->input('filters.paymentGateway');
        $provider = request()->input('filters.provider');
        $providerTerminalId = request()->input('filters.providerTerminalId') ? (int) request()->input('filters.providerTerminalId') : null;
        $statusCode = request()->input('filters.statusCode');

        $currentFilters = [
            'orderStatuses' => $orderStatuses,
            'disputeStatuses' => $disputeStatuses,
            'invoiceStatuses' => $invoiceStatuses,
            'apiLogStatuses' => $apiLogStatuses,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'externalID' => $externalID,
            'uuid' => $uuid,
            'search' => request()->input('filters.search'),
            'onlySuccessParsing' => request()->input('filters.onlySuccessParsing') === 'true',
            'amount' => request()->input('filters.amount'),
            'minAmount' => request()->input('filters.minAmount'),
            'maxAmount' => request()->input('filters.maxAmount'),
            'paymentDetail' => request()->input('filters.paymentDetail'),
            'user' => request()->input('filters.user'),
            'id' => request()->input('filters.id'),
            'name' => request()->input('filters.name'),
            'active' => request()->input('filters.active') === 'true',
            'multipliedDetails' => request()->input('filters.multipliedDetails') === 'true',
            'online' => request()->input('filters.online') === 'true',
            'address' => request()->input('filters.address'),
            'merchant' => request()->input('filters.merchant'),
            'currency' => request()->input('filters.currency'),
            'method' => request()->input('filters.method'),
            'traffic_disabled' => request()->input('filters.traffic_disabled') === 'true',
            'roles' => $roles,
            'detailTypes' => $detailTypes,
            'paymentGateway' => $paymentGateway,
            'provider' => $provider,
            'providerTerminalId' => $providerTerminalId,
            'statusCode' => $statusCode,
        ];

        return new TableFiltersValue(
            startDate: $currentFilters['startDate'],
            endDate: $currentFilters['endDate'],
            orderStatuses: $currentFilters['orderStatuses'],
            disputeStatuses: $currentFilters['disputeStatuses'],
            invoiceStatuses: $currentFilters['invoiceStatuses'],
            apiLogStatuses: $currentFilters['apiLogStatuses'],
            externalID: $currentFilters['externalID'],
            uuid: $currentFilters['uuid'],
            search: $currentFilters['search'],
            onlySuccessParsing: $currentFilters['onlySuccessParsing'],
            amount: $currentFilters['amount'],
            minAmount: $currentFilters['minAmount'],
            maxAmount: $currentFilters['maxAmount'],
            paymentDetail: $currentFilters['paymentDetail'],
            user: $currentFilters['user'],
            id: $currentFilters['id'],
            name: $currentFilters['name'],
            active: $currentFilters['active'],
            multipliedDetails: $currentFilters['multipliedDetails'],
            online: $currentFilters['online'],
            address: $currentFilters['address'],
            merchant: $currentFilters['merchant'],
            currency: $currentFilters['currency'],
            method: $currentFilters['method'],
            traffic_disabled: $currentFilters['traffic_disabled'],
            roles: $currentFilters['roles'],
            detailTypes: $currentFilters['detailTypes'],
            paymentGateway: $currentFilters['paymentGateway'],
            provider: $currentFilters['provider'],
            providerTerminalId: $currentFilters['providerTerminalId'],
            statusCode: $currentFilters['statusCode'],
        );
    }

    public function getFiltersData(): array
    {
        $orderStatuses = [];
        foreach (OrderStatus::values() as $status) {
            $orderStatuses[] = [
                'name' => trans("order.status.{$status}"),
                'value' => $status,
            ];
        }

        $disputeStatuses = [];
        foreach (DisputeStatus::values() as $status) {
            $disputeStatuses[] = [
                'name' => trans("dispute.status.{$status}"),
                'value' => $status,
            ];
        }

        $invoiceStatuses = [];
        foreach (InvoiceStatus::values() as $status) {
            $invoiceStatuses[] = [
                'name' => trans("invoice.status.{$status}"),
                'value' => $status,
            ];
        }

        $apiLogStatuses = [
            [
                'name' => 'Успешные',
                'value' => '1',
            ],
            [
                'name' => 'Неуспешные',
                'value' => '0',
            ],
        ];

        $detailTypes = [];
        foreach (DetailType::values() as $type) {
            $detailTypes[] = [
                'name' => trans("detail-type.{$type}"),
                'value' => $type,
            ];
        }

        // Получаем список всех ролей из БД
        $roles = \Spatie\Permission\Models\Role::all()
            ->map(function ($role) {
                return [
                    'name' => $role->name,
                    'value' => $role->name,
                ];
            })
            ->toArray();

        return [
            'orderStatuses' => $orderStatuses,
            'disputeStatuses' => $disputeStatuses,
            'invoiceStatuses' => $invoiceStatuses,
            'apiLogStatuses' => $apiLogStatuses,
            'roles' => $roles,
            'detailTypes' => $detailTypes,
        ];
    }
}
