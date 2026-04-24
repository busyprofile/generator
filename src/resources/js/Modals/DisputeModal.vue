<script setup>
import PaymentDetail from "@/Components/PaymentDetail.vue";
import { storeToRefs } from 'pinia'
import { useModalStore } from "@/store/modal.js";
import {useViewStore} from "@/store/view.js";
import DisplayUUID from "@/Components/DisplayUUID.vue";
import { computed } from 'vue';

import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const viewStore = useViewStore();
const modalStore = useModalStore();
const { disputeModal } = storeToRefs(modalStore);

const emit = defineEmits(['accept', 'cancel', 'rollback']);

const close = () => {
    modalStore.closeModal('dispute')
};

const accept = () => {
    emit('accept', disputeModal.value.params.dispute);
};

const cancel = () => {
    emit('cancel', disputeModal.value.params.dispute);
};

const rollback = () => {
    emit('rollback', disputeModal.value.params.dispute);
};

const showReceipt = () => {
    window.open(disputeModal.value.params.dispute.receipt_url, '_blank').focus();
};

const dialogHeader = computed(() => {
    const disputeId = disputeModal.value?.params?.dispute?.id;
    return disputeId ? `Спор #${disputeId}` : 'Спор';
});

const isVisible = computed({
    get: () => disputeModal.value.showed,
    set: (value) => {
        if (!value) {
            close();
        }
    }
});

const dispute = computed(() => disputeModal.value.params.dispute);
</script>

<template>
    <Dialog
        v-if="dispute"
        v-model:visible="isVisible"
        modal
        :header="dialogHeader"
        :style="{ width: '38rem' }"
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
        @hide="close"
    >
        <div class="mx-auto max-w-screen-xl px-2 2xl:px-0 py-4">
            <div class="mx-auto max-w-3xl">
                <div>
                    <div>
                        <div v-if="dispute.status === 'accepted'">
                            <div class="flex items-center justify-center mb-2">
                                <svg class="w-16 h-16 text-green-400 dark:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-300 text-center">Спор принят</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-400 text-center">{{ dispute.created_at }}</p>
                        </div>
                        <div v-else-if="dispute.status === 'canceled'">
                            <div class="flex items-center justify-center mb-2">
                                <svg class="w-16 h-16 text-red-500 dark:text-red-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-300 text-center">Спор отклонен</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-400 text-center">{{ dispute.created_at }}</p>
                            <div class="mt-4 p-3 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow text-left">
                                <div class="flex items-center">
                                    <div>
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Причина отклонения спора
                                        </div>
                                        <div class="mr-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ dispute.reason }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="dispute.status === 'pending'">
                            <div class="flex items-center justify-center mb-2">
                                <svg class="w-16 h-16 text-primary" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-300 text-center">Спор ожидает проверки</p>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 text-center">{{ dispute.created_at }}</p>
                        </div>
                        <div class="space-y-3 mt-6">
                            <div class="py-2 px-4 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow">
                                <div class="flex justify-between items-center">
                                    <div class="items-center">
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Сумма спора
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-right text-gray-600 dark:text-gray-400">
                                            <div class="text-nowrap text-gray-900 dark:text-gray-300">{{ dispute.order.amount }} {{dispute.order.currency.toUpperCase()}}</div>
                                            <div class="text-nowrap text-gray-500 dark:text-gray-500">{{ dispute.order.total_profit }} {{dispute.order.base_currency.toUpperCase()}}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-2 px-4 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow">
                                <div class="flex justify-between items-center">
                                    <div class="items-center">
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Сделка
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-right text-gray-600 dark:text-gray-400">
                                            <div class="text-nowrap text-gray-900 dark:text-gray-300">UUID</div>
                                            <div class="text-nowrap text-gray-500 dark:text-gray-500">
                                                <DisplayUUID :uuid="dispute.order.uuid" :copyable="false"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-2 px-4 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow">
                                <div class="flex justify-between items-center">
                                    <div class="items-center">
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Реквизит #{{ dispute.payment_detail.id }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-right dark:text-gray-400">
                                            <PaymentDetail
                                                :detail="dispute.payment_detail.detail"
                                                :type="dispute.payment_detail.type"
                                                :copyable="false"
                                                class="text-gray-900 dark:text-gray-300"
                                            />
                                            <div class="text-nowrap text-gray-500 dark:text-gray-500">{{ dispute.payment_detail.name }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="viewStore.isAdminViewMode || viewStore.isSupportViewMode" class="py-2 px-4 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow">
                                <div class="flex justify-between items-center">
                                    <div class="items-center">
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Трейдер #{{ dispute.user.id }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-right text-gray-600 dark:text-gray-400">
                                            <div class="text-nowrap text-gray-900 dark:text-gray-300">{{ dispute.user.name }}</div>
                                            <div class="text-nowrap text-gray-500 dark:text-gray-500">{{ dispute.user.email }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="py-2 px-4 bg-white border border-gray-200 dark:bg-gray-700/50 dark:border-gray-700 rounded-plate dark:shadow">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="mr-3 text-sm text-nowrap text-gray-900 dark:text-gray-300">
                                            Квитанция
                                        </div>
                                    </div>
                                    <div v-if="dispute.receipt_url">
                                        <Button
                                            @click.prevent="showReceipt"
                                            label="Посмотреть"
                                            icon="pi pi-arrow-up-right"
                                            iconPos="right"
                                            size="small"
                                            link
                                            class="p-0 m-0 text-sm"
                                        />
                                    </div>
                                    <div v-else>
                                        <div class="text-sm text-nowrap text-gray-500 dark:text-gray-400">
                                            Отсутствует
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
             <div v-if="viewStore.isAdminViewMode || dispute.status === 'pending' || dispute.status === 'canceled' || viewStore.isSupportViewMode" class="flex justify-center gap-2">
                 <template v-if="dispute.status === 'pending'">
                     <Button
                         @click.prevent="cancel"
                         label="Отклонить"
                         severity="danger"
                         icon="pi pi-times"
                      />
                     <Button
                         @click.prevent="accept"
                         label="Принять"
                         severity="success"
                         icon="pi pi-check"
                      />
                 </template>
                 <template v-if="dispute.status !== 'pending'">
                     <Button
                         @click.prevent="rollback"
                         label="Открыть спор"
                         severity="warning"
                         icon="pi pi-undo"
                      />
                 </template>
             </div>
             <div v-else class="flex justify-end">
                 <Button label="Закрыть" severity="secondary" text @click="close" />
            </div>
         </template>
    </Dialog>
</template>

<style scoped>

</style>
