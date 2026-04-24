<script setup>
import { onMounted, ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { useViewStore } from "@/store/view.js";
import GatewayLogo from "@/Components/GatewayLogo.vue";

// PrimeVue Imports
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputSwitch from 'primevue/inputswitch';
import InputNumber from 'primevue/inputnumber';

const viewStore = useViewStore();

const merchant = ref(usePage().props.merchant);
const paymentGateways = usePage().props.paymentGateways;
const gatewaySettings = ref(
    typeof usePage().props.gatewaySettings === 'object' && usePage().props.gatewaySettings !== null && !Array.isArray(usePage().props.gatewaySettings)
        ? usePage().props.gatewaySettings
        : {}
);

const formCommission = useForm({
    gateway_settings: null,
});

const gatewayEditMode = ref(false);

const groupedGateways = ref(null);

const macros = ref({
    commission: null,
    reservation_time: null,
});

const getSetting = (gatewayId, settingName) => {
    const settings = gatewaySettings.value[gatewayId];
    if (!settings) {
        return settingName === 'active' ? true : null; // Default active to true if no settings exist
    }

    if (settings[settingName] === undefined) {
         return settingName === 'active' ? true : null; // Default active to true if setting specifically is missing
    }
    return settings[settingName]; // Return the actual value or null/undefined if it exists as such
};

const setSetting = (gatewayId, settingName, value) => {
    if (!gatewaySettings.value[gatewayId]) {
        gatewaySettings.value[gatewayId] = {};
    }

    let processedValue = value;
    if (settingName === "custom_gateway_commission") {
        // InputNumber gives number or null, normalize handles null
         processedValue = normalizeValue(value, 0, 100);
    } else if (settingName === "custom_gateway_reservation_time") {
        // InputNumber gives number or null, normalize handles null
         processedValue = normalizeValue(value, 1, 10000);
    }
     // For InputSwitch, value is already boolean

    gatewaySettings.value[gatewayId][settingName] = processedValue;
};

const submitGatewaySettings = () => {
    formCommission
        .transform((data) => {
            data.gateway_settings = gatewaySettings.value;
            return data;
        })
        .patch(route("merchants.gateway-settings.update", merchant.value.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                 // Update local state if needed after successful save
                 gatewaySettings.value = typeof page.props.gatewaySettings === 'object' && page.props.gatewaySettings !== null && !Array.isArray(page.props.gatewaySettings)
                    ? page.props.gatewaySettings
                    : {};
                gatewayEditMode.value = false; // Exit edit mode on success
            },
            onError: () => {
                // Maybe show an error message
            }
        });
};

const normalizeValue = (value, min = 1, max = 1000) => {
    // Allow null to be set
    if (value === null || value === undefined) {
        return null;
    }

    let num = Number(value);
    if (isNaN(num)) {
        return min; // Or null depending on desired behavior for invalid input
    }

    // Ensure value stays within bounds
    if (num < min) return min;
    if (num > max) return max;
    return num;
};

const applyMacros = (type) => {
    let valueToApply = null;
     if (type === "commission") {
        valueToApply = normalizeValue(macros.value.commission, 0, 100);
        for (const key in paymentGateways.data) {
             setSetting(paymentGateways.data[key].id, 'custom_gateway_commission', valueToApply)
        }
        // Update ref value in case normalize changed it
        macros.value.commission = valueToApply;
    } else if (type === "reservation_time") {
        valueToApply = normalizeValue(macros.value.reservation_time, 1, 10000);
        for (const key in paymentGateways.data) {
            setSetting(paymentGateways.data[key].id, 'custom_gateway_reservation_time', valueToApply)
        }
         // Update ref value in case normalize changed it
        macros.value.reservation_time = valueToApply;
    }
};

onMounted(() => {
    if (paymentGateways?.data) {
        groupedGateways.value = Object.groupBy(
            paymentGateways.data,
            ({ currency }) => currency || 'Unknown' // Handle potential null/undefined currency
        );
    }
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-medium text-gray-900 dark:text-white">Методы</h3>
            <div class="flex items-center">
                <Button
                    v-if="gatewayEditMode === false"
                    @click.prevent="gatewayEditMode = true"
                    label="Изменить"
                    icon="pi pi-pencil"
                    severity="info"
                    text
                    size="small"
                />
                 <Button
                    v-else
                    @click.prevent="submitGatewaySettings"
                    label="Сохранить"
                    icon="pi pi-check"
                    severity="success"
                    size="small"
                    :loading="formCommission.processing"
                />
            </div>
        </div>

        <Card v-if="gatewayEditMode === true && viewStore.isAdminViewMode" class="w-full">
             <template #title>
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                     Макросы для настроек
                 </h2>
             </template>
            <template #content>
                 <form @submit.prevent class="mt-6 space-y-6">
                     <div class="grid lg:grid-cols-2 grid-cols-1 gap-6">
                         <div>
                             <label for="commission_macros" class="block mb-1 text-sm font-medium">Комиссия (%)</label>
                             <InputNumber
                                 id="commission_macros"
                                 v-model="macros.commission"
                                 inputId="commission_macros_input"
                                 class="w-full"
                                 inputClass="w-full"
                                 mode="decimal"
                                 :min="0"
                                 :max="100"
                                 :minFractionDigits="0"
                                 :maxFractionDigits="2"
                                 @update:modelValue="applyMacros('commission')"
                                 @blur="applyMacros('commission')" />
                             <small class="mt-1 block text-gray-500 dark:text-gray-400">Установит у всех методов указанную комиссию.</small>
                         </div>
                         <div>
                             <label for="reservation_time_macros" class="block mb-1 text-sm font-medium">Время на сделку (мин)</label>
                             <InputNumber
                                 id="reservation_time_macros"
                                 v-model="macros.reservation_time"
                                 inputId="reservation_time_macros_input"
                                 class="w-full"
                                 inputClass="w-full"
                                 mode="decimal"
                                 :min="1"
                                 :max="10000" 
                                 @update:modelValue="applyMacros('reservation_time')"
                                 @blur="applyMacros('reservation_time')" />
                             <small class="mt-1 block text-gray-500 dark:text-gray-400">Установит у всех методов указанное время на сделку.</small>
                         </div>
                     </div>
                 </form>
            </template>
        </Card>

        <div class="mb-5" v-for="(gateways, currency) in groupedGateways" :key="currency">
            <div>
                <span class="bg-white text-xs shadow-md font-semibold py-1.5 px-3.5 dark:text-gray-200 rounded-xl dark:bg-gray-800">
                  {{ currency.toUpperCase() }}
                </span>
            </div>
            <div class="mt-3 gap-3 grid 2xl:grid-cols-4 xl:grid-cols-3 lg:grid-cols-2 md:grid-cols-2 sm:grid-cols-1">
                <Card v-for="gateway in gateways" :key="gateway.id" class="overflow-hidden">
                    <template #header>
                         <div
                            class="p-2 text-sm font-semibold"
                            :class="[
                                getSetting(gateway.id, 'active')
                                ? 'bg-white dark:bg-gray-800'
                                : 'bg-gray-100 dark:bg-gray-700 opacity-70 rounded-plate'
                            ]"
                         >
                            <div class="flex justify-between gap-2 items-center">
                                <GatewayLogo :img_path="gateway.logo_path" class="w-8 h-8 text-gray-500 dark:text-gray-400 flex-shrink-0"/>
                                <div class="flex-grow overflow-hidden min-w-0">
                                     <div class="truncate" :class="getSetting(gateway.id, 'active') ? 'text-gray-900 dark:text-gray-200' : 'text-gray-700 dark:text-gray-300'">
                                         {{ gateway.original_name }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 text-sm flex-shrink-0" :class="getSetting(gateway.id, 'active') ? 'text-gray-900 dark:text-gray-200' : 'text-gray-700 dark:text-gray-300'">
                                     <template v-if="getSetting(gateway.id, 'custom_gateway_commission') !== null && getSetting(gateway.id, 'custom_gateway_commission') >= 0">
                                        <span class="text-xs text-red-500 line-through" v-if="getSetting(gateway.id, 'active') && gateway.total_service_commission_rate_for_orders != getSetting(gateway.id, 'custom_gateway_commission')">
                                            {{ gateway.total_service_commission_rate_for_orders }}%
                                        </span>
                                        <span>
                                            {{ getSetting(gateway.id, "custom_gateway_commission") }}%
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span>{{ gateway.total_service_commission_rate_for_orders }}%</span>
                                    </template>
                                </div>
                             </div>
                        </div>
                    </template>
                    <template #content>
                         <div v-if="gatewayEditMode === true" class="space-y-2 pt-2 px-2 pb-1">
                             <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-700 dark:text-gray-400">Включен</span>
                                <InputSwitch
                                    :modelValue="getSetting(gateway.id, 'active')"
                                    @update:modelValue="setSetting(gateway.id, 'active', $event)"
                                    :inputId="'active-switch-' + gateway.id"
                                />
                             </div>
                             <div v-if="viewStore.isAdminViewMode" class="flex justify-between items-center">
                                 <span class="text-xs text-gray-700 dark:text-gray-400">Комиссия (%)</span>
                                 <InputNumber
                                     :modelValue="getSetting(gateway.id, 'custom_gateway_commission')"
                                     @update:modelValue="setSetting(gateway.id, 'custom_gateway_commission', $event)"
                                     :inputId="'commission-' + gateway.id"
                                     mode="decimal"
                                     :min="0"
                                     :max="100"
                                     :minFractionDigits="0"
                                     :maxFractionDigits="2"
                                     inputClass="w-16 p-1 text-center"
                                     placeholder="Авто"
                                 />
                             </div>
                             <div v-if="viewStore.isAdminViewMode" class="flex justify-between items-center">
                                 <span class="text-xs text-gray-700 dark:text-gray-400">Время (мин)</span>
                                 <InputNumber
                                     :modelValue="getSetting(gateway.id, 'custom_gateway_reservation_time')"
                                     @update:modelValue="setSetting(gateway.id, 'custom_gateway_reservation_time', $event)"
                                     :inputId="'time-' + gateway.id"
                                     mode="decimal"
                                     :min="1"
                                     :max="10000"
                                     inputClass="w-16 p-1 text-center"
                                      placeholder="Авто"
                                 />
                             </div>
                         </div>
                    </template>
                 </Card>
            </div>
        </div>
         <div v-if="!groupedGateways || Object.keys(groupedGateways).length === 0">
            <p class="text-gray-500 dark:text-gray-400">Нет доступных методов оплаты.</p>
        </div>
    </div>
</template>

<style scoped>
/* Remove deep styles for content padding */
/* :deep(.p-card-content) {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
} */
:deep(.p-card-header) {
    padding: 0; /* Keep header padding at 0 */
}
/* Ensure content has minimal padding when empty in view mode */
:deep(.p-card-content) {
    min-height: 1px; /* Prevents card from collapsing completely */
    padding: 0; /* Remove default padding */
    /* padding: 0.25rem 0; Reduce vertical padding */
}
:deep(.p-card) {
    padding: 1rem;
}
</style>
