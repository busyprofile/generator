<script setup>
import { computed, watch, reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from '@/Wrappers/SecondaryPageSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Select from '@/Components/Select.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    providers: Array,
    integrations: Array,
    integrationFields: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    provider_id: null,
    name: '',
    min_sum: '',
    max_sum: '',
    time_for_order: '',
    rate: '',
    max_response_time_ms: '',
    number_of_retries: '',
    integration_settings: {},
});

// Настройки интеграции для выбранного провайдера
const integrationSettings = reactive({});

// Получаем строковое значение integration (может быть объект enum или строка)
const getIntegrationValue = (integration) => {
    if (!integration) return '';
    if (typeof integration === 'string') return integration;
    return integration.value ?? integration.name ?? String(integration);
};

const providerOptions = computed(() =>
    (props.providers ?? []).map((p) => ({ 
        id: p.id, 
        name: `${p.name} (${getIntegrationValue(p.integration)})` 
    }))
);

// Получаем интеграцию выбранного провайдера
const selectedProvider = computed(() => {
    if (!form.provider_id) return null;
    // Используем == для нестрогого сравнения (строка/число)
    return props.providers?.find(p => p.id == form.provider_id);
});

const selectedIntegration = computed(() => {
    const integration = selectedProvider.value?.integration;
    return getIntegrationValue(integration) || null;
});

// Поля для выбранной интеграции
const currentIntegrationFields = computed(() => {
    if (!selectedIntegration.value) return [];
    return props.integrationFields[selectedIntegration.value]?.fields ?? [];
});

const integrationName = computed(() => {
    if (!selectedIntegration.value) return '';
    return props.integrationFields[selectedIntegration.value]?.name ?? selectedIntegration.value;
});

// При смене провайдера обновляем поля интеграции
watch(selectedIntegration, (newIntegration) => {
    // Очищаем старые настройки
    Object.keys(integrationSettings).forEach(key => delete integrationSettings[key]);
    
    if (newIntegration && props.integrationFields[newIntegration]) {
        const fields = props.integrationFields[newIntegration].fields ?? [];
        fields.forEach(field => {
            integrationSettings[field.key] = field.default ?? '';
        });
    }
});

const submit = () => {
    // Копируем настройки интеграции в форму
    form.integration_settings = { ...integrationSettings };
    
    console.log('Отправка формы:', form.data());
    
    form.post(route('admin.provider-terminals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Успешно создан!');
            form.reset();
        },
        onError: (errors) => {
            console.error('Ошибки валидации:', errors);
            console.log('Все ошибки формы:', form.errors);
        },
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Создание провайдер терминала" />

        <SecondaryPageSection
            :back-link="route('admin.provider-terminals.index')"
            title="Создание провайдер терминала"
            description="Укажите параметры терминала и интеграции."
        >
            <!-- Общее сообщение об ошибках -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            Пожалуйста, исправьте ошибки в форме
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="(error, field) in form.errors" :key="field">
                                    <strong>{{ field }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="mt-6">
                <div class="md:grid md:grid-cols-2 md:gap-x-8 gap-y-4">
                    <div>
                        <InputLabel for="provider_id" value="Провайдер" :error="!!form.errors.provider_id" />
                        <Select
                            id="provider_id"
                            v-model="form.provider_id"
                            :items="providerOptions"
                            value="id"
                            name="name"
                            default_title="Выберите провайдера"
                            :error="!!form.errors.provider_id"
                            @change="form.clearErrors('provider_id')"
                        />
                        <InputError class="mt-2" :message="form.errors.provider_id" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Название терминала" :error="!!form.errors.name" />
                        <TextInput
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="off"
                            :error="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="min_sum" value="Минимальная сумма" :error="!!form.errors.min_sum" />
                        <TextInput
                            id="min_sum"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.min_sum"
                            min="0"
                            step="0.01"
                            :error="!!form.errors.min_sum"
                            @input="form.clearErrors('min_sum')"
                        />
                        <InputError class="mt-2" :message="form.errors.min_sum" />
                    </div>

                    <div>
                        <InputLabel for="max_sum" value="Максимальная сумма" :error="!!form.errors.max_sum" />
                        <TextInput
                            id="max_sum"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.max_sum"
                            min="0"
                            step="0.01"
                            :error="!!form.errors.max_sum"
                            @input="form.clearErrors('max_sum')"
                        />
                        <InputError class="mt-2" :message="form.errors.max_sum" />
                    </div>

                    <div>
                        <InputLabel for="time_for_order" value="Время на сделку (мин)" :error="!!form.errors.time_for_order" />
                        <TextInput
                            id="time_for_order"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.time_for_order"
                            min="0"
                            :error="!!form.errors.time_for_order"
                            @input="form.clearErrors('time_for_order')"
                        />
                        <InputError class="mt-2" :message="form.errors.time_for_order" />
                    </div>

                    <div>
                        <InputLabel for="rate" value="Ставка (%)" :error="!!form.errors.rate" />
                        <p class="text-xs text-gray-500 mb-1">Влияет на приоритет (rate × 10)</p>
                        <TextInput
                            id="rate"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.rate"
                            min="0"
                            step="0.01"
                            :error="!!form.errors.rate"
                            @input="form.clearErrors('rate')"
                        />
                        <InputError class="mt-2" :message="form.errors.rate" />
                    </div>

                    <div>
                        <InputLabel for="max_response_time_ms" value="Таймаут HTTP-запроса (мс)" :error="!!form.errors.max_response_time_ms" />
                        <TextInput
                            id="max_response_time_ms"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.max_response_time_ms"
                            min="0"
                            :error="!!form.errors.max_response_time_ms"
                            @input="form.clearErrors('max_response_time_ms')"
                        />
                        <InputError class="mt-2" :message="form.errors.max_response_time_ms" />
                    </div>

                    <div>
                        <InputLabel for="number_of_retries" value="Количество повторных попыток" :error="!!form.errors.number_of_retries" />
                        <TextInput
                            id="number_of_retries"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.number_of_retries"
                            min="0"
                            :error="!!form.errors.number_of_retries"
                            @input="form.clearErrors('number_of_retries')"
                        />
                        <InputError class="mt-2" :message="form.errors.number_of_retries" />
                    </div>
                </div>

                <!-- Динамические поля интеграции -->
                <div v-if="currentIntegrationFields.length > 0" class="mt-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">
                        Настройки интеграции {{ integrationName }}
                    </h3>
                    <div class="md:grid md:grid-cols-2 md:gap-x-8 gap-y-4">
                        <div v-for="field in currentIntegrationFields" :key="field.key">
                            <InputLabel :for="field.key" :error="!!form.errors[`integration_settings.${field.key}`]">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500">*</span>
                            </InputLabel>
                            <p v-if="field.description" class="text-xs text-gray-500 mb-1">{{ field.description }}</p>
                            
                            <TextInput
                                v-if="field.type === 'text'"
                                :id="field.key"
                                class="mt-1 block w-full"
                                v-model="integrationSettings[field.key]"
                                :placeholder="field.placeholder"
                                autocomplete="off"
                                :error="!!form.errors[`integration_settings.${field.key}`]"
                            />
                            
                            <TextInput
                                v-else-if="field.type === 'password'"
                                :id="field.key"
                                type="password"
                                class="mt-1 block w-full"
                                v-model="integrationSettings[field.key]"
                                :placeholder="field.placeholder"
                                autocomplete="new-password"
                                :error="!!form.errors[`integration_settings.${field.key}`]"
                            />
                            
                            <TextInput
                                v-else-if="field.type === 'number'"
                                :id="field.key"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="integrationSettings[field.key]"
                                :min="field.min"
                                :max="field.max"
                                :placeholder="field.placeholder"
                                :error="!!form.errors[`integration_settings.${field.key}`]"
                            />
                            
                            <InputError class="mt-2" :message="form.errors[`integration_settings.${field.key}`]" />
                        </div>
                    </div>
                    
                    <!-- Общая ошибка валидации integration_settings -->
                    <InputError class="mt-4" :message="form.errors.integration_settings" />
                </div>

                <div v-else-if="selectedProvider" class="mt-8 p-4 bg-primary/8 rounded-lg">
                    <p class="text-sm text-primary">
                        Для интеграции {{ selectedIntegration }} не определены настраиваемые поля.
                    </p>
                </div>

                <div class="flex items-center gap-4 mt-10 md:col-span-2">
                    <PrimaryButton :disabled="form.processing">Создать</PrimaryButton>
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Сохранено.</p>
                    </Transition>
                </div>
            </form>
        </SecondaryPageSection>
    </div>
</template>
