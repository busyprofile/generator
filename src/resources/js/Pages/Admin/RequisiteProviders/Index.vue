<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Skeleton from 'primevue/skeleton';
import ProviderLogsModal from './Components/ProviderLogsModal.vue';

const props = defineProps({
    providers: Array,
    summary: Object,
    merchants: Array,
    paymentGateways: Array,
    currencies: Array,
    detailTypes: Array,
});

const showLogsModal = ref(false);
const selectedProvider = ref(null);
const refreshing = ref(false);

// Статистика в реальном времени
const realtimeStats = ref({
    providers: [...props.providers],
    summary: { ...props.summary }
});

// Вычисляемые свойства для фильтрации
const activeProviders = computed(() => 
    realtimeStats.value.providers.filter(p => p.available)
);

const inactiveProviders = computed(() => 
    realtimeStats.value.providers.filter(p => !p.available)
);

// Функции для модальных окон
const openLogsModal = (provider = null) => {
    selectedProvider.value = provider;
    showLogsModal.value = true;
};

// Обновление данных
const refreshData = async () => {
    refreshing.value = true;
    try {
        const response = await router.reload({
            only: ['providers', 'summary'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                realtimeStats.value.providers = page.props.providers;
                realtimeStats.value.summary = page.props.summary;
            }
        });
    } catch (error) {
        console.error('Ошибка обновления данных:', error);
    } finally {
        refreshing.value = false;
    }
};

// Автообновление каждые 30 секунд
let autoRefreshInterval;
onMounted(() => {
    autoRefreshInterval = setInterval(refreshData, 30000);
});

// Очистка интервала при размонтировании
onUnmounted(() => {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

// Форматирование чисел
const formatNumber = (num) => {
    return new Intl.NumberFormat('ru-RU').format(num);
};

// Функции для отображения
const getProviderDisplayName = (name) => {
    const names = {
        'internal': 'Внутренний',
        'external_provider_1': 'Внешний провайдер 1',
        'external_provider_2': 'Внешний провайдер 2',
        'partner_platform': 'app.hillcard.net',
    };
    return names[name] || name;
};

const getStatusSeverity = (available) => {
    return available ? 'success' : 'danger';
};

const getPrioritySeverity = (priority) => {
    if (priority <= 5) return 'danger';
    if (priority <= 15) return 'warning';
    return 'success';
};

const getSuccessRateSeverity = (rate) => {
    const numRate = parseFloat(rate) || 0;
    if (numRate >= 95) return 'success';
    if (numRate >= 80) return 'warning';
    return 'danger';
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Провайдеры реквизитов" />

        <div class="">
            <div class="max-w-7xl mx-auto">
                <!-- Заголовок -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-surface-900 dark:text-surface-0">
                            Провайдеры реквизитов
                        </h1>
                        <p class="mt-2 text-surface-600 dark:text-surface-400">
                            Управление каскадными провайдерами платежных реквизитов
                        </p>
                    </div>

                    <Button
                        @click="refreshData"
                        :loading="refreshing"
                        icon="pi pi-refresh"
                        label="Обновить"
                        severity="secondary"
                        outlined
                    />
                </div>

                <!-- Общая статистика -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                    <Card class="text-center">
                        <template #content>
                            <div class="flex flex-col items-center">
                                <i class="pi pi-server text-4xl text-blue-500 mb-3"></i>
                                <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                    {{ realtimeStats.summary.total_providers }}
                                </div>
                                <div class="text-sm text-surface-600 dark:text-surface-400">
                                    Всего провайдеров
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="text-center">
                        <template #content>
                            <div class="flex flex-col items-center">
                                <i class="pi pi-check-circle text-4xl text-green-500 mb-3"></i>
                                <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                    {{ realtimeStats.summary.active_providers }}
                                </div>
                                <div class="text-sm text-surface-600 dark:text-surface-400">
                                    Активных
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="text-center">
                        <template #content>
                            <div class="flex flex-col items-center">
                                <i class="pi pi-times-circle text-4xl text-red-500 mb-3"></i>
                                <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                    {{ realtimeStats.summary.inactive_providers }}
                                </div>
                                <div class="text-sm text-surface-600 dark:text-surface-400">
                                    Неактивных
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="text-center">
                        <template #content>
                            <div class="flex flex-col items-center">
                                <i class="pi pi-chart-line text-4xl text-purple-500 mb-3"></i>
                                <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                    {{ formatNumber(realtimeStats.summary.total_requests_24h) }}
                                </div>
                                <div class="text-sm text-surface-600 dark:text-surface-400">
                                    Запросов за 24ч
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="text-center">
                        <template #content>
                            <div class="flex flex-col items-center">
                                <i class="pi pi-bolt text-4xl text-primary mb-3"></i>
                                <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                    {{ realtimeStats.summary.average_success_rate }}%
                                </div>
                                <div class="text-sm text-surface-600 dark:text-surface-400">
                                    Успешность
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Активные провайдеры -->
                <div v-if="activeProviders.length > 0" class="mb-8">
                    <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0 mb-4">
                        Активные провайдеры ({{ activeProviders.length }})
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <Card v-for="provider in activeProviders" :key="provider.name" class="hover:shadow-lg transition-shadow duration-200">
                            <template #header>
                                <div class="flex items-center justify-between p-4 border-b border-surface-200 dark:border-surface-700">
                                    <div class="flex items-center">
                                        <i class="pi pi-server text-2xl text-blue-500 mr-3"></i>
                                        <div>
                                            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                                                {{ getProviderDisplayName(provider.name) }}
                                            </h3>
                                            <p class="text-sm text-surface-600 dark:text-surface-400">
                                                {{ provider.name }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <Tag 
                                            :value="provider.available ? 'Включен' : 'Выключен'"
                                            :severity="getStatusSeverity(provider.available)"
                                        />
                                        <Badge 
                                            :value="`#${provider.priority}`"
                                            :severity="getPrioritySeverity(provider.priority)"
                                        />
                                    </div>
                                </div>
                            </template>

                            <template #content>
                                <!-- Статистика -->
                                <div class="grid grid-cols-2 gap-4 mb-4 mt-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                            {{ formatNumber(provider.total_requests) }}
                                        </div>
                                        <div class="text-xs text-surface-600 dark:text-surface-400">
                                            Всего запросов
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <Tag 
                                            :value="`${parseFloat(provider.success_rate || 0).toFixed(1)}%`"
                                            :severity="getSuccessRateSeverity(provider.success_rate)"
                                        />
                                        <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                            Успешность
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                            {{ formatNumber(provider.successful_requests) }}
                                        </div>
                                        <div class="text-xs text-surface-600 dark:text-surface-400">
                                            Успешных
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                            {{ Math.round(parseFloat(provider.avg_response_time) || 0) }}ms
                                        </div>
                                        <div class="text-xs text-surface-600 dark:text-surface-400">
                                            Время ответа
                                        </div>
                                    </div>
                                </div>

                                <!-- Конфигурация -->
                                <div class="mb-4 p-3 surface-100 dark:surface-800 border-round">
                                    <div class="text-sm font-medium text-surface-700 dark:text-surface-300 mb-2">
                                        Конфигурация:
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="text-surface-600 dark:text-surface-400">Таймаут:</span>
                                            <span class="ml-1 text-surface-900 dark:text-surface-100">
                                                {{ provider.config.timeout || 10 }}с
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-surface-600 dark:text-surface-400">Повторы:</span>
                                            <span class="ml-1 text-surface-900 dark:text-surface-100">
                                                {{ provider.config.retry_attempts || 3 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Действия -->
                                <div class="flex space-x-2">
                                    <Button
                                        @click="openLogsModal(provider)"
                                        icon="pi pi-file-text"
                                        label="Логи"
                                        severity="secondary"
                                        outlined
                                        class="flex-1"
                                    />
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <!-- Неактивные провайдеры -->
                <div v-if="inactiveProviders.length > 0">
                    <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0 mb-4">
                        Неактивные провайдеры ({{ inactiveProviders.length }})
                    </h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <Card v-for="provider in inactiveProviders" :key="provider.name" class="opacity-60">
                            <template #header>
                                <div class="flex items-center justify-between p-4 border-b border-surface-200 dark:border-surface-700">
                                    <div class="flex items-center">
                                        <i class="pi pi-server text-2xl text-surface-400 mr-3"></i>
                                        <div>
                                            <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                                                {{ getProviderDisplayName(provider.name) }}
                                            </h3>
                                            <p class="text-sm text-surface-600 dark:text-surface-400">
                                                {{ provider.name }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <Tag 
                                        value="Выключен"
                                        severity="danger"
                                    />
                                </div>
                            </template>

                            <template #content>
                                <div class="text-center py-8">
                                    <i class="pi pi-power-off text-4xl text-surface-400 mb-3"></i>
                                    <p class="text-surface-600 dark:text-surface-400">
                                        Провайдер отключен
                                    </p>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>

                <!-- Пустое состояние -->
                <div v-if="realtimeStats.providers.length === 0" class="text-center py-12">
                    <i class="pi pi-inbox text-6xl text-surface-400 mb-4"></i>
                    <h3 class="text-xl font-medium text-surface-900 dark:text-surface-0 mb-2">
                        Нет настроенных провайдеров
                    </h3>
                    <p class="text-surface-600 dark:text-surface-400">
                        Настройте провайдеров в конфигурации приложения
                    </p>
                </div>
            </div>
        </div>

        <!-- Модальные окна -->
        <ProviderLogsModal
            :show="showLogsModal"
            :provider="selectedProvider"
            @close="showLogsModal = false"
        />
    </div>
</template>