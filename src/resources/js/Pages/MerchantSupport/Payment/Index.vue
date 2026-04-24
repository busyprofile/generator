<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OrderStatus from "@/Components/OrderStatus.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import OrderModal from "@/Modals/OrderModal.vue";
import DateTime from "@/Components/DateTime.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import TableActionsDropdown from "@/Components/Table/TableActionsDropdown.vue";
import TableAction from "@/Components/Table/TableAction.vue";

const orders = usePage().props.orders;

const orderPaymentLink = (payment_link) => {
    window.open(payment_link, '_blank')
}

router.on('success', (event) => {
    orders.value = usePage().props.orders;
})

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Платежи" />

        <MainTableSection
            title="Платежи"
            :data="orders"
        >
            <template v-slot:table-filters>
                <FiltersPanel name="merchant-support-payments">
                    <DropdownFilter
                        name="orderStatuses"
                        title="Статусы"
                    />
                    <InputFilter
                        name="externalID"
                        placeholder="Внешний ID"
                    />
                    <InputFilter
                        name="uuid"
                        placeholder="UUID"
                    />
                    <InputFilter
                        name="amount"
                        placeholder="Сумма"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md sm:rounded-table ">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                UUID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Сумма
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Прибыль
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Комиссия
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Курс
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Статус
                            </th>
                            <th scope="col" class="px-6 py-3 text-nowrap">
                                Внешний ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Создан
                            </th>
                            <th scope="col" class="px-0 py-3"></th>
                            <th scope="col" class="px-6 py-3 flex justify-center">
                                <span class="sr-only">Действия</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="order in orders.data" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                <DisplayUUID :uuid="order.uuid"/>
                            </th>
                            <td class="px-6 py-3">
                                <div class="text-nowrap text-gray-900 dark:text-gray-200">{{ order.amount }} {{ order.currency.toUpperCase() }}</div>
                                <div class="text-nowrap text-xs">{{ order.total_profit }} {{ order.base_currency.toUpperCase() }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-nowrap">{{ order.merchant_profit }} {{ order.base_currency.toUpperCase() }}</div>
                            </td>
                            <td class="px-6 py-3 text-nowrap">
                                {{ order.service_commission_amount_total }} {{ order.base_currency.toUpperCase() }}
                            </td>
                            <td class="px-6 py-3">
                                {{ order.conversion_price }}
                            </td>
                            <td class="px-6 py-3">
                                <OrderStatus :status="order.status" :status_name="order.status_name"></OrderStatus>
                            </td>
                            <td class="px-6 py-3">
                                {{ order.external_id }}
                            </td>
                            <td class="px-6 py-3">
                                <DateTime class="justify-center" :data="order.created_at"/>
                            </td>
                            <td class="px-0 py-3">
                                <div>
                                    <button
                                        v-if="order.is_h2h"
                                        @click.prevent="false"
                                        type="button"
                                        class="px-2 py-1 text-xs font-medium text-center inline-flex items-center text-gray-900 bg-white border border-gray-300 focus:outline-none focus:ring-4 focus:ring-gray-100 rounded-xl  dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:focus:ring-gray-700"
                                    >
                                        H2H
                                    </button>
                                    <button
                                        v-else
                                        @click.prevent="false"
                                        type="button"
                                        class="px-2 py-1 text-xs font-medium text-center inline-flex items-center text-gray-900 bg-white border border-gray-300 focus:outline-none focus:ring-4 focus:ring-gray-100 rounded-xl  dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:focus:ring-gray-700"
                                    >
                                        Merchant
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <TableActionsDropdown>
                                    <TableAction v-if="!order.is_h2h" @click="orderPaymentLink(order.payment_link)">
                                        Платежная страница
                                    </TableAction>
                                    <TableAction @click="router.post(route('merchant-support.payment.callback.resend', order.id))">
                                        Отправить Callback
                                    </TableAction>
                                </TableActionsDropdown>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>

        <OrderModal/>
        <ConfirmModal/>
    </div>
</template>
