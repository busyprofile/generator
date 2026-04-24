<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OrderStatus from "@/Components/OrderStatus.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import DateTime from "@/Components/DateTime.vue";
import AddMobileIcon from "@/Components/AddMobileIcon.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";

import { ref, computed, onMounted } from 'vue';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import Calendar from 'primevue/calendar';

const orders = computed(() => usePage().props.orders);

// Переменные для дат экспорта
const startDate = ref(null);
const endDate = ref(null);

const orderPaymentLink = (payment_link) => {
    window.open(payment_link, '_blank');
};

const menu = ref();
const actionMenuItems = ref([]);

const toggleMenu = (event, order) => {
    actionMenuItems.value = [
        {
            label: 'Платежная страница',
            icon: 'pi pi-link',
            visible: !order.is_h2h,
            command: () => {
                orderPaymentLink(order.payment_link);
            }
        },
        {
            label: 'Отправить Callback',
            icon: 'pi pi-send',
            command: () => {
                router.post(route('payment.callback.resend', order.id), {}, {
                    preserveScroll: true,
                });
            }
        }
    ];
    menu.value.toggle(event);
};

const screenWidth = ref(window.innerWidth);
const updateScreenWidth = () => {
    screenWidth.value = window.innerWidth;
};

onMounted(() => {
    window.addEventListener('resize', updateScreenWidth);
});

const isMobile = computed(() => screenWidth.value < 768);

// Метод для экспорта платежей
const exportPayments = () => {
    if (!startDate.value || !endDate.value) {
        // Можно добавить уведомление пользователю, что даты не выбраны
        alert('Пожалуйста, выберите начальную и конечную даты для выгрузки.');
        return;
    }

    // Форматируем даты в YYYY-MM-DD, если Calendar не возвращает их в таком виде
    const formattedStartDate = startDate.value.toISOString().split('T')[0];
    const formattedEndDate = endDate.value.toISOString().split('T')[0];

    // Формируем URL для запроса. Имя маршрута 'merchant.payments.export' будет создано позже.
    // Предполагаем, что это маршрут для мерчантов. Если страница общая, логику определения роли нужно будет учесть.
    // или использовать общий маршрут, который внутри определит пользователя
    let exportUrl = route('merchant.payments.export', {
        startDate: formattedStartDate,
        endDate: formattedEndDate,
        // Дополнительные текущие фильтры, если это необходимо
        // ...usePage().props.filters // Пример, если фильтры доступны глобально и нужны для экспорта
    });
    
    // Открываем URL для скачивания файла
    window.location.href = exportUrl;
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Платежи" />

        <MainTableSection
            title="Платежи"
            :data="orders"
        >
            <template v-slot:button>
                <Button
                    label="Создать платеж"
                    icon="pi pi-plus"
                    @click="router.visit(route('payments.create'))"
                    :class="{'  !text-sm': isMobile, 'p-button-sm': isMobile }" 
                    class="p-button-primary  "
                />
            </template>
            <template v-slot:table-filters>
                <FiltersPanel name="payments">
                    <DropdownFilter
                        name="orderStatuses"
                        title="Статусы"
                    />
                    <InputFilter
                        name="externalID"
                        placeholder="Внешний ID"
                    />
                    <InputFilter
                        name="uuid"
                        placeholder="UUID"
                    />
                    <InputFilter
                        name="amount"
                        placeholder="Сумма"
                    />
                </FiltersPanel>
            </template>
            <template v-slot:custom-actions>
                <div class="flex flex-wrap gap-2 items-center p-2 border-t border-gray-200 dark:border-gray-700 md:border-t-0">
                    <Calendar v-model="startDate" placeholder="Дата С" dateFormat="yy-mm-dd" showIcon :class="{'w-full md:w-auto': isMobile}"/>
                    <Calendar v-model="endDate" placeholder="Дата По" dateFormat="yy-mm-dd" showIcon :class="{'w-full md:w-auto': isMobile}"/>
                    <Button
                        label="Выгрузить сделки"
                        icon="pi pi-download"
                        @click="exportPayments"
                        class="p-button-info"
                        :class="{'w-full md:w-auto': isMobile}"
                    />
                </div>
            </template>
            <template v-slot:body>
                <DataTable :value="orders.data" responsiveLayout="scroll" class="p-datatable-sm sm:rounded-table shadow-md p-2" :paginator="orders.total > orders.per_page" :rows="orders.per_page" :totalRecords="orders.total">
                    <Column field="uuid" header="UUID" style="min-width: 200px;">
                        <template #body="{data}">
                            <DisplayUUID :uuid="data.uuid"/>
                        </template>
                    </Column>
                    <Column header="Сумма" style="min-width: 150px;">
                        <template #body="{data}">
                            <div class="text-nowrap font-semibold text-gray-900 dark:text-gray-200">{{ data.amount }} {{ data.currency.toUpperCase() }}</div>
                            <div class="text-nowrap text-xs text-gray-500 dark:text-gray-400">Прибыль: {{ data.total_profit }} {{ data.base_currency.toUpperCase() }}</div>
                        </template>
                    </Column>
                    <Column field="merchant_profit" header="Прибыль мерчанта" style="min-width: 150px;">
                        <template #body="{data}">
                            <div class="text-nowrap">{{ data.merchant_profit }} {{ data.base_currency.toUpperCase() }}</div>
                        </template>
                    </Column>
                    <Column field="service_commission_amount_total" header="Комиссия" style="min-width: 120px;">
                        <template #body="{data}">
                            {{ data.service_commission_amount_total }} {{ data.base_currency.toUpperCase() }}
                        </template>
                    </Column>
                    <Column field="conversion_price" header="Курс" style="min-width: 100px;"></Column>
                    <Column header="Статус" style="min-width: 150px;">
                        <template #body="{data}">
                            <OrderStatus :status="data.status" :status_name="data.status_name"></OrderStatus>
                        </template>
                    </Column>
                    <Column field="external_id" header="Внешний ID" style="min-width: 150px;"></Column>
                    <Column header="Создан" style="min-width: 150px;">
                        <template #body="{data}">
                            <DateTime class="justify-start" :data="data.created_at"/>
                        </template>
                    </Column>
                    <Column header="Тип" style="min-width: 100px;">
                        <template #body="{data}">
                            <Tag :value="data.is_h2h ? 'H2H' : 'Merchant'" :severity="data.is_h2h ? 'info' : 'primary'" rounded></Tag>
                        </template>
                    </Column>
                    <Column header="Действия" class="text-center w-20">
                         <template #body="{data}">
                            <Button
                                icon="pi pi-ellipsis-v"
                                class="p-button-text p-button-rounded p-button-secondary"
                                @click="toggleMenu($event, data)"
                                aria-haspopup="true"
                                aria-controls="actions_menu"
                            />
                            <Menu ref="menu" id="actions_menu" :model="actionMenuItems" :popup="true" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
/* Optional: Add any specific styles if needed */
</style>
