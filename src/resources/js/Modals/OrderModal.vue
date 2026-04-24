<script setup>
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { useModalStore } from "@/store/modal.js";
import { storeToRefs } from "pinia";
import { useViewStore } from "@/store/view.js";
import { ref, computed } from "vue";
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Fieldset from 'primevue/fieldset';
import ProgressSpinner from 'primevue/progressspinner';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';
import PaymentDetail from "@/Components/PaymentDetail.vue";

const viewStore = useViewStore();
const modalStore = useModalStore();
const { orderModal } = storeToRefs(modalStore);
const user = usePage().props.auth.user;
const order = ref(null);
const loading = ref(false);
const paymentProcessing = ref(false);

const closeModal = () => {
    order.value = null;
    modalStore.closeModal('order');
};

const confirmAcceptOrder = (order) => {
    paymentProcessing.value = true;
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите закрыть сделку как оплаченную?',
        confirm_button_name: 'Платеж поступил',
        confirm: () => {
            let routeName = viewStore.isSupportViewMode ? 'support.orders.accept' : 'orders.accept';
            
            useForm({}).patch(route(routeName, order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    modalStore.closeAll()
                    // Redirect to the appropriate order list based on role
                    let redirectListRoute = viewStore.isSupportViewMode ? route('support.orders.index') :
                                            (viewStore.isAdminViewMode ? route('admin.orders.index') :
                                            route('orders.index')); // Default to trader's order list
                    router.visit(redirectListRoute, {
                        only: ['orders'],
                    })
                },
                onError: () => {
                    paymentProcessing.value = false;
                },
            })
        },
        cancel: () => {
            paymentProcessing.value = false;
        }
    });
}

const confirmCreateDispute = (order) => {
    modalStore.openConfirmModal({
        title: 'Вы уверены что хотите открыть спор по сделке?',
        confirm_button_name: 'Открыть спор',
        confirm: () => {
            let routeName = '';
            if (viewStore.isSupportViewMode) {
                routeName = 'support.disputes.store';
            } else if (viewStore.isAdminViewMode) {
                routeName = 'admin.disputes.store';
            } else {
                console.error('Cannot determine role for creating dispute.');
                return;
            }
            useForm({}).post(route(routeName, order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    modalStore.closeAll()
                    let redirectRoute = viewStore.isAdminViewMode ? route('admin.orders.index') : route('support.orders.index');
                    router.visit(redirectRoute, {
                        only: ['orders'],
                    })
                },
            })
        }
    });
}

const orderPaymentLink = (payment_link) => {
    window.open(payment_link, '_blank')
}

const show = async () => {
    let order_id = orderModal.value.params.order_id;
    if (order.value?.id !== order_id || !order.value) {
        order.value = null;
        loading.value = true;
        try {
            const response = await axios.get(route('orders.show', order_id));
            if (response.data.success) {
                order.value = response.data.data.order;
            } else {
                console.error("Failed to load order:", response.data.message);
                closeModal();
            }
        } catch (error) {
            console.error("Error fetching order:", error);
            closeModal();
        } finally {
            loading.value = false;
        }
    }
};

const statusSeverity = computed(() => {
    if (!order.value) return 'info';
    switch (order.value.status) {
        case 'completed':
        case 'success':
            return 'success';
        case 'pending':
        case 'processing':
            return 'warning';
        case 'failed':
        case 'canceled':
        case 'error':
            return 'danger';
        default:
            return 'info';
    }
});

const statusIcon = computed(() => {
    if (!order.value) return 'pi pi-question-circle';
    switch (order.value.status) {
        case 'completed':
        case 'success':
            return 'pi pi-check-circle';
        case 'pending':
        case 'processing':
            return 'pi pi-spin pi-spinner';
        case 'failed':
        case 'canceled':
        case 'error':
            return 'pi pi-times-circle';
        default:
            return 'pi pi-info-circle';
    }
});

const statusLabel = computed(() => {
    if (!order.value) return 'Загрузка...';
    switch (order.value.status) {
        case 'completed':
        case 'success':
            return 'Платеж зачислен';
        case 'pending':
            return 'Ожидание платежа';
        case 'processing':
            return 'В обработке';
        case 'failed':
            return 'Платеж не удался';
        case 'canceled':
            return 'Отменен';
        case 'error':
            return 'Ошибка';
        default:
            return order.value.status_name || 'Неизвестный статус';
    }
});

const vTooltip = Tooltip;
</script>

<template>
    <Dialog
        :visible="!!orderModal.showed"
        @update:visible="!$event && closeModal()"
        modal
        :header="'Детали сделки #' + (order ? order.uuid_short : '...')"
        :style="{ width: '30rem' }"
        :breakpoints="{ '1199px': '75vw', '767px': '90vw' }"
        @show="show"
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
    >
        <div v-if="loading" class="flex justify-center items-center p-8">
            <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" />
        </div>

        <template v-else-if="order">
            <div class="flex flex-col items-center justify-center mb-6 p-4 bg-surface-100 dark:bg-surface-700 rounded-lg">
                <Avatar :icon="statusIcon" size="xlarge" shape="circle" :class="{
                    'bg-green-500 dark:bg-green-400 text-white': statusSeverity === 'success',
                    'bg-primary text-primary-foreground': statusSeverity === 'warning',
                    'bg-red-500 dark:bg-red-400 text-white': statusSeverity === 'danger',
                    'bg-blue-500 dark:bg-red-400 text-white': statusSeverity === 'info',
                }" />
                <p class="mt-2 mb-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ statusLabel }}</p>
                <p v-if="order.finished_at" class="text-sm text-gray-500 dark:text-gray-400">
                    Завершен: {{ new Date(order.finished_at).toLocaleString() }}
                </p>
                <p v-else-if="order.status === 'pending' || order.status === 'processing'" class="text-sm text-gray-500 dark:text-gray-400">
                    Истекает: {{ new Date(order.expires_at).toLocaleString() }}
                </p>
            </div>

            <!-- Main Order Info - Single Line Layout -->
            <div class="space-y-3 mb-4">
                 <div class="field flex justify-between items-baseline">
                     <span class="text-sm text-gray-500 dark:text-gray-400">UUID:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 break-all text-right">{{ order.uuid }}</span>
                 </div>

                 <div v-if="viewStore.isAdminViewMode || viewStore.isSupportViewMode" class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Внешний ID:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 break-all text-right">{{ order.external_id || '-' }}</span>
                 </div>

                 <div v-if="(viewStore.isAdminViewMode || viewStore.isSupportViewMode) && order.provider_order_id" class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">ID у провайдера:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 break-all text-right">{{ order.provider_order_id }}</span>
                 </div>

                 <div class="field flex justify-between items-center"> <!-- Use items-center for button alignment -->
                    <span class="text-sm text-gray-500 dark:text-gray-400">Сумма:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 flex items-center gap-2 text-right">
                         <span>{{ order.amount }} {{order.currency.toUpperCase()}}</span>
                         <Button
                             v-if="order.canEditAmount"
                             icon="pi pi-pencil"
                             text
                             rounded
                             size="small"
                             class="p-0 w-6 h-6"
                             v-tooltip.top="'Редактировать сумму'"
                             @click.prevent="modalStore.openEditOrderAmountModal({order: order})"
                         />
                     </span>
                 </div>

                <div class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Сумма в {{ order.base_currency.toUpperCase() }}:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.total_profit }} {{order.base_currency.toUpperCase()}}</span>
                </div>

                <div class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Курс:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.conversion_price }} {{order.currency.toUpperCase()}}</span>
                 </div>

                 <div v-if="viewStore.isAdminViewMode" class="field flex justify-between items-baseline">
                     <span class="text-sm text-gray-500 dark:text-gray-400">Мерчант:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 truncate text-right" v-tooltip.top="order.merchant.name + ' (id:' + order.merchant.id + ')'" :title="order.merchant.name + ' (id:' + order.merchant.id + ')'">{{ order.merchant.name }} (id:{{ order.merchant.id }})</span>
                 </div>

                 <div v-if="viewStore.isAdminViewMode" class="field flex justify-between items-baseline">
                     <span class="text-sm text-gray-500 dark:text-gray-400">Трейдер:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 truncate text-right" v-tooltip.top="order.user.email" :title="order.user.email">{{ order.user.email }}</span>
                 </div>

                 <div class="field flex justify-between items-baseline">
                     <span class="text-sm text-gray-500 dark:text-gray-400">Метод:</span>
                    <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.payment_gateway_name }}</span>
                 </div>

                 <div class="field flex justify-between items-start"> <!-- Use items-start for multiline detail -->
                    <span class="text-sm text-gray-500 dark:text-gray-400 mt-1">Реквизиты:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">
                         <PaymentDetail :detail="order.payment_detail" :copyable="false" :type="order.payment_detail_type"></PaymentDetail>
                     </span>
                 </div>

                 <div class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Создан:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">{{ new Date(order.created_at).toLocaleString() }}</span>
                 </div>

                 <div class="field flex justify-between items-baseline">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Истекает:</span>
                     <span class="text-base font-medium text-gray-900 dark:text-gray-200 text-right">{{ new Date(order.expires_at).toLocaleString() }}</span>
                 </div>

                 <div v-if="(viewStore.isAdminViewMode || viewStore.isSupportViewMode) && !order.is_h2h && order.payment_link" class="field flex justify-between items-center">
                     <span class="text-sm text-gray-500 dark:text-gray-400">Страница оплаты:</span>
                     <Button
                        label="Перейти"
                        icon="pi pi-external-link"
                         link
                         size="small"
                         class="p-0 h-auto"
                        @click="orderPaymentLink(order.payment_link)"
                    />
                 </div>
             </div>

             <template v-if="(viewStore.isAdminViewMode || viewStore.isSupportViewMode) && order.amount_updates_history && order.amount_updates_history.length > 0">
                 <Divider align="left" type="dashed" class="my-4"><b>История изменений суммы</b></Divider>
                 <DataTable :value="order.amount_updates_history" size="small" stripedRows responsiveLayout="scroll" class="text-sm p-datatable-sm">
                     <Column field="old_amount" header="Старая сумма">
                         <template #body="slotProps">
                             {{ slotProps.data.old_amount }} {{ order.currency.toUpperCase() }}
                         </template>
                     </Column>
                     <Column field="new_amount" header="Новая сумма">
                         <template #body="slotProps">
                             {{ slotProps.data.new_amount }} {{ order.currency.toUpperCase() }}
                         </template>
                     </Column>
                     <Column field="updated_at" header="Дата изменения">
                         <template #body="slotProps">
                             {{ new Date(slotProps.data.updated_at).toLocaleString() }}
                         </template>
                     </Column>
                 </DataTable>
             </template>

             <!-- Profit Details Section -->
             <template v-if="viewStore.isAdminViewMode || viewStore.isSupportViewMode">
                 <Divider align="left" type="dashed" class="my-4"><b>Детализация прибыли</b></Divider>
                 <div class="space-y-2 text-sm">
                     <div v-if="viewStore.isAdminViewMode" class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Прибыль мерчанта:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.merchant_profit }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                     <div class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Прибыль трейдера:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.trader_profit }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                     <div v-if="viewStore.isAdminViewMode" class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Прибыль сервиса:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.service_profit }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                     <div class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Трейдер заплатил:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.trader_paid_for_order }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                     <div class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Ком-ия трейдера:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.trader_commission_rate }} %</span>
                     </div>
                     <div v-if="viewStore.isAdminViewMode" class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Полная ком-ия сервиса:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.total_service_commission_rate }} %</span>
                     </div>
                 </div>
             </template>

             <!-- Тимлидеры трейдера -->
             <template v-if="(viewStore.isAdminViewMode || viewStore.isSupportViewMode) && (order.additional_profits && order.additional_profits.length > 0)">
                 <Divider align="left" type="dashed" class="my-4"><b>Тимлидеры трейдера</b></Divider>
                 <div class="space-y-2">
                     <div v-for="(profitRecord, index) in order.additional_profits" :key="profitRecord.team_leader.id" class="flex items-center justify-between gap-4">
                         <span class="text-sm text-gray-500 dark:text-gray-400 truncate flex items-center" :title="profitRecord.team_leader.email">
                             <i class="pi pi-user mr-1 text-xs"></i>Тимлидер #{{ index + 1 }}: {{ profitRecord.team_leader.email }}
                         </span>
                         <span class="text-sm font-medium text-gray-900 dark:text-gray-200 whitespace-nowrap">
                             {{ profitRecord.profit_amount }} {{order.base_currency.toUpperCase()}} ({{ profitRecord.commission_rate }}%)
                         </span>
                     </div>
                 </div>
             </template>

             <!-- Тимлидеры мерчанта -->
             <template v-if="(viewStore.isAdminViewMode || viewStore.isSupportViewMode) && order.merchant_team_leaders && order.merchant_team_leaders.length">
                 <Divider align="left" type="dashed" class="my-4"><b>Тимлидеры мерчанта</b></Divider>
                 <div class="space-y-2">
                     <div v-for="(tl, idx) in order.merchant_team_leaders" :key="tl.email" class="flex items-center justify-between gap-4">
                         <span class="text-sm text-gray-500 dark:text-gray-400 truncate flex items-center" :title="tl.email">
                            <i class="pi pi-user mr-1 text-xs"></i>Тимлидер #{{ idx + 1 }}: {{ tl.email }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200 whitespace-nowrap">
                            {{ tl.profit }} {{order.base_currency.toUpperCase()}} ({{ tl.commission }}%)
                        </span>
                     </div>
                 </div>
             </template>

             <!-- Finance Section (Non-Admin) -->
             <template v-else>
                 <Divider align="left" type="dashed" class="my-4"><b>Финансы</b></Divider>
                 <div class="space-y-2 text-sm">
                     <div class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Списано со счета:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.trader_paid_for_order }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                     <div class="field flex justify-between items-baseline">
                         <span class="text-gray-500 dark:text-gray-400">Прибыль:</span>
                         <span class="font-medium text-gray-900 dark:text-gray-200 text-right">{{ order.trader_profit }} {{order.base_currency.toUpperCase()}}</span>
                     </div>
                 </div>
             </template>

             <template v-if="order.sms_log">
                 <Fieldset :legend="'SMS Лог от ' + order.sms_log.sender + ' (' + new Date(order.sms_log.created_at).toLocaleString() + ')'" :toggleable="true" :collapsed="true" class="mt-4 mb-4 text-sm">
                     <p class="m-0 p-2 text-gray-600 dark:text-gray-300 bg-surface-50 dark:bg-surface-700 rounded-md whitespace-pre-wrap break-words">
                         {{ order.sms_log.message }}
                     </p>
                 </Fieldset>
             </template>
        </template>

        <template #footer v-if="order && ((order.status === 'pending' || order.status === 'fail' || viewStore.isAdminViewMode || viewStore.isSupportViewMode))">
            <div class="flex justify-end flex-col md:flex-row w-full md:w-auto gap-2 pt-4 border-t border-surface-200 dark:border-surface-700">
                <template v-if="!order.has_dispute">
                    <Button
                        v-if="(order.status === 'pending' || (order.status === 'fail' && (viewStore.isAdminViewMode || viewStore.isSupportViewMode)))"
                        label="Оплачен"
                        icon="pi pi-check"
                        severity="success"
                        @click.prevent="confirmAcceptOrder(order)"
                        :disabled="loading"
                        :loading="paymentProcessing"
                    />
                    <Button
                        v-if="viewStore.isAdminViewMode || viewStore.isSupportViewMode"
                        label="Открыть спор"
                        icon="pi pi-exclamation-triangle"
                        severity="warning"
                        @click.prevent="confirmCreateDispute(order)"
                        :disabled="loading"
                    />
                </template>
                <template v-else>
                    <div class="flex flex-col items-end">
                        <Tag severity="warn" value="По этой сделке открыт спор"></Tag>
                        <Link
                            @click="closeModal()"
                            :href="route(viewStore.adminPrefix + 'disputes.index')"
                            class="p-button p-button-link p-button-sm mt-1"
                        >
                            Перейти к спорам <i class="pi pi-arrow-right ml-1"></i>
                        </Link>
                    </div>
                </template>
                <Button
                    label="Закрыть"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    @click="closeModal"
                    :disabled="loading"
                />
            </div>
        </template>
        <template #footer v-else-if="order">
            <div class="flex justify-end gap-2 pt-4 border-t border-surface-200 dark:border-surface-700">
                <Button
                    label="Закрыть"
                    icon="pi pi-times"
                    severity="secondary"
                    text
                    @click="closeModal"
                />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* Scoped styles for fine-tuning if needed */
/* Ensure DataTable adapts */
:deep(.p-datatable-sm .p-datatable-tbody > tr > td) {
    padding: 0.5rem 0.5rem; /* Adjust padding for small datatable */
}
</style>
