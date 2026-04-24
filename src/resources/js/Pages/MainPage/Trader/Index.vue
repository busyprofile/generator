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
        totalTurnover: formatNumber(statistics.totalTurnover),
        totalProfit: formatNumber(statistics.totalProfit),
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
            data: chartData.data || [],
        }],
        xaxis: {
            categories: chartData.labels || [],
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

    if (chart.value) {
        const apexChart = new ApexCharts(chart.value, options);
        apexChart.render();
    }
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
                <section class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 2xl:grid-cols-4 gap-6">
                        <Card :pt="{ root: { class: 'rounded-lg' } }">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Оборот</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.totalTurnover }}</p>
                                    </div>
                                    <div class="bg-cyan-100 dark:bg-cyan-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-sync text-cyan-600 dark:text-cyan-400 text-2xl"></i>
                                    </div>
                                </div>
                            </template>
                        </Card>
                        <Card :pt="{ root: { class: 'rounded-lg' } }">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Доход</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.totalProfit }}</p>
                                    </div>
                                    <div class="bg-green-100 dark:bg-green-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-dollar text-green-600 dark:text-green-400 text-2xl"></i>
                                    </div>
                                </div>
                            </template>
                        </Card>
                        <Card :pt="{ root: { class: 'rounded-lg' } }">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Общий баланс</p>
                                        <p class="text-2xl font-bold dark:text-white">${{ statisticsFormated.balance }}</p>
                                    </div>
                                    <div class="bg-purple-100 dark:bg-purple-900 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-wallet text-purple-600 dark:text-purple-400 text-2xl"></i>
                                    </div>
                                </div>
                            </template>
                        </Card>
                        <Card :pt="{ root: { class: 'rounded-lg' } }">
                            <template #content>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-400 mb-1">Сделки</p>
                                        <p class="text-2xl font-bold dark:text-white">{{ statisticsFormated.successOrderCount }}</p>
                                    </div>
                                    <div class="bg-primary/10 p-3 w-12 h-12 rounded-xl flex items-center justify-center">
                                        <i class="pi pi-check text-primary text-2xl"></i>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <!-- График -->
                    <Card :pt="{ root: { class: 'rounded-lg' } }">
                        <template #title>
                            <h2 class="text-xl font-bold mb-4 dark:text-white">График доходов за месяц</h2>
                        </template>
                        <template #content>
                             <div ref="chart" class="h-[400px]"></div>
                        </template>
                    </Card>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Удалены стили .stat-card-minimal, .stat-label-minimal, .stat-value-minimal, .stat-icon-minimal */
</style>
