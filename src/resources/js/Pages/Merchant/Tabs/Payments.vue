<script setup>
import DateTime from "@/Components/DateTime.vue";
import {usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import Pagination from "@/Components/Pagination/Pagination.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";

const emit = defineEmits(['openPage']);

const props = defineProps({
    tab: {},
});

const orders = usePage().props.orders;
const merchant = usePage().props.merchant;

const openPage = (page) => {
    emit("openPage", page);
};

const currentPage = ref(orders?.meta?.current_page)
</script>

<template>
    <div>
        <h2 class="text-gray-500 text-xs mb-3">Здесь отображаются только оплаченные сделки</h2>

        <div class="relative overflow-x-auto shadow-md rounded-table mb-5">
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
                        Создан
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
                    <td class="px-6 py-3">
                        {{ order.service_commission_amount_total }} {{ order.base_currency.toUpperCase() }}
                    </td>
                    <td class="px-6 py-3">
                        <DateTime class="justify-center" :data="order.created_at"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <Pagination
            v-model="currentPage"
            :total-items="orders.meta.total"
            previous-label="Назад" next-label="Вперед"
            @page-changed="openPage"
            :per-page="orders.meta.per_page"
        ></Pagination>
    </div>
</template>

<style scoped>

</style>
