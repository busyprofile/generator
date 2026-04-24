<script setup>
import { storeToRefs } from 'pinia'
import { useModalStore } from "@/store/modal.js";
import {router, useForm} from "@inertiajs/vue3";
import { computed, ref, watch } from 'vue';

import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';

const modalStore = useModalStore();
const { editOrderAmountModal } = storeToRefs(modalStore);

const order = computed(() => editOrderAmountModal.value?.params?.order);

const close = () => {
    modalStore.closeModal('editOrderAmount');
    form.reset();
    form.clearErrors();
};

const form = useForm({
    amount: null,
});

watch(() => editOrderAmountModal.value.showed, (newValue) => {
    if (newValue && order.value) {
        form.amount = order.value.amount;
    } else {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    if (!order.value?.id) return;
    form
        .patch(route('orders.update.amount', order.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                modalStore.closeAll();
            },
            onError: (errors) => {
                console.error("Error updating order amount:", errors);
            }
        });
};

const isVisible = computed({
    get: () => editOrderAmountModal.value.showed,
    set: (value) => {
        if (!value) {
            close();
        }
    }
});

const dialogTitle = computed(() => `Cделка # ${order.value?.uuid_short || ''}`);
const inputPlaceholder = computed(() => `Сумма в ${order.value?.currency?.toUpperCase() || 'валюте'}`);
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :header="dialogTitle"
        :style="{ width: '25rem' }" 
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
        @hide="close"
    >
        <form @submit.prevent="submit" class="py-4 flex flex-col gap-3">
            <div>
                <label for="amount" class="block text-sm font-medium mb-1" :class="{'text-red-500': form.errors.amount}">
                    Сумма сделки
                </label>
                <InputNumber
                    v-if="isVisible" 
                    id="amount"
                    v-model="form.amount"
                    :placeholder="inputPlaceholder"
                    required
                    autofocus
                    class="w-full"
                    inputId="order-amount-input" 
                    mode="decimal" 
                    :minFractionDigits="order?.currency_decimal_places ?? 2" 
                    :maxFractionDigits="order?.currency_decimal_places ?? 2" 
                    :invalid="!!form.errors.amount"
                    @input="form.clearErrors('amount')"
                />
                <small v-if="form.errors.amount" class="p-error text-xs">{{ form.errors.amount }}</small>
                <small v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                    Прибыль мерчанта и комиссия сервиса будут пересчитаны по курсу и проценту комиссии на момент открытия сделки.
                </small>
            </div>
        </form>

        <template #footer>
            <Button 
                label="Отмена"
                severity="secondary"
                text
                @click="close"
                :disabled="form.processing"
            />
            <Button 
                label="Обновить"
                icon="pi pi-check"
                type="submit" 
                @click="submit" 
                :loading="form.processing"
            />
        </template>
    </Dialog>
</template>

<style scoped>

</style>
