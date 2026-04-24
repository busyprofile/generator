<script setup>
// Removed: import Modal from '@/Components/Modals/Modal.vue';
// Removed: import ModalHeader from '@/Components/Modals/Components/ModalHeader.vue';
// Removed: import ModalBody from '@/Components/Modals/Components/ModalBody.vue';
// Removed: import ModalFooter from '@/Components/Modals/Components/ModalFooter.vue';
// Removed: import SecondaryButton from '@/Components/Buttons/SecondaryButton.vue';
// Removed: import DangerButton from '@/Components/Buttons/DangerButton.vue';
import { computed, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useModalStore } from '@/store/modal.js';

// Added PrimeVue components
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';


const modalStore = useModalStore();
const { confirmModal } = storeToRefs(modalStore);
const isLoading = ref(false);

// Сбрасываем isLoading, когда модальное окно закрывается
watch(() => confirmModal.value.showed, (newValue) => {
    if (!newValue) {
        isLoading.value = false;
    }
});

// Removed props definition as data comes from store

const close = () => {
    modalStore.closeModal('confirm');
    isLoading.value = false;
};

const confirm = () => {
    if (confirmModal.value?.params?.confirm) {
        isLoading.value = true;
        try {
            confirmModal.value.params.confirm();
            // Модальное окно будет закрыто в обработчике onSuccess
        } catch (error) {
            console.error("Error executing confirm callback:", error);
            isLoading.value = false;
        }
    } else {
        console.warn("Confirm callback is missing in modal params.");
        close(); // Close even if callback is missing
    }
};

// Computed properties for dialog attributes based on store state
const isVisible = computed({
    get: () => confirmModal.value.showed,
    set: (value) => {
        if (!value) {
            close();
        }
        // Visibility is controlled by the store state
    }
});

const dialogTitle = computed(() => confirmModal.value?.params?.title || 'Подтверждение');
const dialogBody = computed(() => confirmModal.value?.params?.body || 'Вы уверены?');
const confirmButtonLabel = computed(() => confirmModal.value?.params?.confirm_button_name || 'Подтвердить');
const confirmButtonSeverity = computed(() => confirmModal.value?.params?.confirm_button_severity || 'danger');

</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :header="dialogTitle"
        :pt="{
            root: { class: 'border-none !w-[25rem]' },
            mask: { class: 'backdrop-blur-[2px]' }
        }"
        @hide="close"
        :closable="true"
    >
        <!-- Removed ModalHeader, ModalBody, ModalFooter wrappers -->
        <p class="py-4">{{ dialogBody }}</p> <!-- Added padding -->

        <template #footer>
            <Button
                label="Отмена"
                icon="pi pi-times"
                severity="secondary"
                text
                @click="close"
                :disabled="isLoading"
            />
            <Button
                :label="confirmButtonLabel"
                :icon="isLoading ? 'pi pi-spinner pi-spin' : 'pi pi-check'"
                :severity="confirmButtonSeverity"
                @click="confirm"
                :loading="isLoading"
                :disabled="isLoading"
                autofocus
            />
        </template>
    </Dialog>

    <!-- Removed old Modal structure -->
    <!--
    <Modal :show="confirmModal.showed" @close="close" maxWidth="md">
        <ModalHeader :title="confirmModal.params.title" @close="close"/>
        <ModalBody>
            <div class="p-6">
                {{ confirmModal.params.body }}
            </div>
        </ModalBody>
        <ModalFooter>
            <SecondaryButton @click="close">
                Отмена
            </SecondaryButton>

            <DangerButton
                class="ml-3"
                @click="confirm"
            >
                {{ confirmModal.params.confirm_button_name }}
            </DangerButton>
        </ModalFooter>
    </Modal>
    -->
</template>

