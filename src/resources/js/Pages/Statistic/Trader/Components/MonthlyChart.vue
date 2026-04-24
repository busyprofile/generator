<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { format, addDays, startOfMonth, endOfMonth, parseISO } from 'date-fns';
import { ru } from 'date-fns/locale';
import ApexCharts from 'apexcharts';
import { router, usePage } from '@inertiajs/vue3';
import SelectButton from 'primevue/selectbutton';
import Chart from 'primevue/chart';

// Получаем данные из контроллера
const props = defineProps({
    chartData: {
        type: Object,
        required: true
    },
    currentMonth: {
        type: String,
        required: true
    },
    prevMonth: {
        type: String,
        required: true
    },
    nextMonth: {
        type: String,
        required: true
    },
    initialChartType: {
        type: String,
        default: 'turnover'
    }
});

const emit = defineEmits(['chart-type-changed']);

// Тип графика (оборот, количество сделок, доход)
const chartType = ref(props.initialChartType); // Используем переданный тип графика

const chartTypeOptions = [
    {
        label: 'Оборот',
        value: 'turnover',
        icon: 'pi pi-dollar',
        color: 'green',
        amount: () => '$' + formatNumber(props.chartData.totalTurnover)
    },
    {
        label: 'Доход',
        value: 'income',
        icon: 'pi pi-wallet',
        color: 'blue',
        amount: () => '$' + formatNumber(props.chartData.totalIncome)
    },
    {
        label: 'Сделки',
        value: 'orders',
        icon: 'pi pi-briefcase',
        color: 'amber',
        amount: () => props.chartData.totalOrders
    }
];

// Функция форматирования чисел
const formatNumber = (num) => {
    // Округляем до двух знаков после запятой, если есть дробная часть
    const roundedNum = Math.round(num * 100) / 100;

    // Форматируем число с разделителями тысяч
    return roundedNum.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

// Переключение месяца для графиков
const prevMonth = () => {
    // Получаем текущие параметры URL
    const urlParams = new URLSearchParams(window.location.search);
    const tableType = urlParams.get('tableType') || 'payment-details';

    router.visit(route(route().current()), {
        data: {
            month: props.prevMonth,
            chartType: chartType.value,
            tableType: tableType,
            page: 1 // Сбрасываем пагинацию при смене месяца
        },
        preserveScroll: true,
        preserveState: false // Сбрасываем состояние для корректного обновления данных
    });
};

const nextMonth = () => {
    // Получаем текущие параметры URL
    const urlParams = new URLSearchParams(window.location.search);
    const tableType = urlParams.get('tableType') || 'payment-details';

    router.visit(route(route().current()), {
        data: {
            month: props.nextMonth,
            chartType: chartType.value,
            tableType: tableType,
            page: 1 // Сбрасываем пагинацию при смене месяца
        },
        preserveScroll: true,
        preserveState: false // Сбрасываем состояние для корректного обновления данных
    });
};

// Форматирование текущего месяца
const currentMonthDisplay = computed(() => {
    if (!props.currentMonth) return '';
    const [year, month] = props.currentMonth.split('-');
    return format(new Date(parseInt(year), parseInt(month) - 1, 1), 'LLLL yyyy', { locale: ru });
});

// Ссылка на DOM-элемент для графика
const chart = ref(null);

// Получение настроек графика в зависимости от выбранного типа
const getChartOptions = () => {
    let seriesName, seriesData, color, formatter;

    switch(chartType.value) {
        case 'orders':
            seriesName = 'Количество сделок';
            seriesData = props.chartData.ordersCountData;
            color = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#43E3F4';
            formatter = (value) => Math.round(value);
            break;
        case 'income':
            seriesName = 'Доход ($)';
            seriesData = props.chartData.incomeData;
            color = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#43E3F4';
            formatter = (value) => '$' + value;
            break;
        case 'turnover':
        default:
            seriesName = 'Оборот ($)';
            seriesData = props.chartData.turnoverData;
            color = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#43E3F4';
            formatter = (value) => '$' + value;
            break;
    }

    return {
        chart: {
            type: 'line',
            height: '100%',
            background: 'transparent',
            toolbar: {
                show: false,
            },
        },
        series: [{
            name: seriesName,
            data: seriesData,
        }],
        xaxis: {
            categories: props.chartData.labels,
            labels: {
                style: {
                    colors: '#999',
                },
                rotateAlways: false,
                hideOverlappingLabels: true,
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
                formatter: formatter
            },
        },
        grid: {
            borderColor: 'rgba(200, 200, 200, 0.1)',
        },
        stroke: {
            width: 2,
            curve: 'smooth',
        },
        colors: [color],
        markers: {
            size: 4,
            colors: [color],
            strokeColors: '#fff',
            strokeWidth: 2,
        },
        tooltip: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
            x: {
                formatter: (index) => props.chartData.fullDates[index - 1]
            },
            y: {
                formatter: formatter
            }
        },
    };
};

// Функция для рендеринга графика
const renderChart = () => {
    // Уничтожаем предыдущий график, если он существует
    if (chart.value && chart.value.__chartInstance) {
        chart.value.__chartInstance.destroy();
    }

    // Создаем новый график
    const options = getChartOptions();
    const apexChart = new ApexCharts(chart.value, options);
    chart.value.__chartInstance = apexChart;
    apexChart.render();
};

// Следим за изменением типа графика
watch(chartType, (newType) => {
    renderChart();
    emit('chart-type-changed', newType);

    // Обновляем URL параметры без перезагрузки страницы
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || props.currentMonth;
    const tableType = urlParams.get('tableType') || 'payment-details';

    router.visit(route(route().current()), {
        data: {
            month: month,
            chartType: newType,
            tableType: tableType,
            page: 1 // Сбрасываем пагинацию при смене типа графика
        },
        preserveScroll: true,
        preserveState: true,
        only: []
    });
});

// Следим за изменением initialChartType из props
watch(() => props.initialChartType, (newType) => {
    if (newType !== chartType.value) {
        chartType.value = newType;
    }
});

// Следим за изменением данных графика
watch(() => props.chartData, () => {
    renderChart();
}, { deep: true });

// Рендерим график при монтировании компонента
onMounted(() => {
    // Проверяем URL параметры при загрузке
    const urlParams = new URLSearchParams(window.location.search);
    const chartTypeParam = urlParams.get('chartType');

    if (chartTypeParam && ['turnover', 'income', 'orders'].includes(chartTypeParam)) {
        chartType.value = chartTypeParam;
    }

    renderChart();
});

// Изменение типа графика
const setChartType = (type) => {
    chartType.value = type;
};

// Получение иконки для типа графика
const getIconForType = (type) => {
    switch(type) {
        case 'orders':
            return 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'; // График
        case 'income':
            return 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'; // Деньги
        case 'turnover':
        default:
            return 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; // Монета
    }
};

// Получение цвета для типа графика
const getColorForType = (type) => {
    switch(type) {
        case 'orders':
            return getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#43E3F4';
        case 'income':
            return getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#43E3F4';
        case 'turnover':
        default:
            return 'green'; // Зеленый
    }
};

// Получение заголовка для типа графика
const getTitleForType = (type) => {
    switch(type) {
        case 'orders':
            return 'Количество сделок';
        case 'income':
            return 'Доход';
        case 'turnover':
        default:
            return 'Оборот';
    }
};

// Получение значения для типа графика
const getValueForType = (type) => {
    switch(type) {
        case 'orders':
            return props.chartData.totalOrders;
        case 'income':
            return '$' + formatNumber(props.chartData.totalIncome);
        case 'turnover':
        default:
            return '$' + formatNumber(props.chartData.totalTurnover);
    }
};

// --- Responsive Button Size Logic ---
const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => { screenWidth.value = window.innerWidth; };
onMounted(() => { window.addEventListener('resize', updateScreenWidth); });
onUnmounted(() => { window.removeEventListener('resize', updateScreenWidth); });
const selectButtonSize = computed(() => { return screenWidth.value < 768 ? 'small' : null; });
// --- End Responsive Button Size Logic ---
</script>

<template>
    <section>
        <!-- PrimeVue SelectButton для выбора типа графика -->
<div class="flex justify-between items-center flex-col md:flex-row gap-2 align-items-left">
        <div class="mb-1 md:mb-6">
            <SelectButton
                :options="chartTypeOptions"
                v-model="chartType"
                optionLabel="label"
                optionValue="value"
                class="w-full sm:w-auto"
                :size="selectButtonSize"
            >
                <template #option="slotProps">
                    <div
                        class="flex items-center md:gap-2  gap-1 rounded-lg font-semibold transition-all md:w-40 w-auto justify-between"
                        :class="[
                            slotProps.selected
                                ? 'bg-black text-white shadow ring-2 ring-black dark:bg-green-900 dark:text-green-200 dark:ring-green-500'
                                : ' '
                        ]"
                    >
                        
                        <span class="font-medium">{{ slotProps.option.label }}</span>
                        <span v-if="slotProps.option.amount" class="text-xs font-bold opacity-80">
                            {{ typeof slotProps.option.amount === 'function' ? slotProps.option.amount() : slotProps.option.amount }}
                        </span>
                    </div>
                </template>
            </SelectButton>
        </div>

        <div class="flex justify-between items-center mb-2 w-full">
            <div class="flex items-center gap-2">
                <button
                    @click="prevMonth"
                    class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    aria-label="Предыдущий месяц"
                    title="Предыдущий месяц"
                >
                    <i class="pi pi-angle-left text-xl text-gray-500 dark:text-gray-300"></i>
                </button>
                <span class="mx-2 text-base font-semibold dark:text-white select-none  ">
                    {{ currentMonthDisplay }}
                </span>
                <button
                    @click="nextMonth"
                    class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    aria-label="Следующий месяц"
                    title="Следующий месяц"
                >
                    <i class="pi pi-angle-right text-xl text-gray-500 dark:text-gray-300"></i>
                </button>
            </div>
        </div>

         </div>

        <!-- График -->
        <div class="bg-white dark:bg-gray-800 shadow-md p-6 rounded-plate border border-gray-200 dark:border-gray-700" >
            <h4 class="text-xl font-bold mb-4 dark:text-white">
                {{ chartType === 'turnover' ? 'График оборота' : chartType === 'orders' ? 'График количества сделок' : 'График доходов' }} за {{ currentMonthDisplay }}
            </h4>
            <div ref="chart" class="h-100"></div>
        </div>
    </section>
</template>
