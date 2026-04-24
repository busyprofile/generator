<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import DateTime from '@/Components/DateTime.vue';
import FiltersPanel from '@/Components/Filters/FiltersPanel.vue';
import InputFilter from '@/Components/Filters/Pertials/InputFilter.vue';
import { ref } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

const expandedRows = ref({});

const toggleRow = (id) => {
    expandedRows.value[id] = !expandedRows.value[id];
};

const applyFilters = (payload) => {
    router.get(route('admin.provider-callback-logs.index'), payload, { preserveState: true, replace: true });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Колбеки провайдеров" />

        <MainTableSection
            title="Колбеки провайдеров"
            :data="logs"
        >
            <template #table-filters>
                <FiltersPanel name="provider-callback-logs" @apply="applyFilters">
                    <InputFilter name="provider" placeholder="Провайдер (имя или id)" />
                    <InputFilter name="provider_terminal_id" placeholder="ID терминала" />
                    <InputFilter name="status_code" placeholder="HTTP код" />
                </FiltersPanel>
            </template>

            <template #body>
                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Провайдер</th>
                                <th class="px-6 py-3">Терминал</th>
                                <th class="px-6 py-3">HTTP код</th>
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
                                        {{ log.provider_real_name ?? log.provider_name ?? '—' }} (ID: {{ log.provider_id ?? '—' }})
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ log.provider_terminal_id ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3">
                                        {{ log.status_code ?? '-' }}
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
                                    <td colspan="6" class="px-6 py-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div v-if="log.request_data">
                                                <div class="text-gray-700 dark:text-gray-300 mb-1">Тело запроса:</div>
                                                <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto max-h-40 text-xs">{{ JSON.stringify(log.request_data, null, 2) }}</pre>
                                            </div>
                                            <div v-if="log.response_data">
                                                <div class="text-gray-700 dark:text-gray-300 mb-1">Ответ:</div>
                                                <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto max-h-40 text-xs">{{ JSON.stringify(log.response_data, null, 2) }}</pre>
                                            </div>
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
