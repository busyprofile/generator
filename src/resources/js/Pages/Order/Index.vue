<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OrderStatus from "@/Components/OrderStatus.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import OrderModal from "@/Modals/OrderModal.vue";
import {useModalStore} from "@/store/modal.js";
import DateTime from "@/Components/DateTime.vue";
import {useViewStore} from "@/store/view.js";
import {ref, watch, computed } from "vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import EditOrderAmountModal from "@/Modals/Order/EditOrderAmountModal.vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import RefreshTableData from "@/Components/Table/RefreshTableData.vue";
import DateFilter from "@/Components/Filters/Pertials/DateFilter.vue";
import Chip from 'primevue/chip';
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useClipboard } from '@vueuse/core';

const viewStore = useViewStore();
const orders = ref(usePage().props.orders);
const modalStore = useModalStore();

const filtersVariants = ref(usePage().props.filtersVariants);

router.on('success', (event) => {
    orders.value = usePage().props.orders;
})

const reloadingTableData = ref(false);

const openOrderModal = (order) => {
    if (reloadingTableData.value) {
        return;
    }
    modalStore.openOrderModal({order_id: order.id})
}

const { copy, copied, isSupported } = useClipboard();

const copyToClipboard = (text) => {
    if (isSupported.value && text) {
        copy(text);
    }
};

const formatDetail = (detail, type) => {
    if (type && detail) {
         if (type.includes('card') && detail.length > 10) {
            return `**** ${detail.substring(detail.length - 4)}`;
        }
    }
    return detail || 'N/A';
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Сделки" />

        <MainTableSection
            title="Сделки"
            :data="orders"
        >
            <template v-slot:header>
                <div class="flex justify-between items-center">


                    <FiltersPanel name="orders">
                        <DateFilter name="startDate" title="Начальная дата"/>
                        <DateFilter name="endDate" title="Конечная дата"/>
                        <InputFilter
                            v-if="viewStore.isAdminViewMode"
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
                        <InputFilter
                            name="paymentDetail"
                            placeholder="Реквизит"
                        />
                        <DropdownFilter
                            name="detailTypes"
                            title="Тип реквизита"
                        />
                        <InputFilter
                            name="paymentGateway"
                            placeholder="Платежный метод"
                        />
                        <InputFilter
                            v-if="viewStore.isAdminViewMode"
                            name="user"
                            placeholder="Пользователь"
                        />
                        <DropdownFilter
                            name="orderStatuses"
                            :options="filtersVariants.orderStatuses"
                            title="Статусы"
                        />
                    </FiltersPanel>

                    <div class="flex justify-end ">
                        <RefreshTableData
                            @refresh-started="reloadingTableData = true"
                            @refresh-finished="reloadingTableData = false"
                        />
                    </div>


                </div>
            </template>
            <template v-slot:body>
                <DataTable
                    :value="orders?.data"
                    :loading="reloadingTableData"
                    stripedRows
                    class="w-full"
                    size="small"
                    @row-click="(e) => openOrderModal(e.data)"
                    rowHover
                >
                    <template #empty>
                        <div class="text-center py-10">
                            <i class="pi pi-inbox text-5xl text-muted-foreground mb-3"></i>
                            <p class="text-lg text-muted-foreground">Сделок не найдено.</p>
                        </div>
                    </template>
                    <Column header="Метод / UUID">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <GatewayLogo :img_path="data.payment_gateway_logo_path" :name="data.payment_gateway_name" class="w-7 h-7 flex-shrink-0" />
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-foreground truncate">{{ data.payment_detail_name }}</div>
                                    <DisplayUUID :uuid="data.uuid" class="text-xs text-muted-foreground" />
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Статус">
                        <template #body="{ data }">
                            <OrderStatus :status="data.status" :status_name="data.status_name" />
                        </template>
                    </Column>
                    <Column header="Реквизит">
                        <template #body="{ data }">
                            <Chip
                                v-if="data.payment_detail"
                                :label="formatDetail(data.payment_detail, data.payment_detail_type)"
                                class="text-xs cursor-pointer"
                                v-tooltip.top="copied ? 'Скопировано!' : 'Нажмите, чтобы скопировать'"
                                @click.stop="copyToClipboard(data.payment_detail)"
                            />
                            <span v-else class="text-xs text-muted-foreground italic">—</span>
                        </template>
                    </Column>
                    <Column header="Сумма">
                        <template #body="{ data }">
                            <span class="font-semibold text-foreground whitespace-nowrap">{{ data.amount }} {{ data.currency?.toUpperCase() }}</span>
                        </template>
                    </Column>
                    <Column header="Прибыль">
                        <template #body="{ data }">
                            <span class="text-sm font-medium text-foreground whitespace-nowrap">{{ data.total_profit }} {{ data.base_currency?.toUpperCase() }}</span>
                        </template>
                    </Column>
                    <Column v-if="viewStore.isAdminViewMode" header="Трейдер">
                        <template #body="{ data }">
                            <div class="text-sm text-foreground truncate max-w-[140px]" v-tooltip.top="data.trader_email">{{ data.trader_email }}</div>
                            <div class="text-xs text-muted-foreground">{{ data.device_name || '—' }}</div>
                        </template>
                    </Column>
                    <Column header="Создан">
                        <template #body="{ data }">
                            <DateTime class="justify-start text-sm" :data="data.created_at" />
                        </template>
                    </Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" text rounded size="small" @click.stop="openOrderModal(data)" v-tooltip.top="'Просмотреть'" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </MainTableSection>

        <OrderModal/>
        <ConfirmModal/>
        <EditOrderAmountModal/>
    </div>
</template>

 
<style scoped>
:deep(.p-calendar) {
    width: 100%;
}

:deep(.p-dropdown) {
    width: 100%;
}
.p-card  {
    padding: 0rem;
}
</style>