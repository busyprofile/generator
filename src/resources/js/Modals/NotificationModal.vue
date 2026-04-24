<script setup>
import { storeToRefs } from 'pinia'
import { useModalStore } from "@/store/modal.js";
import {router, useForm} from "@inertiajs/vue3";
import { computed } from 'vue';

import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';

const modalStore = useModalStore();
const { notificationModal } = storeToRefs(modalStore);

const close = () => {
    modalStore.closeModal('notification');
    form.reset();
    form.clearErrors();
};

const form = useForm({
    message: null,
});

const send = () => {
    form.post(route('admin.notifications.store'), {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('admin.notifications.index'));
            modalStore.closeAll();
        },
        onError: (errors) => {
            console.error("Error sending notification:", errors);
        }
    });
};

const isVisible = computed({
    get: () => notificationModal.value.showed,
    set: (value) => {
        if (!value) {
            close();
        }
    }
});

const dialogHeader = "Рассылка уведомления";
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :header="dialogHeader"
        :style="{ width: '30rem' }" 
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
        @hide="close"
    >
        <p class="text-gray-900 dark:text-gray-200 text-center mb-6">
            Введите сообщение которое будет отправлено всем пользователям в телеграмм.
        </p>
        
        <form @submit.prevent="send" class="flex flex-col gap-3">
            <div>
                <label for="message" class="block text-sm font-medium mb-1" :class="{'text-red-500': form.errors.message}">
                    Сообщение
                </label>
                <Textarea 
                    id="message"
                    v-model="form.message"
                    required
                    rows="5" 
                    class="w-full"
                    :invalid="!!form.errors.message" 
                    @input="form.clearErrors('message')"
                />
                <small v-if="form.errors.message" class="p-error text-xs">{{ form.errors.message }}</small>
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
                label="Отправить"
                icon="pi pi-send"
                @click="send" 
                :loading="form.processing"
                type="submit" 
            />
        </template>
    </Dialog>
</template>

<style scoped>

</style>
