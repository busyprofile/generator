<script setup>
import {router, useForm} from "@inertiajs/vue3";
import { storeToRefs } from 'pinia'
import { useModalStore } from "@/store/modal.js";
import { computed, ref, onMounted } from 'vue';
import { useViewStore } from "@/store/view.js";
import axios from 'axios';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';

const modalStore = useModalStore();
const { disputeCancelModal } = storeToRefs(modalStore);
const viewStore = useViewStore();

const cancelReasons = ref([]);

// Загружаем причины отказа при монтировании компонента
onMounted(async () => {
    try {
        const response = await axios.get('/api/dispute/cancel-reasons');
        if (response.data.success) {
            cancelReasons.value = response.data.data.cancel_reasons;
        }
    } catch (error) {
        console.error('Ошибка загрузки причин отказа:', error);
        // Fallback на статичные причины
        cancelReasons.value = [
            { value: 'requires_bank_statement', label: 'Требуется выписка с банка' },
            // { value: 'requires_video_proof', label: 'Требуется видеодоказательство платежа' },
            { value: 'wrong_payment_refund_required', label: 'Неправильный платеж, требуется возврат' },
            { value: 'incorrect_amount_received', label: 'Получена неправильная сумма' }
        ];
    }
});

const close = () => {
    modalStore.closeModal('disputeCancel');
    form.reset();
    form.clearErrors();
};

const form = useForm({
    cancel_reason: '',
    comment: '',
});

const cancel = () => {
    const dispute = disputeCancelModal.value?.params?.dispute;
    if (!dispute) {
        console.error("Dispute data is missing from modal params.");
        return;
    }

    let routeName = viewStore.isSupportViewMode ? 'support.disputes.cancel' : 'disputes.cancel';
    form.patch(route(routeName, dispute.id), {
        preserveScroll: true,
        onSuccess: () => {
            modalStore.closeAll()
            router.visit(route(route().current()))
        },
        onError: (errors) => {
            console.error("Error cancelling dispute:", errors);
        }
    });
};

// Computed property for the dialog header
const dialogHeader = computed(() => {
    const disputeId = disputeCancelModal.value?.params?.dispute?.id;
    return disputeId ? `Отклонение спора #${disputeId}` : 'Отклонение спора';
});

// Handle visibility based on store state
const isVisible = computed({
    get: () => {
        return disputeCancelModal.value.showed;
    },
    set: (value) => {
        if (!value) {
            close();
        }
        // We don't directly set the store value here,
        // visibility is controlled by the store state `disputeCancelModal.showed`
    }
});
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :header="dialogHeader"
        :style="{ width: '30rem' }" >

        <form @submit.prevent="cancel" class="py-3">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <label for="cancel_reason" class="font-medium text-sm" :class="{'p-error': form.errors.cancel_reason}">Причина отклонения</label>
                    <Dropdown
                        inputId="cancel_reason"
                        v-model="form.cancel_reason"
                        :options="cancelReasons"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Выберите причину отклонения"
                        class="w-full"
                        :class="{'p-invalid': form.errors.cancel_reason}"
                        @change="form.clearErrors('cancel_reason')"
                    />
                    <small v-if="form.errors.cancel_reason" class="p-error">{{ form.errors.cancel_reason }}</small>
                    <small v-else class="text-xs text-gray-500 dark:text-gray-400">Выберите причину отклонения спора</small>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="comment" class="font-medium text-sm" :class="{'p-error': form.errors.comment}">Комментарий (необязательно)</label>
                    <Textarea
                        inputId="comment"
                        v-model="form.comment"
                        class="w-full"
                        :class="{'p-invalid': form.errors.comment}"
                        placeholder="Дополнительная информация о причине отказа..."
                        rows="3"
                        @input="form.clearErrors('comment')"
                    />
                    <small v-if="form.errors.comment" class="p-error">{{ form.errors.comment }}</small>
                    <small v-else class="text-xs text-gray-500 dark:text-gray-400">Дополнительная информация (не более 500 символов)</small>
                </div>
            </div>
            <!-- Form submit is handled by footer button -->
        </form>

        <template #footer>
            <Button
                label="Отмена"
                severity="secondary"
                text
                @click="close"
            />
            <Button
                label="Подтвердить"
                severity="danger" 
                @click="cancel"
                :loading="form.processing"
                :disabled="!form.cancel_reason"
            />
        </template>
    </Dialog>
</template>

<style scoped>

</style>
