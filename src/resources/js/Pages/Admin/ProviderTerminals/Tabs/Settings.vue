<script setup>
import { reactive, computed, ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useModalStore } from '@/store/modal';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Password from 'primevue/password';
import Select from 'primevue/select';
import CopyUUID from '@/Components/CopyUUID.vue';

const props = defineProps({
    terminal: {
        type: Object,
        default: () => ({}),
    },
    merchants: {
        type: Array,
        default: () => [],
    },
    integrationFields: {
        type: Array,
        default: () => [],
    },
});

// Получаем строковое значение integration (может быть объект enum или строка)
const getIntegrationValue = (integration) => {
    if (!integration) return '';
    if (typeof integration === 'string') return integration;
    return integration.value ?? integration.name ?? String(integration);
};

const terminalInfo = computed(() => ({
    name: props.terminal?.name ?? '',
    provider: props.terminal?.provider?.name ?? '',
    integration: getIntegrationValue(props.terminal?.provider?.integration),
    owner_email: props.terminal?.provider?.owner_email ?? '—',
    active: props.terminal?.is_active ?? false,
    uuid: props.terminal?.uuid ?? props.terminal?.id ?? '',
    callback_url: `${window.location.origin}/api/callback/${props.terminal?.uuid ?? ''}`,
}));

const adminSettings = reactive({
    min_amount: props.terminal?.min_sum ?? 0,
    max_amount: props.terminal?.max_sum ?? 0,
    order_time_minutes: props.terminal?.time_for_order ?? 0,
    max_response_ms: props.terminal?.max_response_time_ms ?? 0,
    retries: props.terminal?.number_of_retries ?? 3,
    fee_percent: props.terminal?.rate ?? 0,
});

// Инициализируем поля интеграции из additional_settings терминала
const integrationSettings = reactive({});

// Получаем additional_settings как объект (может быть null, [], или {})
const additionalSettings = props.terminal?.additional_settings || {};

props.integrationFields.forEach(field => {
    // Получаем сохраненное значение из дополнительных настроек
    const savedValue = additionalSettings[field.key];
    integrationSettings[field.key] = savedValue ?? field.default ?? '';
});

const methods = reactive([
    { name: 'СБП', active: props.terminal?.enabled_detail_types?.includes('phone') ?? false },
    { name: 'Карта', active: props.terminal?.enabled_detail_types?.includes('card') ?? false },
    { name: 'БА', active: props.terminal?.enabled_detail_types?.includes('account_number') ?? false },
]);

const merchantsList = ref((props.merchants ?? []).map((m) => ({
    ...m,
    fee_percent: m.fee_percent ?? m.rate ?? 0,
    pivot_active: m.pivot_active ?? m.active ?? false,
})));

// Синхронизируем с props при обновлении данных с сервера
watch(() => props.merchants, (newMerchants) => {
    merchantsList.value = (newMerchants ?? []).map((m) => ({
        ...m,
        fee_percent: m.fee_percent ?? m.rate ?? 0,
        pivot_active: m.pivot_active ?? m.active ?? false,
    }));
}, { deep: true });

const modalStore = useModalStore();

const saveSettings = () => {
    // Собираем все настройки интеграции
    const allAdditionalSettings = {
        ...integrationSettings,
    };

    const payload = {
        name: props.terminal?.name || 'Terminal',
        min_sum: adminSettings.min_amount,
        max_sum: adminSettings.max_amount,
        time_for_order: adminSettings.order_time_minutes,
        max_response_time_ms: adminSettings.max_response_ms,
        number_of_retries: adminSettings.retries,
        rate: adminSettings.fee_percent,
        enabled_detail_types: methods.filter(m => m.active).map(m => {
            if (m.name === 'СБП') return 'phone';
            if (m.name === 'Карта') return 'card';
            if (m.name === 'БА') return 'account_number';
            return m.name;
        }),
        integration_settings: integrationSettings,
        additional_settings: JSON.stringify(allAdditionalSettings),
    };
    
    router.patch(route('admin.provider-terminals.update', { providerTerminal: props.terminal?.id }), payload, { preserveScroll: true });
};

const toggleMerchant = (merchant) => {
    // v-model уже обновил значение pivot_active
    // Контроллер сам создаст связь, если её нет
    router.patch(route('admin.provider-terminals.merchants.toggle', { 
        providerTerminal: props.terminal?.id, 
        merchantId: merchant.id 
    }), {
        is_active: merchant.pivot_active,
    }, { 
        preserveScroll: true,
        onError: () => {
            merchant.pivot_active = !merchant.pivot_active;
        }
    });
};

const confirmDelete = () => {
    modalStore.openConfirmModal({
        title: `Удалить терминал "${props.terminal?.name}"?`,
        body: 'Действие необратимо.',
        confirm_button_name: 'Удалить',
        confirm: () =>
            router.delete(route('admin.provider-terminals.destroy', { providerTerminal: props.terminal?.id }), {
                preserveScroll: true,
            }),
    });
};

const copyCallbackUrl = () => {
    navigator.clipboard.writeText(terminalInfo.value.callback_url);
};
</script>

<template>
    <div class="space-y-6">
        <div class="gap-8 grid grid-cols-1 2xl:grid-cols-7 xl:grid-cols-5">
            <div class="2xl:col-span-3 xl:col-span-2 space-y-6">
                <Card>
                    <template #title><h3 class="text-xl font-medium text-gray-900 dark:text-white">Провайдер терминал</h3></template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Название</span>
                                <span class="text-gray-500 dark:text-gray-400 break-all">{{ terminalInfo.name }}</span>
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Провайдер</span>
                                <span class="text-gray-500 dark:text-gray-400 break-all">{{ terminalInfo.provider }}</span>
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Интеграция</span>
                                <Tag :value="terminalInfo.integration" severity="info" />
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Статус</span>
                                <Tag :severity="terminalInfo.active ? 'success' : 'danger'" :value="terminalInfo.active ? 'Активен' : 'Неактивен'" />
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Владелец (трейдер)</span>
                                <span class="text-gray-500 dark:text-gray-400 break-all">{{ terminalInfo.owner_email }}</span>
                            </div>
                            <div class="flex items-center border-b dark:border-gray-700 pb-2">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">UUID терминала</span>
                                <CopyUUID :text="terminalInfo.uuid" />
                            </div>
                            <div class="flex items-center pt-1">
                                <span class="font-medium text-gray-900 dark:text-gray-200 mr-auto">Callback URL</span>
                                <div class="flex items-center gap-2">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded max-w-xs truncate">
                                        {{ terminalInfo.callback_url }}
                                    </code>
                                    <Button icon="pi pi-copy" severity="secondary" text size="small" @click="copyCallbackUrl" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #title><h3 class="text-xl font-medium text-gray-900 dark:text-white">Методы</h3></template>
                    <template #content>
                        <div class="space-y-3">
                            <div v-for="method in methods" :key="method.name" class="flex items-center justify-between">
                                <span class="text-sm text-gray-800 dark:text-gray-200">{{ method.name }}</span>
                                <InputSwitch v-model="method.active" @update:modelValue="saveSettings" />
                            </div>
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #title><h3 class="text-xl font-medium text-gray-900 dark:text-white">Модерация</h3></template>
                    <template #content>
                        <Button label="Удалить" icon="pi pi-trash" severity="danger" outlined class="w-full" @click="confirmDelete" />
                    </template>
                </Card>
            </div>

            <div class="2xl:col-span-4 xl:col-span-3 space-y-6">
                <!-- Настройки интеграции -->
                <Card v-if="integrationFields.length > 0">
                    <template #title>
                        <h3 class="text-xl font-medium text-gray-900 dark:text-white">
                            Настройки интеграции {{ terminalInfo.integration }}
                        </h3>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div v-for="field in integrationFields" :key="field.key">
                                <label class="block mb-1 text-sm font-medium">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <p v-if="field.description" class="text-xs text-gray-500 mb-2">{{ field.description }}</p>
                                
                                <!-- Текстовое поле -->
                                <InputText 
                                    v-if="field.type === 'text'" 
                                    v-model="integrationSettings[field.key]" 
                                    :placeholder="field.placeholder"
                                    class="w-full" 
                                />
                                
                                <!-- Пароль (скрытое поле) -->
                                <Password 
                                    v-else-if="field.type === 'password'" 
                                    v-model="integrationSettings[field.key]" 
                                    :placeholder="field.placeholder"
                                    :feedback="false"
                                    toggleMask
                                    class="w-full"
                                    inputClass="w-full"
                                />
                                
                                <!-- Числовое поле -->
                                <InputNumber 
                                    v-else-if="field.type === 'number'" 
                                    v-model="integrationSettings[field.key]" 
                                    :min="field.min" 
                                    :max="field.max"
                                    :placeholder="field.placeholder"
                                    class="w-full" 
                                    inputClass="w-full"
                                />
                            </div>
                            
                            <Button label="Сохранить настройки интеграции" class="w-full md:w-auto" @click="saveSettings" />
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #title><h3 class="text-xl font-medium text-gray-900 dark:text-white">Настройки терминала</h3></template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium">Минимальная сумма</label>
                                <InputNumber v-model="adminSettings.min_amount" mode="decimal" :min="0" class="w-full" inputClass="w-full" placeholder="Минимальная сумма" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium">Максимальная сумма</label>
                                <InputNumber v-model="adminSettings.max_amount" mode="decimal" :min="0" class="w-full" inputClass="w-full" placeholder="Максимальная сумма" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium">Время на ордер (минут)</label>
                                <InputNumber v-model="adminSettings.order_time_minutes" mode="decimal" :min="1" class="w-full" inputClass="w-full" placeholder="Минуты" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium">Максимальное время ответа (мс)</label>
                                <InputNumber v-model="adminSettings.max_response_ms" mode="decimal" :min="1" class="w-full" inputClass="w-full" placeholder="Время в миллисекундах" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium">Количество повторных попыток</label>
                                <InputNumber v-model="adminSettings.retries" mode="decimal" :min="1" :max="10" class="w-full" inputClass="w-full" placeholder="Количество попыток" />
                            </div>

                            <div>
                                <label class="block mb-1 text-sm font-medium">Ставка (%)</label>
                                <p class="text-xs text-gray-500 mb-2">Влияет на приоритет терминала (rate × 10 = приоритет)</p>
                                <InputNumber v-model="adminSettings.fee_percent" mode="decimal" :min="0" :maxFractionDigits="2" class="w-full" inputClass="w-full" placeholder="Процент комиссии" suffix=" %" />
                            </div>

                            <Button label="Сохранить" class="w-full md:w-auto" @click="saveSettings" />
                        </div>
                    </template>
                </Card>

                <Card>
                    <template #title>Оставшийся лимит USDT</template>
                    <template #content>
                        <div class="flex flex-col gap-3">
                            <InputText class="w-full" placeholder="Введите лимит" v-model="remainingLimit" />
                            <Button label="Сохранить" icon="pi pi-check" class="w-full md:w-auto" @click="saveSettings" />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <Card>
            <template #title>Мерчанты</template>
            <template #content>
                <div v-if="merchantsList.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    Нет доступных мерчантов
                </div>

                <div v-else class="relative overflow-x-auto shadow-md rounded-table">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">ID</th>
                                <th scope="col" class="px-6 py-3">Название</th>
                                <th scope="col" class="px-6 py-3">Владелец</th>
                                <th scope="col" class="px-6 py-3">Ставка</th>
                                <th scope="col" class="px-6 py-3">Статус мерчанта</th>
                                <th scope="col" class="px-6 py-3">Активен для терминала</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="merchant in merchantsList" :key="merchant.id" class="bg-white border-b last:border-none dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row" class="px-6 py-3 font-medium whitespace-nowrap text-gray-900 dark:text-gray-200">
                                    {{ merchant.id }}
                                </th>
                                <td class="px-6 py-3">
                                    <Link :href="`/admin/merchants/${merchant.id}?tab=settings`" class="text-blue-600 dark:text-blue-400 hover:underline max-w-48 truncate block">{{ merchant.name }}</Link>
                                    <div class="text-xs max-w-36 truncate">{{ merchant.domain ?? '' }}</div>
                                </td>
                                <td class="px-6 py-3">
                                    {{ merchant.owner?.email ?? merchant.owner ?? '—' }}
                                </td>
                                <td class="px-6 py-3">
                                    {{ merchant.fee_percent }}%
                                </td>
                                <td class="px-6 py-3">
                                    <Tag :severity="merchant.banned_at ? 'danger' : 'success'" :value="merchant.banned_at ? 'Выключен' : 'Включен'" />
                                </td>
                                <td class="px-6 py-3">
                                    <InputSwitch v-model="merchant.pivot_active" @update:modelValue="toggleMerchant(merchant)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </Card>
    </div>
</template>
