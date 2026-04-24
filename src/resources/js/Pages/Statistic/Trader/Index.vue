<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import { ref } from 'vue';
import MonthlyChart from './Components/MonthlyChart.vue';
import TablesSection from './Components/TablesSection.vue';
import Button from 'primevue/button';
// Получаем данные из контроллера
const paymentDetails = ref(usePage().props.paymentDetails || {});
const closedOrders = ref(usePage().props.closedOrders || {});
const chartData = ref(usePage().props.chartData || {});
const currentMonth = ref(usePage().props.currentMonth || '');
const prevMonth = ref(usePage().props.prevMonth || '');
const nextMonth = ref(usePage().props.nextMonth || '');
const chartType = ref(usePage().props.chartType || 'turnover');
const tableType = ref(usePage().props.tableType || 'payment-details');

// Обработка изменения типа графика
const handleChartTypeChanged = (type) => {
    chartType.value = type;
    
    // URL параметры обновляются прямо в компоненте MonthlyChart
};

// Обработка изменения типа таблицы
const handleTableTypeChanged = (type) => {
    tableType.value = type;
    
    // URL параметры обновляются прямо в компоненте TablesSection
};

// Экспорт сделок
const exportOrders = () => {
    window.open(route('trader.export.orders'), '_blank');
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Статистика"/>

        <div class="mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">Статистика</h2>
                <div>
                
                       <Button
                            @click="exportOrders"
                            icon="pi pi-download"
                            label="Выгрузить сделки"
                            class="p-button-primary p-button-rounded px-4 py-2 text-sm font-medium hidden md:inline-flex"
                        />
                  
                
                </div>
            </div>

            <MonthlyChart 
                :chart-data="chartData"
                :current-month="currentMonth"
                :prev-month="prevMonth"
                :next-month="nextMonth"
                :initial-chart-type="chartType"
                @chart-type-changed="handleChartTypeChanged"
            />

            <TablesSection
                :payment-details="paymentDetails"
                :closed-orders="closedOrders"
                :initial-table-type="tableType"
                @table-type-changed="handleTableTypeChanged"
            />
        </div>
    </div>
</template>
