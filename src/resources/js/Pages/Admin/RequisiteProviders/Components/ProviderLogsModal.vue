<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    provider: Object,
});

const emit = defineEmits(['close']);

const logs = ref([]);
const loading = ref(false);
const pagination = ref({});
const filters = reactive({
    provider_name: '',
    success: null,
    date_from: '',
    date_to: '',
});

// Сброс при открытии модала
watch(() => props.show, (newVal) => {
    if (newVal) {
        resetFilters();
        loadLogs();
    }
});

watch(() => props.provider, (newProvider) => {
    if (newProvider) {
        filters.provider_name = newProvider.name;
    } else {
        filters.provider_name = '';
    }
});

const resetFilters = () => {
    filters.provider_name = props.provider?.name || '';
    filters.success = null;
    filters.date_from = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString().split('T')[0]; // вчера
    filters.date_to = new Date().toISOString().split('T')[0]; // сегодня
};

const loadLogs = async (page = 1) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.provider_name) params.append('provider_name', filters.provider_name);
        if (filters.success !== null) params.append('success', filters.success);
        if (filters.date_from) params.append('date_from', filters.date_from);
        if (filters.date_to) params.append('date_to', filters.date_to);
        params.append('page', page);

        const response = await axios.get(`/admin/requisite-providers/logs?${params.toString()}`);
        logs.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
        };
    } catch (error) {
        console.error('Ошибка загрузки логов:', error);
    } finally {
        loading.value = false;
    }
};

const applyFilters = () => {
    loadLogs(1);
};

const changePage = (page) => {
    loadLogs(page);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('ru-RU');
};

const getProviderDisplayName = (name) => {
    const names = {
        'internal': 'Внутренний',
        'external_provider_1': 'Внешний провайдер 1',
        'external_provider_2': 'Внешний провайдер 2',
    };
    return names[name] || name;
};

const getStatusColor = (success) => {
    return success 
        ? 'bg-green-100 text-green-800 dark:text-green-200'
        : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200';
};

const getResponseTimeColor = (time) => {
    if (time < 1000) return 'text-green-600 dark:text-green-400';
    if (time < 3000) return 'text-primary';
    return 'text-red-600 dark:text-red-400';
};

const modalTitle = computed(() => {
    return props.provider 
        ? `Логи провайдера: ${getProviderDisplayName(props.provider.name)}`
        : 'Логи всех провайдеров';
});

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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ modalTitle }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Логи запросов к провайдерам реквизитов
                                    </p>
                                </div>
                            </div>
                        </div>
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

                    <!-- Фильтры -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Статус
                                </label>
                                <select
                                    v-model="filters.success"
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-gray-100 text-sm"
                                >
                                    <option :value="null">Все</option>
                                    <option :value="true">Успешные</option>
                                    <option :value="false">Ошибки</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Дата от
                                </label>
                                <input
                                    type="date"
                                    v-model="filters.date_from"
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-gray-100 text-sm"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Дата до
                                </label>
                                <input
                                    type="date"
                                    v-model="filters.date_to"
                                    class="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-gray-100 text-sm"
                                >
                            </div>

                            <div class="flex items-end">
                                <button
                                    @click="applyFilters"
                                    :disabled="loading"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Применить
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика -->
                    <div v-if="pagination.total" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Показано {{ pagination.from }}-{{ pagination.to }} из {{ pagination.total }} записей
                    </div>

                    <!-- Таблица логов -->
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Время
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Провайдер
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Статус
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Время ответа
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Попытка
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Тип запроса
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Ошибка
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="loading">
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center justify-center">
                                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Загрузка...
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-else-if="logs.length === 0">
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Логи не найдены
                                        </td>
                                    </tr>
                                    <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ formatDate(log.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ getProviderDisplayName(log.provider_name) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    getStatusColor(log.success)
                                                ]"
                                            >
                                                {{ log.success ? 'Успех' : 'Ошибка' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm" :class="getResponseTimeColor(log.response_time_ms)">
                                            {{ log.response_time_ms }}мс
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ log.retry_attempt }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ log.request_type || 'getRequisites' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                            <span v-if="log.error_message" :title="log.error_message">
                                                {{ log.error_message }}
                                            </span>
                                            <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Пагинация -->
                    <div v-if="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <button
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="pagination.current_page <= 1"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                            >
                                Предыдущая
                            </button>
                            <button
                                @click="changePage(pagination.current_page + 1)"
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                            >
                                Следующая
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Страница {{ pagination.current_page }} из {{ pagination.last_page }}
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button
                                        @click="changePage(pagination.current_page - 1)"
                                        :disabled="pagination.current_page <= 1"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                                    >
                                        <span class="sr-only">Предыдущая</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>

                                    <template v-for="page in Math.min(pagination.last_page, 5)" :key="page">
                                        <button
                                            @click="changePage(page)"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                page === pagination.current_page
                                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900/20 dark:border-blue-400'
                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'
                                            ]"
                                        >
                                            {{ page }}
                                        </button>
                                    </template>

                                    <button
                                        @click="changePage(pagination.current_page + 1)"
                                        :disabled="pagination.current_page >= pagination.last_page"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"
                                    >
                                        <span class="sr-only">Следующая</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template> 