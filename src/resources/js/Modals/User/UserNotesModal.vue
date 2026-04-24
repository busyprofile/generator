<script setup>
import { storeToRefs } from 'pinia'
import { useModalStore } from "@/store/modal.js";
import {ref, watch, computed} from "vue";
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import ProgressSpinner from 'primevue/progressspinner';

const modalStore = useModalStore();
const { userNotesModal } = storeToRefs(modalStore);

const userNotes = ref([]);
const loading = ref(false);
const processing = ref(false);
const form = ref({
    content: '',
});
const errors = ref({});

const close = () => {
    modalStore.closeModal('userNotes');
};

const loadUserNotes = () => {
    if (!userNotesModal.value.params.user?.id) return;
    loading.value = true;
    axios.get(route('admin.users.notes.index', userNotesModal.value.params.user.id))
        .then(response => {
            if (response.data.success) {
                userNotes.value = response.data.data;
            }
            loading.value = false;
        })
        .catch(error => {
            console.error("Error loading user notes:", error);
            loading.value = false;
        });
};

const addNote = () => {
    if (!userNotesModal.value.params.user?.id) return;
    processing.value = true;
    errors.value = {};
    
    axios.post(route('admin.users.notes.store', userNotesModal.value.params.user.id), form.value)
        .then(response => {
            if (response.data.success) {
                userNotes.value.unshift(response.data.data);
                form.value.content = '';
            }
            processing.value = false;
        })
        .catch(error => {
            if (error.response && error.response.data && error.response.data.errors) {
                errors.value = error.response.data.errors;
            } else {
                console.error("Error adding note:", error);
            }
            processing.value = false;
        });
};

watch(
    () => userNotesModal.value.showed,
    (state) => {
        if (state && userNotesModal.value.params.user?.id) {
            loadUserNotes();
        } else {
            userNotes.value = [];
            form.value.content = '';
            errors.value = {};
            loading.value = false;
            processing.value = false;
        }
    }
);

const isVisible = computed({
    get: () => userNotesModal.value.showed,
    set: (value) => {
        if (!value) {
            close();
        }
    }
});

const dialogTitle = computed(() => `Заметки о пользователе: ${userNotesModal.value.params.user?.name || ''}`);
</script>

<template>
    <Dialog
        v-model:visible="isVisible"
        modal
        :header="dialogTitle"
        :style="{ width: '40rem' }" 
        :pt="{
            root: 'border-none',
            mask: {
                style: 'backdrop-filter: blur(2px)'
            }
        }"
        @hide="close"
    >
        <div class="space-y-4 py-4">
            <form @submit.prevent="addNote" class="space-y-3"> 
                <div>
                    <Textarea
                        v-model="form.content"
                        placeholder="Напишите заметку о пользователе..."
                        class="w-full"
                        rows="3"
                        :invalid="!!errors.content"
                        :disabled="processing || loading"
                        aria-describedby="content-error"
                    />
                    <small v-if="errors.content" id="content-error" class="p-error text-xs">{{ errors.content[0] }}</small>
                </div>
                
                <div class="flex justify-end">
                    <Button 
                        type="submit" 
                        label="Добавить заметку"
                        icon="pi pi-plus"
                        :loading="processing"
                        :disabled="loading || !form.content.trim()" 
                    />
                </div>
            </form>
            
            <div v-if="loading" class="text-center py-10 flex justify-center">
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="8" animationDuration=".5s" />
            </div>
            
            <div v-else-if="userNotes.length === 0 && !loading" class="text-center py-10 text-gray-500 dark:text-gray-400">
                 <i class="pi pi-comments text-3xl mb-2"></i>
                <p>Нет заметок о пользователе.</p>
            </div>
            
            <div v-else class="space-y-3 max-h-80 overflow-y-auto p-1">
                <div 
                    v-for="note in userNotes" 
                    :key="note.id" 
                    class="p-3 bg-surface-50 dark:bg-surface-700 rounded-lg shadow-sm border border-surface-200 dark:border-surface-600"
                >
                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line mb-2">{{ note.content }}</p>
                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                        <span>Добавил: {{ note.creator?.name || 'N/A' }}</span>
                        <span>{{ new Date(note.created_at).toLocaleString() }}</span> 
                    </div>
                </div>
            </div>
        </div>
        
        <template #footer>
            <Button 
                label="Закрыть"
                severity="secondary"
                text
                @click="close" 
            />
        </template>
    </Dialog>
</template>

<style scoped>
.max-h-80 {
    max-height: 20rem;
}
</style> 