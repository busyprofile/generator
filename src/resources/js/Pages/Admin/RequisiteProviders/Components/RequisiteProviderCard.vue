<script setup>
import { computed } from 'vue';

const props = defineProps({
    provider: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['test', 'logs']);

// Вычисляемые свойства
const statusColor = computed(() => {
    return props.provider.available 
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
});

const priorityColor = computed(() => {
    const priority = props.provider.priority;
    if (priority <= 5) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    if (priority <= 15) return 'bg-primary/10 text-primary';
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
});

const successRateColor = computed(() => {
    const rate = parseFloat(props.provider.success_rate) || 0;
    if (rate >= 95) return 'text-green-600 dark:text-green-400';
    if (rate >= 80) return 'text-primary';
    return 'text-red-600 dark:text-red-400';
});

const avgResponseTime = computed(() => {
    const time = parseFloat(props.provider.avg_response_time) || 0;
    return Math.round(time);
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('ru-RU').format(parseInt(num) || 0);
};

const formatSuccessRate = (rate) => {
    const numRate = parseFloat(rate) || 0;
    return numRate.toFixed(1);
};

const getProviderDisplayName = (name) => {
    const names = {
        'internal': 'Внутренний',
        'external_provider_1': 'Внешний провайдер 1',
        'external_provider_2': 'Внешний провайдер 2',
        'partner_platform': 'app.hillcard.net',
    };
    return names[name] || name;
};

const getProviderIcon = (name) => {
    if (name === 'internal') {
        return `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>`;
    }
    if (name === 'partner_platform') {
        return `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>`;
    }
    return `<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
            </svg>`;
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200">
        <!-- Заголовок карточки -->
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div 
                        class="flex-shrink-0 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg mr-3"
                        v-html="getProviderIcon(provider.name)"
                    ></div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            {{ getProviderDisplayName(provider.name) }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ provider.name }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- Статус -->
                    <span 
                        :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            statusColor
                        ]"
                    >
                        {{ provider.available ? 'Активен' : 'Неактивен' }}
                    </span>
                    
                    <!-- Приоритет -->
                    <span 
                        :class="[
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            priorityColor
                        ]"
                    >
                        #{{ provider.priority }}
                    </span>
                </div>
            </div>

            <!-- Статистика за 24 часа -->
            <div class="grid grid-cols-2 gap-4 mb-4 mt-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatNumber(provider.total_requests) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Всего запросов
                    </div>
                </div>

                <div class="text-center">
                    <div class="text-2xl font-bold" :class="successRateColor">
                        {{ formatSuccessRate(provider.success_rate) }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Успешность
                    </div>
                </div>

                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ formatNumber(provider.successful_requests) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Успешных
                    </div>
                </div>

                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ avgResponseTime }}ms
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Время ответа
                    </div>
                </div>
            </div>

            <!-- Конфигурация (кратко) -->
            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Конфигурация:
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Таймаут:</span>
                        <span class="ml-1 text-gray-900 dark:text-gray-100">
                            {{ provider.config.timeout || 10 }}с
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Повторы:</span>
                        <span class="ml-1 text-gray-900 dark:text-gray-100">
                            {{ provider.config.retry_attempts || 3 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Действия -->
            <div class="flex space-x-2">
                <button
                    @click="$emit('test', provider)"
                    :disabled="!provider.available"
                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    Тест
                </button>

                <button
                    @click="$emit('logs', provider)"
                    class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Логи
                </button>
            </div>
        </div>
    </div>
</template> 