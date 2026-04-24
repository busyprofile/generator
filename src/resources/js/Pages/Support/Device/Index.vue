<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import DateTime from '@/Components/DateTime.vue';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";

const devices = usePage().props.devices;
const filters = usePage().props.filters;

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
        // Можно добавить toast уведомление
        alert('Токен скопирован в буфер обмена');
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Устройства" />

        <MainTableSection 
            title="Все устройства" 
            :data="devices" 
            :filters="filters"
        >
            <template v-slot:header>
                <FiltersPanel name="devices">
                    <InputFilter
                        name="search"
                        placeholder="Поиск (название, модель, производитель, Android ID)"
                        class="w-64"
                    />
                    <InputFilter
                        name="user"
                        placeholder="Пользователь (имя или email)"
                        class="w-64"
                    />
                </FiltersPanel>
            </template>

            <template v-slot:body>
                <div class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">ID</th>
                                <th scope="col" class="px-6 py-3">Пользователь</th>
                                <th scope="col" class="px-6 py-3">Название</th>
                                <th scope="col" class="px-6 py-3">Токен</th>
                                <th scope="col" class="px-6 py-3">Модель</th>
                                <th scope="col" class="px-6 py-3">Android</th>
                                <th scope="col" class="px-6 py-3">Производитель</th>
                                <th scope="col" class="px-6 py-3">Статус</th>
                                <th scope="col" class="px-6 py-3">Создан</th>
                                <th scope="col" class="px-6 py-3">Подключен</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="device in devices.data" :key="device.id" 
                                class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-gray-200">
                                    {{ device.id }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="text-gray-900 dark:text-gray-200">
                                        {{ device.user?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ device.user?.email || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-200">
                                    {{ device.name }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate max-w-36 text-gray-900 dark:text-gray-200">
                                            {{ device.token }}
                                        </span>
                                        <Button
                                            icon="pi pi-copy"
                                            text rounded severity="secondary"
                                            size="small"
                                            @click="copyToClipboard(device.token)"
                                            v-tooltip.top="'Копировать токен'"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-200">
                                    {{ device.device_model || '--' }}
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-200">
                                    {{ device.android_version || '--' }}
                                </td>
                                <td class="px-6 py-3 text-gray-900 dark:text-gray-200">
                                    <div v-if="device.manufacturer || device.brand">
                                        {{ device.manufacturer || '--' }}
                                        <span v-if="device.brand" class="text-xs text-gray-500 block">
                                            {{ device.brand }}
                                        </span>
                                    </div>
                                    <span v-else>--</span>
                                </td>
                                <td class="px-6 py-3">
                                    <Tag
                                        :value="device.android_id ? 'Подключено' : 'Не подключено'"
                                        :severity="device.android_id ? 'success' : 'warn'"
                                    />
                                </td>
                                <td class="px-6 py-3">
                                    <DateTime class="justify-start" :data="device.created_at"/>
                                </td>
                                <td class="px-6 py-3">
                                    <DateTime v-if="device.connected_at" class="justify-start" :data="device.connected_at"/>
                                    <span v-else class="text-gray-500">--</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </MainTableSection>
    </div>
</template> 