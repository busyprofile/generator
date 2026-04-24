<script setup>
import {Head, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditAction from "@/Components/Table/EditAction.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import {computed, ref} from "vue";

import SelectButton from 'primevue/selectbutton';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const markets = usePage().props.markets;

const selectedMarket = ref('bybit');

// Опции для SelectButton - все доступные маркеты
const marketOptions = ref([
    {name: 'ByBit', value: 'bybit'},
    {name: 'Rapira', value: 'rapira'},
    {name: 'Rapira топ-1 +10₽', value: 'rapira_top1_plus10'}
]);

const currencies = computed(() => {
    return markets[selectedMarket.value] || []; // Добавим || [] на случай отсутствия данных
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Валюты" />

        <MainTableSection
            title="Валюты"
            :data="currencies"
            :paginate="false"
        >
            <template v-slot:header>
                <div class="flex justify-start mb-4">
                    <SelectButton v-model="selectedMarket" :options="marketOptions" optionLabel="name" optionValue="value" aria-labelledby="basic" />
                </div>
            </template>
            <template v-slot:body>
                <DataTable :value="currencies" class="p-datatable-sm" stripedRows sortMode="multiple" resizableColumns columnResizeMode="fit" showGridlines>
                    <Column field="code" header="Код" :sortable="true">
                        <template #body="slotProps">
                            {{ slotProps.data.code.toUpperCase() }}
                        </template>
                    </Column>
                    <Column field="buy_price" header="Покупка USDT" :sortable="true">
                        <template #body="slotProps">
                            <span :class="slotProps.data.buy_price === '0.00' || !slotProps.data.buy_price ? 'text-red-500 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'">
                                {{ slotProps.data.buy_price || '-' }}
                            </span>
                        </template>
                    </Column>
                    <Column field="sell_price" header="Продажа USDT" :sortable="true">
                        <template #body="slotProps">
                            <span :class="slotProps.data.sell_price === '0.00' || !slotProps.data.sell_price ? 'text-red-500 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'">
                                {{ slotProps.data.sell_price || '-' }}
                            </span>
                        </template>
                    </Column>
                    <Column field="symbol" header="Символ" :sortable="true"></Column>
                    <Column field="name" header="Название" :sortable="true" style="min-width: 200px"></Column>
                    <Column header="Действия" bodyClass="text-right">
                        <template #body="slotProps">
                            <EditAction v-if="selectedMarket === 'bybit' && slotProps.data.code" :link="route('admin.currencies.price-parsers.edit', slotProps.data.code)"></EditAction>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </MainTableSection>
    </div>
</template>
