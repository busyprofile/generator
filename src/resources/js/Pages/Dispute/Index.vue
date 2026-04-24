<script setup>
import {Head, router, useForm, usePage} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PaymentDetail from "@/Components/PaymentDetail.vue";
import DisputeStatus from "@/Components/DisputeStatus.vue";
import {useModalStore} from "@/store/modal.js";
import DisputeModal from "@/Modals/DisputeModal.vue";
import CancelDisputeModal from "@/Modals/CancelDisputeModal.vue";
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import MainTableSection from "@/Wrappers/MainTableSection.vue";
import DateTime from "@/Components/DateTime.vue";
import {useViewStore} from "@/store/view.js";
import ShowAction from "@/Components/Table/ShowAction.vue";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import InputFilter from "@/Components/Filters/Pertials/InputFilter.vue";
import FiltersPanel from "@/Components/Filters/FiltersPanel.vue";
import DropdownFilter from "@/Components/Filters/Pertials/DropdownFilter.vue";
import {ref, watch} from "vue";
import GatewayLogo from "@/Components/GatewayLogo.vue";
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const viewStore = useViewStore();
const modalStore = useModalStore();

const disputes = usePage().props.disputes;
const oldestDisputeCreatedAt = usePage().props.oldestDisputeCreatedAt;

const displayShortDetail = ref(getCookieValue('displayShortDetail', true));

function getCookieValue(name, defaultValue) {
    const currentRoute = route().current();
    const cookieName = `${name}_${currentRoute}`;
    const match = document.cookie.match(new RegExp('(^| )' + cookieName + '=([^;]+)'));
    return match ? match[2] === 'true' : defaultValue;
}

function updateDisplayShortDetailCookie() {
    const currentRoute = route().current();
    const cookieName = `displayShortDetail_${currentRoute}`;
    document.cookie = `${cookieName}=${displayShortDetail.value}; path=/; max-age=31536000`; // 1 год
}

// Следим за изменениями и обновляем cookie
watch(displayShortDetail, () => {
    updateDisplayShortDetailCookie();
});

const confirmAcceptDispute = (dispute) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите принять спор #' + dispute?.id + '?',
        body: 'В таком случае, сделка будет закрыта как оплаченная.',
        confirm_button_name: 'Принять спор',
        confirm: () => {
            useForm({}).patch(route('disputes.accept', dispute.id), {
                preserveScroll: true,
                onFinish: () => {
                    modalStore.closeAll()
                    router.visit(route(viewStore.adminPrefix + 'disputes.index'), {
                        only: ['disputes'],
                    })
                },
            });
        }
    });
}

const confirmRollbackDispute = (dispute) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите открыть спор #' + dispute?.id + '?',
        body: 'Референтная сделка не изменит свой статус.',
        confirm_button_name: 'Открыть спор',
        confirm: () => {
            useForm({}).patch(route('disputes.rollback', dispute.id), {
                preserveScroll: true,
                onFinish: () => {
                    modalStore.closeAll()
                    router.visit(route(viewStore.adminPrefix + 'disputes.index'), {
                        only: ['disputes'],
                    })
                },
            });
        }
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Споры" />

        <MainTableSection
            title="Споры по сделкам"
            :data="disputes"
        >
            <template v-slot:header>
                <div>
                    <FiltersPanel name="orders">
                        <InputFilter
                            name="uuid"
                            placeholder="UUID"
                        />
                        <InputFilter
                            name="externalID"
                            placeholder="Внешний ID"
                        />
                        <InputFilter
                            name="amount"
                            placeholder="Сумма"
                        />
                        <InputFilter
                            name="paymentDetail"
                            placeholder="Реквизит"
                        />
                        <InputFilter
                            v-if="viewStore.isAdminViewMode"
                            name="user"
                            placeholder="Пользователь"
                        />
                        <DropdownFilter
                            name="disputeStatuses"
                            title="Статусы"
                        />
                    </FiltersPanel>
                </div>
            </template>
            <template v-slot:body>
                <div v-if="viewStore.isAdminViewMode && oldestDisputeCreatedAt" class="text-sm text-muted-foreground mb-3 flex gap-2 items-center">
                    <span>Самый старый:</span>
                    <DateTime :data="oldestDisputeCreatedAt" :plural="true" />
                </div>
                <DataTable
                    :value="disputes?.data"
                    stripedRows
                    class="w-full"
                    size="small"
                    @row-click="(e) => modalStore.openDisputeModal({ dispute: e.data })"
                    rowHover
                >
                    <template #empty>
                        <div class="text-center py-10">
                            <i class="pi pi-inbox text-5xl text-muted-foreground mb-3"></i>
                            <p class="text-lg text-muted-foreground">Споров не найдено.</p>
                        </div>
                    </template>
                    <Column header="#">
                        <template #body="{ data }">
                            <span class="font-medium text-foreground">#{{ data.id }}</span>
                        </template>
                    </Column>
                    <Column header="Метод / Реквизит">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <GatewayLogo :img_path="data.payment_gateway.logo_path" :name="data.payment_gateway.name" class="w-7 h-7 flex-shrink-0" />
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-foreground">{{ data.payment_detail.name }}</div>
                                    <PaymentDetail
                                        :detail="data.payment_detail.detail"
                                        :type="data.payment_detail.type"
                                        :name="data.payment_detail.name"
                                        :short="true"
                                        class="text-xs text-muted-foreground"
                                    />
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Статус">
                        <template #body="{ data }">
                            <DisputeStatus :status="data.status" />
                        </template>
                    </Column>
                    <Column header="Сделка">
                        <template #body="{ data }">
                            <DisplayUUID :uuid="data.order.uuid" />
                        </template>
                    </Column>
                    <Column header="Сумма">
                        <template #body="{ data }">
                            <div class="font-semibold text-foreground whitespace-nowrap">{{ data.order.amount }} {{ data.order.currency.toUpperCase() }}</div>
                            <div class="text-xs text-muted-foreground whitespace-nowrap">{{ data.order.total_profit }} {{ data.order.base_currency.toUpperCase() }}</div>
                        </template>
                    </Column>
                    <Column v-if="viewStore.isAdminViewMode" header="Пользователь">
                        <template #body="{ data }">
                            <span class="text-sm text-foreground truncate max-w-[130px]" v-tooltip.top="data.user.email">{{ data.user.email }}</span>
                        </template>
                    </Column>
                    <Column header="Создан">
                        <template #body="{ data }">
                            <DateTime class="text-sm justify-start" :data="data.created_at" />
                        </template>
                    </Column>
                    <Column header="">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" text rounded size="small" @click.stop="modalStore.openDisputeModal({ dispute: data })" v-tooltip.top="'Просмотреть'" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </MainTableSection>

        <DisputeModal
            @accept="confirmAcceptDispute"
            @cancel="modalStore.openDisputeCancelModal({dispute:$event})"
            @rollback="confirmRollbackDispute"
        />

        <CancelDisputeModal/>
        <ConfirmModal/>
    </div>
</template>

<style scoped>
/* Remove old card styling, rely on PrimeVue PT or utility classes */
/* :deep(.p-card) ... and other similar rules are removed */

/* Keep any other necessary scoped styles, for example: */
:deep(.p-calendar) { /* If used on this page, example */
    width: 100%;
}

:deep(.p-dropdown) { /* If used on this page, example */
    width: 100%;
}
.p-card  {
    padding: 0rem;
}
</style>
