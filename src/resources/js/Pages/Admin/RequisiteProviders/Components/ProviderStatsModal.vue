<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const stats = ref({
    time_stats: [],
    top_errors: []
});
const loading = ref(false);
const selectedPeriod = ref('24h');

const periods = [
    { value: '24h', label: 'Последние 24 часа' },
    { value: '7d', label: 'Последние 7 дней' },
    { value: '30d', label: 'Последние 30 дней' },
];

// Загрузка статистики при открытии модала
watch(() => props.show, (newVal) => {
    if (newVal) {
        loadStats();
    }
});

const loadStats = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/admin/requisite-providers/stats?period=${selectedPeriod.value}`);
        stats.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки статистики:', error);
    } finally {
        loading.value = false;
    }
};

const changePeriod = () => {
    loadStats();
};

// Группировка статистики по времени для графика
const chartData = computed(() => {
    const grouped = {};
    
    stats.value.time_stats.forEach(stat => {
        if (!grouped[stat.hour]) {
            grouped[stat.hour] = {};
        }
        grouped[stat.hour][stat.provider_name] = {
            total: stat.total,
            successful: stat.successful,
            success_rate: stat.total > 0 ? (stat.successful / stat.total * 100).toFixed(1) : 0
        };
    });

    return Object.keys(grouped).sort().map(hour => ({
        hour: formatDateForChart(hour),
        ...grouped[hour]
    }));
});

const providerNames = computed(() => {
    const names = new Set();
    stats.value.time_stats.forEach(stat => names.add(stat.provider_name));
    return Array.from(names);
});

const formatDateForChart = (dateString) => {
    const date = new Date(dateString);
    if (selectedPeriod.value === '24h') {
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    } else {
        return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
    }
};

const getProviderDisplayName = (name) => {
    const names = {
        'internal': 'Внутренний',
        'external_provider_1': 'Внешний провайдер 1',
        'external_provider_2': 'Внешний провайдер 2',
    };
    return names[name] || name;
};

const getProviderColor = (providerName, index) => {
    const colors = [
        'text-blue-600 dark:text-blue-400',
        'text-green-600 dark:text-green-400',
        'text-purple-600 dark:text-purple-400',
        'text-primary',
        'text-red-600 dark:text-red-400',
    ];
    return colors[index % colors.length];
};

const getProviderBgColor = (providerName, index) => {
    const colors = [
        'bg-blue-100 dark:bg-blue-900/20',
        'bg-green-100 dark:bg-green-900/20',
        'bg-purple-100 dark:bg-purple-900/20',
        'bg-primary/10',
        'bg-red-100 dark:bg-red-900/20',
    ];
    return colors[index % colors.length];
};

const close = () => {
    emit('close');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mx-auto sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Расширенная статистика провайдеров
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Аналитика работы системы каскадных провайдеров
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <!-- Выбор периода -->
                            <select
                                v-model="selectedPeriod"
                                @change="changePeriod"
                                class="block border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                            >
                                <option v-for="period in periods" :key="period.value" :value="period.value">
                                    {{ period.label }}
                                </option>
                            </select>

                            <button
                                @click="close"
                                class="bg-white dark:bg-gray-700 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <span class="sr-only">Закрыть</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400">Загрузка статистики...</span>
                    </div>

                    <div v-else class="space-y-8">
                        <!-- График активности по времени -->
                        <div v-if="chartData.length > 0" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Активность провайдеров по времени
                            </h4>
                            
                            <!-- Легенда -->
                            <div class="flex flex-wrap gap-4 mb-6">
                                <div 
                                    v-for="(providerName, index) in providerNames" 
                                    :key="providerName"
                                    class="flex items-center"
                                >
                                    <div 
                                        class="w-3 h-3 rounded-full mr-2"
                                        :class="getProviderBgColor(providerName, index)"
                                    ></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ getProviderDisplayName(providerName) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Простой график (таблица) -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                    <thead class="bg-gray-100 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Время
                                            </th>
                                            <th 
                                                v-for="(providerName, index) in providerNames" 
                                                :key="providerName"
                                                class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                                            >
                                                {{ getProviderDisplayName(providerName) }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="dataPoint in chartData.slice(-10)" :key="dataPoint.hour">
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ dataPoint.hour }}
                                            </td>
                                            <td 
                                                v-for="(providerName, index) in providerNames" 
                                                :key="providerName"
                                                class="px-3 py-2 whitespace-nowrap text-center text-sm"
                                            >
                                                <div v-if="dataPoint[providerName]">
                                                    <div class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ dataPoint[providerName].total }}
                                                    </div>
                                                    <div class="text-xs" :class="getProviderColor(providerName, index)">
                                                        {{ dataPoint[providerName].success_rate }}%
                                                    </div>
                                                </div>
                                                <div v-else class="text-gray-400">-</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Топ ошибок -->
                        <div v-if="stats.top_errors && stats.top_errors.length > 0" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Топ ошибок ({{ selectedPeriod === '24h' ? 'за сутки' : selectedPeriod === '7d' ? 'за неделю' : 'за месяц' }})
                            </h4>
                            <div class="space-y-4">
                                <div 
                                    v-for="(error, index) in stats.top_errors.slice(0, 5)" 
                                    :key="index"
                                    class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center mb-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200 mr-2">
                                                    {{ getProviderDisplayName(error.provider_name) }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ error.count }} раз
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 break-words">
                                                {{ error.error_message }}
                                            </p>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                                #{{ index + 1 }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Пустое состояние -->
                        <div v-if="!loading && chartData.length === 0 && (!stats.top_errors || stats.top_errors.length === 0)" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                Нет данных для выбранного периода
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Выберите другой период или дождитесь появления данных
                            </p>
                        </div>
                    </div>

                    <!-- Кнопка закрытия -->
                    <div class="mt-8 flex justify-end">
                        <button
                            @click="close"
                            class="inline-flex justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template> 