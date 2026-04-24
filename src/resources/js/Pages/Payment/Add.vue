<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import { ref, watch, computed } from "vue";

// PrimeVue components
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import SelectButton from 'primevue/selectbutton';
import Message from 'primevue/message';
import InlineMessage from 'primevue/inlinemessage';
import Card from 'primevue/card';

const payment_gateways = usePage().props.paymentGateways;
const currencies = usePage().props.currencies;
const merchants = usePage().props.merchants;

const paymentModeOptions = ref([
    { name: 'Авто выбор', value: 'auto' },
    { name: 'Ручной выбор', value: 'manual' }
]);
const paymentModeSelection = ref('auto'); // 'auto' or 'manual'

const directionOptions = ref([
    { name: 'Метод', value: 'gateway' },
    { name: 'Валюта', value: 'currency' }
]);
const directionSelection = ref('gateway'); // 'gateway' or 'currency'

const detailTypeOptions = ref([
    { name: 'Карта', value: 'card' },
    { name: 'Телефон', value: 'phone' },
    { name: 'Номер счета', value: 'account_number' },
    { name: 'Сим-карта', value: 'sim' }
]);
// detail_type_mode is now directly form.payment_detail_type

const form = useForm({
    amount: null,
    currency: null, // Use null for dropdown defaults
    payment_gateway: null, // Use null for dropdown defaults
    payment_detail_type: 'card', // Default detail type
    merchant_id: null, // Use null for dropdown defaults
    manually: null, // Will be set based on paymentModeSelection
});

// Watch for changes in selection modes to clear dependent fields
watch(paymentModeSelection, (newMode) => {
    if (newMode === 'manual') {
        form.payment_gateway = null;
        form.currency = null; // Manual mode might still need a currency? Clarify requirement. Let's keep it for now.
        form.payment_detail_type = 'card'; // Reset detail type if needed
        directionSelection.value = 'currency'; // Force currency selection in manual? Or hide direction selection? Hide for now.
    }
});

watch(directionSelection, (newDirection) => {
    if (paymentModeSelection.value === 'auto') {
        if (newDirection === 'gateway') {
            form.currency = null;
        } else { // currency
            form.payment_gateway = null;
        }
    }
});

const submit = () => {
    form.transform((data) => {
        let transformedData = { ...data }; // Create a copy to modify

        transformedData.manually = paymentModeSelection.value === 'manual' ? 1 : 0;

        if (transformedData.manually) {
            // In manual mode, we might only need amount and currency, maybe merchant?
            // Let's assume only amount, currency, merchant_id and manually are sent.
            delete transformedData.payment_gateway;
            delete transformedData.payment_detail_type;
            if (!transformedData.currency) delete transformedData.currency; // Only send currency if selected
        } else {
            // Auto mode
            delete transformedData.manually; // Remove the manually flag
            if (directionSelection.value === 'gateway') {
                if (!transformedData.payment_gateway) delete transformedData.payment_gateway;
                 delete transformedData.currency;
            } else { // currency
                 if (!transformedData.currency) delete transformedData.currency;
                 delete transformedData.payment_gateway;
            }
             if (!transformedData.payment_detail_type) delete transformedData.payment_detail_type;
        }

        // Clean up null/zero values before sending
        if (!transformedData.merchant_id) delete transformedData.merchant_id;
        if (!transformedData.amount) delete transformedData.amount;


        console.log("Transformed data:", transformedData);
        return transformedData;
    })
    .post(route('payments.store'), {
        preserveScroll: true,
        onError: (errors) => {
             console.error("Form errors:", errors);
             form.errors = errors; // Ensure errors are set back to the form object
        },
         onSuccess: () => {
            form.reset(); // Reset form on success
             paymentModeSelection.value = 'auto';
             directionSelection.value = 'gateway';
        }
    });
};

const flashMessage = computed(() => usePage().props.flash.message);

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Создание платежа" />

        <SecondaryPageSection
            :back-link="route('payments.index')"
            title="Создание платежа"
            description="Здесь вы можете вручную создать платеж для клиента."
        >
            <Card class="mt-6 shadow-lg rounded-xl">
                <template #content>
                    <form @submit.prevent="submit" class="space-y-6 p-2 sm:p-4">
                        <Message v-if="flashMessage" severity="error" :closable="false" class="w-full">{{ flashMessage }}</Message>

                        <div>
                            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Способ выбора платежного метода</label>
                            <SelectButton v-model="paymentModeSelection" :options="paymentModeOptions" optionLabel="name" optionValue="value" aria-labelledby="paymentModeLabel" class="w-full" />
                            <small v-if="form.errors.manually" class="p-error mt-1 block">{{ form.errors.manually }}</small>
                        </div>

                        <Message v-if="paymentModeSelection === 'manual'" severity="info" :closable="false" class="w-full">
                            Клиенту будет предложен список доступных методов на выбор, из которых он сам может выбрать наиболее удобный.
                        </Message>
                        <Message v-if="paymentModeSelection === 'auto'" severity="info" :closable="false" class="w-full">
                            У клиента не будет возможности выбрать метод для оплаты. Вместо этого будут предложены реквизиты по выбранным ниже фильтрам.
                        </Message>

                         <div class="p-field">
                            <label for="amount" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Сумма платежа</label>
                            <InputNumber id="amount" v-model="form.amount" placeholder="0" mode="decimal" :minFractionDigits="2" :maxFractionDigits="8" :class="{ 'p-invalid': form.errors.amount }" class="w-full" />
                            <small v-if="form.errors.amount" class="p-error mt-1 block">{{ form.errors.amount }}</small>
                         </div>

                         <div v-if="paymentModeSelection === 'auto'" class="space-y-6 border p-4 rounded-lg border-surface-200 dark:border-surface-700">
                             <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Настройки автоматического выбора</h3>
                             <div>
                                  <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Направление</label>
                                 <SelectButton v-model="directionSelection" :options="directionOptions" optionLabel="name" optionValue="value" aria-labelledby="directionLabel" class="w-full"/>
                             </div>

                             <div v-if="directionSelection === 'gateway'" class="p-field">
                                 <label for="payment_gateway" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Платежный метод</label>
                                 <Dropdown id="payment_gateway"
                                           v-model="form.payment_gateway"
                                           :options="payment_gateways"
                                           optionLabel="name"
                                           optionValue="code"
                                           placeholder="Выберите метод"
                                           class="w-full"
                                           :class="{ 'p-invalid': form.errors.payment_gateway }"
                                           showClear
                                           filter  />
                                 <small v-if="form.errors.payment_gateway" class="p-error mt-1 block">{{ form.errors.payment_gateway }}</small>
                                 <small v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">Платеж будет создан только в рамках выбранного платежного метода.</small>
                             </div>
                             <div v-else class="p-field"> <!-- Corresponds to directionSelection === 'currency' -->
                                <label for="currency_auto" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Валюта (Авто)</label>
                                 <Dropdown id="currency_auto"
                                           v-model="form.currency" 
                                           :options="currencies"
                                           optionLabel="name"
                                           optionValue="code"
                                           placeholder="Выберите валюту"
                                           class="w-full"
                                           :class="{ 'p-invalid': form.errors.currency }"
                                           showClear />
                                 <small v-if="form.errors.currency" class="p-error mt-1 block">{{ form.errors.currency }}</small>
                                 <small v-if="!form.errors.currency" class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">Будет использован любой платежный метод в рамках выбранной валюты.</small>
                             </div>

                             <div>
                                 <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Тип реквизитов</label>
                                 <SelectButton v-model="form.payment_detail_type" :options="detailTypeOptions" optionLabel="name" optionValue="value" aria-labelledby="detailTypeLabel" :class="{ 'p-invalid': form.errors.payment_detail_type }" class="w-full" />
                                 <small v-if="form.errors.payment_detail_type" class="p-error mt-1 block">{{ form.errors.payment_detail_type }}</small>
                             </div>
                         </div>

                         <div v-if="paymentModeSelection === 'manual'" class="p-field">
                            <label for="currency_manual" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Валюта (Ручной)</label>
                             <Dropdown id="currency_manual"
                                       v-model="form.currency"
                                       :options="currencies"
                                       optionLabel="name"
                                       optionValue="code"
                                       placeholder="Выберите валюту"
                                       class="w-full"
                                       :class="{ 'p-invalid': form.errors.currency }"
                                       showClear />
                             <small v-if="form.errors.currency" class="p-error mt-1 block">{{ form.errors.currency }}</small>
                         </div>

                         <div class="p-field">
                             <label for="merchant_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Мерчант</label>
                             <Dropdown id="merchant_id"
                                       v-model="form.merchant_id"
                                       :options="merchants"
                                       optionLabel="name"
                                       optionValue="id"
                                       placeholder="Выберите мерчант"
                                       class="w-full"
                                       :class="{ 'p-invalid': form.errors.merchant_id }"
                                       showClear
                                       filter />
                             <small v-if="form.errors.merchant_id" class="p-error mt-1 block">{{ form.errors.merchant_id }}</small>
                         </div>

                        <Message severity="warn" :closable="false" class="w-full">
                            Не для всех вариантов выбранных параметров могут быть подходящие реквизиты.
                        </Message>

                         <Button type="submit" label="Создать платеж" :loading="form.processing" :disabled="form.processing" class="w-full sm:w-auto"/>
                    </form>
                </template>
            </Card>
        </SecondaryPageSection>
    </div>
</template>

<style scoped>
/* Add any specific styles if needed */
.p-field > label {
    display: block;
    margin-bottom: 0.5rem; /* Adjusted from original */
}
/* Ensure full width for dropdowns and input number */
:deep(.p-dropdown),
:deep(.p-inputnumber) {
    width: 100%;
}
:deep(.p-selectbutton .p-button) {
    flex-grow: 1; /* Make buttons fill the width */
}
</style>
