<script setup>
import { ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import InputSwitch from 'primevue/inputswitch';
import Card from 'primevue/card';
import Button from 'primevue/button';

const is_online = ref(!!usePage().props.auth.user.is_online);
const is_payout_online = ref(!!usePage().props.auth.user.is_payout_online);

router.on('success', (event) => {
    is_online.value = !!usePage().props.auth.user.is_online;
    is_payout_online.value = !!usePage().props.auth.user.is_payout_online;
})

const user = usePage().props.auth.user;
const form = useForm({});
const payoutForm = useForm({});

const submit = () => {
    form.patch(route('user.online.toggle'), {
        preserveScroll: true,
        onSuccess: (result) => {
            is_online.value = !!result.props.auth.user.is_online;
        },
    });
};

const payoutSubmit = () => {
    form.patch(route('user.payout.online.toggle'), {
        preserveScroll: true,
        onSuccess: (result) => {
            is_online.value = !!result.props.auth.user.is_online;
        },
    });
};
</script>

<template>
    <div class="online-status-container space-y-3">
        <!-- Статус онлайн для сделок -->
        <div class="status-item p-3 rounded-md">
            <div class="flex items-center justify-between">
                <div class="status-info flex items-center">
                    <div class="flex flex-col">
                        <div class="flex items-center"> 
                            <span class="status-text dark:text-white   font-medium">{{ is_online ? 'Вы онлайн' : 'Вы офлайн' }}</span>
                        </div>
                        <span class="text-xs text-gray-400 mt-1">Сделки: {{ is_online ? 'доступны' : 'не доступны' }}</span>
                    </div>
                </div>
                
                <Button 
                    :class="is_online ? '  text-white   on' : 'bg-gray-700 text-white hover:bg-gray-600 off'"
                    :label="is_online ? 'Онлайн' : 'Офлайн'"
                    @click="is_online = !is_online; submit()"
                    :disabled="form.processing"
                    size="small"
                    
                />
            </div>
        </div>
        
        <!-- Статус онлайн для выплат -->
        <div v-if="user.payouts_enabled" class="status-item  p-3 rounded-md">
            <div class="flex items-center justify-between">
                <div class="status-info flex items-center">
                    <div class="flex flex-col">
                        <div class="flex items-center">
                            <!-- <i :class="is_payout_online ? 'pi pi-circle-fill text-green-500 ' : 'pi pi-circle-fill text-red-500 '" class="mr-2 text--xs"></i> -->
                            <span class="status-text dark:text-white   font-medium ">{{ is_payout_online ? 'Вы онлайн' : 'Вы офлайн' }}</span>
                        </div>
                        <span class="text-xs text-gray-400 mt-1">Выплаты: {{ is_payout_online ? 'доступны' : 'не доступны' }}</span>
                    </div>
                </div>
                
                <Button 
                    :class="is_payout_online ? '  text-white   on' : 'bg-gray-700 text-white hover:bg-gray-600 off'"
                    :label="is_payout_online ? ' Онлайн' : 'Офлайн'"
                    @click="is_payout_online = !is_payout_online; payoutSubmit()"
                    :disabled="payoutForm.processing"
                    size="small"
                    rounded
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.online-status-container {
    width: 100%;
}

.status-item {
    transition: all 0.2s;
    @apply border border-gray-200;
}

.dark .status-item {
    transition: all 0.2s;
    @apply border border-white/5;
}

 

:deep(.p-button) {
    font-weight: 500;
    padding: 0.4rem 0.8rem;
    border: none;
}
 
.off {
    @apply !bg-badge-danger/25 !text-badge-danger-text;
}
.on {
    
    color: var(--p-button-text-primary-color) !important;
}
/* :deep(.p-button:hover) {
    background-color: theme('colors.sky.500') !important;
} */
</style>
