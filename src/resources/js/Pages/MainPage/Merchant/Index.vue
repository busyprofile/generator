<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {ref, onMounted, computed} from 'vue';
import ApexCharts from 'apexcharts';
import Card from 'primevue/card';

const statistics = usePage().props.statistics;
const chartData = usePage().props.chart;

const formatNumber = (num) => { //TODO move to utils
    // Округляем до двух знаков после запятой, если есть дробная часть
    const roundedNum = Math.round(num * 100) / 100;

    // Форматируем число с разделителями тысяч
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const statisticsFormated = computed(() => {
    return {
        totalProfit: formatNumber(statistics.totalProfit),
        totalWithdrawalAmount: formatNumber(statistics.totalWithdrawalAmount),
        balance: formatNumber(statistics.balance),
        successOrderCount: statistics.successOrderCount,
    }
});


const chart = ref(null);

onMounted(() => {
    const options = {
        chart: {
            type: 'line',
            height: '100%',
            background: 'transparent',
            toolbar: {
                show: false,
            },
        },
        series: [{
            name: 'Доходы ($)',
            data: chartData.data,
        }],
        xaxis: {
            categories: chartData.labels, // Дни месяца
            labels: {
                style: {
                    colors: '#999',
                },
            },
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#999',
                },
            },
        },
        grid: {
            borderColor: 'rgba(200, 200, 200, 0.1)',
        },
        stroke: {
            width: 2,
            curve: 'smooth',
        },
        colors: ['#6366f1'],
        markers: {
            size: 4,
            colors: ['#6366f1'],
            strokeColors: '#fff',
            strokeWidth: 2,
        },
        tooltip: {
            theme: 'dark',
        },
    };

    const apexChart = new ApexCharts(chart.value, options);
    apexChart.render();
});



defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Главная"/>

        <div class="mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">Главная</h2>
                <slot name="button"></slot>
            </div>
            <div>
                <section>
                    <!-- Карточки статистики с использованием Card -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 2xl:grid-cols-4 gap-6">
                        <!-- Заработано -->
                        <Card class="shadow-md rounded-plate" :pt="{ root: { class: 'rounded-lg' } }">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Заработано</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.totalProfit }}</p>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-dollar text-green-600 dark:text-green-400 text-2xl"></i>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Выплачено -->
                        <Card class="shadow-md rounded-plate" :pt="{ root: { class: 'rounded-lg' } }">
                             <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Выплачено</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.totalWithdrawalAmount }}</p>
                                    </div>
                                    <div class="bg-blue-100 dark:bg-blue-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-arrow-circle-up text-blue-600 dark:text-blue-400 text-2xl"></i>
                                    </div>
                                </div>
                             </template>
                        </Card>

                        <!-- Баланс -->
                         <Card class="shadow-md rounded-plate" :pt="{ root: { class: 'rounded-lg' } }">
                             <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Баланс</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.balance }}</p>
                                    </div>
                                    <div class="bg-purple-100 dark:bg-purple-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-wallet text-purple-600 dark:text-purple-400 text-2xl"></i>
                                    </div>
                                </div>
                             </template>
                        </Card>

                        <!-- Сделки -->
                        <Card class="shadow-md rounded-plate" :pt="{ root: { class: 'rounded-lg' } }">
                             <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Сделки</p>
                                        <p class="text-2xl font-bold dark:text-white">{{ statisticsFormated.successOrderCount }}</p>
                                    </div>
                                    <div class="bg-primary/10 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-chart-line text-primary text-2xl"></i>
                                    </div>
                                </div>
                             </template>
                        </Card>
                    </div>

                    <!-- График в Card -->
                    <Card class="shadow-md rounded-plate mt-8" :pt="{ root: { class: 'rounded-lg' } }">
                         <template #title>
                            <h2 class="text-xl font-bold mb-4 dark:text-white">График доходов за месяц</h2>
                         </template>
                         <template #content>
                             <div ref="chart" class="h-100"></div>
                        </template>
                    </Card>
                </section>
            </div>
        </div>
    </div>
</template>
