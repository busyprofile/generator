<script setup>
import { reactive, computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Sidebar from 'primevue/sidebar';

const props = defineProps({
    terminals: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({ query: '', integration: 'all', status: 'all' });
const filtersVisible = ref(false);

const terminals = computed(() => props.terminals?.data ?? props.terminals ?? []);

const integrationOptions = computed(() => {
    const unique = new Set(
        (terminals.value || [])
            .map((t) => t.provider_integration)
            .filter(Boolean)
    );
    return [
        { label: 'Все интеграции', value: 'all' },
        ...Array.from(unique).map((value) => ({ label: value, value })),
    ];
});

const filteredTerminals = computed(() => {
    const q = filters.query.trim().toLowerCase();
    const status = filters.status;
    const integration = filters.integration;
    return (terminals.value || []).filter((t) => {
        const matchQuery =
            !q ||
            (t.name ?? '').toLowerCase().includes(q) ||
            (t.provider_name ?? '').toLowerCase().includes(q);
        const matchStatus =
            status === 'all' ||
            (status === 'active' && t.available) ||
            (status === 'inactive' && !t.available);
        const matchIntegration =
            integration === 'all' || (t.provider_integration ?? '').toLowerCase() === integration.toLowerCase();
        return matchQuery && matchStatus && matchIntegration;
    });
});

const activeTerminals = computed(() =>
    filteredTerminals.value.filter(t => t.available)
);

const inactiveTerminals = computed(() =>
    filteredTerminals.value.filter(t => !t.available)
);

const formatNumber = (num) => new Intl.NumberFormat('ru-RU').format(num ?? 0);
const getStatusSeverity = (available) => available ? 'success' : 'danger';
const getSuccessRateSeverity = (rate) => {
    const numRate = parseFloat(rate) || 0;
    if (numRate >= 95) return 'success';
    if (numRate >= 80) return 'warning';
    return 'danger';
};

const openView = (terminal) => router.visit(route('admin.provider-terminals.show', terminal.id));
const openCreate = () => router.visit(route('admin.provider-terminals.create'));
const openLogs = (terminal) => router.visit(route('admin.provider-logs.index', { 'filters[providerTerminalId]': terminal.id }));
const openCallbacks = (terminal) => router.visit(route('admin.provider-callback-logs.index', { 'filters[providerTerminalId]': terminal.id }));
const openFilters = () => { filtersVisible.value = true; };
const applyFilters = () => {
    filtersVisible.value = false;
    router.get(route('admin.provider-terminals.index'), {
        query: filters.query,
        integration: filters.integration,
        status: filters.status,
    }, { preserveState: true, replace: true });
};
const clearFilters = () => {
    filters.query = '';
    filters.status = 'all';
    filters.integration = 'all';
    applyFilters();
};
const toggleTerminal = (terminal) => {
    router.patch(route('admin.provider-terminals.toggle', { providerTerminal: terminal.id }), {}, { preserveScroll: true, preserveState: true });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Провайдер терминалы" />

        <div class="mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-xl text-gray-900 dark:text-white sm:text-4xl">
                        Провайдер терминалы
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        icon="pi pi-filter"
                        label="Фильтры"
                        outlined
                        severity="secondary"
                        @click="openFilters"
                    />
                    <Button
                        icon="pi pi-plus"
                        label="Добавить"
                        severity="primary"
                        @click="openCreate"
                    />
                </div>
            </div>

            <Sidebar v-model:visible="filtersVisible" position="right" class="w-full md:w-1/2 lg:w-1/3">
                <template #header>
                    <h3 class="text-lg font-semibold">Фильтры</h3>
                </template>
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-xs text-surface-500 dark:text-surface-400">Поиск</label>
                        <InputText v-model="filters.query" placeholder="Название или провайдер" class="w-full" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-surface-500 dark:text-surface-400">Тип интеграции</label>
                        <Dropdown
                            v-model="filters.integration"
                            :options="integrationOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                            placeholder="Выберите интеграцию"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs text-surface-500 dark:text-surface-400">Статус</label>
                        <Dropdown
                            v-model="filters.status"
                            :options="[
                                { label: 'Все', value: 'all' },
                                { label: 'Активные', value: 'active' },
                                { label: 'Неактивные', value: 'inactive' },
                            ]"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                            placeholder="Выберите статус"
                        />
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-end gap-2">
                        <Button label="Очистить" icon="pi pi-filter-slash" severity="danger" outlined @click="clearFilters" />
                        <Button label="Применить" icon="pi pi-check" severity="info" @click="applyFilters" />
                    </div>
                </template>
            </Sidebar>

            <!-- Общая статистика -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <Card class="text-center">
                    <template #content>
                        <div class="flex flex-col items-center">
                            <i class="pi pi-server text-4xl text-blue-500 mb-3"></i>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                {{ summary?.total_terminals ?? 0 }}
                            </div>
                            <div class="text-sm text-surface-600 dark:text-surface-400">
                                Всего терминалов
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="text-center">
                    <template #content>
                        <div class="flex flex-col items-center">
                            <i class="pi pi-check-circle text-4xl text-green-500 mb-3"></i>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                {{ summary?.active_terminals ?? 0 }}
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
                            <i class="pi pi-chart-line text-4xl text-purple-500 mb-3"></i>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                {{ formatNumber(summary?.total_requests_24h) }}
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
                                {{ formatNumber(summary?.success_24h) }}
                            </div>
                            <div class="text-sm text-surface-600 dark:text-surface-400">
                                Успешных за 24 часа
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="text-center">
                    <template #content>
                        <div class="flex flex-col items-center">
                            <i class="pi pi-percentage text-4xl text-cyan-500 mb-3"></i>
                            <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                {{ summary?.average_success_rate_24h ?? 0 }}%
                            </div>
                            <div class="text-sm text-surface-600 dark:text-surface-400">
                                Успешность за 24 часа
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Активные терминалы -->
            <div v-if="activeTerminals.length > 0" class="mb-8">
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0">
                        Активные терминалы
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <Card v-for="terminal in activeTerminals" :key="terminal.id" class="hover:shadow-lg transition-shadow duration-200">
                        <template #header>
                            <div class="flex items-center justify-between pb-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="flex items-center">
                                    <i class="pi pi-server text-2xl text-blue-500 mr-3"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                                            {{ terminal.name }}
                                        </h3>
                                        <p class="text-sm text-surface-600 dark:text-surface-400">
                                            {{ terminal.provider_name }} · {{ terminal.provider_integration }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <Button
                                        size="small"
                                        :label="terminal.available ? 'Включен' : 'Выключен'"
                                        :icon="terminal.available ? 'pi pi-check' : 'pi pi-times'"
                                        :severity="terminal.available ? 'success' : 'danger'"
                                        outlined
                                        @click="toggleTerminal(terminal)"
                                    />
                                </div>
                            </div>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-2 gap-4 mb-4 mt-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.total_requests) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Всего запросов
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.total_requests_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Запросов 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_requests) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Сделок
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_requests_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Сделок 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_deals) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Успешных сделок
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_deals_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Успешных сделок 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.success_rate || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.success_rate)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Выдача
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.success_rate_24h || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.success_rate_24h)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Выдача 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.deal_conversion_rate || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.deal_conversion_rate)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Конверсия
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.deal_conversion_rate_24h || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.deal_conversion_rate_24h)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Конверсия 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ Math.round(parseFloat(terminal.avg_response_time_24h) || 0) }}ms
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Время 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ terminal.trader_balance !== null ? formatNumber(terminal.trader_balance) : '—' }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Лимит USDT
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Button
                                    @click="openLogs(terminal)"
                                    icon="pi pi-code"
                                    label="Логи"
                                    severity="secondary"
                                    outlined
                                    class="flex-1"
                                />
                                <Button
                                    @click="openCallbacks(terminal)"
                                    icon="pi pi-cloud-download"
                                    label="Callbacks"
                                    severity="secondary"
                                    outlined
                                    class="flex-1"
                                />
                            </div>
                            <div class="mt-2">
                                <Button
                                    @click="openView(terminal)"
                                    icon="pi pi-eye"
                                    label="Посмотреть"
                                    severity="primary"
                                    class="w-full"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Неактивные терминалы -->
            <div v-if="inactiveTerminals.length > 0">
                <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0 mb-4">
                    Неактивные терминалы
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    <Card v-for="terminal in inactiveTerminals" :key="terminal.id" class="opacity-70">
                        <template #header>
                            <div class="flex items-center justify-between pb-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="flex items-center">
                                    <i class="pi pi-server text-2xl text-surface-400 mr-3"></i>
                                    <div>
                                        <h3 class="text-lg font-semibold text-surface-900 dark:text-surface-0">
                                            {{ terminal.name }}
                                        </h3>
                                        <p class="text-sm text-surface-600 dark:text-surface-400">
                                            {{ terminal.provider_name }} · {{ terminal.provider_integration }}
                                        </p>
                                    </div>
                                </div>
                                
                                <Button
                                    size="small"
                                    label="Выключен"
                                    icon="pi pi-times"
                                    severity="danger"
                                    outlined
                                    @click="toggleTerminal(terminal)"
                                />
                            </div>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-2 gap-4 mb-4 mt-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.total_requests) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Всего запросов
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.total_requests_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Запросов 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_requests) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Сделок
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_requests_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Сделок 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_deals) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Успешных сделок
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ formatNumber(terminal.successful_deals_24h) }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Успешных сделок 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.success_rate || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.success_rate)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Выдача
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.success_rate_24h || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.success_rate_24h)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Выдача 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.deal_conversion_rate || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.deal_conversion_rate)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Конверсия
                                    </div>
                                </div>

                                <div class="text-center">
                                    <Tag 
                                        :value="`${parseFloat(terminal.deal_conversion_rate_24h || 0).toFixed(1)}%`"
                                        :severity="getSuccessRateSeverity(terminal.deal_conversion_rate_24h)"
                                    />
                                    <div class="text-xs text-surface-600 dark:text-surface-400 mt-1">
                                        Конверсия 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ Math.round(parseFloat(terminal.avg_response_time_24h) || 0) }}ms
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Время 24 часа
                                    </div>
                                </div>

                                <div class="text-center">
                                    <div class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                                        {{ terminal.trader_balance !== null ? formatNumber(terminal.trader_balance) : '—' }}
                                    </div>
                                    <div class="text-xs text-surface-600 dark:text-surface-400">
                                        Баланс трейдера USDT
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Button
                                    @click="openLogs(terminal)"
                                    icon="pi pi-code"
                                    label="Логи"
                                    severity="secondary"
                                    outlined
                                    class="flex-1"
                                />
                                <Button
                                    @click="openCallbacks(terminal)"
                                    icon="pi pi-cloud-download"
                                    label="Callbacks"
                                    severity="secondary"
                                    outlined
                                    class="flex-1"
                                />
                            </div>
                            <div class="mt-2">
                                <Button
                                    @click="openView(terminal)"
                                    icon="pi pi-eye"
                                    label="Посмотреть"
                                    severity="primary"
                                    class="w-full"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Пустое состояние -->
            <div v-if="filteredTerminals.length === 0" class="text-center py-12">
                <i class="pi pi-inbox text-6xl text-surface-400 mb-4"></i>
                <h3 class="text-xl font-medium text-surface-900 dark:text-surface-0 mb-2">
                    Нет терминалов
                </h3>
                <p class="text-surface-600 dark:text-surface-400">
                    Добавьте терминалы для работы
                </p>
            </div>
        </div>
    </div>
</template>
