<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import DateTime from '@/Components/DateTime.vue';
import FiltersPanel from '@/Components/Filters/FiltersPanel.vue';
import InputFilter from '@/Components/Filters/Pertials/InputFilter.vue';
import DropdownFilter from '@/Components/Filters/Pertials/DropdownFilter.vue';
import { ref } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    successTotal: Number,
    successToday: Number,
    failedTotal: Number,
    failedToday: Number,
    totalTotal: Number,
    totalToday: Number,
    avgResponseTimeTotal: Number,
    avgResponseTimeToday: Number,
    byTerminalToday: Object,
    byTerminalTotal: Object,
});

const expandedRows = ref({});

const toggleRow = (id) => {
    expandedRows.value[id] = !expandedRows.value[id];
};

// Функция для форматирования времени выполнения в секунды
const formatResponseTime = (timeMs) => {
    if (timeMs === undefined || timeMs === null) return '-';
    const seconds = timeMs / 1000;
    return seconds.toLocaleString('ru-RU', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3,
    }) + ' сек';
}

const applyFilters = (payload) => {
    router.get(route('admin.provider-logs.index'), payload, { preserveState: true, replace: true });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Логи провайдеров" />

        <MainTableSection
            title="Логи провайдеров"
            :data="logs"
        >
            <template #table-filters>
                <FiltersPanel name="provider-logs" @apply="applyFilters">
                    <InputFilter name="provider" placeholder="Провайдер (имя или id)" />
                    <InputFilter name="providerTerminalId" placeholder="ID терминала" />
                    <InputFilter name="status" placeholder="Статус (success/fail)" />
                </FiltersPanel>
            </template>

            <template #body>
                <!-- Панель статистики -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Статистика запросов</h2>

                    <!-- Карточки статистики -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Успешные запросы сегодня -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Успешно сегодня</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ successToday }}</p>
                                </div>
                                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Неудачные запросы сегодня -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Ошибок сегодня</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ failedToday }}</p>
                                </div>
                                <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Успешные запросы всего -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Успешно всего</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ successTotal }}</p>
                                </div>
                                <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Неудачные запросы всего -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Ошибок всего</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ failedTotal }}</p>
                                </div>
                                <div class="bg-red-100 dark:bg-red-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Среднее время ответа -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Ср. время ответа сегодня</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatResponseTime(avgResponseTimeToday) }}</p>
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Ср. время ответа всего</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatResponseTime(avgResponseTimeTotal) }}</p>
                                </div>
                                <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика по терминалам -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4" v-if="byTerminalToday && Object.keys(byTerminalToday).length > 0 || byTerminalTotal && Object.keys(byTerminalTotal).length > 0">
                        <!-- Сегодня по терминалам -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">По терминалам сегодня</h3>
                            <div class="space-y-2">
                                <div v-for="(stats, terminalName) in byTerminalToday" :key="'today-' + terminalName" class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">{{ terminalName }}</span>
                                    <div class="flex gap-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ stats.successful }}</span>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">{{ stats.failed }}</span>
                                    </div>
                                </div>
                                <div v-if="!byTerminalToday || Object.keys(byTerminalToday).length === 0" class="text-gray-500 dark:text-gray-400">
                                    Нет данных
                                </div>
                            </div>
                        </div>

                        <!-- Всего по терминалам -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-plate shadow-md">
                            <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">По терминалам всего</h3>
                            <div class="space-y-2">
                                <div v-for="(stats, terminalName) in byTerminalTotal" :key="'total-' + terminalName" class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300">{{ terminalName }}</span>
                                    <div class="flex gap-2">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">{{ stats.successful }}</span>
                                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">{{ stats.failed }}</span>
                                    </div>
                                </div>
                                <div v-if="!byTerminalTotal || Object.keys(byTerminalTotal).length === 0" class="text-gray-500 dark:text-gray-400">
                                    Нет данных
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Провайдер</th>
                                <th class="px-6 py-3">Терминал</th>
                                <th class="px-6 py-3">Тип запроса</th>
                                <th class="px-6 py-3">Время ответа</th>
                                <th class="px-6 py-3">Статус</th>
                                <th class="px-6 py-3">Создан</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="log in logs.data" :key="log.id">
                                <tr
                                    class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/75"
                                    @click.stop="toggleRow(log.id)"
                                >
                                    <td class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                        {{ log.id }}
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ log.provider?.name }} (ID: {{ log.provider?.id }})
                                    </td>
                                    <td class="px-6 py-3">
                                        <span v-if="log.provider_terminal">
                                            {{ log.provider_terminal.name }} (ID: {{ log.provider_terminal.id }})
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ log.request_type ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <span
                                            :class="log.response_time_ms
                                                ? (log.response_time_ms < 1000 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                : log.response_time_ms < 3000 ? 'bg-primary/10 text-primary'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300')
                                                : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                                            class="text-xs font-medium px-2.5 py-0.5 rounded text-nowrap"
                                        >
                                            {{ formatResponseTime(log.response_time_ms) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span
                                            :class="log.is_success
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
                                            class="text-xs font-medium px-2.5 py-0.5 rounded-full"
                                        >
                                            {{ log.is_success ? 'Успешно' : 'Ошибка' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <DateTime :data="log.created_at" show-time />
                                    </td>
                                </tr>
                                <tr v-if="expandedRows[log.id]" class="bg-gray-50 dark:bg-gray-700">
                                    <td colspan="7" class="px-6 py-4">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Детали</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div v-if="log.request_data">
                                                <div class="text-gray-700 dark:text-gray-300 mb-1">Запрос:</div>
                                                <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto max-h-40 text-xs">{{ JSON.stringify(log.request_data, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.response_data">
                                                <div class="text-gray-700 dark:text-gray-300 mb-1">Ответ:</div>
                                                <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto max-h-40 text-xs">{{ JSON.stringify(log.response_data, null, 2) }}</pre>
                                            </div>
                                        </div>
                                        <div v-if="log.error_message" class="mt-4">
                                            <div class="text-gray-700 dark:text-gray-300 mb-1">Сообщение об ошибке:</div>
                                            <div class="text-red-600 dark:text-red-400">{{ log.error_message }}</div>
                                        </div>
                                        <div v-if="log.provider_response_status_code || log.provider_response_text" class="mt-4">
                                            <div class="text-gray-700 dark:text-gray-300 mb-1">Ответ провайдера (raw):</div>
                                            <div v-if="log.provider_response_status_code" class="text-gray-900 dark:text-gray-200 mb-1">
                                                HTTP: {{ log.provider_response_status_code }}
                                            </div>
                                            <pre
                                                v-if="log.provider_response_text"
                                                class="bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto max-h-40 text-xs whitespace-pre-wrap"
                                            >{{ log.provider_response_text }}</pre>
                                            <div v-else class="text-gray-500 dark:text-gray-400">—</div>
                                        </div>
                                        <div v-if="log.retry_attempt > 1" class="mt-4">
                                            <div class="text-gray-700 dark:text-gray-300 mb-1">Попытка:</div>
                                            <div class="text-gray-900 dark:text-gray-200">{{ log.retry_attempt }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
