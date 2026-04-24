<script setup>
import { ref, defineProps, defineEmits, computed } from 'vue';
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import PaymentDetail from "@/Components/PaymentDetail.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import { router } from '@inertiajs/vue3';
import AlertError from "@/Components/Alerts/AlertError.vue";
import Pagination from "@/Components/Pagination/Pagination.vue";
import AlertInfo from "@/Components/Alerts/AlertInfo.vue";

const props = defineProps({
    paymentDetails: {
        type: Object,
        required: true
    }
});

// Форматирование числа
const formatNumber = (num) => {
    // Округляем до двух знаков после запятой, если есть дробная часть
    const roundedNum = Math.round(num * 100) / 100;

    // Форматируем число с разделителями тысяч
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const currentPage = ref(props.paymentDetails?.meta?.current_page);

const openPage = (page) => {
    // Получаем текущие параметры URL
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || '';
    const chartType = urlParams.get('chartType') || 'turnover';
    const tableType = urlParams.get('tableType') || 'payment-details';
    
    router.visit(route(route().current()), {
        data: {
            page,
            month,
            chartType,
            tableType
        },
        preserveScroll: true,
        only: ['paymentDetails'] // Обновляем только данные реквизитов
    });
}
</script>

<template>
    <section class="space-y-4">
        <div class="mx-auto space-y-6">
            <div>
                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Название
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Реквизит
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Оборот ($)
                            </th>
                            <th scope="col" class="px-6 py-3">
                                Кол-во сделок
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="detail in paymentDetails.data" :key="detail.id" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">{{ detail.id }}</th>
                            <td class="px-6 py-3">
                                <div class="text-nowrap text-gray-900 dark:text-gray-200">
                                    {{ detail.name }}
                                </div>
                                <div class="text-nowrap text-xs">
                                    {{ detail.payment_gateway.name }}
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <GatewayLogo :img_path="detail.payment_gateway.logo_path" class="w-10 h-10 text-gray-500 dark:text-gray-400"/>
                                    <PaymentDetail :detail="detail.detail" :type="detail.detail_type"></PaymentDetail>
                                </div>
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-200">
                                ${{ formatNumber(detail.monthly_turnover || 0) }}
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-200">
                                {{ detail.monthly_orders_count || 0 }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <Pagination
                    v-model="currentPage"
                    :total-items="paymentDetails.meta.total"
                    previous-label="Назад" next-label="Вперед"
                    @page-changed="openPage"
                    :per-page="paymentDetails.meta.per_page"
                ></Pagination>
            </div>
        </div>
    </section>
</template>
