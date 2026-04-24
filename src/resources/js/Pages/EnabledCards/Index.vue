<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import FiltersSection from './Components/FiltersSection.vue';

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps({
    statistics: Object,
    filters: Object
});

// Имя для куки
const CURRENCY_COOKIE_NAME = 'selected_currency';

// Функция для получения значения из куки
const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
};

// Функция для установки куки
const setCookie = (name, value, days = 30) => {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = `expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
};

// Получаем сохраненную валюту из куки или устанавливаем первую доступную
const getInitialCurrency = () => {
    const savedCurrency = getCookie(CURRENCY_COOKIE_NAME);

    // Проверяем, существует ли сохраненная валюта в списке доступных
    if (savedCurrency && props.statistics.availableCurrencies.some(c => c.code === savedCurrency)) {
        return savedCurrency;
    }

    // Иначе возвращаем первую валюту из списка
    return props.statistics.availableCurrencies.length > 0
        ? props.statistics.availableCurrencies[0].code
        : null;
};

// Устанавливаем начальное значение валюты
const selectedCurrency = ref(getInitialCurrency());

// Сохраняем выбранную валюту в куки при изменении
watch(selectedCurrency, (newValue) => {
    if (newValue) {
        setCookie(CURRENCY_COOKIE_NAME, newValue);
    }
});

// Находим данные о свободном лимите для выбранной валюты
const selectedCurrencyLimit = computed(() => {
    if (!selectedCurrency.value) return null;

    return props.statistics.currencyLimits.find(item => item.code === selectedCurrency.value) || {
        code: selectedCurrency.value,
        symbol: props.statistics.availableCurrencies.find(c => c.code === selectedCurrency.value)?.symbol || '',
        total_free_limit: '0.00'
    };
});

// Находим данные о потенциальном лимите для выбранной валюты
const selectedPotentialLimit = computed(() => {
    if (!selectedCurrency.value) return null;

    return props.statistics.potentialLimits.find(item => item.code === selectedCurrency.value) || {
        code: selectedCurrency.value,
        symbol: props.statistics.availableCurrencies.find(c => c.code === selectedCurrency.value)?.symbol || '',
        total_potential_limit: '0.00'
    };
});

// Находим полную информацию о выбранной валюте
const selectedCurrencyInfo = computed(() => {
    return props.statistics.availableCurrencies.find(c => c.code === selectedCurrency.value) || null;
});

// Получаем группы статистики по минимальным лимитам для выбранной валюты
const minAmountStatsByGroups = computed(() => {
    if (!selectedCurrency.value || !props.statistics.minAmountStats) return [];

    return props.statistics.minAmountStats[selectedCurrency.value] || [];
});
</script>

<template>
    <div>
        <Head title="Включенные реквизиты" />

        <div class="mx-auto space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl text-gray-900 dark:text-white sm:text-4xl">Включенные реквизиты</h2>

                <!-- Селект валют -->
                <div class="flex items-center gap-2">
                    <label for="currency-select" class="text-gray-700 dark:text-gray-300">Валюта:</label>
                    <select
                        id="currency-select"
                        v-model="selectedCurrency"
                        class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    >
                        <option
                            v-for="currency in statistics.availableCurrencies"
                            :key="currency.code"
                            :value="currency.code"
                        >
                            {{ currency.name }} ({{ currency.symbol }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- Фильтры -->
            <FiltersSection :initial-filters="filters" />

            <!-- Статистика -->
            <div class="grid grid-cols-1 3xl:grid-cols-4 xl:grid-cols-2 gap-6 mt-6">
                <!-- Общее количество включенных реквизитов -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-plate shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Количество реквизитов</p>
                            <p class="text-2xl font-bold dark:text-white">{{ statistics.totalPaymentDetails }}</p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Свободный лимит по выбранной валюте -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-plate shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                Свободный лимит ({{ selectedCurrencyInfo?.symbol || 'Не выбрано' }})
                            </p>
                            <p class="text-2xl font-bold dark:text-white">
                                {{ selectedCurrencyLimit?.symbol }} {{ $page.props.auth.can_see_finances ? (selectedCurrencyLimit?.total_free_limit || '0.00') : '****' }}
                            </p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Потенциальный лимит по выбранной валюте -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-plate shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                Потенциальный лимит ({{ selectedCurrencyInfo?.symbol || 'Не выбрано' }})
                            </p>
                            <p class="text-2xl font-bold dark:text-white">
                                {{ selectedPotentialLimit?.symbol }} {{ $page.props.auth.can_see_finances ? (selectedPotentialLimit?.total_potential_limit || '0.00') : '****' }}
                            </p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900 p-3 rounded-full">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Баланс трейдеров -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-plate shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                Баланс трейдеров ({{ statistics.tradersBalance.symbol }})
                            </p>
                            <p class="md:flex grid gap-x-4">
                                <span class="flex items-center">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm mr-2">Всего:</span>
                                    <span class="font-bold dark:text-white">
                                        {{ statistics.tradersBalance.symbol }} {{ $page.props.auth.can_see_finances ? statistics.tradersBalance.total : '****' }}
                                    </span>
                                </span>
                                <span class="flex items-center">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm mr-2">Онлайн:</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                                        {{ statistics.tradersBalance.symbol }} {{ $page.props.auth.can_see_finances ? statistics.tradersBalance.online : '****' }}
                                    </span>
                                </span>
                            </p>
                        </div>
                        <div class="bg-primary/10 p-3 rounded-full">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Таблица статистики по группам минимальных лимитов -->
            <div class="mt-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Статистика по минимальным лимитам ({{ selectedCurrencyInfo?.symbol || 'Не выбрано' }})
                </h3>

                <div class="bg-white dark:bg-gray-800 rounded-plate shadow-sm overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    Минимальный лимит
                                </th>
                                <th scope="col" class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    Количество реквизитов
                                </th>
                                <th scope="col" class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    Свободный лимит
                                </th>
                                <th scope="col" class="px-6 py-3 text-gray-700 dark:text-gray-300">
                                    Потенциальный лимит
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(stats, key) in minAmountStatsByGroups" :key="key"
                                class="border-b dark:border-gray-700">
                                <th scope="row" class="px-6 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ stats.title }}
                                </th>
                                <td class="px-6 py-3 text-gray-900 dark:text-white">
                                    {{ stats.count }}
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-white">
                                    {{ selectedCurrencyInfo?.symbol }} {{ $page.props.auth.can_see_finances ? stats.free_limit : '****' }}
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-white">
                                    {{ selectedCurrencyInfo?.symbol }} {{ $page.props.auth.can_see_finances ? stats.potential_limit : '****' }}
                                </td>
                            </tr>
                            <!-- Если нет данных -->
                            <tr v-if="Object.keys(minAmountStatsByGroups).length === 0" class="text-center">
                                <td colspan="4" class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                    Нет данных для выбранной валюты
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
