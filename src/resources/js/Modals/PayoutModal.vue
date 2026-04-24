<script setup>
import PaymentDetail from "@/Components/PaymentDetail.vue";
import {usePage} from "@inertiajs/vue3";
import {useModalStore} from "@/store/modal.js";
import {storeToRefs} from "pinia";
import {useViewStore} from "@/store/view.js";
import {computed, nextTick, onMounted, ref, watch} from "vue";

// PrimeVue components added
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const viewStore = useViewStore();
const modalStore = useModalStore();
const { payoutModal } = storeToRefs(modalStore);
const user = usePage().props.auth.user;

const closeModal = () => {
    modalStore.closeModal('payout');
};

const payout = computed(() => {
    // Ensure params and payout exist before accessing
    return payoutModal.value?.params?.payout;
})

const showReceipt = () => {
    if (payout.value?.receipt_url) {
        window.open(payout.value.receipt_url, '_blank').focus();
    }
};

// Computed properties for Dialog
const isVisible = computed({
    get: () => payoutModal.value.showed,
    set: (value) => {
        if (!value) {
            closeModal();
        }
    }
});

const dialogTitle = computed(() => payout.value ? `Выплата #${payout.value.id}` : 'Выплата');

</script>

<template>
    <Dialog
        v-if="payout" 
        v-model:visible="isVisible"
        modal
        :header="dialogTitle"
        :style="{ width: '35rem' }" 
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
        @hide="closeModal"
    >
        <!-- Removed ModalHeader & ModalBody wrappers -->
        <!-- Removed outer form tag as it was not submitting -->
        <div v-if="payout" class="mx-auto max-w-screen-xl px-2 2xl:px-0 py-4"> 
            <div class="mx-auto max-w-3xl">
                <div>
                    <div class="space-y-5">
                        <!-- Status Display Section -->
                        <div class="text-center mb-4"> 
                            <div v-if="payout.status === 'success'">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-16 h-16 text-green-400 dark:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <p class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-300">Выплата завершена</p>
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ payout.finished_at }}</p>
                            </div>
                            <div v-else-if="payout.status === 'fail'">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-16 h-16 text-red-500 dark:text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-300">Выплата отменена</p>
                                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ payout.finished_at }}</p>
                            </div>
                            <div v-else-if="payout.status === 'pending'">
                                <div class="flex items-center justify-center mb-2">
                                    <svg class="w-16 h-16 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-300">Выплата еще не произведена</p>
                            </div>
                        </div>
                        
                        <!-- Hold Status Section -->
                        <div v-if="payout.funds_on_hold?.is_on_hold && (viewStore.isTraderViewMode || viewStore.isAdminViewMode)" class="flex justify-center items-center gap-2 border-y border-dashed border-surface-200 dark:border-surface-700 p-3 mb-4"> 
                            <div>
                                <i class="pi pi-spin pi-clock text-2xl text-primary"></i> 
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-900 dark:text-gray-300 font-semibold">
                                    {{ payout.funds_on_hold.hold_until }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Средства на удержании
                                </div>
                            </div>
                        </div>
                        
                        <!-- Details Section -->
                        <div class="space-y-4">
                            <div class="space-y-2 text-sm"> 
                                <dl class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">UUID</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-300">{{ payout.uuid }}</dd>
                                </dl>
                                
                                <!-- Merchant View Details -->
                                <template v-if="viewStore.isMerchantViewMode">
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Внешний ID</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">{{ payout.external_id || '-' }}</dd> 
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Выплата клиенту</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.payout_amount }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Списание со счета</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.liquidity_amount }} {{ payout.liquidity_currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Курс обмена</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right"> 
                                            {{ payout.exchange_price }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Комиссия сервиса</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.service_commission_amount }} {{ payout.liquidity_currency?.toUpperCase() }} ({{ payout.service_commission_rate }}%)
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Реквизит</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            <PaymentDetail v-if="payout.detail_type" :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Держатель</dt> 
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.detail_initials || '-' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Платежный метод</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.payment_gateway?.name }} <span v-if="payout.sub_payment_gateway">({{ payout.sub_payment_gateway.name }})</span>
                                        </dd>
                                    </dl>
                                </template>
                                
                                <!-- Trader View Details -->
                                <template v-if="viewStore.isTraderViewMode">
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Сумма выплаты</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.payout_amount }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Реквизит</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            <PaymentDetail v-if="payout.detail_type" :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Держатель</dt> 
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.detail_initials || '-' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Платежный метод</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.payment_gateway?.name }} <span v-if="payout.sub_payment_gateway">({{ payout.sub_payment_gateway.name }})</span>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Зачисление на баланс</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.trader_profit_amount }} {{ payout.liquidity_currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Курс обмена</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.exchange_price }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                </template>
                                
                                <!-- Admin View Details -->
                                <template v-if="viewStore.isAdminViewMode">
                                     <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Сумма выплаты</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.payout_amount }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Реквизит</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            <PaymentDetail v-if="payout.detail_type" :detail="payout.detail" :copyable="false" :type="payout.detail_type.code"></PaymentDetail>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Держатель</dt> 
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.detail_initials || '-' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Платежный метод</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.payment_gateway?.name }} <span v-if="payout.sub_payment_gateway">({{ payout.sub_payment_gateway.name }})</span>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Списание у мерчанта</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.liquidity_amount }} {{ payout.liquidity_currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Зачисление трейдеру</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.trader_profit_amount }} {{ payout.liquidity_currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Курс обмена</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.exchange_price }} {{ payout.currency?.toUpperCase() }}
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Комиссия сервиса</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 flex items-center justify-end gap-2 text-right"> 
                                            <span>{{ payout.service_commission_rate }}%</span>
                                            <span class="border-r border-surface-300 dark:border-surface-600 h-3"></span> 
                                            <span>{{ payout.service_commission_amount }} {{ payout.liquidity_currency?.toUpperCase() }}</span>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Наценка трейдера</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 flex items-center justify-end gap-2 text-right"> 
                                            <span>{{ payout.trader_exchange_markup_rate }}%</span>
                                            <span class="border-r border-surface-300 dark:border-surface-600 h-3"></span> 
                                            <span>{{ payout.trader_exchange_markup_amount }} {{ payout.liquidity_currency?.toUpperCase() }}</span>
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Трейдер</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.trader?.email || 'N/A' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Владелец</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">
                                            {{ payout.owner?.email || 'N/A' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Направление</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            {{ payout.payout_gateway?.name || 'N/A' }} 
                                        </dd>
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Callback URL</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300 text-right">
                                            <div class="w-60 break-words text-xs">{{ payout.callback_url || '-' }}</div> 
                                        </dd>
                                    </dl>
                                    <dl v-if="payout.receipt_url" class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Квитанция</dt>
                                        <dd>
                                            <Button
                                                @click.prevent="showReceipt"
                                                label="Посмотреть"
                                                icon="pi pi-external-link"
                                                link
                                                class="p-0 text-sm"
                                            />
                                        </dd>
                                    </dl>
                                </template>
                                
                                <!-- Common Details -->
                                <dl class="flex items-center justify-between gap-4 border-t border-surface-200 dark:border-surface-700 pt-2 mt-2"> 
                                    <dt class="text-gray-500 dark:text-gray-400">Создан</dt>
                                    <dd class="font-medium text-gray-900 dark:text-gray-300">{{ payout.created_at }}</dd>
                                </dl>
                                <template v-if="viewStore.isAdminViewMode">
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Истекает</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">{{ payout.expires_at || '-' }}</dd> 
                                    </dl>
                                    <dl class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">Завершен</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-300">{{ payout.finished_at || '-' }}</dd> 
                                    </dl>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Added Footer with Close button -->
        <template #footer>
            <Button label="Закрыть" severity="secondary" text @click="closeModal" />
        </template>
    </Dialog>
</template>

<style scoped>
/* Optional: Add specific styles if needed */
</style>
