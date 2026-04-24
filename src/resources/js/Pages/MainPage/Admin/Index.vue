<script setup>
import {Head, usePage, router} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {ref, onMounted, computed, watch} from 'vue';
import ApexCharts from 'apexcharts';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import SelectButton from 'primevue/selectbutton';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import dayjs from 'dayjs';

const chartData = usePage().props.chart;
const conversionChartData = usePage().props.conversionChart;
const hourlyConversionChartData = usePage().props.hourlyConversionChart;
const hourlyEarningsChartData = usePage().props.hourlyEarningsChart;
const merchants = usePage().props.merchants;

if (!usePage().props.auth.can_see_finances) {
    if (chartData) chartData.data = chartData.data.map(() => 0);
    if (hourlyEarningsChartData) hourlyEarningsChartData.data = hourlyEarningsChartData.data.map(() => 0);
}
const selectedMerchantId = usePage().props.selectedMerchantId;

const selectedMerchant = ref(selectedMerchantId || '');
const processing = ref(false);

const chart = ref(null);
const conversionChart = ref(null);
const hourlyConversionChart = ref(null);
const apexChart = ref(null);
const conversionApexChart = ref(null);
const hourlyConversionApexChart = ref(null);

const selectedIncomeChartRange = ref('month');
const incomeChartRangeOptions = ref([
    {label: 'За 3 дня', value: '3days'},
    {label: 'За неделю', value: 'week'},
    {label: 'За месяц', value: 'month'},
]);

const dateFrom = ref(usePage().props.dateFrom ? new Date(usePage().props.dateFrom) : null);
const dateTo = ref(usePage().props.dateTo ? new Date(usePage().props.dateTo) : null);

const merchantOptionsForDropdown = computed(() => {
    return [
        { name: 'Все мерчанты', id: '' },
        ...merchants.map(merchant => ({ name: merchant.name, id: merchant.id }))
    ];
});

const handleIncomeChartRangeChange = (event) => {
    console.log('Income chart range selected:', selectedIncomeChartRange.value);
    processing.value = true;

    router.get(route('admin.main.index'), {
        merchant_id: selectedMerchant.value || undefined,
        income_range: selectedIncomeChartRange.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: () => {
            console.log('Successfully fetched data for new income range.');
        },
        onError: (errors) => {
            console.error('Error fetching data for new income range:', errors);
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

const applyDateFilter = () => {
    processing.value = true;
    router.get(route('admin.main.index'), {
        merchant_id: selectedMerchant.value || undefined,
        income_range: selectedIncomeChartRange.value,
        date_from: dateFrom.value ? dayjs(dateFrom.value).format('YYYY-MM-DD HH:mm:ss') : undefined,
        date_to: dateTo.value ? dayjs(dateTo.value).format('YYYY-MM-DD HH:mm:ss') : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { processing.value = false; }
    });
};

const resetDateFilter = () => {
    dateFrom.value = null;
    dateTo.value = null;
    applyDateFilter();
};

const updateCharts = () => {
    if (apexChart.value && chartData) {
        apexChart.value.updateSeries([{
            name: 'Доходы ($)',
            data: chartData.data,
        }]);
        apexChart.value.updateOptions({
            xaxis: {
                categories: chartData.labels,
            }
        });
    }

    if (conversionApexChart.value && conversionChartData) {
        conversionApexChart.value.updateSeries([{
            name: 'Конверсия (%)',
            data: conversionChartData.data,
        }]);
        conversionApexChart.value.updateOptions({
            xaxis: {
                categories: conversionChartData.labels,
            }
        });
    }

    if (hourlyConversionApexChart.value && hourlyConversionChartData) {
        hourlyConversionApexChart.value.updateSeries([{
            name: 'Конверсия по часам (%)',
            data: hourlyConversionChartData.data,
        }]);
        hourlyConversionApexChart.value.updateOptions({
            xaxis: {
                categories: hourlyConversionChartData.labels,
            }
        });
    }
};

const applyFilter = () => {
    processing.value = true;
    router.get(route('admin.main.index'), {
        merchant_id: selectedMerchant.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => { processing.value = false; }
    });
};

const formatNumber = (num) => {
    if (typeof num !== 'number') {
        const parsed = parseFloat(num);
        if (isNaN(parsed)) return num;
        num = parsed;
    }
    const roundedNum = Math.round(num * 100) / 100;
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const statisticsFormated = computed(() => {
    const currentStatistics = usePage().props.statistics;
    const canSeeFinances = usePage().props.auth.can_see_finances;
    return {
        totalTurnover: canSeeFinances ? formatNumber(currentStatistics.totalTurnover) : '****',
        totalProfit: canSeeFinances ? formatNumber(currentStatistics.totalProfit) : '****',
        totalOrderCount: currentStatistics.totalOrderCount,
        successOrderCount: currentStatistics.successOrderCount,
        failedOrderCount: currentStatistics.failedOrderCount,
        pendingOrderCount: currentStatistics.pendingOrderCount,
        conversionRate: currentStatistics.conversionRate,
        turnoverChange: currentStatistics.turnoverChange || 15,
        turnoverChangePositive: currentStatistics.turnoverChangePositive === undefined ? true : currentStatistics.turnoverChangePositive,
        profitChange: currentStatistics.profitChange || 12,
        profitChangePositive: currentStatistics.profitChangePositive === undefined ? true : currentStatistics.profitChangePositive,
        ordersChange: currentStatistics.ordersChange || 8,
        ordersChangePositive: currentStatistics.ordersChangePositive === undefined ? true : currentStatistics.ordersChangePositive,
        conversionChange: currentStatistics.conversionChange || 5,
        conversionChangePositive: currentStatistics.conversionChangePositive === undefined ? true : currentStatistics.conversionChangePositive,
    }
});

// Watch for all prop changes and update charts accordingly
watch(() => usePage().props, (newProps, oldProps) => {
    if (!newProps.auth.can_see_finances) {
        if (newProps.chart) newProps.chart.data = newProps.chart.data.map(() => 0);
        if (newProps.hourlyEarningsChart) newProps.hourlyEarningsChart.data = newProps.hourlyEarningsChart.data.map(() => 0);
    }
    console.log("Props changed!");
    // Log specific props to see if they are actually changing
    if (newProps.statistics) {
        console.log('New statistics prop:', JSON.parse(JSON.stringify(newProps.statistics)));
    }
    if (oldProps && oldProps.statistics) {
        console.log('Old statistics prop:', JSON.parse(JSON.stringify(oldProps.statistics)));
    }

    // Check if statistics object itself has changed, or just its content
    if (oldProps && newProps.statistics !== oldProps.statistics) {
        console.log('Statistics object reference changed.');
    } else if (oldProps) {
        console.log('Statistics object reference did NOT change.');
    }

    updateCharts();
}, { deep: true });

onMounted(() => {
    const commonChartOptions = (labels, seriesName, seriesData, colors) => ({
        chart: {
            type: 'line',
            height: 300,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
        },
        series: [{ name: seriesName, data: seriesData }],
        xaxis: {
            categories: labels,
            labels: { style: { colors: 'var(--text-color-secondary)' } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: 'var(--text-color-secondary)' } }
        },
        grid: {
            borderColor: 'var(--surface-border)',
        },
        stroke: { width: 2, curve: 'smooth' },
        colors: colors,
        markers: {
            size: 4,
            colors: colors,
            strokeColors: 'var(--surface-card)',
            strokeWidth: 2,
        },
        tooltip: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        },
        theme: {
            mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        }
    });

    // Initial chart rendering
    if (chart.value && chartData) {
        apexChart.value = new ApexCharts(chart.value, commonChartOptions(chartData.labels, 'Доходы ($)', chartData.data, ['#6366F1']));
        apexChart.value.render();
    }

    if (conversionChart.value && conversionChartData) {
        const yaxisOptions = { ...commonChartOptions(conversionChartData.labels, 'Конверсия (%)', conversionChartData.data, ['#10B981']).yaxis };
        yaxisOptions.formatter = function (value) { return value + '%'; };
        yaxisOptions.min = 0;
        yaxisOptions.max = 100;

        conversionApexChart.value = new ApexCharts(conversionChart.value, {
            ...commonChartOptions(conversionChartData.labels, 'Конверсия (%)', conversionChartData.data, ['#10B981']),
            yaxis: yaxisOptions,
            tooltip: {
                ...commonChartOptions(conversionChartData.labels, 'Конверсия (%)', conversionChartData.data, ['#10B981']).tooltip,
                 y: { formatter: function(value) { return value + '%'; } }
            }
        });
        conversionApexChart.value.render();
    }

    if (hourlyConversionChart.value && hourlyConversionChartData) {
         const yaxisOptions = { ...commonChartOptions(hourlyConversionChartData.labels, 'Конверсия по часам (%)', hourlyConversionChartData.data, ['#9333EA']).yaxis };
        yaxisOptions.formatter = function (value) { return value + '%'; };
        yaxisOptions.min = 0;
        yaxisOptions.max = 100;

        hourlyConversionApexChart.value = new ApexCharts(hourlyConversionChart.value, {
            ...commonChartOptions(hourlyConversionChartData.labels, 'Конверсия по часам (%)', hourlyConversionChartData.data, ['#9333EA']),
            yaxis: yaxisOptions,
            tooltip: {
                 ...commonChartOptions(hourlyConversionChartData.labels, 'Конверсия по часам (%)', hourlyConversionChartData.data, ['#9333EA']).tooltip,
                 y: { formatter: function(value) { return value + '%'; } }
            }
        });
        hourlyConversionApexChart.value.render();
    }

    if (hourlyEarningsChartData && hourlyEarningsChartData.labels && hourlyEarningsChartData.labels.length > 0) {
        const hourlyEarningsChart = document.createElement('div');
        hourlyEarningsChart.style.height = '300px';
        document.querySelector('.hourly-earnings-chart-container').appendChild(hourlyEarningsChart);
        new ApexCharts(hourlyEarningsChart, {
            chart: { type: 'line', height: 300, background: 'transparent', toolbar: { show: false }, zoom: { enabled: false } },
            series: [{ name: 'Доход по часам ($)', data: hourlyEarningsChartData.data }],
            xaxis: { categories: hourlyEarningsChartData.labels, labels: { style: { colors: 'var(--text-color-secondary)' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: 'var(--text-color-secondary)' } } },
            grid: { borderColor: 'var(--surface-border)' },
            stroke: { width: 2, curve: 'smooth' },
            colors: ['#F59E42'],
            markers: { size: 4, colors: ['#F59E42'], strokeColors: 'var(--surface-card)', strokeWidth: 2 },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Дашборд"/>

        <div class=" space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-left gap-4 align-items-left">
                <h2 class="text-2xl text-color font-semibold">Дашборд</h2>
                <div class="flex items-center space-x-3">
                    <Dropdown
                        v-model="selectedMerchant"
                        :options="merchantOptionsForDropdown"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Все мерчанты"
                        class="w-full sm:w-auto md:w-60"
                        :showClear="selectedMerchant !== ''"
                    />
                    <Button
                        @click="applyFilter"
                        :loading="processing"
                        label="Применить"
                        icon="pi pi-filter"
                        class="whitespace-nowrap w-full sm:w-auto md:w-60"
                    />
                    <slot name="button"></slot>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center w-full">
                <Calendar v-model="dateFrom" showTime hourFormat="24" dateFormat="dd.mm.yy" placeholder="Дата и время с" class="w-full sm:w-[180px]" />
                <Calendar v-model="dateTo" showTime hourFormat="24" dateFormat="dd.mm.yy" placeholder="Дата и время по" class="w-full sm:w-[180px]" />
                <Button :loading="processing" @click="applyDateFilter" class="w-full sm:w-auto">Показать</Button>
                <Button severity="secondary" outlined @click="resetDateFilter" class="w-full sm:w-auto">Сбросить</Button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-color-secondary mb-1">Оборот</p>
                                <p class="text-2xl font-bold text-color">${{ statisticsFormated.totalTurnover }}</p>

                            </div>
                            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-700/30 text-blue-500 dark:text-blue-300 rounded-xl p-3 w-12 h-12">
                                <i class="pi pi-dollar text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-color-secondary mb-1">Доход</p>
                                <p class="text-2xl font-bold text-color">${{ statisticsFormated.totalProfit }}</p>

                            </div>
                            <div class="flex items-center justify-center bg-green-100 dark:bg-green-700/30 text-green-500 dark:text-green-300 rounded-xl p-3 w-12 h-12">
                                <i class="pi pi-wallet text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-color-secondary mb-1">Все сделки</p>
                                <p class="text-2xl font-bold text-color">{{ statisticsFormated.totalOrderCount }}</p>

                            </div>
                            <div class="flex items-center justify-center bg-primary/10 text-primary rounded-xl p-3 w-12 h-12">
                                <i class="pi pi-chart-bar text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-color-secondary mb-1">Конверсия</p>
                                <p class="text-2xl font-bold text-color">{{ statisticsFormated.conversionRate }}</p>

                            </div>
                            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-700/30 text-purple-500 dark:text-purple-300 rounded-xl p-3 w-12 h-12">
                                <i class="pi pi-percentage text-xl"></i>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <Card :pt="{ root: { class: 'rounded-lg' } }">
                <template #title>
                    <div class="flex flex-col sm:flex-row justify-between items-center">
                        <h2 class="text-xl font-semibold text-color mb-2 sm:mb-0">График доходов за месяц</h2>
                        <!-- <SelectButton v-model="selectedIncomeChartRange" :options="incomeChartRangeOptions" optionLabel="label" optionValue="value" @change="handleIncomeChartRangeChange" />
                    -->
                    </div>
                </template>
                <template #content>
                    <div ref="chart" class="h-[300px]"></div>
                </template>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-base font-medium text-color-secondary">Успешные сделки</p>
                            <div class="flex items-center justify-center bg-green-100 dark:bg-green-700/30 text-green-500 dark:text-green-300 rounded-xl p-2 w-10 h-10">
                                <i class="pi pi-check-circle text-lg"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-color mb-2">{{ statisticsFormated.successOrderCount }}</p>

                    </template>
                </Card>
                 <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-base font-medium text-color-secondary">Неуспешные сделки</p>
                            <div class="flex items-center justify-center bg-red-100 dark:bg-red-700/30 text-red-500 dark:text-red-300 rounded-xl p-2 w-10 h-10">
                                <i class="pi pi-times-circle text-lg"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-color mb-2">{{ statisticsFormated.failedOrderCount }}</p>

                    </template>
                </Card>
                 <Card class="stat-card" :pt="{ root: { class: 'rounded-lg' } }">
                    <template #content>
                        <div class="flex items-center justify-between mb-2">
                             <p class="text-base font-medium text-color-secondary">Активные сделки</p>
                            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-700/30 text-orange-500 dark:text-orange-300 rounded-xl p-2 w-10 h-10">
                                <i class="pi pi-clock text-lg"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-color mb-2">{{ statisticsFormated.pendingOrderCount }}</p>

                    </template>
                </Card>
            </div>

            <Card :pt="{ root: { class: 'rounded-lg' } }">
                <template #title>
                    <h2 class="text-xl font-semibold text-color">График конверсии за месяц</h2>
                </template>
                <template #content>
                    <div ref="conversionChart" class="h-[300px]"></div>
                </template>
            </Card>

            <Card :pt="{ root: { class: 'rounded-lg' } }">
                <template #title>
                    <h2 class="text-xl font-semibold text-color">График конверсии за 24 часа</h2>
                </template>
                <template #content>
                    <div ref="hourlyConversionChart" class="h-[300px]"></div>
                </template>
            </Card>

            <div v-if="hourlyEarningsChartData && hourlyEarningsChartData.labels && hourlyEarningsChartData.labels.length > 0" class="mt-6">
                <Card :pt="{ root: { class: 'rounded-lg' } }">
                    <template #title>
                        <h2 class="text-xl font-semibold text-color">График доходов по часам за день</h2>
                    </template>
                    <template #content>
                        <div class="hourly-earnings-chart-container"></div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat-card .p-card-content {
    padding: 1rem;
}

.h-1_5 {
    height: 0.375rem;
}
/* Удалены конфликтующие стили для .p-card и .p-card-body */
</style>
