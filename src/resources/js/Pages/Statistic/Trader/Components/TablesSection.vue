<script setup>
import { ref, defineProps, onMounted, watch } from 'vue';
import PaymentDetailsStats from './PaymentDetailsStats.vue';
import ClosedOrdersTable from './ClosedOrdersTable.vue';
import { router } from '@inertiajs/vue3';
import SelectButton from 'primevue/selectbutton';

const props = defineProps({
    paymentDetails: {
        type: Object,
        required: true
    },
    closedOrders: {
        type: Object,
        required: true
    },
    initialTableType: {
        type: String,
        default: 'payment-details'
    }
});

const emit = defineEmits(['table-type-changed']);

const tabItems = [
    {
        label: 'Реквизиты',
        icon: 'pi pi-credit-card',
        value: 'payment-details',
    },
    {
        label: 'Сделки',
        icon: 'pi pi-briefcase',
        value: 'closed-orders',
    }
];

const activeTab = ref(props.initialTableType);

const setActiveTab = (tab) => {
    activeTab.value = tab;
    emit('table-type-changed', tab);
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || '';
    const chartType = urlParams.get('chartType') || 'turnover';
    router.visit(route(route().current()), {
        data: {
            month: month,
            chartType: chartType,
            tableType: tab,
            page: 1
        },
        preserveScroll: true,
        preserveState: false,
        only: []
    });
};

watch(() => props.initialTableType, (newType) => {
    if (newType !== activeTab.value) {
        activeTab.value = newType;
    }
});

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const tableTypeParam = urlParams.get('tableType');
    if (tableTypeParam && (tableTypeParam === 'payment-details' || tableTypeParam === 'closed-orders')) {
        activeTab.value = tableTypeParam;
    }
});

// Для SelectButton синхронизируем с setActiveTab
watch(activeTab, (val) => {
    setActiveTab(val);
});
</script>

<template>
    <section class="space-y-6">
        <!-- SelectButton для табов -->
        <div class="mb-4">
<SelectButton
    :options="tabItems"
    v-model="activeTab"
    optionLabel="label"
    optionValue="value"
    class="w-full sm:w-auto"
>
    <template #option="slotProps">
        <div
            class="flex items-center gap-2   rounded-lg font-semibold transition-all w-36 justify-center"
            :class="[
                slotProps.selected
                    ? 'bg-black text-white shadow ring-2 ring-black dark:bg-purple-900 dark:text-purple-200 dark:ring-purple-500'
                    : ' '
            ]"
        >
            
            <span>{{ slotProps.option.label }}</span>
        </div>
    </template>
</SelectButton>
        </div>

        <!-- Содержимое табов -->
        <div>
            <div v-if="activeTab === 'payment-details'">
                <PaymentDetailsStats :payment-details="paymentDetails" />
            </div>
            <div v-if="activeTab === 'closed-orders'">
                <ClosedOrdersTable :closed-orders="closedOrders" />
            </div>
        </div>
    </section>
</template>

<style scoped>
/* Убираем стандартные стили TabMenu */
</style>
